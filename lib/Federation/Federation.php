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

/**
 *  Utility functions for Federation
 *
 *  [OpenID Connect Federation Entity Statement](https://openid.net/specs/openid-connect-federation-1_0.html#rfc.section.3.1)
 *
 */
class Federation
{
    /**
     *  creates a new Federation instance
     *
     * @param array $config base configuration
     * @param array $fed_config federation configuration
     * @throws Exception
     * @return Federation
     */
    public function __construct(array $config, array $fed_config)
    {
        $this->config = $config;
        $this->fed_config = $fed_config;
    }

    /**
     *  check if federation is supported
     *
     * @param string $federation entity id of federation
     * @throws Exception
     * @return boolean if federation is supported
     */
    public function isFederationSupported(string $federation)
    {
        $supported_fed = $this->fed_config;
        $supported_fed_list = array_keys($supported_fed);
        return in_array($federation, $supported_fed_list);
    }
}
