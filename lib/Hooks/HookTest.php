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

namespace SPID_CIE_OIDC_PHP\Hooks;

/**
 *  Test class that show how to code an hook plugin
 *
 *  to enable this hook add the value "&bsol;SPID_CIE_OIDC_PHP&bsol;Hooks&bsol;HookTest" into the hooks.json config file inside the hook section where you want to enable this hook
 */
class HookTest
{
    /**
     *  creates a new <HookTest> instance
     *
     * @param object $config base configuration
     * @throws Exception
     * @return <HookTest>
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     *  main function that will be executed when the hook is enabled
     *
     * @param object $data data provided by caller related to hook position
     * @throws Exception
     * @return mixed what you want
     */
    public function run($data)
    {
        header("Content-Type: application/json");
        echo json_encode($data);
        die();
    }
}
