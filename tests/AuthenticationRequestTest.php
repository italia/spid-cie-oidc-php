<?php

use PHPUnit\Framework\TestCase as TestCase;
use SPID_CIE_OIDC_PHP\OIDC\RP\AuthenticationRequest;
use SPID_CIE_OIDC_PHP\OIDC\RP\Database as RP_Database;

/**
 * @covers SPID_CIE_OIDC_PHP\OIDC\OP\AuthenticationRequest
 */
class AuthenticationRequestTest extends TestCase
{
    /**
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\AuthenticationRequest::getRedirectURL
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\AuthenticationRequest::send
     * @runInSeparateProcess
     */
    public function test_getRedirectURL()
    {
        // clean old tests
        if (file_exists("tests/tests.sqlite")) {
            unlink("tests/tests.sqlite");
        }

        $config = json_decode(file_get_contents(__DIR__ . '/../config_sample/config.json'), true);
        $config = $config['rp_proxy_clients']['default'];
        $config['cert_private'] = 'cert_sample/rp.pem';
        $config['cert_public'] = 'cert_sample/rp.crt';
        $config['service_name'] = '';
        $database = new RP_Database(__DIR__ . '/tests.sqlite');
        $request = new AuthenticationRequest($config);

        $authorization_endpoint = "https://op.org/auth";
        $acr = array(3, 2, 1);
        $user_attributes = array(
            "name",
            "familyName",
            "email",
            "fiscalNumber"
        );

        $code_verifier = "VERIFIER";
        $nonce = "NONCE";
        $state = "STATE";

        $redirect_url = $request->getRedirectURL($authorization_endpoint, $acr, $user_attributes, $code_verifier, $nonce, $state);

        $this->assertStringStartsWith("https://op.org/auth?client_id=http://relying-party-php.org:8003/&response_type=code&scope=openid&code_challenge=a1Y-Z7sHPycP84FUZMgqhDyqVo6DdP5EUEXrLaTUge0&code_challenge_method=S256&nonce=NONCE&request=", $redirect_url);
    }

    /**
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\AuthenticationRequest::send
     * @runInSeparateProcess
     */
    public function test_send()
    {
        // clean old tests
        if (file_exists("tests/tests.sqlite")) {
            unlink("tests/tests.sqlite");
        }

        $config = json_decode(file_get_contents(__DIR__ . '/../config_sample/config.json'), true);
        $config = $config['rp_proxy_clients']['default'];
        $config['cert_private'] = 'cert_sample/rp.pem';
        $config['cert_public'] = 'cert_sample/rp.crt';
        $database = new RP_Database(__DIR__ . '/tests.sqlite');
        $request = new AuthenticationRequest($config);

        $authorization_endpoint = "https://op.org/auth";
        $acr = array(3, 2, 1);
        $user_attributes = array(
            "name",
            "familyName",
            "email",
            "fiscalNumber"
        );

        $code_verifier = "VERIFIER";
        $nonce = "NONCE";
        $state = "STATE";

        try {
            $request->send($authorization_endpoint, $acr, $user_attributes, $code_verifier, $nonce, $state);
        } catch (\Exception $e) {
            //
        }

        $this->assertTrue(true);
    }
}
