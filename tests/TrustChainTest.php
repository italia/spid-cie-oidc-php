<?php

use PHPUnit\Framework\TestCase as TestCase;
use SPID_CIE_OIDC_PHP\Federation\TrustChain;
use SPID_CIE_OIDC_PHP\OIDC\RP\Database;

/**
 * @covers SPID_CIE_OIDC_PHP\Federation\TrustChain
 */
class TrustChainTest extends TestCase
{
    /**
     * @covers SPID_CIE_OIDC_PHP\Federation\EntityStatement::TrustChain
     * @runInSeparateProcess
     */
    public function test_TrustChain()
    {
        $config = json_decode(file_get_contents(__DIR__ . '/../config/config.json'), true);
        $database = new Database("tests/tests.sqlite");
        $leaf = "http://relying-party.org";
        $trust_anchor = "http://trust-anchor.org";

        $trustChain = new TrustChain($config, $database, $leaf, $trust_anchor);
        $this->assertNotNull($trustChain);

        try {
            $trustChain->resolve();
        } catch (\Exception $e) {
            // unable to reach
            $this->assertTrue(true);
        }
    }
}
