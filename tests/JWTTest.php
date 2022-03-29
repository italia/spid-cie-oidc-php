<?php

use PHPUnit\Framework\TestCase as TestCase;
use SPID_CIE_OIDC_PHP\Core\JWT;

/**
 * @covers SPID_CIE_OIDC_PHP\Core\JWT
 */
class JWTTest extends TestCase
{
    /**
     * @covers SPID_CIE_OIDC_PHP\Core\JWT::isValid
     */
    public function test_isValid()
    {
        $header = array(
            "typ" => "JWT",
            "alg" => "RS256"
        );

        $jwk = JWT::getKeyJWK('./cert/rp.pem');

        $payload = array(
            "exp" => strtotime('+1 hour'),
            "iat" => strtotime('now'),
            "iss" => 'https://iss',
            "sub" => 'sub'
        );

        $token = JWT::makeJWS($header, $payload, $jwk);

        $this->assertTrue(JWT::isValid($token));

        $payload2 = array(
            "exp" => strtotime('-1 hour'),
            "iat" => strtotime('now'),
            "iss" => 'https://iss',
            "sub" => 'sub'
        );

        $token2 = JWT::makeJWS($header, $payload2, $jwk);

        $this->assertFalse(JWT::isValid($token2));
    }
}
