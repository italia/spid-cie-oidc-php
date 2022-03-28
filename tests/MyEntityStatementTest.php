<?php

use PHPUnit\Framework\TestCase as TestCase;
use SPID_CIE_OIDC_PHP\Federation\MyEntityStatement;

/**
 * @covers SPID_CIE_OIDC_PHP\Federation\MyEntityStatement
 */
class MyEntityStatementTest extends TestCase
{
    /**
     * @covers SPID_CIE_OIDC_PHP\Federation\MyEntityStatement::getConfiguration
     */
    public function test_getConfiguration()
    {
        $config = json_decode(file_get_contents(__DIR__ . '/../config/config.json'));
        $es = new MyEntityStatement($config);
        $metadata = $es->getConfiguration();
        $this->assertNotEmpty($es, "MyEntityStatement cannot be empty");
    }
}
