<?php

use PHPUnit\Framework\TestCase as TestCase;
use SPID_CIE_OIDC_PHP\Federation\Federation;

/**
 * @covers SPID_CIE_OIDC_PHP\Federation\Federation
 */
class FederationTest extends TestCase
{
    /**
     * @covers SPID_CIE_OIDC_PHP\Federation\Federation::isFederationSupported
     */
    public function test_isFederationSupported()
    {
        $config = (object) array();
        $fed_config = (object) array(
            "https://registry.spid.gov.it"=> array(),
            "http://127.0.0.1:8000"=> array()
        );

        $federation = new Federation($config, $fed_config);

        $this->assertTrue($federation->isFederationSupported('https://registry.spid.gov.it'));
        $this->assertTrue($federation->isFederationSupported('http://127.0.0.1:8000'));
        $this->assertFalse($federation->isFederationSupported('http://127.0.0.1:8002'));
        $this->assertFalse($federation->isFederationSupported(''));
    }
}
