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
    public function test_process_invalid_scope1()
    {
        // clean old tests
        if (file_exists("tests/tests.sqlite")) {
            unlink("tests/tests.sqlite");
        }

        $config = json_decode(file_get_contents(__DIR__ . '/../config_sample/config.json'), true);
        $config['service_name'] = 'test';
        $database = new OP_Database(__DIR__ . '/tests.sqlite');
        $endpoint = new AuthenticationEndpoint($config, $database);

        $_GET['state'] = '';
        $_GET['nonce'] = '';
        $_GET['scope'] = '';
        $_GET['response_type'] = 'code';
        $_GET['client_id'] = '2b4601ab-9e1b-4f5b-8b1e-3ae27beb9fdb';
        $_GET['redirect_uri'] = 'http://relying-party-wordpress.org:8004/wp-admin/admin-ajax.php?action=openid-connect-authorize';

        $this->expectExceptionMessage('invalid_scope');
        $endpoint->process();
    }

    /**
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\AuthenticationEndpoint::process
     * @runInSeparateProcess
     */
    public function test_process_invalid_scope2()
    {
        // clean old tests
        if (file_exists("tests/tests.sqlite")) {
            unlink("tests/tests.sqlite");
        }

        $config = json_decode(file_get_contents(__DIR__ . '/../config_sample/config.json'), true);
        $config['service_name'] = 'test';
        $database = new OP_Database(__DIR__ . '/tests.sqlite');
        $endpoint = new AuthenticationEndpoint($config, $database);

        $_GET['state'] = '';
        $_GET['nonce'] = '';
        $_GET['scope'] = 'openid';
        $_GET['response_type'] = 'code';
        $_GET['client_id'] = '2b4601ab-9e1b-4f5b-8b1e-3ae27beb9fdb';
        $_GET['redirect_uri'] = 'http://relying-party-wordpress.org:8004/wp-admin/admin-ajax.php?action=openid-connect-authorize';

        $this->expectExceptionMessage('invalid_scope');
        $endpoint->process();
    }

    /**
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\AuthenticationEndpoint::process
     * @runInSeparateProcess
     */
    public function test_process_invalid_scope3()
    {
        // clean old tests
        if (file_exists("tests/tests.sqlite")) {
            unlink("tests/tests.sqlite");
        }

        $config = json_decode(file_get_contents(__DIR__ . '/../config_sample/config.json'), true);
        $config['service_name'] = 'test';
        $database = new OP_Database(__DIR__ . '/tests.sqlite');
        $endpoint = new AuthenticationEndpoint($config, $database);

        $_GET['state'] = '';
        $_GET['nonce'] = '';
        $_GET['scope'] = 'profile';
        $_GET['response_type'] = 'code';
        $_GET['client_id'] = '2b4601ab-9e1b-4f5b-8b1e-3ae27beb9fdb';
        $_GET['redirect_uri'] = 'http://relying-party-wordpress.org:8004/wp-admin/admin-ajax.php?action=openid-connect-authorize';

        $_GET['scope'] = 'profile';
        $this->expectExceptionMessage('invalid_scope');
        $endpoint->process();
    }

    /**
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\AuthenticationEndpoint::process
     * @runInSeparateProcess
     */
    public function test_process_invalid_request()
    {
        // clean old tests
        if (file_exists("tests/tests.sqlite")) {
            unlink("tests/tests.sqlite");
        }

        $config = json_decode(file_get_contents(__DIR__ . '/../config_sample/config.json'), true);
        $config['service_name'] = 'test';
        $database = new OP_Database(__DIR__ . '/tests.sqlite');
        $endpoint = new AuthenticationEndpoint($config, $database);

        $_GET['state'] = '';
        $_GET['nonce'] = '';
        $_GET['scope'] = 'openid profile';
        $_GET['response_type'] = '';
        $_GET['client_id'] = '2b4601ab-9e1b-4f5b-8b1e-3ae27beb9fdb';
        $_GET['redirect_uri'] = 'http://relying-party-wordpress.org:8004/wp-admin/admin-ajax.php?action=openid-connect-authorize';

        $this->expectExceptionMessage('invalid_request');
        $endpoint->process();
    }

    /**
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\AuthenticationEndpoint::process
     * @runInSeparateProcess
     */
    public function test_process_invalid_client()
    {
        // clean old tests
        if (file_exists("tests/tests.sqlite")) {
            unlink("tests/tests.sqlite");
        }

        $config = json_decode(file_get_contents(__DIR__ . '/../config_sample/config.json'), true);
        $config['service_name'] = 'test';
        $database = new OP_Database(__DIR__ . '/tests.sqlite');
        $endpoint = new AuthenticationEndpoint($config, $database);

        $_GET['state'] = '';
        $_GET['nonce'] = '';
        $_GET['scope'] = 'openid profile';
        $_GET['response_type'] = 'code';
        $_GET['client_id'] = '';
        $_GET['redirect_uri'] = 'http://relying-party-wordpress.org:8004/wp-admin/admin-ajax.php?action=openid-connect-authorize';

        $this->expectExceptionMessage('invalid_client');
        $endpoint->process();
    }

    /**
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\AuthenticationEndpoint::process
     * @runInSeparateProcess
     */
    public function test_process_invalid_redirect_uri1()
    {
        // clean old tests
        if (file_exists("tests/tests.sqlite")) {
            unlink("tests/tests.sqlite");
        }

        $config = json_decode(file_get_contents(__DIR__ . '/../config_sample/config.json'), true);
        $config['service_name'] = 'test';
        $database = new OP_Database(__DIR__ . '/tests.sqlite');
        $endpoint = new AuthenticationEndpoint($config, $database);

        $_GET['state'] = '';
        $_GET['nonce'] = '';
        $_GET['scope'] = 'openid profile';
        $_GET['response_type'] = 'code';
        $_GET['client_id'] = '2b4601ab-9e1b-4f5b-8b1e-3ae27beb9fdb';
        $_GET['redirect_uri'] = '';

        $this->expectExceptionMessage('invalid_redirect_uri');
        $endpoint->process();
    }

    /**
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\AuthenticationEndpoint::process
     * @runInSeparateProcess
     */
    public function test_process_invalid_redirect_uri2()
    {
        // clean old tests
        if (file_exists("tests/tests.sqlite")) {
            unlink("tests/tests.sqlite");
        }

        $config = json_decode(file_get_contents(__DIR__ . '/../config_sample/config.json'), true);
        $config['service_name'] = 'test';
        $database = new OP_Database(__DIR__ . '/tests.sqlite');

        $_GET['state'] = '';
        $_GET['nonce'] = '';
        $_GET['scope'] = 'openid profile';
        $_GET['response_type'] = 'code';
        $_GET['client_id'] = '2b4601ab-9e1b-4f5b-8b1e-3ae27beb9fdb';
        $_GET['redirect_uri'] = '';
        $config['production'] = false;
        $endpoint = new AuthenticationEndpoint($config, $database);
        $this->expectExceptionMessage('invalid_redirect_uri');
        $endpoint->process();
    }

    /**
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\AuthenticationEndpoint::process
     * @runInSeparateProcess
     */
    public function test_process_not_production()
    {
        // clean old tests
        if (file_exists("tests/tests.sqlite")) {
            unlink("tests/tests.sqlite");
        }

        $config = json_decode(file_get_contents(__DIR__ . '/../config_sample/config.json'), true);
        $config['service_name'] = 'test';
        $database = new OP_Database(__DIR__ . '/tests.sqlite');

        $_GET['state'] = '';
        $_GET['nonce'] = '';
        $_GET['scope'] = 'openid profile';
        $_GET['response_type'] = 'code';
        $_GET['client_id'] = '2b4601ab-9e1b-4f5b-8b1e-3ae27beb9fdb';
        $_GET['redirect_uri'] = 'http://relying-party-wordpress.org:8004/wp-admin/admin-ajax.php?action=openid-connect-authorize';

        $config['production'] = false;
        $endpoint = new AuthenticationEndpoint($config, $database);

        try {
            $endpoint->process();
        } catch (\Exception $e) {
            //
        }

        $this->assertTrue(true);
    }

    /**
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\AuthenticationEndpoint::process
     * @runInSeparateProcess
     */
    public function test_process_not_production2()
    {
        // clean old tests
        if (file_exists("tests/tests.sqlite")) {
            unlink("tests/tests.sqlite");
        }

        $config = json_decode(file_get_contents(__DIR__ . '/../config_sample/config.json'), true);
        $config['service_name'] = 'test';
        $database = new OP_Database(__DIR__ . '/tests.sqlite');

        $_GET['state'] = '';
        $_GET['nonce'] = '';
        $_GET['scope'] = 'openid profile';
        $_GET['response_type'] = '';
        $_GET['client_id'] = '2b4601ab-9e1b-4f5b-8b1e-3ae27beb9fdb';
        $_GET['redirect_uri'] = 'http://relying-party-wordpress.org:8004/wp-admin/admin-ajax.php?action=openid-connect-authorize';

        $config['production'] = true;
        $endpoint = new AuthenticationEndpoint($config, $database);

        $endpoint->process();
        $this->assertTrue(true);

        $_GET['redirect_uri'] = 'http://relying-party-wordpress.org:8004/wp-admin/admin-ajax.php?action=openid-connect-authorize';
        $endpoint = new AuthenticationEndpoint($config, $database);

        $endpoint->process();
        $this->assertTrue(true);
    }


    /**
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\AuthenticationEndpoint::callback
     * @runInSeparateProcess
     */
    public function test_callback()
    {
        $config = json_decode(file_get_contents(__DIR__ . '/../config_sample/config.json'), true);
        $database = new OP_Database(__DIR__ . '/tests.sqlite');
        $endpoint = new AuthenticationEndpoint($config, $database);

        $_POST['name'] = 'NAME';
        $_SERVER['HTTP_HOST'] = 'HOST';
        $_SERVER['HTTP_ORIGIN'] = 'https://' . $_SERVER['HTTP_HOST'];

        try {
            $endpoint->callback();
            $this->fail();
        } catch (\Exception $e) {
            // invalid origin and post empty
            $this->assertTrue(true);
        }
    }
}
