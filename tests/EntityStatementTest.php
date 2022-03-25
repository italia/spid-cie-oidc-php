<?php

use PHPUnit\Framework\TestCase as TestCase;
use SPID_CIE_OIDC_PHP\Federation\EntityStatement;

/**
 * @covers SPID_CIE_OIDC_PHP\Federation\EntityStatement
 */
class EntityStatementTest extends TestCase
{
    /**
     * @covers SPID_CIE_OIDC_PHP\Federation\EntityStatement::getConfiguration
     */
    public function test_getEntityStatement()
    {
        $config = json_decode(file_get_contents(__DIR__ . '/../config/config.json'));
        $es = new EntityStatement($config);
        $metadata = $es->getConfiguration();
        $this->assertNotEmpty($es, "EntityStatement cannot be empty");
    }
}
