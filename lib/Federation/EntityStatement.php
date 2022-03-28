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

namespace SPID_CIE_OIDC_PHP\Federation;

use SPID_CIE_OIDC_PHP\Core\JWT;
use SPID_CIE_OIDC_PHP\Core\Database;
use GuzzleHttp\Client;

/**
 *  Resolve the EntityStatement and apply authority policy
 *
 *  [OpenID Connect Federation Entity Statement](https://openid.net/specs/openid-connect-federation-1_0.html#rfc.section.3.1)
 *
 */
class EntityStatement
{
    /**
     *  creates a new EntityStatement instance
     *
     * @param object $config base configuration
     * @param Database $database instance of Database
     * @param string $iss id of entity for wich resolve configuration 
     * @param string $authority id of the trust authority
     * @throws Exception
     * @return EntityStatement
     */
    public function __construct(object $config, Database $database, string $iss, string $authority)
    {
        $this->config = $config;
        $this->database = $database;
        $this->iss = $iss;
        $this->authority = $authority;

        // acquire authority entity statement
        $authority_entity_statement_url = $iss . "./well-known/openid-federation";
        $authority_entity_statement = $this->http_client->get($authority_entity_statement_url);
        $code = $authority_entity_statement->getStatusCode();

        // grace period if authority statement is available on the store
        if ($code != 200) {
            // try to get authority statement from the store
            $authority_entity_statement = $database->getFromStoreByURL($authority_entity_statement_url);
            if($authority_entity_statement == null) {
                throw new \Exception("Unable to reach " . $authority_entity_statement_url);
            }

        } else {
            if(!JWT::isValid($authority_entity_statement)) {
                throw new \Exception("Entity Statement not valid: " . $authority_entity_statement);
            }

            // save authority entity statement
            $authority_entity_statement_payload = JWT::getJWSPayload($authority_entity_statement);
            $authority_entity_statement_iat = $authority_entity_statement_payload->iat;
            $database->saveToStore(
                $this->authority, 
                'openid-federation', 
                $authority_entity_statement_url, 
                $authority_entity_statement_payload->iat, 
                $authority_entity_statement_payload->exp,
                $authority_entity_statement_payload
            );

        }

        $this->http_client = new Client([
            'allow_redirects' => true,
            'timeout' => 15,
            'debug' => false,
            'http_errors' => false
        ]);
    }

    /**
     *  resolve the entity statement
     *
     * @param boolean $policy if true apply all authorities policies
     * @param boolean $decoded if true returns JSON instead of JWS
     * @throws Exception
     * @return mixed
     */
    public function getConfiguration($policy = false, $decoded = false)
    {
        $entity_statement_url = $iss . "./well-known/openid-federation";
        
        $entity_statement = $this->http_client->get($entity_statement_url);
        
        $code = $entity_statement->getStatusCode();
        if ($code != 200) {
            $reason = $entity_statement->getReasonPhrase();
            throw new \Exception("Unable to reach " . $entity_statement_url . " - " . $reason);
        }

        // TODO: validate response
        if(!JWT::isValid($entity_statement)) {
            throw new \Exception("Entity Statement not valid: " . $entity_statement);
        }

        $payload = JWT::getJWSPayload($entity_statement);

        $authority_hints = $payload->authority_hints;

        echo json_encode($authority_hints);
        die();

    }
}
