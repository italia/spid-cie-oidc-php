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
use SPID_CIE_OIDC_PHP\OIDC\RP\Database;
use SPID_CIE_OIDC_PHP\Federation\EntityStatement;
use GuzzleHttp\Client;

/**
 *  Resolve the EntityStatement and apply authority policy
 *
 *  [OpenID Connect Federation Entity Statement](https://openid.net/specs/openid-connect-federation-1_0.html#rfc.section.3.1)
 *
 */
class TrustChain
{
    /**
     *  creates a new EntityStatement instance
     *
     * @param array $config base configuration
     * @param Database $database instance of Database
     * @param string $leaf id of leaf entity for wich resolve configuration
     * @param string $trust_anchor id of the trust anchor authority
     * @param string $entity id of the current entity node if it's intermediate
     * @throws Exception
     * @return EntityStatement
     */
    public function __construct(array $config, Database $database, string $leaf, string $trust_anchor, string $entity = null)
    {
        $this->config = $config;
        $this->database = $database;
        $this->leaf = $leaf;
        $this->trust_anchor = $trust_anchor;
        $this->entity = $entity != null ? $entity : $leaf;

        $this->leaf_entity_statement = null;
        $this->federation_entity_statement = null;

        $this->http_client = new Client([
            'allow_redirects' => true,
            'timeout' => 15,
            'debug' => false,
            'http_errors' => false
        ]);

        $this->database->log("TrustChain", "created", $this);
    }

    /**
     *  resolve the entity statement recursively
     *
     * @param boolean $apply_policy if true applies trust anchor authorities policies
     * @throws Exception
     * @return mixed
     * @codeCoverageIgnore
     */
    public function resolve($apply_policy = true)
    {
        // acquire entity statement
        $entity_statement_url = Util::stringEndsWith($this->entity, '/') ? $this->entity : $this->entity . '/';
        $entity_statement_url .= ".well-known/openid-federation";

        try {
            $response = $this->http_client->get($entity_statement_url);
            $code = $response->getStatusCode();
            $reason = $response->getReasonPhrase();
        } catch (\Exception $e) {
            $this->database->log("TrustChain", "error while connecting to " . $entity_statement_url, $e->getMessage(), "ERROR");
            $reason = "error while connecting to " . $entity_statement_url;
            $code = 500;
        }

        // grace period if statement is available on the store
        if ($code != 200) {
            $this->database->log("TrustChain", "openid-federation for " . $this->entity . " not found. Try to get it from store", $entity_statement_url, "WARNING");

            // try to get entity statement from the store
            $entity_statement_token = $this->database->getFromStoreByURL($entity_statement_url);
            if ($entity_statement_token == null) {
                $this->database->log("TrustChain", "openid-federation for " . $this->entity . " not found on store", $entity_statement_url, "WARNING");
                throw new \Exception("Unable to reach " . $entity_statement_url . " - " . $reason);
            }

            $this->database->log("TrustChain", "acquired from store openid-federation for " . $this->entity, $entity_statement_token);
        } else {
            $entity_statement_token = (string) $response->getBody();
            $this->database->log("TrustChain", "acquired openid-federation for " . $this->entity, $entity_statement_token);
        }

        // validate
        try {
            $this->leaf_entity_statement = new EntityStatement($entity_statement_token, $this->entity);
            $entity_statement_payload = $this->leaf_entity_statement->getPayload();
        } catch (\Exception $e) {
            $this->database->log("TrustChain", "entity statement for " . $this->entity . " not valid: " . $e->getMessage(), $entity_statement_token, "ERROR");
            throw new \Exception("Entity statement for " . $this->entity . " not valid: " . $e->getMessage());
        }

        // not save to store if in grace period
        if ($code == 200) {
            $this->database->saveToStore(
                $this->entity,
                'openid-federation',
                $entity_statement_url,
                $entity_statement_payload->iat,
                $entity_statement_payload->exp,
                $entity_statement
            );

            $this->database->log("TrustChain", "saved openid-federation for " . $this->entity . " to store", $entity_statement_payload);
        }

        $authority_hints = $entity_statement_payload->authority_hints;

        // follow entity statement untill authority_hints
        if (
            $authority_hints == null ||
            (is_array($authority_hints) && count($authority_hints) == 0)
        ) {
            // trust anchor
            $this->database->log("TrustChain", "found trust anchor for leaf " . $this->leaf, $this->entity);

            // get federation fetch endpoint
            $federation_fetch_endpoint = $entity_statement_payload->metadata->federation_entity->federation_fetch_endpoint;
            $federation_fetch_endpoint = Util::stringEndsWith($this->entity, '/') ? $federation_fetch_endpoint : $federation_fetch_endpoint . '/';

            $federation_fetch_url = $federation_fetch_endpoint . '?sub=' . $this->leaf;

            // fetch metadata from trust anchor
            $this->database->log("TrustChain", "fetch configuration for leaf " . $this->leaf, $federation_fetch_url);

            try {
                $response = $this->http_client->get($federation_fetch_url);
                $code = $response->getStatusCode();
                $reason = $response->getReasonPhrase();
            } catch (\Exception $e) {
                $code = 500;
                $reason = $e->getMessage();
            }

            if ($code != 200) {
                $this->database->log("TrustChain", "failed fetching configuration for " . $this->leaf, $reason, "ERROR");
                throw new \Exception("Unable to trust " . $this->leaf, $reason . " - " . $reason);
            }

            $federation_entity_statement_token = (string) $response->getBody();

            // validate
            try {
                $this->federation_entity_statement = new EntityStatement($federation_entity_statement_token, $this->trust_anchor);
                //$federation_entity_statement_payload = $this->federation_entity_statement->getPayload();
            } catch (\Exception $e) {
                $this->database->log("TrustChain", "federation entity statement for " . $this->leaf . " not valid: " . $e->getMessage(), $federation_entity_statement_token, "ERROR");
                throw new \Exception("Federation entity statement for " . $this->leaf . " not valid: " . $e->getMessage());
            }

            return $this->federation_entity_statement;
            die();
        } else {
            if (!in_array($this->trust_anchor, $authority_hints)) {
                throw new \Exception("Authority not hinted: " . $this->trust_anchor);
            }

            $this->database->log("TrustChain", "found intermediate for leaf " . $this->leaf, $this->entity);

            foreach ($authority_hints as $authority) {
                $this->database->log("TrustChain", "resolve trust for leaf " . $this->leaf . " on authority", $authority);

                $parent_entity_statement = new TrustChain($this->config, $this->database, $this->leaf, $this->trust_anchor, $authority);
                $this->federation_entity_statement = $parent_entity_statement->resolve($policy);
            }

            $this->database->log("TrustChain", "trust verified for leaf " . $this->leaf, $this->federation_entity_statement);
        }

        if ($apply_policy) {
            $this->leaf_entity_statement->applyPolicy($this->federation_entity_statement);
        }
        return $this->leaf_entity_statement->getPayload();
    }
}
