<?php

use PHPUnit\Framework\TestCase as TestCase;
use SPID_CIE_OIDC_PHP\Core\Util;

/**
 * @covers SPID_CIE_OIDC_PHP\Core\Util
 */
class UtilTest extends TestCase
{
    /**
     * @covers SPID_CIE_OIDC_PHP\Core\Util
     * @runInSeparateProcess
     */
    public function test_Util()
    {
        $code = Util::getRandomCode();
        $this->assertTrue(strlen($code) == 64);

        $code_verifier = "code";
        $code_challenge = Util::getCodeChallenge($code_verifier);
        $mustBe = Util::base64UrlEncode(hash('sha256', $code_verifier, true));
        $this->assertEquals($code_challenge, $mustBe);

        $string = "string";
        $encoded = Util::base64UrlEncode($string);
        $decoded = Util::base64UrlDecode($encoded);
        $this->assertEquals($string, $decoded);

        $startsWith = Util::stringStartsWith($string, "s");
        $this->assertTrue($startsWith);

        $startsWith = Util::stringStartsWith($string, "S", false);
        $this->assertTrue($startsWith);

        $endsWith = Util::stringEndsWith($string, "g");
        $this->assertTrue($endsWith);

        $endsWith = Util::stringEndsWith($string, "G", false);
        $this->assertTrue($endsWith);
    }
}
