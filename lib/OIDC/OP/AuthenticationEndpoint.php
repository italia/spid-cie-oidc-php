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
    /**
     *  creates a new AuthenticationEndpoint instance
     *
     * @param array $config base configuration
     * @param Database $database database instance
     * @throws Exception
     * @return AuthenticationEndpoint
     */
    public function __construct(array $config, Database $database)
    {
        $this->config = $config;
        $this->database = $database;
    }

    /**
     *  process an authentication request
     *
     * @param array $_GET containing the request parameters
     * @throws Exception
     */
    public function process()
    {

        $clients        = $this->config['op_proxy_clients'];
        $scope          = $_GET['scope'];
        $response_type  = $_GET['response_type'];
        $client_id      = $_GET['client_id'];
        $redirect_uri   = $_GET['redirect_uri'];
        $state          = $_GET['state'] ? $_GET['state'] : '';
        $nonce          = $_GET['nonce'] ? $_GET['nonce'] : '';

        $this->database->log("AuthenticationEndpoint", "AUTH", $_GET);

        try {
            if (!str_contains($scope, 'openid')) {
                throw new \Exception('invalid_scope');
            }
            if (!str_contains($scope, 'profile')) {
                throw new \Exception('invalid_scope');
            }
            if ($response_type != 'code') {
                throw new \Exception('invalid_request');
            }
            if (!in_array($client_id, array_keys($clients))) {
                throw new \Exception('invalid_client');
            }
            if (!in_array($redirect_uri, $clients[$client_id]['redirect_uri'])) {
                throw new \Exception('invalid_redirect_uri');
            }

            $req_id = $this->database->updateRequest($client_id, $redirect_uri, $state, $nonce);
            if ($req_id == null) {
                $req_id = $this->database->createRequest($client_id, $redirect_uri, $state, $nonce);
            }

            $url = '/';
            if ($this->config['service_name'] != '') {
                $url = '/' . $this->config['service_name'] . '/';
            }

            $url .= 'oidc/rp/' . $client_id . '/authz?state=' . base64_encode($req_id);
            header('Location: ' . $url);
        } catch (\Exception $e) {
            if (!$this->config['production'] || $e->getMessage() == 'invalid_redirect_uri') {
                throw $e;
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
     * @codeCoverageIgnore
     */
    public function callback()
    {
        if (
            isset($_POST) && (
            $_SERVER['HTTP_ORIGIN'] == 'https://' . $_SERVER['HTTP_HOST']
            || $_SERVER['HTTP_ORIGIN'] == 'http://' . $_SERVER['HTTP_HOST']
            )
        ) {
            $req_id         = base64_decode($_POST['state']);
            $auth_code      = $this->database->createAuthorizationCode($req_id);
            $request        = $this->database->getRequest($req_id);
            $client_id      = $request['client_id'];
            $redirect_uri   = $request['redirect_uri'];
            $state          = $request['state'];
            $userinfo       = $_POST;

            if ($redirect_uri == null || $client_id == null) {
                throw new \Exception("Session not found");
            }

            foreach ($userinfo as $claim => $value) {
                if (substr($claim, 0, 31) == 'https://attributes_spid_gov_it/') {
                    $c = substr($claim, 31);
                    $userinfo[$c] = $value;
                    unset($userinfo[$claim]);
                }
            }

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
            throw new \Exception("Invalid origin");
        }
    }
}
