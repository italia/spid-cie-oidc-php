<?php

use PHPUnit\Framework\TestCase as TestCase;
use SPID_CIE_OIDC_PHP\OIDC\RP\IntrospectionRequest;
use SPID_CIE_OIDC_PHP\OIDC\RP\Database as RP_Database;

/**
 * @covers SPID_CIE_OIDC_PHP\OIDC\OP\IntrospectionRequest
 */
class IntrospectionRequestTest extends TestCase
{
    /**
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\IntrospectionRequest::send
     * @runInSeparateProcess
     */
    public function test_IntrospectionRequest()
    {
        // clean old tests
        if (file_exists("tests/tests.sqlite")) {
            unlink("tests/tests.sqlite");
        }

        $config = json_decode(file_get_contents(__DIR__ . '/../config_sample/config.json'), true);
        $config = $config['rp_proxy_clients']['default'];
        $config['cert_private'] = 'cert_sample/rp.pem';
        $config['cert_public'] = 'cert_sample/rp.crt';

        $request = new IntrospectionRequest($config);
        $introspection_endpoint = "http://127.0.0.1";
        $token = "TOKEN";

        try {
            $request->send($introspection_endpoint, $token);
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->assertTrue(true);
    }
}
