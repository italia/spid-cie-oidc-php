<?php

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
