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
use SPID_CIE_OIDC_PHP\OIDC\OP\Database as OP_Database;
use SPID_CIE_OIDC_PHP\OIDC\OP\Metadata as OP_Metadata;
use SPID_CIE_OIDC_PHP\OIDC\OP\CertsEndpoint;
use SPID_CIE_OIDC_PHP\OIDC\OP\AuthenticationEndpoint;
use SPID_CIE_OIDC_PHP\OIDC\OP\TokenEndpoint;
use SPID_CIE_OIDC_PHP\OIDC\OP\UserinfoEndpoint;
use SPID_CIE_OIDC_PHP\OIDC\OP\SessionEndEndpoint;

$f3 = \Base::instance();

//----------------------------------------------------------------------------------------
// Available configurations / objects
//----------------------------------------------------------------------------------------

$config = json_decode(file_get_contents(__DIR__ . '/../config/config.json'), true);
$f3->set("CONFIG", $config);

$rp_database = new RP_Database(__DIR__ . '/../data/store-rp.sqlite');
$f3->set("RP_DATABASE", $rp_database);

$op_database = new OP_Database(__DIR__ . '/../data/store-op.sqlite');
$f3->set("OP_DATABASE", $op_database);

$federation = new Federation($config, json_decode(file_get_contents(__DIR__ . '/../config/federation-authority.json'), true));
$f3->set("FEDERATION", $federation);

$hooks = json_decode(file_get_contents(__DIR__ . '/../config/hooks.json'), true);
$f3->set("HOOKS", $hooks);

$logger = new Logger($config);
$f3->set("LOGGER", $logger);

$service_name = trim($config['service_name']);
$f3->set('BASEURL', ($service_name == '') ? '' : '/' . $service_name);
//----------------------------------------------------------------------------------------




$f3->route('GET /info', function ($f3) {
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
    $f3->set('error_description', $error_description);
    echo View::instance()->render('view/error.php');
    die();
});


//----------------------------------------------------------------------------------------
// Routes for @domain Relying Party
//----------------------------------------------------------------------------------------

$f3->route([
    'GET /.well-known/openid-federation',
    'GET /@domain/.well-known/openid-federation'
], function ($f3) {
    $domain = $f3->get("PARAMS.domain") ? $f3->get("PARAMS.domain") : 'default';
    $config = $f3->get("CONFIG")['rp_proxy_clients'][$domain];
    if (!$config) {
        $f3->error(400, "Domain not found");
    }

    $logger = $f3->get("LOGGER");
    $logger->log('OIDC', 'GET /.well-known/openid-federation');

    $output = $f3->get("GET.output");
    $json = strtolower($output) == 'json';
    $mediaType = $json ? 'application/json' : 'application/jwt';
    header('Content-Type: ' . $mediaType);
    echo EntityStatement::makeFromConfig($config, $json);
});

$f3->route([
    'GET /oidc/rp/authz',
    'GET /oidc/rp/@domain/authz'
], function ($f3) {
    $domain = $f3->get("PARAMS.domain") ? $f3->get("PARAMS.domain") : 'default';
    $config = $f3->get("CONFIG")['rp_proxy_clients'][$domain];
    if (!$config) {
        $f3->error(400, "Domain not found");
    }

    $logger = $f3->get("LOGGER");
    $logger->log('VIEW', 'GET /oidc/rp/authz');

    // stash params state from proxy requests
    // (OIDC generic 2 OIDC SPID)
    $f3->set("SESSION.state", $_GET['state']);

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
        $responseHandlerClass = $config['response_handler'];
        $responseHandler = new $responseHandlerClass($config);
        $responseHandler->sendResponse($redirect_uri, $userinfoResponse, $state);
        die();
    }

    $f3->set("DOMAIN", $domain);
    echo View::instance()->render('view/login.php');
});

