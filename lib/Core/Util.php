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
 *  Provides utility functions
 */
class Util
{
    /**
     *  generate a random code
     *
     *  used for generate code_verifier and nonce
     *  [code verifier for PKCE](https://datatracker.ietf.org/doc/html/rfc7636#section-4.1)
     *
     * @param int $length length of generated code
     * @throws Exception
     * @return string the random code
     */
    public static function getRandomCode(int $length = 64)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-._~";
        $random = substr(str_shuffle($chars), 0, $length);
        return $random;
    }

    /**
     *  generate the code_challenge for PKCE
     *
     *  [code challenge for PKCE](https://datatracker.ietf.org/doc/html/rfc7636#section-4.2)
     *
     * @param string $code_verifier the code verifier
     * @throws Exception
     * @return string the code_challenge
     */
    public static function getCodeChallenge(string $code_verifier)
    {
        $code_challenge = self::base64UrlEncode(hash('sha256', $code_verifier, true));
        //$code_challenge = str_replace("=", "", $code_challenge);
        return $code_challenge;
    }

    /**
     *  return the base64 url encoded value of the given string
     *
     * @param string $data the string to encode
     * @throws Exception
     * @return string the encoded string
     */
    public static function base64UrlEncode(string $data)
    {
        $b64 = base64_encode($data);

        // @codeCoverageIgnoreStart
        if ($b64 === false) {
            return false;
        }
        // @codeCoverageIgnoreEnd

        $url = strtr($b64, '+/', '-_');
        return rtrim($url, '=');
    }

    /**
     *  decode a base64 url encoded string
     *
     * @param string $data the string to decode
     * @param boolean $strict if true, will return false if the input contains character from outside the base64 alphabet
     * @throws Exception
     * @return string the decoded string
     */
    public static function base64UrlDecode(string $data, $strict = false)
    {
        $b64 = strtr($data, '-_', '+/');
        return base64_decode($b64, $strict);
    }

    /**
     *  check if haystack string starts with needle string
     *
     * @param string $haystack the string to check
     * @param string $needle the string to check for
     * @param string $case if case sensitive
     * @throws Exception
     * @return boolean true if haystack starts with needle
     */
    public static function stringStartsWith(string $haystack, string $needle, $case = true)
    {
        if ($case) {
            return strpos($haystack, $needle, 0) === 0;
        }
        return stripos($haystack, $needle, 0) === 0;
    }

    /**
     *  check if haystack string ends with needle string
     *
     * @param string $haystack the string to check
     * @param string $needle the string to check for
     * @param string $case if case sensitive
     * @throws Exception
     * @return boolean true if haystack ends with needle
     */
    public static function stringEndsWith(string $haystack, string $needle, $case = true)
    {
        $expectedPosition = strlen($haystack) - strlen($needle);
        if ($case) {
            return strrpos($haystack, $needle, 0) === $expectedPosition;
        }
        return strripos($haystack, $needle, 0) === $expectedPosition;
    }


    /**
    * @codeCoverageIgnore
    */
    public static function debug($object)
    {
        header('Content-Type: application/json');
        echo json_encode($object);
        die();
    }
}
