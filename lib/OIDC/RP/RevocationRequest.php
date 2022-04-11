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
 *  Generates the Revocation Request
 *
 *  [Linee Guida OpenID Connect in SPID](https://www.agid.gov.it/sites/default/files/repository_files/linee_guida_openid_connect_in_spid.pdf)
 *
 */
class RevocationRequest
{
    /**
     *  creates a new RevocationRequest instance
     *
     * @param array $config base configuration
     * @throws Exception
     * @return RevocationRequest
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        $this->http_client = new Client([
            'allow_redirects' => true,
            'timeout' => 15,
            'debug' => false,
            'http_errors' => false
        ]);
    }

    /**
     *  send the revocation request
     *
     * @param string $revocation_endpoint revocation endpoint of the provider
     * @param string $token token to be revoked
     * @throws Exception
     * @return object response returned from revocation
     * @codeCoverageIgnore
     */
    public function send(string $revocation_endpoint, string $token)
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
            'token' => $token
        );

        $response = $this->http_client->post($revocation_endpoint, [ 'form_params' => $data ]);
        $code = $response->getStatusCode();
        if ($code != 200) {
            $reason = $response->getReasonPhrase();
            throw new \Exception($reason);
        }

        $this->response = json_decode((string) $response->getBody());

        // Check Response

        return $this->response;
    }

    /**
     *  retrieves the response returned from previous revocation request
     *
     * @throws Exception
     * @return object response returned from revocation
     */
    public function getResponse()
    {
        return $this->response;
    }
}