$f3->route([
    'GET /oidc/rp/authz/@ta/@op',
    'GET /oidc/rp/@domain/authz/@ta/@op'
], function ($f3) {
    $domain = $f3->get("PARAMS.domain") ? $f3->get("PARAMS.domain") : 'default';
    $config = $f3->get("CONFIG")['rp_proxy_clients'][$domain];
    if (!$config) {
        $f3->error(400, "Domain not found");
    }

    $federation = $f3->get("FEDERATION");
    $rp_database = $f3->get("RP_DATABASE");
    $hooks = $f3->get("HOOKS");
    $logger = $f3->get("LOGGER");

    $logger->log('OIDC', 'GET /oidc/rp/authz/' . $f3->get('PARAMS.op'));

    $ta_id = base64_decode($f3->get('PARAMS.ta'));
    $op_id = base64_decode($f3->get('PARAMS.op'));

    // try to get state first from session, if routed from proxy
    $state = $f3->get('SESSION.state');
    if ($state == null) {
        $state = $f3->get('GET.state');
    }
    if ($state == null) {
        $state = 'state';
    }

    $acr = $config['requested_acr'];
    $user_attributes = $config['spid_user_attributes'];
    $redirect_uri = $config['redirect_uri'];
    $req_id = $rp_database->createRequest($ta_id, $op_id, $redirect_uri, $state, $acr, $user_attributes);
    $request = $rp_database->getRequest($req_id);
    $code_verifier = $request['code_verifier'];
    $nonce = $request['nonce'];

    if (!$federation->isFederationSupported($ta_id)) {
        $f3->error(401, "Federation non supported: " . $ta_id);
    }

    // resolve entity statement on federation
    try {
        $trustchain = new TrustChain($config, $rp_database, $op_id, $ta_id);
        $configuration = $trustchain->resolve();
    } catch (Exception $e) {
        $f3->error(401, $e->getMessage());
    }

    $authorization_endpoint = $configuration->metadata->openid_provider->authorization_endpoint;

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

$f3->route([
    'GET /oidc/rp/redirect',
    'GET /oidc/rp/@domain/redirect'
], function ($f3) {
    $domain = $f3->get("PARAMS.domain") ? $f3->get("PARAMS.domain") : 'default';
    $config = $f3->get("CONFIG")['rp_proxy_clients'][$domain];
    if (!$config) {
        $f3->error(400, "Domain not found");
    }

    $rp_database = $f3->get("RP_DATABASE");
    $hooks = $f3->get("HOOKS");
    $logger = $f3->get("LOGGER");

    $logger->log('OIDC', 'GET /oidc/rp/redirect');

    $error = $f3->get("GET.error");
    if ($error != null) {
        $error_description = $f3->get("GET.error_description");
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
        $trustchain = new TrustChain($config, $rp_database, $op_id, $ta_id);
        $configuration = $trustchain->resolve();
    } catch (Exception $e) {
        $f3->error(401, $e->getMessage());
    }

    $token_endpoint = $configuration->metadata->openid_provider->token_endpoint;
    $userinfo_endpoint = $configuration->metadata->openid_provider->userinfo_endpoint;

    try {
        $tokenRequest = new TokenRequest($config, $hooks);
        $tokenResponse = $tokenRequest->send($token_endpoint, $code, $code_verifier);
        $access_token = $tokenResponse->access_token;

        $userinfoRequest = new UserinfoRequest($config, $configuration->metadata->openid_provider, $hooks);
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

        $responseHandlerClass = $config['response_handler'];
        $responseHandler = new $responseHandlerClass($config);
        $responseHandler->sendResponse($redirect_uri, $userinfoResponse, $state);
    } catch (Exception $e) {
        $f3->error($e->getCode(), $e->getMessage());
    }
});

$f3->route([
    'GET /oidc/rp/introspection',
    'GET /oidc/rp/@domain/introspection',
], function ($f3) {
    $domain = $f3->get("PARAMS.domain") ? $f3->get("PARAMS.domain") : 'default';
    $config = $f3->get("CONFIG")['rp_proxy_clients'][$domain];
    if (!$config) {
        $f3->error(400, "Domain not found");
    }

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
        $trustchain = new TrustChain($config, $rp_database, $op_id, $ta_id);
        $configuration = $trustchain->resolve();
    } catch (Exception $e) {
        $f3->error(401, $e->getMessage());
    }

    try {
        $introspection_endpoint = $configuration->metadata->openid_provider->introspection_endpoint;
        $introspectionRequest = new IntrospectionRequest($config);
        $introspectionResponse = $introspectionRequest->send($introspection_endpoint, $access_token);
    } catch (\Exception $e) {
        $f3->error(401, $e->getMessage());
    }


    header('Content-Type: application/json');
    echo json_encode($introspectionResponse);
});

$f3->route([
    'GET /oidc/rp/logout',
    'GET /oidc/rp/@domain/logout'
], function ($f3) {
    $domain = $f3->get("PARAMS.domain") ? $f3->get("PARAMS.domain") : 'default';
    $config = $f3->get("CONFIG")['rp_proxy_clients'][$domain];
    if (!$config) {
        $f3->error(400, "Domain not found");
    }

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
        $trustchain = new TrustChain($config, $rp_database, $op_id, $ta_id);
        $configuration = $trustchain->resolve();
    } catch (Exception $e) {
        $f3->error(401, $e->getMessage());
    }

    $revocation_endpoint = $configuration->metadata->openid_provider->revocation_endpoint;

    try {
        $revocationRequest = new RevocationRequest($config);
        $revocationResponse = $revocationRequest->send($revocation_endpoint, $access_token);
    } catch (Exception $e) {
        // do not null
    } finally {
        $f3->clear('SESSION.auth');
    }

    $post_logout_redirect_uri = $f3->get('GET.post_logout_redirect_uri');
    if ($post_logout_redirect_uri == null) {
        $post_logout_redirect_uri = '/oidc/rp/authz';
    }

    $f3->reroute($post_logout_redirect_uri);
});

