<?php

use PHPUnit\Framework\TestCase as TestCase;
use SPID_CIE_OIDC_PHP\OIDC\OP\Metadata as OP_Metadata;
use SPID_CIE_OIDC_PHP\OIDC\RP\UserinfoRequest;
use SPID_CIE_OIDC_PHP\OIDC\RP\Database as RP_Database;

/**
 * @covers SPID_CIE_OIDC_PHP\OIDC\OP\UserinfoRequest
 */
class UserinfoRequestTest extends TestCase
{
    /**
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\UserinfoRequest::send
     * @runInSeparateProcess
     */
    public function test_UserinfoRequest()
    {
        // clean old tests
        if (file_exists("tests/tests.sqlite")) {
            unlink("tests/tests.sqlite");
        }

        $config = json_decode(file_get_contents(__DIR__ . '/../config_sample/config.json'), true);
        $op_metadata = new OP_METADATA($config);
        $config = $config['rp_proxy_clients']['default'];
        $config['cert_private'] = 'cert_sample/rp.pem';
        $config['cert_public'] = 'cert_sample/rp.crt';

        $request = new UserinfoRequest($config, $op_metadata);
        $userinfo_endpoint = "http://127.0.0.1";
        $access_token = "TOKEN";

        try {
            $request->send($userinfo_endpoint, $access_token);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }
}
