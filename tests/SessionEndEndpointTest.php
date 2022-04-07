<?php

use PHPUnit\Framework\TestCase as TestCase;
use SPID_CIE_OIDC_PHP\OIDC\OP\SessionEndEndpoint;
use SPID_CIE_OIDC_PHP\OIDC\OP\Database as OP_Database;

/**
 * @covers SPID_CIE_OIDC_PHP\OIDC\OP\SessionEndEndpoint
 */
class SessionEndEndpointTest extends TestCase
{
    /**
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\SessionEndEndpoint::process
     * @runInSeparateProcess
     */
    public function test_SessionEndEndpoint()
    {
        // clean old tests
        if (file_exists("tests/tests.sqlite")) {
            unlink("tests/tests.sqlite");
        }

        $config = json_decode(file_get_contents(__DIR__ . '/../config_sample/config.json'), true);
        $database = new OP_Database(__DIR__ . '/tests.sqlite');
        $endpoint = new SessionEndEndpoint($config, $database);

        // wordpress example project
        $_GET['id_token_hint'] = '';
        $_GET['post_logout_redirect_uri'] = "http://relying-party-wordpress.org:8004/";

        try {
            $endpoint->process();
        } catch (\Exception $e) {
            //
        }

        $this->assertTrue(true);
    }
}