//----------------------------------------------------------------------------------------


//----------------------------------------------------------------------------------------
// Routes for Proxy OIDC Provider
//----------------------------------------------------------------------------------------

$f3->route('GET /oidc/proxy/.well-known/openid-configuration', function ($f3) {
    $config = $f3->get("CONFIG");

    try {
        $op_metadata = new OP_Metadata($config);
        header('Content-Type: application/json');
        echo $op_metadata->getConfiguration();
    } catch (Exception $e) {
        $f3->error(500, $e->getMessage());
    }
});

$f3->route('GET /oidc/proxy/certs', function ($f3) {
    $config = $f3->get("CONFIG");
    $op_database = $f3->get("OP_DATABASE");

    try {
        $handler = new CertsEndpoint($config, $op_database);
        $handler->process();
    } catch (Exception $e) {
        $f3->error(500, $e->getMessage());
    }
});

$f3->route('GET /oidc/proxy/authz', function ($f3) {
    $config = $f3->get("CONFIG");
    $op_database = $f3->get("OP_DATABASE");

    try {
        $handler = new AuthenticationEndpoint($config, $op_database);
        $handler->process();
    } catch (\Exception $e) {
        $f3->error(400, $e->getMessage());
    }
});

$f3->route('POST /oidc/proxy/callback', function ($f3) {
    $config = $f3->get("CONFIG");
    $op_database = $f3->get("OP_DATABASE");

    try {
        $handler = new AuthenticationEndpoint($config, $op_database);
        $handler->callback();
    } catch (\Exception $e) {
        $f3->error(400, $e->getMessage());
    }
});

$f3->route('POST /oidc/proxy/token', function ($f3) {
    $config = $f3->get("CONFIG");
    $op_database = $f3->get("OP_DATABASE");

    try {
        $handler = new TokenEndpoint($config, $op_database);
        $handler->process();
    } catch (\Exception $e) {
        $f3->error(400, $e->getMessage());
    }
});

$f3->route('POST /oidc/proxy/userinfo', function ($f3) {
    $config = $f3->get("CONFIG");
    $op_database = $f3->get("OP_DATABASE");

    try {
        $handler = new UserinfoEndpoint($config, $op_database);
        $handler->process();
    } catch (\Exception $e) {
        $f3->error(400, $e->getMessage());
    }
});

$f3->route('GET /oidc/proxy/session/end', function ($f3) {
    $config = $f3->get("CONFIG");
    $op_database = $f3->get("OP_DATABASE");

    try {
        $handler = new SessionEndEndpoint($config, $op_database);
        $handler->process();
    } catch (\Exception $e) {
        $f3->error(400, $e->getMessage());
    }
});

//----------------------------------------------------------------------------------------




//----------------------------------------------------------------------------------------
// DEMO
//----------------------------------------------------------------------------------------
$f3->route('GET /', function ($f3) {
    $f3->reroute('/test.php');
});



$f3->run();
