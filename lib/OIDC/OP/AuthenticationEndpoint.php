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
 *  Authentication Endpoint
 *
 */
class AuthenticationEndpoint
{
    public $name = "Authentication Endpoint";

    /**
     *  creates a new AuthenticationEndpoint instance
     *
     * @param object $config base configuration
     * @param Database $database database instance
     * @throws Exception
     * @return AuthenticationEndpoint
     */
    public function __construct(object $config, Database $database)
    {
        $this->config = $config;
        $this->database = $database;
    }

    /**
     *  process an authentication request
     *
     * @param object $request object containing the request parameters
     * @throws Exception
     */
    public function process(object $request)
    {

        $clients        = $this->config->clients;
        $scope          = $request->scope;
        $response_type  = $request->response_type;
        $client_id      = $request->client_id;
        $redirect_uri   = $request->redirect_uri;
        $state          = $request->state;
        $nonce          = $request->nonce;

        $this->database->log("AuthenticationEndpoint", "AUTH", var_export($_GET, true));

        try {
            if (strpos($scope, 'openid') < 0) {
                throw new Exception('invalid_scope');
            }
            if (strpos($scope, 'profile') < 0) {
                throw new Exception('invalid_scope');
            }
            if ($response_type != 'code') {
                throw new Exception('invalid_request');
            }
            if (!in_array($client_id, array_keys($clients))) {
                throw new Exception('invalid_client');
            }
            if (!in_array($redirect_uri, $clients[$client_id]['redirect_uri'])) {
                throw new Exception('invalid_redirect_uri');
            }

            $req_id = $this->database->updateRequest($client_id, $redirect_uri, $state, $nonce);
            if ($req_id == null) {
                $req_id = $this->database->createRequest($client_id, $redirect_uri, $state, $nonce);
            }

            $url = $this->config['spid-php-proxy']['login_url']
            . '?client_id=' . $this->config['spid-php-proxy']['client_id']
            . '&level=' . $this->config['clients'][$client_id]['level']
            . '&redirect_uri=' . $this->config['spid-php-proxy']['redirect_uri']
            . '&state=' . base64_encode($req_id);

            header('Location: ' . $url);
        } catch (Exception $e) {
            if ($this->config['debug'] || $e->getMessage() == 'invalid_redirect_uri') {
                http_response_code(400);
                echo "ERROR: " . $e->getMessage();
            } else {
                $return = $redirect_uri;
                if (strpos($return, '?') > -1) {
                    $return .= '&error=' . $e->getMessage();
                } else {
                    $return .= '?error=' . $e->getMessage();
                }
                $return .= '&error_description=' . $e->getMessage();
                $return .= '&state=' . $state;
                header('Location: ' . $return);
            }
        }
    }

    /**
     *  receive and process an authentication response
     *
     * @throws Exception
     */
    public function callback()
    {
        $referer = $_SERVER['HTTP_REFERER'];
        $origin = $this->config['spid-php-proxy']['origin'];

        if ((substr($referer, 0, strlen($origin)) === $origin)) {
            $req_id         = base64_decode($_POST['state']);
            $auth_code      = $this->database->createAuthorizationCode($req_id);
            $request        = $this->database->getRequest($req_id);
            $client_id      = $request['client_id'];
            $redirect_uri   = $request['redirect_uri'];
            $state          = $request['state'];
            $userinfo       = $_POST;

            unset($userinfo['state']);
            $this->database->saveUserinfo($req_id, $userinfo);

            $return = $redirect_uri;
            if (strpos($redirect_uri, '?') > -1) {
                $return .= '&code=' . $auth_code;
            } else {
                $return .= '?code=' . $auth_code;
            }
            $return .= '&state=' . $state;

            header("Location: " . $return);
        } else {
            if ($this->config['debug']) {
                echo "Invalid origin: " . $origin;
            }
            http_response_code(404);
        }
    }
}