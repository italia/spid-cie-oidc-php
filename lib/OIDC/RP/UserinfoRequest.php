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
 *  Generates the Userinfo Request
 *
 *  [Linee Guida OpenID Connect in SPID](https://www.agid.gov.it/sites/default/files/repository_files/linee_guida_openid_connect_in_spid.pdf)
 *
 */
class UserinfoRequest
{
    /**
     *  creates a new UserinfoRequest instance
     *
     * @param array $config base configuration
     * @param object $op_metadata provider metadata
     * @param array $hooks hooks defined list
     * @throws Exception
     * @return UserinfoRequest
     */
    public function __construct(array $config, object $op_metadata, array $hooks = null)
    {
        $this->config = $config;
        $this->op_metadata = $op_metadata;
        $this->hooks = $hooks;

        $this->http_client = new Client([
            'allow_redirects' => true,
            'timeout' => 15,
            'debug' => false,
            'http_errors' => false
        ]);
    }

    /**
     *  send the userinfo request
     *
     * @param string $userinfo_endpoint userinfo endpoint of the provider
     * @param string $access_token access_token needed to access to userinfo endpoint
     * @throws Exception
     * @return object response returned from userinfo endpoint
     * @codeCoverageIgnore
     */
    public function send(string $userinfo_endpoint, string $access_token)
    {
        // HOOK: pre_userinfo_request
        // @codeCoverageIgnoreStart
        if ($this->hooks != null) {
            $hooks_pre = $this->hooks['pre_userinfo_request'];
            if ($hooks_pre != null && is_array($hooks_pre)) {
                foreach ($hooks_pre as $hpreClass) {
                    $hpre = new $hpreClass($config);
                    $hpre->run(array(
                        "userinfo_endpoint" => $userinfo_endpoint,
                        "access_token" => $access_token
                    ));
                }
            }
        }
        // @codeCoverageIgnoreEnd

        $response = $this->http_client->get($userinfo_endpoint, ['headers' => [ 'Authorization' => 'Bearer ' . $access_token ]]);

        $code = $response->getStatusCode();
        if ($code != 200) {
            $reason = $response->getReasonPhrase();
            throw new \Exception($reason);
        }

        $jwe = $response->getBody()->getContents();

        // HOOK: post_userinfo_request
        // @codeCoverageIgnoreStart
        if ($this->hooks != null) {
            $hooks_pre = $this->hooks['post_userinfo_request'];
            if ($hooks_pre != null && is_array($hooks_pre)) {
                foreach ($hooks_pre as $hpreClass) {
                    $hpre = new $hpreClass($config);
                    $hpre->run(array(
                        "response" => $jwe
                    ));
                }
            }
        }
        // @codeCoverageIgnoreEnd

        $file_key = $this->config['cert_private'];
        $jws = JWT::decryptJWE($jwe, $file_key);

        $file_cert = $this->config['cert_public'];
        $decrypted = $jws->getPayload();
        $decrypted = str_replace("\"", "", $decrypted);

        // TODO: verify response against OP public key
        // TODO: select key by kid
        $jwks = $this->op_metadata->jwks;
        if (!JWT::isSignatureVerified($decrypted, $jwks)) {
            throw new \Exception("Impossibile stabilire l'autenticità della risposta");
        }

        $payload = JWT::getJWSPayload($decrypted);

        return $payload;
    }
}
