<?php

use PHPUnit\Framework\TestCase as TestCase;
use SPID_CIE_OIDC_PHP\Setup\Setup;

/**
 * @covers SPID_CIE_OIDC_PHP\OIDC\OP\AuthenticationEndpoint
 */
class SetupTest extends TestCase
{
    /**
     * @covers SPID_CIE_OIDC_PHP\Setup\Setup::setup
     */
    public function test_Setup()
    {
        try {
            Setup::setup();
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
    }
}
