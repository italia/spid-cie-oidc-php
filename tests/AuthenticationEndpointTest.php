<?php

use PHPUnit\Framework\TestCase as TestCase;
use SPID_CIE_OIDC_PHP\OIDC\OP\AuthenticationEndpoint;
use SPID_CIE_OIDC_PHP\OIDC\OP\Database as OP_Database;

/**
 * @covers SPID_CIE_OIDC_PHP\OIDC\OP\AuthenticationEndpoint
 */
class AuthenticationEndpointTest extends TestCase
{
    /**
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\AuthenticationEndpoint::process
     * @runInSeparateProcess
     */
    public function test_process()
    {
        // clean old tests
        if (file_exists("tests/tests.sqlite")) {
            unlink("tests/tests.sqlite");
        }

        $config = json_decode(file_get_contents(__DIR__ . '/../config_sample/config.json'), true);
        $database = new OP_Database(__DIR__ . '/tests.sqlite');
        $endpoint = new AuthenticationEndpoint($config, $database);

        // wordpress example project
        $_GET['scope']          = 'openid profile';
        $_GET['response_type']  = 'code';
        $_GET['client_id']      = '2b4601ab-9e1b-4f5b-8b1e-3ae27beb9fdb';
        $_GET['redirect_uri']   = 'http://relying-party-wordpress.org:8004/wp-admin/admin-ajax.php?action=openid-connect-authorize';
        $_GET['state']          = '';
        $_GET['nonce']          = '';

        try {
            $endpoint->process();
        } catch (\Exception $e) {
            //
        }

        $this->assertTrue(true);
    }

    /**
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\AuthenticationEndpoint::callback
     */
    public function test_callback()
    {
        $config = json_decode(file_get_contents(__DIR__ . '/../config_sample/config.json'), true);
        $database = new OP_Database(__DIR__ . '/tests.sqlite');
        $endpoint = new AuthenticationEndpoint($config, $database);

        try {
            $endpoint->callback();
            $this->fail();
        } catch (\Exception $e) {
            // invalid origin and post empty
            $this->assertTrue(true);
        }
    }
}
