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
 
namespace SPID_CIE_OIDC_PHP\OIDC;

use SPID_CIE_OIDC_PHP\Core\JWT;
use GuzzleHttp\Client;

class UserinfoRequest
{
    public function __construct($config, $op_metadata)
    {
        $this->config = $config;
        $this->op_metadata = $op_metadata;
    }

    public function send($userinfo_endpoint, $access_token)
    {
        $client = new Client([
            'allow_redirects' => true,
            'timeout' => 15,
            'debug' => false,
            'http_errors' => false
        ]);

        $response = $client->get($userinfo_endpoint, ['headers' => [ 'Authorization' => 'Bearer ' . $access_token ]]);
        $code = $response->getStatusCode();
        if ($code != 200) {
            $reason = $response->getReasonPhrase();
            throw new \Exception($reason);
        }

        $jwe = $response->getBody()->getContents();

        $file_key = $this->config->rp_cert_private;
        $jws = JWT::decryptJWE($jwe, $file_key);

        $file_cert = $this->config->rp_cert_public;
        $decrypted = $jws->getPayload();
        $decrypted = str_replace("\"", "", $decrypted);

        // verify response against OP public key
        // TODO : select key by kid
        $key = $this->op_metadata->jwks->keys[0];
        $jwk = JWT::getJWKFromJSON(json_encode($key));
        if (!JWT::isVerified($decrypted, $jwk)) {
            throw new \Exception("Impossibile stabilire l'autenticità della risposta");
        }

        $payload = JWT::getJWSPayload($decrypted);

        return json_decode($payload);
    }
}
