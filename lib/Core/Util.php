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

class Util
{
    public static function getRandomCode($length = 64)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-._~";
        $random = substr(str_shuffle($chars), 0, $length);
        return $random;
    }

    public static function getCodeChallenge($code_verifier)
    {
        $code_challenge = self::base64UrlEncode(hash('sha256', $code_verifier, true));
        //$code_challenge = str_replace("=", "", $code_challenge);
        return $code_challenge;
    }

    public static function base64UrlEncode($data)
    {
        $b64 = base64_encode($data);
        if ($b64 === false) {
            return false;
        }
        $url = strtr($b64, '+/', '-_');
        return rtrim($url, '=');
    }

    public static function base64UrlDecode($data, $strict = false)
    {
        $b64 = strtr($data, '-_', '+/');
        return base64_decode($b64, $strict);
    }
}
