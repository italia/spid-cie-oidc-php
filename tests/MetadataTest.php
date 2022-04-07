<?php

use PHPUnit\Framework\TestCase as TestCase;
use SPID_CIE_OIDC_PHP\OIDC\OP\Metadata;

/**
 * @covers SPID_CIE_OIDC_PHP\OIDC\OP\Metadata
 */
class MetadataTest extends TestCase
{
    /**
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\Metadata::getConfiguration
     * @runInSeparateProcess
     */
    public function test_Metadata()
    {
        $config = json_decode(file_get_contents(__DIR__ . '/../config_sample/config.json'), true);
        $metadata = new Metadata($config);
        $configuration = $metadata->getConfiguration();

        $this->assertNotNull($configuration);
    }
}
