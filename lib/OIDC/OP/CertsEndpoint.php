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

namespace SPID_CIE_OIDC_PHP\OIDC\OP;

use SPID_CIE_OIDC_PHP\Core\JWT;
use SPID_CIE_OIDC_PHP\OIDC\OP\Database;

/**
 *  Certs Endpoint
 *
 */
class CertsEndpoint
{
    public $name = "Certs Endpoint";

    /**
     *  creates a new CertsEndpoint instance
     *
     * @param array $config base configuration
     * @param Database $database database instance
     * @throws Exception
     * @return CertsEndpoint
     */
    public function __construct(array $config, Database $database)
    {
        $this->config = $config;
        $this->database = $database;
    }

    /**
     *  process a certs request
     *
     * @throws Exception
     */
    public function process()
    {

        try {
            $jwk_pem = $this->config['op_proxy_cert_public'];
            $jwk = JWT::getCertificateJWK($jwk_pem);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($jwk);
        // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            http_response_code(400);
            if (!$this->config['production']) {
                echo "ERROR: " . $e->getMessage();
                $this->database->log("CertsEndpoint", "CERTS_ERR", $e->getMessage());
            }
        }
        // @codeCoverageIgnoreEnd
    }
}
