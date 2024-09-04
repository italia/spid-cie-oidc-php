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
 * @author  Michele D'Amico <michele.damico@linfaservice.it>
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SPID_CIE_OIDC_PHP\Core\Logger;
use SPID_CIE_OIDC_PHP\Core\Util;
use SPID_CIE_OIDC_PHP\Federation\Federation;
use SPID_CIE_OIDC_PHP\Federation\FetchEntityStatementEndpoint;
use SPID_CIE_OIDC_PHP\Federation\EntityListingEndpoint;
use SPID_CIE_OIDC_PHP\Federation\EntityStatement;
use SPID_CIE_OIDC_PHP\Federation\ResolveEndpoint;
use SPID_CIE_OIDC_PHP\Federation\TrustChain;
use SPID_CIE_OIDC_PHP\Federation\TrustMark;
use SPID_CIE_OIDC_PHP\Federation\TrustMarkStatusEndpoint;
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




$f3->route(
    'GET /info',
    function ($f3) {
        $composer = json_decode(file_get_contents(__DIR__ . '/../composer.json'));
        echo "SPID CIE OIDC PHP - Version " . $composer->config->version;
    }
);

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

$f3->set(
    'ONERROR',
    function ($f3) {
        $config = $f3->get("CONFIG");
        $error_description = $f3->get('ERROR.text');
        $f3->set('error_description', $error_description);
        echo View::instance()->render('view/error.php');
        die();
    }
);

//$logger = $f3->get("LOGGER");
//$logger->log('spid-cie-oidc-php', $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'], $_REQUEST); 

//----------------------------------------------------------------------------------------
// Routes for SA
//----------------------------------------------------------------------------------------

// GET /.well-known/openid-federation
$f3->route(
    [
    'GET /.well-known/openid-federation',
    ],
    function ($f3) {
        $config = ($f3->get("CONFIG")['sa']) ?? false;
        if (!$config) {
            $f3->error(400, "SA configuration not found");
        }

        $logger = $f3->get("LOGGER");

        $output = $f3->get("GET.output") ?? 'default';
        $json = (strtolower($output) == 'json');

        $mediaType = $json ? 'application/json' : 'application/entity-statement+jwt';
        header('Content-Type: ' . $mediaType);
        $response = EntityStatement::makeSAEntityStatementFromConfig($config, $json);

        echo $response;

        $logger->log('spid-cie-oidc-php', 'GET /.well-known/openid-federation', $_GET, $response); 
    }
);

// GET /fetch
$f3->route(
    'GET /fetch',
    function ($f3) {
        if (!$f3->get("CONFIG")) $f3->error(400, "Configuration not found");
        if (!$f3->get("CONFIG")['sa']) $f3->error(400, "SA configuration not found");
        if (!$f3->get("CONFIG")['rp_proxy_clients']) $f3->error(400, "Clients configuration not found");

        $sa_config = $f3->get("CONFIG")['sa'];
        $rp_config = $f3->get("CONFIG")['rp_proxy_clients'];

        try {
            $logger = $f3->get("LOGGER");

            $handler = new FetchEntityStatementEndpoint($sa_config, $rp_config);
            $response = $handler->process();

            $logger->log('spid-cie-oidc-php', 'GET /fetch', $_GET, $response); 
 
        } catch (\Exception $e) {
            $f3->error(400, $e->getMessage());
        }
    } 
);
 
// GET /list
$f3->route(
    'GET /list',
    function ($f3) {
        $config = ($f3->get("CONFIG")['rp_proxy_clients']) ?? false;

        if (!$config) {
            $f3->error(400, "Clients configuration not found");
        }

        try {
            $logger = $f3->get("LOGGER");

            $handler = new EntityListingEndpoint($config);
            $response = $handler->process();

            $logger->log('spid-cie-oidc-php', 'GET /list', $_GET, $response); 
 
        } catch (\Exception $e) {
            $f3->error(400, $e->getMessage());
        }
    }
);

