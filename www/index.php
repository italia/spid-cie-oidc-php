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

use SPID_CIE_OIDC_PHP\Core\Logger;
use SPID_CIE_OIDC_PHP\Core\Util;
use SPID_CIE_OIDC_PHP\Federation\Federation;
use SPID_CIE_OIDC_PHP\Federation\EntityStatement;
use SPID_CIE_OIDC_PHP\Federation\TrustChain;
use SPID_CIE_OIDC_PHP\OIDC\RP\Database as RP_Database;
use SPID_CIE_OIDC_PHP\OIDC\RP\AuthenticationRequest;
use SPID_CIE_OIDC_PHP\OIDC\RP\TokenRequest;
use SPID_CIE_OIDC_PHP\OIDC\RP\UserinfoRequest;
use SPID_CIE_OIDC_PHP\OIDC\RP\IntrospectionRequest;
use SPID_CIE_OIDC_PHP\OIDC\RP\RevocationRequest;

$f3 = \Base::instance();

//----------------------------------------------------------------------------------------
// Available configurations / objects
//----------------------------------------------------------------------------------------
$rp_config = json_decode(file_get_contents(__DIR__ . '/../config/rp-config.json'));
$f3->set("RP_CONFIG", $rp_config);

$hooks = json_decode(file_get_contents(__DIR__ . '/../config/hooks.json'));
$f3->set("HOOKS", $hooks);

$federation = new Federation($rp_config, json_decode(file_get_contents(__DIR__ . '/../config/federation-authority.json')));
$f3->set("FEDERATION", $federation);

$rp_database = new RP_Database(__DIR__ . '/../data/db_rp.sqlite');
$f3->set("RP_DATABASE", $rp_database);

$logger = new Logger($rp_config);
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
    $rp_config = $f3->get("RP_CONFIG");
    $error_description = $f3->get('ERROR.text');
    $f3->set('baseurl', '/' . $rp_config->service_name);
    $f3->set('error_description', $error_description);
    echo View::instance()->render('view/error.php');
    die();
});




//----------------------------------------------------------------------------------------
// Routes for Relying Party
//----------------------------------------------------------------------------------------

$f3->route('GET /.well-known/openid-federation', function ($f3) {
    $rp_config = $f3->get("RP_CONFIG");
    $logger = $f3->get("LOGGER");

    $logger->log('OIDC', 'GET /.well-known/openid-federation');

    $decoded = $f3->get("GET.decoded");
    $mediaType = $decoded == 'Y' ? 'application/json' : 'application/jwt';
    header('Content-Type: ' . $mediaType);
    echo EntityStatement::makeFromConfig($rp_config, $decoded == 'Y');
});

$f3->route('GET /oidc/rp/authz', function ($f3) {
    $rp_config = $f3->get("RP_CONFIG");
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
        $responseHandlerClass = $rp_config->rp_response_handler;
        $responseHandler = new $responseHandlerClass($rp_config);
        $responseHandler->sendResponse($redirect_uri, $userinfoResponse, $state);
        die();
    }

    $f3->set('baseurl', '/' . $rp_config->service_name);
    echo View::instance()->render('view/login.php');
});

