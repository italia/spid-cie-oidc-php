<?php

namespace SPID_CIE_OIDC_PHP\OIDC;

use SPID_CIE_OIDC_PHP\Core\JWT;
use SPID_CIE_OIDC_PHP\Core\Util;

class AuthenticationRequestCIE
{
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function getRedirectURL($authorization_endpoint, $acr, $attributes, $code_verifier, $nonce, $state)
    {
        $client_id = $this->config->rp_client_id;
        $redirect_uri = $client_id . '/oidc/rp/redirect';
        $response_type = 'code';
        $scope = 'openid';
        $code_challenge = Util::getCodeChallenge($code_verifier);
        $code_challenge_method = 'S256';
        $prompt = 'consent login';

        $acr_values = array();

        // order is important (Rif. LL.GG. OIDC SPID)
        if (in_array(3, $acr)) {
            $acr_values[] = "https://www.spid.gov.it/SpidL3";
        }
        if (in_array(2, $acr)) {
            $acr_values[] = "https://www.spid.gov.it/SpidL2";
        }
        if (in_array(1, $acr)) {
            $acr_values[] = "https://www.spid.gov.it/SpidL1";
        }

        $userinfo_claims = array();
        foreach ($attributes as $a) {
            $userinfo_claims["https://attributes.spid.gov.it/" . $a] = null;
        }

        $claims = array(
            "id_token" => array(
                "nbf" =>  array( "essential" => true ),
                "jti" =>  array( "essential" => true )
            ),
            "userinfo" => $userinfo_claims
        );

        $request = array(
            "jti" => 'spid-cie-php-oidc_' . uniqid(),
            "iss" => $client_id,
            "sub" => $client_id,
            "aud" => array($client_id),
            "iat" => strtotime("now"),
            "exp" => strtotime("+180 seconds"),
            "client_id" => $client_id,
            "response_type" => $response_type,
            "scope" => explode(" ", $scope),
            "code_challenge" => $code_challenge,
            "code_challenge_method" => $code_challenge_method,
            "nonce" => $nonce,
            "prompt" => $prompt,
            "redirect_uri" => $redirect_uri,
            "acr_values" => $acr_values,
            "claims" => $claims,
            "state" => $state
        );

        $crt = $this->config->rp_cert_public;
        $crt_jwk = JWT::getCertificateJWK($crt);

        $header = array(
            "typ" => "JWT",
            "alg" => "RS256",
            "jwk" => $crt_jwk,
            "kid" => $crt_jwk['kid'],
            "x5c" => $crt_jwk['x5c']
        );

        $key = $this->config->rp_cert_private;
        $key_jwk = JWT::getKeyJWK($key);
        $signed_request = JWT::makeJWS($header, $request, $key_jwk);

        $authentication_request = $authorization_endpoint .
            "?client_id=" . $client_id .
            "&response_type=" . $response_type .
            "&scope=" . $scope .
            "&code_challenge=" . $code_challenge .
            "&code_challenge_method=" . $code_challenge_method .
            "&nonce=" . $nonce .
            "&request=" . $signed_request;

        return $authentication_request;
    }
}