// POST /trust_mark
$f3->route(
    'POST /trust_mark',
    function ($f3) {
        $config = ($f3->get("CONFIG")['sa']) ?? false;
        if (!$config) {
            $f3->error(400, "SA configuration not found");
        }

        $sub = $_POST['sub'] ?? null;
        $id = $_POST['id'] ?? null;
        $organization_type = $_POST['organization_type'] ?? null;
        $id_code = $_POST['id_code'] ?? null;
        $email = $_POST['email'] ?? null;
        $organization_name = $_POST['organization_name'] ?? null;
        $sa_profile = $_POST['sa_profile'] ?? null;

        if (!$sub) $f3->error(400, "sub is mandatory");
        if (!$id) $f3->error(400, "id is mandatory");
        if (!$organization_type) $f3->error(400, "organization_type is mandatory");
        if (!$id_code) $f3->error(400, "id_code is mandatory");
        if (!$email) $f3->error(400, "email is mandatory");
        if (!$organization_name) $f3->error(400, "organization_name is mandatory");

        try {
            $logger = $f3->get("LOGGER");

            $mediaType = 'application/json';
            header('Content-Type: ' . $mediaType);

            $trust_mark = new TrustMark($config, $sub, $id, $organization_type, $id_code, $email, $organization_name, $sa_profile);
            $response = array(
                'id' => $id, 
                'iss' => $config['client_id'],
                'trust_mark' => $trust_mark->makeJwt()
            );

            echo json_encode($response);

            $logger->log('spid-cie-oidc-php', 'POST /trust_mark', $_POST, $response);

        } catch (Exception $e) {
            $f3->error(500, $e->getMessage());
        }
    }
);

// POST /trust_mark_status
$f3->route(
    'POST /trust_mark_status',
    function ($f3) {
        $config = ($f3->get("CONFIG")['rp_proxy_clients']) ?? false;

        if (!$config) {
            $f3->error(400, "Clients configuration not found");
        }

        try {
            $logger = $f3->get("LOGGER"); 

            $handler = new TrustMarkStatusEndpoint($config);
            $response = $handler->process();

            $logger->log('spid-cie-oidc-php', 'POST /trust_mark_status', $_POST, $response);
 
        } catch (\Exception $e) {
            $f3->error(400, $e->getMessage());
        }
    }
);


//----------------------------------------------------------------------------------------
// Routes for @domain Relying Party
//----------------------------------------------------------------------------------------

// GET /@domain/.well-known/openid-federation
$f3->route(
    [
    'GET /@domain/.well-known/openid-federation'
    ],
    function ($f3) {
        $domain = $f3->get("PARAMS.domain") ? $f3->get("PARAMS.domain") : $f3->get("CONFIG")['default_domain'];
        $base = $f3->get("CONFIG")['sa']['client_id'];
        $config = $f3->get("CONFIG")['rp_proxy_clients'][$domain];
        if (!$config) {
            $f3->error(400, "Domain not found");
        }

        $logger = $f3->get("LOGGER");

        $output = $f3->get("GET.output") ?? 'default';
        $json = (strtolower($output) == 'json');

        $mediaType = $json ? 'application/json' : 'application/entity-statement+jwt';
        header('Content-Type: ' . $mediaType);
        $response = EntityStatement::makeRPEntityConfigurationFromConfig($base, $domain, $config, $json);

        echo $response;

        $logger->log('spid-cie-oidc-php', 'GET /'.$domain.'/.well-known/openid-federation', $_GET, $response);
    }
);

// GET /oidc/rp/authz
// GET /oidc/rp/@domain/authz
$f3->route(
    [
    'GET /oidc/rp/authz',
    'GET /oidc/rp/@domain/authz'
    ],
    function ($f3) {
        $domain = $f3->get("PARAMS.domain") ? $f3->get("PARAMS.domain") : $f3->get("CONFIG")['default_domain'];
        $config = $f3->get("CONFIG")['rp_proxy_clients'][$domain];
        if (!$config) {
            $f3->error(400, "Domain not found");
        }

        $logger = $f3->get("LOGGER");

        // stash params state from proxy requests
        // (OIDC generic 2 OIDC SPID)
        $f3->set("SESSION.state", $_GET['state'] ?? '');

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
            $responseHandlerClass = $config['proxy_response_handler'];
            $responseHandler = new $responseHandlerClass($config);
            $responseHandler->sendResponse($redirect_uri, $userinfoResponse, $state);
            die();
        }

        $f3->set("DOMAIN", $domain);
        echo View::instance()->render('view/login.php');

        $logger->log('spid-cie-oidc-php', 'GET /oidc/rp/authz', $_GET, 'redirect to view/login.php');
    }
);

