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

/**
 *  Provides functions to save logs.
 *
 */
class Logger
{
    /**
     *  creates a new Logger instance
     *
     * @param array $config base configuration
     * @throws Exception
     * @return Logger
     * @codeCoverageIgnore
     */
    public function __construct(array $config = null)
    {
        if ($config == null) {
            $config = array();
        }
        $this->config = $config;
    }

    /**
     *  save a log on standard error_log
     *
     * @param string $tag tag to wich save the log
     * @param string $value value of the log
     * @param mixed $object object to dump
     * @param int $priority on wichh save the log
     * @throws Exception
     * @return boolean result of save
     * @codeCoverageIgnore
     */
    public function log(string $tag, string $value, $object = null, int $priority = LOG_NOTICE)
    {
        $message = "[" . $_SERVER['REMOTE_ADDR'] . "][" . $tag . "] - " . $value;
        if ($object != null) {
            $message .= "(" . json_encode($object) . ")";
        }
        return error_log($message);
    }
}
