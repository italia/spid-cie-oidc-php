<?php

use PHPUnit\Framework\TestCase as TestCase;
use SPID_CIE_OIDC_PHP\Core\Logger;

/**
 * @covers SPID_CIE_OIDC_PHP\Core\Logger
 */
class LoggerTest extends TestCase
{
    /**
     * @covers SPID_CIE_OIDC_PHP\Core\Logger
     */
    public function test_Logger()
    {
        $logger = new Logger();
        $logger->log('tag', 'value', null, LOG_NOTICE);
        $this->assertTrue(true);
    }
}