// GET /oidc/rp/authz/@ta/@op
// GET /oidc/rp/@domain/authz/@ta/@op
$f3->route(
    [
    'GET /oidc/rp/authz/@ta/@op',
    'GET /oidc/rp/@domain/authz/@ta/@op'
    ],
    function ($f3) {
        $domain = $f3->get("PARAMS.domain") ? $f3->get("PARAMS.domain") : $f3->get("CONFIG")['default_domain'];
        $service_name = $f3->get("CONFIG")['service_name'];
        $base = $f3->get("CONFIG")['sa']['client_id'];
        $config = $f3->get("CONFIG")['rp_proxy_clients'][$domain];
        if (!$config) {
            $f3->error(400, "Domain not found");
        }

        $federation = $f3->get("FEDERATION");
        $rp_database = $f3->get("RP_DATABASE");
        $hooks = $f3->get("HOOKS");
        $logger = $f3->get("LOGGER");

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
        $proxy_redirect_uri = $config['proxy_redirect_uri'];
        $req_id = $rp_database->createRequest($ta_id, $op_id, $proxy_redirect_uri, $state, $acr, $user_attributes);
        $request = $rp_database->getRequest($req_id);
        $code_verifier = $request['code_verifier'];
        $nonce = $request['nonce'];

        if (!$federation->isFederationSupported($ta_id)) {
            $f3->error(401, "Federation not supported: " . $ta_id);
        }

        // resolve entity statement on federation
        try {
            $trustchain = new TrustChain($config, $rp_database, $op_id, $ta_id);
            $configuration = $trustchain->resolve();
        } catch (Exception $e) {
            $f3->error(401, $e->getMessage());
        }

        $authorization_endpoint = $configuration->metadata->openid_provider->authorization_endpoint;
        $op_issuer = $configuration->metadata->openid_provider->issuer;
 
        $authenticationRequest = new AuthenticationRequest($base, $service_name, $domain, $config, $hooks);
        $authenticationRequestURL = $authenticationRequest->getRedirectURL(
            $op_issuer,
            $authorization_endpoint,
            $acr,
            $user_attributes,
            $code_verifier,
            $nonce,
            Util::base64UrlEncode(str_pad($req_id, 32))
        );

        $authenticationRequest->sendURL($authenticationRequestURL);
        $logger->log('spid-cie-oidc-php', 'GET /oidc/rp/authz/' . $ta_id . '/' . $op_id, $_GET, 'redirect to ' . $authenticationRequestURL);
    }
);

// GET /oidc/rp/redirect
// GET /oidc/rp/@domain/redirect
$f3->route(
    [
    'GET /oidc/rp/redirect',
    'GET /oidc/rp/@domain/redirect'
    ],
    function ($f3) {
        $domain = $f3->get("PARAMS.domain") ? $f3->get("PARAMS.domain") : $f3->get("CONFIG")['default_domain'];
        $config = $f3->get("CONFIG")['rp_proxy_clients'][$domain];
        if (!$config) {
            $f3->error(400, "Domain not found");
        }

        $rp_database = $f3->get("RP_DATABASE");
        $hooks = $f3->get("HOOKS");
        $logger = $f3->get("LOGGER");

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

        $logger->log('spid-cie-oidc-php', 'GET /oidc/rp/redirect', $_GET);

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
            $logger->log('spid-cie-oidc-php', 'TOKEN REQUEST POST ' . $token_endpoint, $tokenRequest, $tokenResponse);

            $userinfoRequest = new UserinfoRequest($config, $configuration->metadata->openid_provider, $hooks);
            $userinfoResponse = $userinfoRequest->send($userinfo_endpoint, $access_token);

            $logger->log('spid-cie-oidc-php', 'USERINFO REQUEST GET ' . $userinfo_endpoint, $userinfoRequest, $userinfoResponse);

            $f3->set(
                'SESSION.auth',
                array(
                "ta_id" => $ta_id,
                "op_id" => $op_id,
                "access_token" => $access_token,
                "redirect_uri" => $redirect_uri,
                "userinfo" => $userinfoResponse,
                "state" => $state
                )
            );

            $userinfoResponse->trust_anchor_id = $ta_id;
            $userinfoResponse->provider_id = $op_id;

            $responseHandlerClass = $config['proxy_response_handler'];
            $responseHandler = new $responseHandlerClass($config);
            $responseHandler->sendResponse($redirect_uri, $userinfoResponse, $state);

            $logger->log('spid-cie-oidc-php', 'GET /oidc/rp/redirect', $_GET, 'redirect response to ' . $redirect_uri);

        } catch (Exception $e) {
            $code = in_array($e->getCode(), [200, 301, 302, 400, 401, 404]) ? $e->getCode() : 500;
            $f3->error($code, $e->getMessage());
        }
    }
);

