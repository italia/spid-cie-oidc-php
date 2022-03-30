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
 *  Token Endpoint
 *
 */
class TokenEndpoint {

    public $name = "Token Endpoint";

    /**
     *  creates a new TokenEndpoint instance
     *
     * @param object $config base configuration
     * @param Database $database database instance
     * @throws Exception
     * @return TokenEndpoint
     */
    public function __construct(object $config, Database $database) {
        $this->config = $config;
        $this->database = $database;
    }

    /**
     *  process a token request
     *
     * @param object $request object containing the request parameters
     * @throws Exception
     */
    public function process(object $request) {
        $clients        = $this->config['clients'];
        $code           = $request->code;
        $scope          = $request->scope;
        $grant_type     = $request->grant_type;
        $client_id      = $request->client_id;
        $client_secret  = $request->client_secret;
        $redirect_uri   = $request->redirect_uri;
        $state          = $request->state;

        try {
            $credential = $this->getBasicAuthCredential();
            if($credential!=false && is_array($credential)) {
                $this->database->log("TokenEndpoint", "TOKEN Credential", var_export($credential, true));
                $username = $credential['username'];
                $password = $credential['password'];

                $auth_method = $clients[$username]['token_endpoint_auth_method'];
                $this->database->log("TokenEndpoint", "TOKEN configured auth_method", var_export($auth_method, true));
                switch($auth_method) { 
                    case 'client_secret_post':
                        // already have client_id and client_secret
                        break;
                    case 'client_secret_basic':
                    default:
                        $client_id = $username;
                        $client_secret = $password;
                        break;
                }
            }
        
            $this->database->log("TokenEndpoint", "TOKEN", var_export($_POST, true));
    
            if(strpos($scope, 'openid')<0) throw new Exception('invalid_scope');
            if(strpos($scope, 'profile')<0) throw new Exception('invalid_scope');
            if($grant_type!='authorization_code') throw new Exception('invalid_request');
            if(!in_array($client_id, array_keys($clients))) throw new Exception('invalid_client');
            if(!in_array($redirect_uri, $clients[$client_id]['redirect_uri'])) throw new Exception('invalid_redirect_uri');
            if(!$this->database->checkAuthorizationCode($client_id, $redirect_uri, $code)) throw new Exception('invalid_code');
    
            $access_token = $this->database->createAccessToken($code);
            $userinfo = (array) $this->database->getUserinfo($access_token);
            $request = $this->database->getRequestByCode($code);
    
            $subject = $userinfo['fiscalNumber'];
            $exp_time = 1800;
            $iss = $this->config['spid-php-proxy']['origin'];
            $aud = $client_id;
            $jwk_pem = $this->config['jwt_private_key'];
            $nonce = $request['nonce'];
            
            $id_token = JWT::makeIdToken($subject, $exp_time, $iss, $aud, $nonce, $jwk_pem);
            
            $this->database->saveIdToken($request['req_id'], $id_token);
    
            $this->database->log("TokenEndpoint", "ID_TOKEN", $id_token);
    
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array(
                "access_token" => $access_token,
                "token_type" => "Bearer",
                "expires_in" => 1800,
                "id_token" => $id_token
            ));
    
        } catch(Exception $e) {
            http_response_code(400);
            if($this->config['debug']) {
                echo "ERROR: ".$e->getMessage();
                $this->database->log("TokenEndpoint", "TOKEN_ERR", $e->getMessage());
            } 
        }
    }


    /**
     * Get username e password of Basic Authentication
     */
    private function getBasicAuthCredential() {
        $credential = false;
        $authHeader = $this->getAuthorizationHeader();
        $this->database->log("TokenEndpoint", "TOKEN BASIC AUTH", var_export($authHeader, true));
        if(substr($authHeader, 0, 5)=='Basic') {
            $creds = base64_decode(substr($authHeader, 6));
            $creds_array = explode(":", $creds);
            $credential = array(
                'username' => $creds_array[0],
                'password' => $creds_array[1]
            );
        } 
        
        return $credential;
    }
    
    /** 
     * Get header Authorization
     * */
    private function getAuthorizationHeader() {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        }
        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            //print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }
}