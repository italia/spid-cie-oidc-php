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

namespace SPID_CIE_OIDC_PHP\OIDC\RP;

use SPID_CIE_OIDC_PHP\Core\JWT;
use SPID_CIE_OIDC_PHP\Core\Util;

/**
 *  Generates the Authentication Request
 *
 *  [Linee Guida OpenID Connect in SPID](https://www.agid.gov.it/sites/default/files/repository_files/linee_guida_openid_connect_in_spid.pdf)
 */
class AuthenticationRequest
{
    private $base;
    private $service;
    private $domain;
    private $config;
    private $hooks;
    
    /**
     *  creates a new AuthenticationRequest instance
     *
     * @param  string $base
     * @param  string $service
     * @param  string $domain
     * @param  array $config base configuration
     * @param  array $hooks  hooks defined list
     * @throws Exception
     * @return AuthenticationRequest
     */
    public function __construct(string $base, string $service, string $domain, array $config, array $hooks = null)
    {
        $this->base = $base;
        $this->service = $service;
        $this->domain = $domain;
        $this->config = $config;
        $this->hooks = $hooks;
    }

    /**
     *  creates the URL to OIDC Provider to which redirect the user
     *
     * @param  string   $op_issuer              id of the provider
     * @param  string   $authorization_endpoint autorization endpoint of the provider
     * @param  int[]    $acr                    array of int values of the acr params to send with the request
     * @param  string[] $user_attributes        array of string values of the user attributes to query with the request
     * @param  string   $code_verifier          value for PKCE code_verifier to send with the the request
     * @param  string   $nonce                  value for nonce to send with the request
     * @param  string   $state                  value for state to send with the request
     * @throws Exception
     * @return string URL of the authentication request
     */
    public function getRedirectURL(string $op_issuer, string $authorization_endpoint, array $acr, array $user_attributes, string $code_verifier, string $nonce, string $state)
    {
        $base = $this->base;
        $service = $this->service;
        $domain = $this->domain;

        $client_id = $this->config['client_id'];
 
        if (!empty($this->config['redirect_uri'])) {
            $redirect_uri = $this->config['redirect_uri'];
        } else {
            $redirect_uri = Util::stringEndsWith($base, '/') ? $base : $base . '/';
            if ($service != '') {
                $redirect_uri .= $service . '/';
            }
            $redirect_uri .= 'oidc/rp/' . $domain . '/redirect';
        }

        $response_type = 'code';
        $scope = $config['scope'] ?? 'openid';
        $code_challenge = Util::getCodeChallenge($code_verifier);
        $code_challenge_method = $config['code_challenge_method'] ?? 'S256';
        $prompt = $config['prompt'] ?? 'consent login';

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
        $acr_values = array_unique(array_merge($acr_values, array_diff($acr, [3, 2, 1])));

        $userinfo_claims = array();
        foreach ($user_attributes as $a) {
            //$userinfo_claims["https://attributes.spid.gov.it/" . $a] = null;  // TODO: check for spid compliance
            $userinfo_claims[$a] = array("essential" => true);
        }

        $claims = array(
            "id_token" => array(
                //"nbf" =>  array( "essential" => true ),   // TODO: check for spid compliance
                //"jti" =>  array( "essential" => true )    // TODO: check for spid compliance
                "family_name" =>  array( "essential" => true ),
                "given_name" =>  array( "essential" => true )
            ),
            "userinfo" => $userinfo_claims
        ); 

        $iat = strtotime("now");
        $exp = strtotime("+1 hour");
        $request = array(
            "iss" => $client_id,
            "scope" => $scope,
            "redirect_uri" => $redirect_uri,
            "response_type" => $response_type,
            "nonce" => $nonce,
            "state" => $state,
            "client_id" => $client_id,
            "acr_values" => implode(" ", $acr_values),
            "iat" => $iat,
            "exp" => $exp,
            "jti" => Util::uuidv4(),
            "aud" => array($op_issuer, $authorization_endpoint),
            "claims" => $claims,
            "prompt" => $prompt,
            "code_challenge" => $code_challenge,
            "code_challenge_method" => $code_challenge_method
        );

        $crt = $this->config['cert_public'];
        $crt_jwk = JWT::getCertificateJWK($crt);

        $header = array(
            "typ" => "entity-statement+jwt",
            "alg" => "RS256",
            "kid" => $crt_jwk['kid'],
            //"jwk" => $crt_jwk,
            //"x5c" => $crt_jwk['x5c']
        );

        $key = $this->config['cert_private'];
        $key_jwk = JWT::getKeyJWK($key);
        $signed_request = JWT::makeJWS($header, $request, $key_jwk);

        $authentication_request = $authorization_endpoint .
            "?client_id=" . urlencode($client_id) .
            "&response_type=" . urlencode($response_type) .
            "&scope=" . urlencode($scope) .
            "&code_challenge=" . urlencode($code_challenge) .
            "&code_challenge_method=" . urlencode($code_challenge_method) .
            //"&nonce=" . urlencode($nonce) .
            "&request=" . urlencode($signed_request);

        return $authentication_request;
    }

    /**
     *  redirect the browser with the authentication request to the URL to OIDC Provider
     *
     * @param              string   $op_issuer              id of the provider
     * @param              string   $authorization_endpoint autorization endpoint of the provider
     * @param              int[]    $acr                    array of int values of the acr params to send with the request
     * @param              string[] $user_attributes        array of string values of the user attributes to query with the request
     * @param              string   $code_verifier          value for PKCE code_verifier to send with the the request
     * @param              string   $nonce                  value for nonce to send with the request
     * @param              string   $state                  value for state to send with the request
     * @throws             Exception
     * @codeCoverageIgnore
     */
    public function send(string $op_issuer, string $authorization_endpoint, array $acr, array $user_attributes, string $code_verifier, string $nonce, string $state)
    {
        $authenticationRequestURL = $this->getRedirectURL($op_issuer, $authorization_endpoint, $acr, $user_attributes, $code_verifier, $nonce, $state);
        $this->sendURL($authenticationRequestURL);
    }

    public function sendURL(string $authenticationRequestURL)
    {
        // HOOK: pre_authorization_request
        if ($this->hooks != null) {
            $hooks_pre = $this->hooks['pre_authorization_request'];
            if ($hooks_pre != null && is_array($hooks_pre)) {
                foreach ($hooks_pre as $hpreClass) {
                    $hpre = new $hpreClass($config);
                    $hpre->run(
                        array(
                        "authorization_endpoint" => $authorization_endpoint,
                        "acr" => $acr,
                        "user_attributes" => $user_attributes,
                        "code_verifier" => $code_verifier,
                        "nonce" => $nonce,
                        "authentication_request_url" => $authenticationRequestURL
                        )
                    );
                }
            }
        }

        header('Location: ' . $authenticationRequestURL);
    }
}
