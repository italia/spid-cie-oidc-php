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

namespace SPID_CIE_OIDC_PHP\OIDC\RP;

use SPID_CIE_OIDC_PHP\Core\JWT;
use GuzzleHttp\Client;

/**
 *  Generates the Token Request
 *
 *  [Linee Guida OpenID Connect in SPID](https://www.agid.gov.it/sites/default/files/repository_files/linee_guida_openid_connect_in_spid.pdf)
 *
 */
class TokenRequest
{
    /**
     *  creates a new TokenRequest instance
     *
     * @param array $config base configuration
     * @param array $hooks hooks defined list
     * @throws Exception
     * @return TokenRequest
     */
    public function __construct(array $config, array $hooks = null)
    {
        $this->config = $config;
        $this->hooks = $hooks;

        $this->http_client = new Client([
            'allow_redirects' => true,
            'timeout' => 15,
            'debug' => false,
            'http_errors' => false
        ]);
    }

    /**
     *  send the token request
     *
     * @param string $token_endpoint token endpoint of the provider
     * @param string $auth_code value of authorization_code obtained from authentication request
     * @param string $code_verifier value of code_verifier whose related code_challenge was sent with authentication request
     * @param boolean $refresh if true send a token request with a refresh token for obtain a new access token
     * @param string $refresh_token value of refresh token
     * @throws Exception
     * @return object response returned from token request
     */
    public function send(string $token_endpoint, string $auth_code, string $code_verifier, $refresh = false, string $refresh_token = null)
    {
        $client_id = $this->config['client_id'];
        $client_assertion = array(
            "jti" => 'spid-cie-php-oidc_' . uniqid(),
            "iss" => $client_id,
            "sub" => $client_id,
            "aud" => $token_endpoint,
            "iat" => strtotime("now"),
            "exp" => strtotime("+180 seconds")
        );
        $client_assertion_type = "urn:ietf:params:oauth:client-assertion-type:jwt-bearer";
        $code = $auth_code;
        $grant_type = ($refresh && $refresh_token != null) ? 'refresh_token' : 'authorization_code';

        $crt = $this->config['cert_public'];
        $crt_jwk = JWT::getCertificateJWK($crt);

        $header = array(
            "typ" => "JWT",
            "alg" => "RS256",
            "jwk" => $crt_jwk,
            "kid" => $crt_jwk['kid'],
            "x5c" => $crt_jwk['x5c']
        );

        $key = $this->config['cert_private'];
        $key_jwk = JWT::getKeyJWK($key);

        $signed_client_assertion = JWT::makeJWS($header, $client_assertion, $key_jwk);

        $data = array(
            'client_id' => $client_id,
            'client_assertion' => $signed_client_assertion,
            'client_assertion_type' => $client_assertion_type,
            'code' => $code,
            'code_verifier' => $code_verifier,
            'grant_type' => $grant_type,
        );

        if ($refresh && $refresh_token != null) {
            $data['refresh_token'] = $refresh_token;
        }

        // HOOK: pre_token_request
        // @codeCoverageIgnoreStart
        if ($this->hooks != null) {
            $hooks_pre = $this->hooks['pre_token_request'];
            if ($hooks_pre != null && is_array($hooks_pre)) {
                foreach ($hooks_pre as $hpreClass) {
                    $hpre = new $hpreClass($config);
                    $hpre->run(array(
                        "token_endpoint" => $token_endpoint,
                        "post_data" => $data
                    ));
                }
            }
        }
        // @codeCoverageIgnoreEnd

        $response = $this->http_client->post($token_endpoint, [ 'form_params' => $data ]);

        // @codeCoverageIgnoreStart
        $code = $response->getStatusCode();
        if ($code != 200) {
            $reason = $response->getReasonPhrase();
            throw new \Exception($reason);
        }
        // @codeCoverageIgnoreEnd

        // HOOK: post_token_request
        // @codeCoverageIgnoreStart
        if ($this->hooks != null) {
            $hooks_pre = $this->hooks['post_token_request'];
            if ($hooks_pre != null && is_array($hooks_pre)) {
                foreach ($hooks_pre as $hpreClass) {
                    $hpre = new $hpreClass($config);
                    $hpre->run(array(
                        "token_endpoint" => $token_endpoint,
                        "response" => json_decode((string) $response->getBody())
                    ));
                }
            }
        }
        // @codeCoverageIgnoreEnd

        $this->response = json_decode((string) $response->getBody());

        // Check Response

        return $this->response;
    }

    /**
     *  retrieves the response returned from previous token request
     *
     * @throws Exception
     * @return object response returned from token request
     * @codeCoverageIgnore
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     *  retrieves the access_token returned from previous token request
     *
     * @throws Exception
     * @return string access_token returned from token request
     * @codeCoverageIgnore
     */
    public function getAccessToken()
    {
        $access_token = $this->response->access_token;
        return $access_token;
    }

    /**
     *  retrieves the id_token returned from previous token request
     *
     * @throws Exception
     * @return string id_token returned from token request
     * @codeCoverageIgnore
     */
    public function getIdToken()
    {
        $id_token = $this->response->id_token;
        return $id_token;
    }
}