// GET /resolve
$f3->route(
    [
    'GET /resolve'
    ],
    function ($f3) {
        $domain = $f3->get("PARAMS.domain") ? $f3->get("PARAMS.domain") : 'default';
        $config = $f3->get("CONFIG")['rp_proxy_clients'][$domain];
        $sub = $f3->get("GET.sub");
        $anchor = $f3->get("GET.anchor");
        if (empty($sub) || empty($anchor)) {
            $f3->error(400, "anchor or sub not not found");
        }

        $rp_database = $f3->get("RP_DATABASE");
        $logger = $f3->get("LOGGER");

        $mediaType = 'application/entity-statement+jwt';
        header('Content-Type: ' . $mediaType);
        $response = ResolveEndpoint::resolve($config, $rp_database, $sub, $anchor);

        echo $response;

        $logger->log('spid-cie-oidc-php', 'GET /resolve', $_GET, $response);
    }
);

// GET /oidc/rp/introspection
// GET /oidc/rp/@domain/introspection
$f3->route(
    [
    'GET /oidc/rp/introspection',
    'GET /oidc/rp/@domain/introspection',
    ],
    function ($f3) {
        $domain = $f3->get("PARAMS.domain") ? $f3->get("PARAMS.domain") : $f3->get("CONFIG")['default_domain'];
        $config = $f3->get("CONFIG")['rp_proxy_clients'][$domain];
        if (!$config) {
            $f3->error(400, "Domain not found");
        }

        $rp_database = $f3->get("RP_DATABASE");
        $auth = $f3->get("SESSION.auth");
        $logger = $f3->get("LOGGER");

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
        $response = json_encode($introspectionResponse);

        echo $response;

        $logger->log('spid-cie-oidc-php', 'GET /oidc/rp/introspection', $_GET, $response);
    }
);

// GET /oidc/rp/logout
// GET /oidc/rp/@domain/logout
$f3->route(
    [
    'GET /oidc/rp/logout',
    'GET /oidc/rp/@domain/logout'
    ],
    function ($f3) {
        $domain = $f3->get("PARAMS.domain") ? $f3->get("PARAMS.domain") : $f3->get("CONFIG")['default_domain'];
        $config = $f3->get("CONFIG")['rp_proxy_clients'][$domain];
        if (!$config) {
            $f3->error(400, "Domain not found");
        }

        $rp_database = $f3->get("RP_DATABASE");
        $auth = $f3->get("SESSION.auth");
        $logger = $f3->get("LOGGER");

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

        $logger->log('spid-cie-oidc-php', 'GET /oidc/rp/logout', $_GET, 'redirect to ' . $post_logout_redirect_uri);
        $f3->reroute($post_logout_redirect_uri);
    }
);

//----------------------------------------------------------------------------------------


//----------------------------------------------------------------------------------------
// Routes for Proxy OIDC Provider
//----------------------------------------------------------------------------------------

// GET /oidc/proxy/.well-known/openid-configuration
$f3->route(
    'GET /oidc/proxy/.well-known/openid-configuration',
    function ($f3) {
        $config = $f3->get("CONFIG");
        $logger = $f3->get("LOGGER");

        try {
            $op_metadata = new OP_Metadata($config);
            header('Content-Type: application/json');
            $response = $op_metadata->getConfiguration();
            
            echo $response;

            $logger->log('spid-cie-oidc-php', 'GET /oidc/proxy/.well-known/openid-configuration', $_GET, $response);

        } catch (Exception $e) {
            $f3->error(500, $e->getMessage());
        }
    }
);

