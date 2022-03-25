<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SPID_CIE_OIDC_PHP\Core\Database;
use SPID_CIE_OIDC_PHP\Core\Logger;
use SPID_CIE_OIDC_PHP\Core\Util;
use SPID_CIE_OIDC_PHP\Federation\EntityStatement;
use SPID_CIE_OIDC_PHP\OIDC\AuthenticationRequestCIE;
use SPID_CIE_OIDC_PHP\OIDC\TokenRequest;
use SPID_CIE_OIDC_PHP\OIDC\UserinfoRequest;

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

$db = new Database(__DIR__ . '/../data/db.sqlite');
$f3->set("DB", $db);

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




/**
 * routes
 */

$f3->route('GET /.well-known/openid-federation', function ($f3) {
    $config = $f3->get("CONFIG");
    $logger = $f3->get("LOGGER");

    $logger->log('OIDC', 'GET /.well-known/openid-federation');

    $entityStatement = new EntityStatement($config);
    $decoded = $f3->get("GET.decoded");
    $mediaType = $decoded == 'Y' ? 'application/json' : 'application/jwt';
    header('Content-Type: ' . $mediaType);
    echo $entityStatement->getConfiguration($decoded);
});

$f3->route('GET /oidc/rp/authz', function ($f3) {
    $config = $f3->get("CONFIG");
    $logger = $f3->get("LOGGER");

    $logger->log('VIEW', 'GET /oidc/rp/authz');

    $f3->set('baseurl', '/' . $config->service_name);
    echo View::instance()->render('view/login.php');
});

$f3->route('GET /oidc/rp/authz/@op', function ($f3) {
    $config = $f3->get("CONFIG");
    $op_metadata = $f3->get("OP_METADATA");
    $db = $f3->get("DB");
    $hooks = $f3->get("HOOKS");
    $logger = $f3->get("LOGGER");

    $logger->log('OIDC', 'GET /oidc/rp/authz/' . $f3->get('PARAMS.op'));

    $op = base64_decode($f3->get('PARAMS.op'));
    $state = $f3->get('GET.state');
    $acr = $config->rp_requested_acr;
    $user_attributes = $config->rp_spid_user_attributes;
    $redirect_uri = $config->rp_redirect_uri;
    $req_id = $db->createRequest($op, $redirect_uri, $state, $acr, $user_attributes);
    $request = $db->getRequest($req_id);
    $code_verifier = $request['code_verifier'];
    $nonce = $request['nonce'];

    // TODO : federation
    $authorization_endpoint = $op_metadata->{$op}->openid_provider->authorization_endpoint;

    $authenticationRequest = new AuthenticationRequestCIE($config);
    $authenticationRequestURL = $authenticationRequest->getRedirectURL(
        $authorization_endpoint,
        $acr,
        $user_attributes,
        $code_verifier,
        $nonce,
        Util::base64UrlEncode(str_pad($req_id, 32))
    );

    // exec hooks pre_authorization_request
    $hooks_pre = $hooks->pre_authorization_request;
    if ($hooks_pre != null && is_array($hooks_pre)) {
        foreach ($hooks_pre as $hpreClass) {
            $hpre = new $hpreClass($config);
            $hpre->run(array(
                "authorization_endpoint" => $authorization_endpoint,
                "acr" => $acr,
                "user_attributes" => $user_attributes,
                "code_verifier" => $code_verifier,
                "nonce" => $nonce,
                "authentication_request_url" => $authenticationRequestURL
            ));
        }
    }

    $f3->reroute($authenticationRequestURL);
});

$f3->route('GET /oidc/rp/redirect', function ($f3) {
    $config = $f3->get("CONFIG");
    $op_metadata = $f3->get("OP_METADATA");
    $db = $f3->get("DB");
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
    $request = $db->getRequest($req_id);
    $op = $request['op_id'];
    $redirect_uri = $request['redirect_uri'];
    $state = $request['state'];
    $code_verifier = $request['code_verifier'];

    // TODO : federation
    $token_endpoint = $op_metadata->{$op}->openid_provider->token_endpoint;
    $userinfo_endpoint = $op_metadata->{$op}->openid_provider->userinfo_endpoint;

    try {
        $tokenRequest = new TokenRequest($config);
        $tokenResponse = $tokenRequest->send($token_endpoint, $code, $code_verifier);
        $access_token = $tokenResponse->access_token;

        $userinfoRequest = new UserinfoRequest($config, $op_metadata->{$op}->openid_provider);
        $userinfoResponse = $userinfoRequest->send($userinfo_endpoint, $access_token);

        $responseHandlerClass = $config->rp_response_handler;
        $responseHandler = new $responseHandlerClass($config);
        $responseHandler->sendResponse($redirect_uri, $userinfoResponse, $state);
    } catch (Exception $e) {
        $f3->error($e->getCode(), $e->getMessage());
    }
});



/*
TODO : /oidc/rp/logout
*/

$f3->run();
