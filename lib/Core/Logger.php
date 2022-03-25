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

namespace SPID_CIE_OIDC_PHP\Core;

class Logger
{
    public function __construct($config = null)
    {
        if ($config == null) {
            $config = (object)[];
        }
        $this->config = $config;
    }

    public function log($tag, $value, $object = null, $priority = LOG_NOTICE)
    {
        $message = "[" . $_SERVER['REMOTE_ADDR'] . "][" . $tag . "] - " . $value;
        if ($object != null) {
            $message .= "(" . json_encode($object) . ")";
        }
        return error_log($message);
    }
}
