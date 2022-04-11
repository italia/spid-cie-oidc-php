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
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer as JWSSerializer;
use Jose\Component\Signature\Algorithm\RS256;

const DEFAULT_TOKEN_EXPIRATION_TIME = 1200;



/**
 *  Token Endpoint
 *
 */
class TokenEndpoint
{
    /**
     *  creates a new TokenEndpoint instance
     *
     * @param array $config base configuration
     * @param Database $database database instance
     * @throws Exception
     * @return TokenEndpoint
     */
    public function __construct(array $config, Database $database)
    {
        $this->config = $config;
        $this->database = $database;
    }

    /**
     *  process a token request
     *
     * @param array $_POST containing the request parameters
     * @throws Exception
     */
    public function process()
    {
        $clients        = $this->config['op_proxy_clients'];
        $code           = $_POST['code'];
        $scope          = $_POST['scope'];
        $grant_type     = $_POST['grant_type'];
        $client_id      = $_POST['client_id'];
        $client_secret  = $_POST['client_secret'];
        $redirect_uri   = $_POST['redirect_uri'];
        $state          = $_POST['state'];

        try {
            $credential = $this->getBasicAuthCredential();
            if ($credential != false && is_array($credential)) {
                // @codeCoverageIgnoreStart
                $this->database->log("TokenEndpoint", "TOKEN Credential", $credential);
                $username = $credential['username'];
                $password = $credential['password'];

                $auth_method = $clients[$username]['token_endpoint_auth_method'];
                $this->database->log("TokenEndpoint", "TOKEN configured auth_method", $auth_method);
                switch ($auth_method) {
                    case 'client_secret_post':
                        // already have client_id and client_secret
                        break;
                    case 'client_secret_basic':
                    default:
                        $client_id = $username;
                        $client_secret = $password;
                        break;
                }
                // @codeCoverageIgnoreEnd
            }
            $this->database->log("TokenEndpoint", "TOKEN REQUEST CREDENTIAL", array(
                "client_id" => $client_id,
                "client_secret" =>  $client_secret
            ));

            $this->database->log("TokenEndpoint", "TOKEN REQUEST", $_POST);

            if (!str_contains($scope, 'openid')) {
                throw new \Exception('invalid_scope');
            }
            if (!str_contains($scope, 'profile')) {
                throw new \Exception('invalid_scope');
            }
            if ($grant_type != 'authorization_code') {
                throw new \Exception('invalid_request');
            }
            if (!in_array($client_id, array_keys($clients))) {
                throw new \Exception('invalid_client');
            }
            if (!in_array($redirect_uri, $clients[$client_id]['redirect_uri'])) {
                throw new \Exception('invalid_redirect_uri');
            }
            if (!isset($code) || !$this->database->checkAuthorizationCode($client_id, $redirect_uri, $code)) {
                throw new \Exception('invalid_code');
            }

            $access_token = $this->database->createAccessToken($code);
            $userinfo = (array) $this->database->getUserinfo($access_token);
            $request = $this->database->getRequestByCode($code);

            $subject = $userinfo['fiscalNumber'];
            $exp_time = 1800;
            $iss = $this->config['rp_proxy_clients'][$client_id]['client_id'];
            $aud = $client_id;
            $jwk_pem = $this->config['rp_proxy_clients'][$client_id]['cert_private'];
            $nonce = $request['nonce'];

            $id_token = $this->makeIdToken($subject, $exp_time, $iss, $aud, $nonce, $jwk_pem);

            $this->database->saveIdToken($request['req_id'], $id_token);

            $this->database->log("TokenEndpoint", "ID_TOKEN", $id_token);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array(
                "access_token" => $access_token,
                "token_type" => "Bearer",
                "expires_in" => 1800,
                "id_token" => $id_token
            ));
        } catch (\Exception $e) {
            // API /token error
            http_response_code(400);
            if (!$this->config['production']) {
                echo "ERROR: " . $e->getMessage();
                $this->database->log("TokenEndpoint", "TOKEN_ERR", $e->getMessage());
            }
        }
    }


    /**
     * Get username e password of Basic Authentication
     * @codeCoverageIgnore
     */
    private function getBasicAuthCredential()
    {
        $credential = false;
        $authHeader = $this->getAuthorizationHeader();
        $this->database->log("TokenEndpoint", "TOKEN BASIC AUTH", $authHeader);
        if (substr($authHeader, 0, 5) == 'Basic') {
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
     * @codeCoverageIgnore
     */
    private function getAuthorizationHeader()
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) { // Nginx or fast CGI
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


        return $headers;
    }


    /**
     * Make ID Token
     * @codeCoverageIgnore
     */
    private function makeIdToken(string $subject, string $exp_time, string $iss, string $aud, string $nonce, string $jwk_pem): string
    {

        $iat        = new \DateTimeImmutable();
        $exp_time   = $exp_time ?: DEFAULT_TOKEN_EXPIRATION_TIME;
        $exp        = $iat->modify("+" . $exp_time . " seconds")->getTimestamp();

        $data = [
            'iss'  => $iss,                                     // Issuer - spDomain
            'aud'  => $aud,                                     // Audience - Redirect_uri
            'iat'  => $iat->getTimestamp(),                     // Issued at: time when the token was generated
            'nbf'  => $iat->getTimestamp(),                     // Not before
            'exp'  => $exp,                                     // Expire
            'sub'  => $subject,                                 // Subject Data
            'nonce' => $nonce,
        ];

        $algorithmManager = new AlgorithmManager([new RS256()]);
        $jwk = JWT::getKeyJWK($jwk_pem);
        $jwsBuilder = new JWSBuilder($algorithmManager);
        $jws = $jwsBuilder
            ->create()
            ->withPayload(json_encode($data))
            ->addSignature($jwk, ['alg' => 'RS256'])
            ->build();

        $serializer = new JWSSerializer();
        $token = $serializer->serialize($jws, 0);

        return $token;
    }
}
