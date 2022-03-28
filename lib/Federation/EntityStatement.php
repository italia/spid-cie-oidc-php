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

use SPID_CIE_OIDC_PHP\Core\Util;
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
     * @param string $entity id of entity for wich resolve configuration 
     * @param string $trust_anchor id of the trust anchor authority
     * @throws Exception
     * @return EntityStatement
     */
    public function __construct(object $config, Database $database, string $entity, string $trust_anchor)
    {
        $this->config = $config;
        $this->database = $database;
        $this->entity = $entity;
        $this->trust_anchor = $trust_anchor;

        $this->http_client = new Client([
            'allow_redirects' => true,
            'timeout' => 15,
            'debug' => false,
            'http_errors' => false
        ]);
    }

    /**
     *  resolve the entity statement recursively
     *
     * @param boolean $policy if true apply all authorities policies
     * @param boolean $decoded if true returns JSON instead of JWS
     * @throws Exception
     * @return mixed
     */
    public function resolve($policy = false)
    {
        // acquire entity statement
        $entity_statement_url = Util::stringEndsWith($this->entity, '/')? $this->entity : $this->entity.'/';
        $entity_statement_url .= ".well-known/openid-federation";

        $response = $this->http_client->get($entity_statement_url);
        $code = $response->getStatusCode();

        // grace period if statement is available on the store
        if ($code != 200) {
            $reason = $response->getReasonPhrase();

            // try to get entity statement from the store
            $entity_statement = $this->database->getFromStoreByURL($entity_statement_url);
            if($entity_statement == null) {   
                throw new \Exception("Unable to reach " . $entity_statement_url . " - " . $reason);
            }

        } else {
            $entity_statement = (string) $response->getBody();
        }

        // validate response
        if(!JWT::isValid($entity_statement)) {
            throw new \Exception("Entity Statement not valid: " . $entity_statement);
        }

        // save entity statement to store
        $entity_statement_payload = JWT::getJWSPayload($entity_statement);
        
        $this->database->saveToStore(
            $this->entity, 
            'openid-federation', 
            $entity_statement_url, 
            $entity_statement_payload->iat, 
            $entity_statement_payload->exp,
            $entity_statement_payload
        );

        $authority_hints = $entity_statement_payload->authority_hints;
        if(!in_array($this->authority, $authority_hints)) {
            throw new \Exception("Authority not hinted: " . $this->authority);
        }

        // follow entity statement untill authority_hints
        if($authority_hints==null ||
            (is_array($authority_hints) && count($authority_hints==0))
        ) {
            foreach($authority_hints as $authority) {
                $parent_entity_statement = new EntityStatement($this->config, $this->database, $authority, $this->trust_anchor);
                $resolved_entity_statement = $parent_entity_statement->resolve($policy);
            }

        } else {
            // trust anchor
            
        }
        

    }


    /**
     *  get the configuration
     *
     * @param boolean $decoded if true returns JSON instead of JWS
     * @throws Exception
     * @return mixed
     */
    public function getConfiguration($decoded = false)
    {

    }


}