// GET /oidc/proxy/certs
$f3->route(
    'GET /oidc/proxy/certs',
    function ($f3) {
        $config = $f3->get("CONFIG");
        $op_database = $f3->get("OP_DATABASE");
        $logger = $f3->get("LOGGER");

        try {
            $handler = new CertsEndpoint($config, $op_database);
            $response = $handler->process();

            $logger->log('spid-cie-oidc-php', 'GET /oidc/proxy/certs', $_GET, $response);

        } catch (Exception $e) {
            $f3->error(500, $e->getMessage());
        }
    }
);

// GET /oidc/proxy/authz
$f3->route(
    'GET /oidc/proxy/authz',
    function ($f3) {
        $config = $f3->get("CONFIG");
        $op_database = $f3->get("OP_DATABASE");
        $logger = $f3->get("LOGGER");

        try {
            $handler = new AuthenticationEndpoint($config, $op_database);
            $response = $handler->process();

            $logger->log('spid-cie-oidc-php', 'GET /oidc/proxy/authz', $_GET, $response);

        } catch (\Exception $e) {
            $f3->error(400, $e->getMessage());
        }
    }
);

// POST /oidc/proxy/callback
$f3->route(
    'POST /oidc/proxy/callback',
    function ($f3) {
        $config = $f3->get("CONFIG");
        $op_database = $f3->get("OP_DATABASE");
        $logger = $f3->get("LOGGER");

        try {
            $handler = new AuthenticationEndpoint($config, $op_database);
            $response = $handler->callback();

            $logger->log('spid-cie-oidc-php', 'POST /oidc/proxy/callback', $_POST, $response);
        } catch (\Exception $e) {
            $f3->error(400, $e->getMessage());
        }
    }
);

// POST /oidc/proxy/token
$f3->route(
    'POST /oidc/proxy/token',
    function ($f3) {
        $config = $f3->get("CONFIG");
        $op_database = $f3->get("OP_DATABASE");
        $logger = $f3->get("LOGGER");

        try {
            $handler = new TokenEndpoint($config, $op_database);
            $response = $handler->process();

            $logger->log('spid-cie-oidc-php', 'POST /oidc/proxy/token', $_POST, $response);
        } catch (\Exception $e) {
            $f3->error(400, $e->getMessage());
        }
    }
);

// POST /oidc/proxy/userinfo
$f3->route(
    'POST /oidc/proxy/userinfo',
    function ($f3) {
        $config = $f3->get("CONFIG");
        $op_database = $f3->get("OP_DATABASE");
        $logger = $f3->get("LOGGER");

        try {
            $handler = new UserinfoEndpoint($config, $op_database);
            $response = $handler->process();

            $logger->log('spid-cie-oidc-php', 'POST /oidc/proxy/userinfo', $_POST, $response);
        } catch (\Exception $e) {
            $f3->error(400, $e->getMessage());
        }
    }
);

// GET /oidc/proxy/session/end
$f3->route(
    'GET /oidc/proxy/session/end',
    function ($f3) {
        $config = $f3->get("CONFIG");
        $op_database = $f3->get("OP_DATABASE");
        $logger = $f3->get("LOGGER");

        try {
            $handler = new SessionEndEndpoint($config, $op_database);
            $response = $handler->process();

            echo $response;

            $logger->log('spid-cie-oidc-php', 'GET /oidc/proxy/session/end', $_GET, $response);

        } catch (\Exception $e) {
            $f3->error(400, $e->getMessage());
        }
    }
);

//----------------------------------------------------------------------------------------




//----------------------------------------------------------------------------------------
// HOME
//----------------------------------------------------------------------------------------
$f3->route(
    'GET /',
    function ($f3) {
        $config = $f3->get("CONFIG");
        $homepage = (isset($config['homepage']) && $config['homepage']!=null)? $config['homepage'] : false;
        if($homepage) {
            $f3->reroute($config['homepage']);
        } else {
            $f3->error(400, "Bad Request");
        }
    }
);



$f3->run();
