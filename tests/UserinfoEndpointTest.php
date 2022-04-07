<?php

use PHPUnit\Framework\TestCase as TestCase;
use SPID_CIE_OIDC_PHP\OIDC\OP\UserinfoEndpoint;
use SPID_CIE_OIDC_PHP\OIDC\OP\TokenEndpoint;
use SPID_CIE_OIDC_PHP\OIDC\OP\Database as OP_Database;

/**
 * @covers SPID_CIE_OIDC_PHP\OIDC\OP\UserinfoEndpoint
 */
class UserinfoEndpointTest extends TestCase
{
    /**
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\UserinfoEndpoint::process
     * @runInSeparateProcess
     */
    public function test_UserinfoEndpoint()
    {
        // clean old tests
        if (file_exists("tests/tests.sqlite")) {
            unlink("tests/tests.sqlite");
        }

        $client_id = "2b4601ab-9e1b-4f5b-8b1e-3ae27beb9fdb";
        $redirect_uri = "http://relying-party-wordpress.org:8004/wp-admin/admin-ajax.php?action=openid-connect-authorize";
        $state = "STATE";
        $nonce = "NONCE";

        $config = json_decode(file_get_contents(__DIR__ . '/../config_sample/config.json'), true);
        $config['rp_proxy_clients'][$client_id]['cert_private'] = "cert_sample/op.pem";

        $database = new OP_Database(__DIR__ . '/tests.sqlite');
        $endpoint = new TokenEndpoint($config, $database);

        $req_id = $database->createRequest($client_id, $redirect_uri, $state, $nonce);
        $code = $database->createAuthorizationCode($req_id);

        $userinfo = array(
            "fiscalNumber" => "FISCALNUMBER",
            "name" => "NAME",
            "familyName" => "FAMILY NAME"
        );

        $database->saveUserinfo($req_id, $userinfo);

        // wordpress example project
        $_POST['code'] = $code;
        $_POST['scope'] = "openid profile";
        $_POST['grant_type'] = "authorization_code";
        $_POST['client_id'] = $client_id;
        $_POST['client_secret'] = "389451f0-dc60-4fba-8c03-eea4adb340b6";
        $_POST['redirect_uri'] = $redirect_uri;
        $_POST['state'] = "STATE";
        $_POST['token_endpoint_auth_method'] = "client_secret_post";

        try {
            $this->expectOutputRegex("{.+?}");
            $endpoint->process();
        } catch (\Exception $e) {
            //
        }

        $access_token = $database->createAccessToken($code);

        try {
            $endpoint->process();

            // authorization not present
            $this->fail();
        } catch (\Exception $e) {
            $_SERVER['Authorization'] = "Bearer " . $access_token;
            $endpoint = new UserinfoEndpoint($config, $database);

            try {
                $this->expectOutputRegex("{.+?}");
                $endpoint->process();

                $this->assertTrue(true);
            } catch (\Exception $e) {
                //
            }
        }
    }
}
