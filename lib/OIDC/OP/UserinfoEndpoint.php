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

use SPID_CIE_OIDC_PHP\OIDC\OP\Database;

/**
 *  Userinfo Endpoint
 *
 */
class UserinfoEndpoint
{
    /**
     *  creates a new UserinfoEndpoint instance
     *
     * @param array $config base configuration
     * @param Database $database database instance
     * @throws Exception
     * @return UserinfoEndpoint
     */
    public function __construct(array $config, Database $database)
    {
        $this->config = $config;
        $this->database = $database;
    }

    /**
     *  process a userinfo request
     *
     * @throws Exception
     */
    public function process()
    {
        try {
            $bearer = $this->getBearerToken();
            if ($bearer == null || $bearer == '') {
                throw new \Exception('access_denied');
            }
            $this->database->log("UserinfoEndpoint", "USERINFO", "Bearer: " . $bearer);
            $userinfo = (array) $this->database->getUserinfo($bearer);
            $userinfo['sub'] = $userinfo['fiscalNumber'];
            $this->database->log("UserinfoEndpoint", "USERINFO", $userinfo);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($userinfo);
        } catch (\Exception $e) {
            http_response_code(400);
            if (!$this->config['production']) {
                echo "ERROR: " . $e->getMessage();
                $this->database->log("UserinfoEndpoint", "USERINFO_ERR", $e->getMessage());
            }
        }
    }

    /**
     * Get hearder Authorization
     * @codeCoverageIgnore
     */
    private function getAuthorizationHeader()
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) { // php builtin server
            $headers = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            //print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        $this->database->log("UserinfoEndpoint", "HEADERS", $headers);
        return $headers;
    }

    /**
     * get access token from header
     * @codeCoverageIgnore
     */
    private function getBearerToken()
    {
        $headers = $this->getAuthorizationHeader();
        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                $this->database->log("UserinfoEndpoint", "BEARER", $matches);
                return $matches[1];
            }
        }
        return null;
    }
}
