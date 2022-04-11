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
 *  Session End Endpoint
 *
 */
class SessionEndEndpoint
{
    /**
     *  creates a new SessionEndEndpoint instance
     *
     * @param array $config base configuration
     * @param Database $database database instance
     * @throws Exception
     * @return SessionEndEndpoint
     */
    public function __construct(array $config, Database $database)
    {
        $this->config = $config;
        $this->database = $database;
    }

    /**
     *  process a session end request
     *
     * @param object $_GET containing the request parameters
     * @throws Exception
     */
    public function process()
    {
        $id_token_hint = $_GET['id_token_hint'];
        $post_logout_redirect_uri = $_GET['post_logout_redirect_uri'];

        if ($id_token_hint) {
            // @codeCoverageIgnoreStart
            if ($this->database->checkIdToken($id_token_hint)) {
                $request = $this->database->getRequestByIdToken($id_token_hint);
                $this->database->deleteRequest($request['req_id']);
            } else {
                http_response_code(400);
                if (!$this->config['production']) {
                    echo "ERROR: id_token not valid";
                    $this->database->log("SessionEndEndpoint", "SESSION_END_ERR", "id_token not valid");
                }
            }
            // @codeCoverageIgnoreEnd
        } else {
            $client_id = null;
            $clients = $this->config['op_proxy_clients'];
            foreach ($clients as $id => $client_config) {
                if (in_array($post_logout_redirect_uri, $client_config['post_logout_redirect_uri'])) {
                    $client_id = $id;
                    break;
                }
            }

            if ($client_id != null) {
                $request = $this->database->getRequestByClientID($client_id);
                foreach ($request as $r) {
                    $req_id = $r['req_id'];
                    $this->database->deleteRequest($req_id);
                }
            } else {
                // @codeCoverageIgnoreStart
                http_response_code(400);
                if (!$this->config['production']) {
                    echo "ERROR: client_id not found for post_logout_redirect_uri";
                    $this->database->log("SessionEndEndpoint", "SESSION_END_ERR", "client_id not found for post_logout_redirect_uri");
                }
                // @codeCoverageIgnoreEnd
            }
        }

        $this->database->log("SessionEndEndpoint", "SESSION END", $post_logout_redirect_uri);

        $logout_url = '/';
        if ($this->config['service_name'] != '') {
            // @codeCoverageIgnoreStart
            $logout_url = '/' . $this->config['service_name'] . '/';
            // @codeCoverageIgnoreEnd
        }

        $logout_url .= 'oidc/rp/' . $request['client_id'] . '/logout?post_logout_redirect_uri=' . $post_logout_redirect_uri;

        // @codeCoverageIgnoreStart
        header('Location: ' . $logout_url);
        // @codeCoverageIgnoreEnd
    }
}
