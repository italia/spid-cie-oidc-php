<?php

/**
 * spid-cie-oidc-php
 * https://github.com/italia/spid-cie-oidc-php
 *
 * 2022 Michele D'Amico (damikael)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author     Michele D'Amico <michele.damico@linfaservice.it>
 * @license    http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SPID_CIE_OIDC_PHP\Core\Database;
use SPID_CIE_OIDC_PHP\Core\Logger;
use SPID_CIE_OIDC_PHP\Core\Util;
use SPID_CIE_OIDC_PHP\Federation\Federation;
use SPID_CIE_OIDC_PHP\Federation\MyEntityStatement;
use SPID_CIE_OIDC_PHP\Federation\EntityStatement;
use SPID_CIE_OIDC_PHP\OIDC\AuthenticationRequest;
use SPID_CIE_OIDC_PHP\OIDC\TokenRequest;
use SPID_CIE_OIDC_PHP\OIDC\UserinfoRequest;
use SPID_CIE_OIDC_PHP\OIDC\IntrospectionRequest;
use SPID_CIE_OIDC_PHP\OIDC\RevocationRequest;

$f3 = \Base::instance();

//----------------------------------------------------------------------------------------
// Available configurations / objects
//----------------------------------------------------------------------------------------
$config = json_decode(file_get_contents(__DIR__ . '/../config/config.json'));
$f3->set("CONFIG", $config);

$hooks = json_decode(file_get_contents(__DIR__ . '/../config/hooks.json'));
$f3->set("HOOKS", $hooks);

$op_metadata = json_decode(file_get_contents(__DIR__ . '/../config/op-metadata.json'));
$f3->set("OP_METADATA", $op_metadata);

$federation = new Federation($config, json_decode(file_get_contents(__DIR__ . '/../config/federation-authority.json')));
$f3->set("FEDERATION", $federation);

$database = new Database(__DIR__ . '/../data/db.sqlite');
$f3->set("DATABASE", $database);

$logger = new Logger($config);
$f3->set("LOGGER", $logger);
//----------------------------------------------------------------------------------------




$f3->route('GET /', function ($f3) {
    $composer = json_decode(file_get_contents(__DIR__ . '/../composer.json'));
    echo "SPID CIE OIDC PHP - Version " . $composer->config->version;
});

// transform json POST body data
if (
    ($f3->VERB == 'POST' || $f3->VERB == 'PUT')
    && preg_match('/json/', $f3->get('HEADERS[Content-Type]'))
) {
    $f3->set('BODY', file_get_contents('php://input'));
    if (strlen($f3->get('BODY'))) {
        $data = json_decode($f3->get('BODY'), true);
        if (json_last_error() == JSON_ERROR_NONE) {
            $f3->set('POST', $data);
        }
    }
}

$f3->set('ONERROR', function ($f3) {
    $config = $f3->get("CONFIG");
    $error_description = $f3->get('ERROR.text');
    $f3->set('baseurl', '/' . $config->service_name);
    $f3->set('error_description', $error_description);
    echo View::instance()->render('view/error.php');
    die();
});




//----------------------------------------------------------------------------------------
// Routes
//----------------------------------------------------------------------------------------

$f3->route('GET /.well-known/openid-federation', function ($f3) {
    $config = $f3->get("CONFIG");
    $logger = $f3->get("LOGGER");

    $logger->log('OIDC', 'GET /.well-known/openid-federation');

    $myEntityStatement = new MyEntityStatement($config);
    $decoded = $f3->get("GET.decoded");
    $mediaType = $decoded == 'Y' ? 'application/json' : 'application/jwt';
    header('Content-Type: ' . $mediaType);
    echo $myEntityStatement->getConfiguration($decoded == 'Y');
});

$f3->route('GET /oidc/rp/authz', function ($f3) {
    $config = $f3->get("CONFIG");
    $logger = $f3->get("LOGGER");

    $logger->log('VIEW', 'GET /oidc/rp/authz');

    $auth = $f3->get('SESSION.auth');
    if (
        $auth != null
        && $auth['userinfo'] != null
        && $auth['redirect_uri'] != null
        && $auth['state'] != null
    ) {
        $userinfoResponse = $auth['userinfo'];
        $redirect_uri = $auth['redirect_uri'];
        $state = $auth['state'];
        $responseHandlerClass = $config->rp_response_handler;
        $responseHandler = new $responseHandlerClass($config);
        $responseHandler->sendResponse($redirect_uri, $userinfoResponse, $state);
        die();
    }

    $f3->set('baseurl', '/' . $config->service_name);
    echo View::instance()->render('view/login.php');
});

$f3->route('GET /oidc/rp/authz/@fed/@op', function ($f3) {
    $config = $f3->get("CONFIG");
    $federation = $f3->get("FEDERATION");
    $op_metadata = $f3->get("OP_METADATA");
    $database = $f3->get("DATABASE");
    $hooks = $f3->get("HOOKS");
    $logger = $f3->get("LOGGER");

    $logger->log('OIDC', 'GET /oidc/rp/authz/' . $f3->get('PARAMS.op'));

    $fed = base64_decode($f3->get('PARAMS.fed'));
    $op = base64_decode($f3->get('PARAMS.op'));
    $state = $f3->get('GET.state');
    $acr = $config->rp_requested_acr;
    $user_attributes = $config->rp_spid_user_attributes;
    $redirect_uri = $config->rp_redirect_uri;
    $req_id = $database->createRequest($op, $redirect_uri, $state, $acr, $user_attributes);
    $request = $database->getRequest($req_id);
    $code_verifier = $request['code_verifier'];
    $nonce = $request['nonce'];

    if(!$federation->isFederationSupported($fed)) {
        $f3->error(401, "Federation non supported: " . $fed);
    }

    $authorization_endpoint = $op_metadata->{$op}->openid_provider->authorization_endpoint;

    $authenticationRequest = new AuthenticationRequest($config, $hooks);
    $authenticationRequestURL = $authenticationRequest->send(
        $authorization_endpoint,
        $acr,
        $user_attributes,
        $code_verifier,
        $nonce,
        Util::base64UrlEncode(str_pad($req_id, 32))
    );
});

$f3->route('GET /oidc/rp/redirect', function ($f3) {
    $config = $f3->get("CONFIG");
    $op_metadata = $f3->get("OP_METADATA");
    $database = $f3->get("DATABASE");
    $hooks = $f3->get("HOOKS");
    $logger = $f3->get("LOGGER");

    $logger->log('OIDC', 'GET /oidc/rp/redirect');

    $error = $f3->get("GET.error");
    if ($error != null) {
        $error_description = $f3->get("GET.error_description");
        $f3->set('baseurl', '/' . $config->service_name);
        $f3->set('error_description', $error_description);
        echo View::instance()->render('view/error.php');
        die();
    }

    $code = $f3->get("GET.code");
    $req_id = trim(Util::base64UrlDecode($f3->get("GET.state")));
    $iss = $f3->get("GET.iss");

    // recover parameters from saved request
    $request = $database->getRequest($req_id);
    $op = $request['op_id'];
    $redirect_uri = $request['redirect_uri'];
    $state = $request['state'];
    $code_verifier = $request['code_verifier'];

    // TODO : federation
    $token_endpoint = $op_metadata->{$op}->openid_provider->token_endpoint;
    $userinfo_endpoint = $op_metadata->{$op}->openid_provider->userinfo_endpoint;

    try {
        $tokenRequest = new TokenRequest($config, $hooks);
        $tokenResponse = $tokenRequest->send($token_endpoint, $code, $code_verifier);
        $access_token = $tokenResponse->access_token;

        $userinfoRequest = new UserinfoRequest($config, $op_metadata->{$op}->openid_provider, $hooks);
        $userinfoResponse = $userinfoRequest->send($userinfo_endpoint, $access_token);

        $f3->set('SESSION.auth', array(
            "op" => $op,
            "access_token" => $access_token,
            "redirect_uri" => $redirect_uri,
            "userinfo" => $userinfoResponse,
            "state" => $state
        ));

        $responseHandlerClass = $config->rp_response_handler;
        $responseHandler = new $responseHandlerClass($config);
        $responseHandler->sendResponse($redirect_uri, $userinfoResponse, $state);
    } catch (Exception $e) {
        $f3->error($e->getCode(), $e->getMessage());
    }
});

$f3->route('GET /oidc/rp/introspection', function ($f3) {
    $config = $f3->get("CONFIG");
    $op_metadata = $f3->get("OP_METADATA");
    $auth = $f3->get("SESSION.auth");

    $op = $auth['op'];
    $access_token = $auth['access_token'];

    if ($access_token == null) {
        $f3->error("Session not found");
    }

    // TODO : federation
    $introspection_endpoint = $op_metadata->{$op}->openid_provider->introspection_endpoint;

    $introspectionRequest = new IntrospectionRequest($config);
    $introspectionResponse = $introspectionRequest->send($introspection_endpoint, $access_token);

    header('ContentType: application/json');
    echo json_encode($introspectionResponse);
});

$f3->route('GET /oidc/rp/logout', function ($f3) {
    $config = $f3->get("CONFIG");
    $op_metadata = $f3->get("OP_METADATA");
    $auth = $f3->get("SESSION.auth");

    $op = $auth['op'];
    $access_token = $auth['access_token'];

    if ($access_token == null) {
        $f3->reroute('/oidc/rp/authz');
    }

    // TODO : federation
    $revocation_endpoint = $op_metadata->{$op}->openid_provider->revocation_endpoint;

    try {
        $revocationRequest = new RevocationRequest($config);
        $revocationResponse = $revocationRequest->send($revocation_endpoint, $access_token);
    } catch (Exception $e) {
        // do not null
    } finally {
        $f3->clear('SESSION.auth');
    }

    $f3->reroute('/oidc/rp/authz');
});

//----------------------------------------------------------------------------------------



$f3->run();