$f3->route('GET /oidc/rp/authz/@ta/@op', function ($f3) {
    $rp_config = $f3->get("RP_CONFIG");
    $federation = $f3->get("FEDERATION");
    $rp_database = $f3->get("RP_DATABASE");
    $hooks = $f3->get("HOOKS");
    $logger = $f3->get("LOGGER");

    $logger->log('OIDC', 'GET /oidc/rp/authz/' . $f3->get('PARAMS.op'));

    $ta_id = base64_decode($f3->get('PARAMS.ta'));
    $op_id = base64_decode($f3->get('PARAMS.op'));
    $state = $f3->get('GET.state');
    $acr = $rp_config->rp_requested_acr;
    $user_attributes = $rp_config->rp_spid_user_attributes;
    $redirect_uri = $rp_config->rp_redirect_uri;
    $req_id = $rp_database->createRequest($ta_id, $op_id, $redirect_uri, $state, $acr, $user_attributes);
    $request = $rp_database->getRequest($req_id);
    $code_verifier = $request['code_verifier'];
    $nonce = $request['nonce'];

    if (!$federation->isFederationSupported($ta_id)) {
        $f3->error(401, "Federation non supported: " . $ta_id);
    }

    // resolve entity statement on federation
    try {
        $trustchain = new TrustChain($rp_config, $rp_database, $op_id, $ta_id);
        $configuration = $trustchain->resolve();
    } catch (Exception $e) {
        $f3->error(401, $e->getMessage());
    }

    $authorization_endpoint = $configuration->metadata->openid_provider->authorization_endpoint;

    $authenticationRequest = new AuthenticationRequest($rp_config, $hooks);
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
    $rp_config = $f3->get("RP_CONFIG");
    $rp_database = $f3->get("RP_DATABASE");
    $hooks = $f3->get("HOOKS");
    $logger = $f3->get("LOGGER");

    $logger->log('OIDC', 'GET /oidc/rp/redirect');

    $error = $f3->get("GET.error");
    if ($error != null) {
        $error_description = $f3->get("GET.error_description");
        $f3->set('baseurl', '/' . $rp_config->service_name);
        $f3->set('error_description', $error_description);
        echo View::instance()->render('view/error.php');
        die();
    }

    $code = $f3->get("GET.code");
    $req_id = trim(Util::base64UrlDecode($f3->get("GET.state")));
    $iss = $f3->get("GET.iss");

    // recover parameters from saved request
    $request = $rp_database->getRequest($req_id);
    $ta_id = $request['ta_id'];
    $op_id = $request['op_id'];
    $redirect_uri = $request['redirect_uri'];
    $state = $request['state'];
    $code_verifier = $request['code_verifier'];

    // resolve entity statement on federation
    try {
        $trustchain = new TrustChain($rp_config, $rp_database, $op_id, $ta_id);
        $configuration = $trustchain->resolve();
    } catch (Exception $e) {
        $f3->error(401, $e->getMessage());
    }

    $token_endpoint = $configuration->metadata->openid_provider->token_endpoint;
    $userinfo_endpoint = $configuration->metadata->openid_provider->userinfo_endpoint;

    try {
        $tokenRequest = new TokenRequest($rp_config, $hooks);
        $tokenResponse = $tokenRequest->send($token_endpoint, $code, $code_verifier);
        $access_token = $tokenResponse->access_token;

        $userinfoRequest = new UserinfoRequest($rp_config, $configuration->metadata->openid_provider, $hooks);
        $userinfoResponse = $userinfoRequest->send($userinfo_endpoint, $access_token);

        $f3->set('SESSION.auth', array(
            "ta_id" => $ta_id,
            "op_id" => $op_id,
            "access_token" => $access_token,
            "redirect_uri" => $redirect_uri,
            "userinfo" => $userinfoResponse,
            "state" => $state
        ));

        $userinfoResponse->trust_anchor_id = $ta_id;
        $userinfoResponse->provider_id = $op_id;

        $responseHandlerClass = $rp_config->rp_response_handler;
        $responseHandler = new $responseHandlerClass($rp_config);
        $responseHandler->sendResponse($redirect_uri, $userinfoResponse, $state);
    } catch (Exception $e) {
        $f3->error($e->getCode(), $e->getMessage());
    }
});

$f3->route('GET /oidc/rp/introspection', function ($f3) {
    $rp_config = $f3->get("RP_CONFIG");
    $rp_database = $f3->get("RP_DATABASE");
    $auth = $f3->get("SESSION.auth");

    $ta_id = $auth['ta_id'];
    $op_id = $auth['op_id'];
    $access_token = $auth['access_token'];

    if ($access_token == null) {
        $f3->error("Session not found");
    }

    // resolve entity statement on federation
    try {
        $trustchain = new TrustChain($rp_config, $rp_database, $op_id, $ta_id);
        $configuration = $trustchain->resolve();
    } catch (Exception $e) {
        $f3->error(401, $e->getMessage());
    }

    try {
        $introspection_endpoint = $configuration->metadata->openid_provider->introspection_endpoint;
        $introspectionRequest = new IntrospectionRequest($rp_config);
        $introspectionResponse = $introspectionRequest->send($introspection_endpoint, $access_token);
    } catch (\Exception $e) {
        $f3->error(401, $e->getMessage());
    }


    header('ContentType: application/json');
    echo json_encode($introspectionResponse);
});

$f3->route('GET /oidc/rp/logout', function ($f3) {
    $rp_config = $f3->get("RP_CONFIG");
    $rp_database = $f3->get("RP_DATABASE");
    $auth = $f3->get("SESSION.auth");

    $ta_id = $auth['ta_id'];
    $op_id = $auth['op_id'];
    $access_token = $auth['access_token'];

    if ($access_token == null) {
        $f3->reroute('/oidc/rp/authz');
    }

    // resolve entity statement on federation
    try {
        $trustchain = new TrustChain($rp_config, $rp_database, $op_id, $ta_id);
        $configuration = $trustchain->resolve();
    } catch (Exception $e) {
        $f3->error(401, $e->getMessage());
    }

    $revocation_endpoint = $configuration->metadata->openid_provider->revocation_endpoint;

    try {
        $revocationRequest = new RevocationRequest($rp_config);
        $revocationResponse = $revocationRequest->send($revocation_endpoint, $access_token);
    } catch (Exception $e) {
        // do not null
    } finally {
        $f3->clear('SESSION.auth');
    }

    $f3->reroute('/oidc/rp/authz');
});

//----------------------------------------------------------------------------------------


//----------------------------------------------------------------------------------------
// Routes for Proxy OIDC Provider
//----------------------------------------------------------------------------------------

$f3->route('GET /oidc/op/certs', function ($f3) {
    //$handler = new CertsEndpoint
});

//----------------------------------------------------------------------------------------






$f3->run();
