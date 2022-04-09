<?php

use PHPUnit\Framework\TestCase as TestCase;
use SPID_CIE_OIDC_PHP\Response\ResponseHandlerPlain;

/**
 * @covers SPID_CIE_OIDC_PHP\Response\ResponseHandlerPlain
 */
class ResponseHandlerPlainTest extends TestCase
{
    /**
     * @covers SPID_CIE_OIDC_PHP\ResponseHandlerPlain\ResponseHandlerPlain
     */
    public function test_ResponseHandlerPlain()
    {
        $config = json_decode(file_get_contents(__DIR__ . '/../config/config.json'), true);
        $responseHandlerPlain = new ResponseHandlerPlain($config);
        $this->expectOutputString("<form name='spidauth' action='http://127.0.0.1' method='POST'><input type='hidden' name='state' value='state' /></form><script type='text/javascript'>  document.spidauth.submit();</script>");
        $responseHandlerPlain->sendResponse('http://127.0.0.1', (object)[], 'state');
        $this->assertTrue(true);
    }
}
