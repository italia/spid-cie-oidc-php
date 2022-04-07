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

use SPID_CIE_OIDC_PHP\Core\Util;
use SPID_CIE_OIDC_PHP\Core\JWT;

/**
 *  Proxy OIDC Metadata Discovery
 *
 *  [OpenID Connect Discovery](https://openid.net/specs/openid-connect-discovery-1_0.html)
 *
 */
class Metadata
{
    /**
     *  creates a new Metadata instance
     *
     * @param array $config base configuration
     * @throws Exception
     * @return Metadata
     */
    public function __construct(array $config = null)
    {
        $this->config = $config;

        //$crt = $config['op_proxy_cert_public'];
        //$crt_jwk = JWT::getCertificateJWK($crt);

        $base_url = Util::stringEndsWith($config['op_proxy_client_id'], '/') ? $config['op_proxy_client_id'] : $config['op_proxy_client_id'] . '/';

        $this->metadata = array(
            "issuer" => $base_url,
            "authorization_endpoint" => $base_url . 'authz',
            "token_endpoint" => $base_url . 'token',
            "userinfo_endpoint" => $base_url . 'userinfo',
            "jwks_uri" => $base_url . 'certs',
            "scopes_supported" => array(
                "openid profile"
            ),
            "response_types_supported" => array(
                "code"
            ),
            "subject_types_supported" => array(
                "public"
            ),
            "id_token_signing_alg_values_supported" => array(
                "RS256"
            ),
            "claims_supported" => array(
                "sub",
                "spidCode",
                "name",
                "familyName",
                "placeOfBirth",
                "countyOfBirth",
                "dateOfBirth",
                "gender",
                "fiscalNumber",
                "mobilePhone",
                "email"
            )
        );
    }

    public function getConfiguration()
    {
        return json_encode($this->metadata);
    }
}
