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

/**
 *  Handle EntityStatement
 *
 *  [OpenID Connect Federation Entity Statement](https://openid.net/specs/openid-connect-federation-1_0.html#rfc.section.3.1)
 *
 */
class EntityStatement
{
    /**
     *  creates a new EntityStatement instance
     *
     * @param string $token entity statement JWS token
     * @param string $iss issuer
     * @throws Exception
     * @return EntityStatement
     */
    public function __construct($token = null, $iss = null)
    {
        if ($token != null) {
            $this->token = $token;
            $this->iss = $iss;
            $this->payload = JWT::getJWSPayload($this->token);
            $this->validate($token);
        }
    }

    /**
     *  creates the JWT to be returned from .well-known/openid-federation endpoint
     *
     * @param array $config base configuration
     * @param boolean $decoded if true returns JSON instead of JWS
     * @throws Exception
     * @return mixed
     * @codeCoverageIgnore
     */
    public static function makeFromConfig(array $config, $json = false)
    {
        $crt = $config['cert_public'];
        $crt_jwk = JWT::getCertificateJWK($crt);

        $payload = array(
            "iss" => $config['client_id'],
            "sub" => $config['client_id'],
            "iat" => strtotime("now"),
            "exp" => strtotime("+1 year"),
            "jwks" => array(
                "keys" => array( $crt_jwk )
            ),
            "authority_hints" => array(
                $config['authority_hint']
            ),
            "trust_marks" => array($config['trust_mark']),
            "metadata" => array(
                "openid_relying_party" => array(
                    "application_type" => "web",
                    "client_registration_types" => array( "automatic" ),
                    "client_name" => $config['client_name'],
                    "contacts" => array( $config['contact'] ),
                    "grant_types" => array( "authorization_code" ),
                    "jwks" => array(
                        "keys" => array( $crt_jwk )
                    ),
                    "redirect_uris" => array( $config['client_id'] . '/oidc/redirect' ),
                    "response_types" => array( "code" ),
                    "subject_type" => "pairwise"
                )
            )
        );

        $header = array(
            "typ" => "entity-statement+jwt",
            "alg" => "RS256",
            "kid" => $crt_jwk['kid']
        );

        $key = $config['cert_private'];
        $key_jwk = JWT::getKeyJWK($key);
        $jws = JWT::makeJWS($header, $payload, $key_jwk);

        return $json ? json_encode($payload) : $jws;
    }

    /**
     *  initialize the entity statement payload from object
     *
     * @param object $object the entity statement object
     * @throws Exception
     * @return EntityStatement
     */
    public function initFromObject(object $object)
    {
        // copy by value, not assign by ref
        $this->payload = json_decode(json_encode($object));
        return $this;
    }

    /**
     *  return entity statement payload
     *
     * @throws Exception
     * @return mixed the entity statement payload
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     *  validate token
     *
     * @param string $token entity statement JWS token
     * @throws Exception
     * @return mixed
     * @codeCoverageIgnore
     */
    public function validate()
    {
        $jwks = $this->payload->jwks;

        // verify signature
        /*
        if (!JWT::isSignatureVerified($this->token, $jwks)) {
            throw new \Exception("signature verification failed");
        }
        */

        // verify if token is not expired and valid
        if (!JWT::isValid($this->token)) {
            throw new \Exception("entity statement not valid");
        }

        // if issuer is correct
        /*
        if ($this->payload->iss != $this->iss) {
            throw new \Exception("issuer not valid");
        }
        */
    }

    /**
     *  apply policy from federation entity statement
     *
     * @param EntityStatement $federation_entity_statement the federation entity statement containing policy
     * @throws Exception
     * @return mixed
     * @codeCoverageIgnore
     */
    public function applyPolicy(EntityStatement $federation_entity_statement)
    {
        $payload = $federation_entity_statement->getPayload();
        $policy = $payload->metadata_policy;

        foreach ($policy as $entity_type => $entity_policy) {
            if ($this->payload->metadata->$entity_type != null) {
                foreach ($entity_policy as $policy_claim => $policy_rule) {
                    if ($this->payload->metadata->$entity_type->$policy_claim != null) {
                        foreach ($policy_rule as $policy_modifier => $policy_value) {
                            switch ($policy_modifier) {
                                case 'value':
                                    $this->applyPolicyModifierValue($entity_type, $policy_claim, $policy_value);
                                    break;
                                case 'add':
                                    $this->applyPolicyModifierAdd($entity_type, $policy_claim, $policy_value);
                                    break;
                                case 'default':
                                    $this->applyPolicyModifierDefault($entity_type, $policy_claim, $policy_value);
                                    break;
                                case 'one_of':
                                    $this->applyPolicyModifierOneOf($entity_type, $policy_claim, $policy_value);
                                    break;
                                case 'subset_of':
                                    $this->applyPolicyModifierSubsetOf($entity_type, $policy_claim, $policy_value);
                                    break;
                                case 'superset_of':
                                    $this->applyPolicyModifierSupersetOf($entity_type, $policy_claim, $policy_value);
                                    break;
                                case 'essential':
                                    $this->applyPolicyModifierEssential($entity_type, $policy_claim, $policy_value);
                                    break;
                            }
                        }
                    }
                }
            }
        }
    }


    private function applyPolicyModifierValue($entity_type, $claim, $policy)
    {
        $this->payload->metadata->$entity_type->$claim = $policy;
    }

    private function applyPolicyModifierAdd($entity_type, $claim, $policy)
    {
        $claim_val = $this->payload->metadata->$entity_type->$claim;
        if (!is_array($policy)) {
            $policy = [$policy];
        }
        foreach ($policy as $p) {
            if (is_array($claim_val) && !in_array($p, $claim_val)) {
                $this->payload->metadata->$entity_type->$claim[] = $p;
            } else {
                $this->payload->metadata->$entity_type->$claim = $p;
            }
        }
    }

    private function applyPolicyModifierDefault($entity_type, $claim, $policy)
    {
        $claim_val = $this->payload->metadata->$entity_type->$claim;
        if ($claim_val == null || $claim_val == '' || $claim_val == array()) {
            $this->payload->metadata->$entity_type->$claim = $policy;
        }
    }

    private function applyPolicyModifierOneOf($entity_type, $claim, $policy)
    {
        $claim_val = $this->payload->metadata->$entity_type->$claim;
        if ($claim_val != null && !in_array($claim_val, $policy)) {
            throw new \Exception("Failed trust policy (" . $claim . " must be one of " . json_encode($policy) . ")");
        }
    }

    private function applyPolicyModifierSubsetOf($entity_type, $claim, $policy)
    {
        $claim_val = $this->payload->metadata->$entity_type->$claim;
        if (!is_array($claim_val) || !(array_intersect($claim_val, $policy) === $claim_val)) {
            throw new \Exception("Failed trust policy (" . $claim . " must be subset of " . json_encode($policy) . ")");
        }
    }

    private function applyPolicyModifierSupersetOf($entity_type, $claim, $policy)
    {
        $claim_val = $this->payload->metadata->$entity_type->$claim;
        if (!is_array($claim_val) || !(array_intersect($policy, $claim_val) === $policy)) {
            throw new \Exception("Failed trust policy (" . $claim . " must be superset of " . json_encode($policy) . ")");
        }
    }

    private function applyPolicyModifierEssential($entity_type, $claim, $policy)
    {
        $claim_val = $this->payload->metadata->$entity_type->$claim;
        if ($policy == true && ($claim_val == null || $claim_val == '')) {
            throw new \Exception("Failed trust policy (" . $claim . " must have a value)");
        }
    }
}
