<?php

use PHPUnit\Framework\TestCase as TestCase;
use SPID_CIE_OIDC_PHP\OIDC\OP\CertsEndpoint;
use SPID_CIE_OIDC_PHP\OIDC\OP\Database as OP_Database;

/**
 * @covers SPID_CIE_OIDC_PHP\OIDC\OP\CertsEndpoint
 */
class CertsEndpointTest extends TestCase
{
    /**
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\CertsEndpoint::process
     * @runInSeparateProcess
     */
    public function test_process()
    {
        $config = json_decode(file_get_contents(__DIR__ . '/../config_sample/config.json'), true);
        $config['op_proxy_cert_public'] = __DIR__ . '/../cert_sample/op.crt';
        $database = new OP_Database(__DIR__ . '/tests.sqlite');
        $endpoint = new CertsEndpoint($config, $database);

        $this->expectOutputString('{"kid":"6a7fccfd4f67d65f7c90e902ee59baa9cf52a06756aa3c82c4295aed5e36be85","kty":"RSA","n":"1m22RXwZsh3UcUiXq-z-TZRY7Scn4kRLjTwxX0mk2BFwf-nDeqVK_joZaxOJRjIj9GWLjUFoBevzoKdZw3FZyUXFcjx_drKfNllzBYLBHe6w9YnUy2yPtFEzdsrD8dSo8LZq8odl9J7c5c7WQrQGEx5TZ7FMsqROEctbYY2912spOYLH2e8xmqYUKJEGXY5JXnjUVXcoSPqNH3T0KCHMajds4YZKMnPPh0ktTn4a4t3bmC4tzHGdCYzWsENn1_45z_z69RE_SylVZEuvjjppQr1JKivFsY2R3cBUia4Fy6BFAPO5pWtclKHyv0FyD-jYqBSohBBa2-3Cir5KrqJUMaMdLCwVyQitSgZa53QU1eTOYoJ-5y1U5FEF4ZC24j5owyEy9XvzXlHXkuTibTpl8Ppls7zwTNuwEvy0MeVaACsb2ASbEQaAZZvyXF7nfUBlzaCtHp3fp53lh-nNSlHAmv-zahfjUI5fWTSA7iOyehJXHPGm3zQpnIiRzGUmyrUD","e":"AQAB","x5c":"MIIE0TCCAzmgAwIBAgIUY5393aBcaUCMpsXFHuFYKed1AvkwDQYJKoZIhvcNAQELBQAwgZUxHjAcBgNVBAoMFU5hbWUgb2YgUmVseWluZyBQYXJ0eTEeMBwGA1UEAwwVTmFtZSBvZiBSZWx5aW5nIFBhcnR5MRowGAYDVQRTDBFodHRwczovL2xvY2FsaG9zdDEVMBMGA1UEYQwMUEE6SVQtY19iNTE5MQswCQYDVQQGEwJJVDETMBEGA1UEBwwKQ2FtcG9iYXNzbzAeFw0yMjAzMjIxNzM3NTdaFw0yNDAzMjExNzM3NTdaMIGVMR4wHAYDVQQKDBVOYW1lIG9mIFJlbHlpbmcgUGFydHkxHjAcBgNVBAMMFU5hbWUgb2YgUmVseWluZyBQYXJ0eTEaMBgGA1UEUwwRaHR0cHM6Ly9sb2NhbGhvc3QxFTATBgNVBGEMDFBBOklULWNfYjUxOTELMAkGA1UEBhMCSVQxEzARBgNVBAcMCkNhbXBvYmFzc28wggGiMA0GCSqGSIb3DQEBAQUAA4IBjwAwggGKAoIBgQDWbbZFfBmyHdRxSJer7P5NlFjtJyfiREuNPDFfSaTYEXB\/6cN6pUr+OhlrE4lGMiP0ZYuNQWgF6\/Ogp1nDcVnJRcVyPH92sp82WXMFgsEd7rD1idTLbI+0UTN2ysPx1Kjwtmryh2X0ntzlztZCtAYTHlNnsUyypE4Ry1thjb3Xayk5gsfZ7zGaphQokQZdjkleeNRVdyhI+o0fdPQoIcxqN2zhhkoyc8+HSS1Ofhri3duYLi3McZ0JjNawQ2fX\/jnP\/Pr1ET9LKVVkS6+OOmlCvUkqK8WxjZHdwFSJrgXLoEUA87mla1yUofK\/QXIP6NioFKiEEFrb7cKKvkquolQxox0sLBXJCK1KBlrndBTV5M5ign7nLVTkUQXhkLbiPmjDITL1e\/NeUdeS5OJtOmXw+mWzvPBM27AS\/LQx5VoAKxvYBJsRBoBlm\/JcXud9QGXNoK0end+nneWH6c1KUcCa\/7NqF+NQjl9ZNIDuI7J6Elcc8abfNCmciJHMZSbKtQMCAwEAAaMXMBUwEwYDVR0gBAwwCjAIBgYrTBAEAgEwDQYJKoZIhvcNAQELBQADggGBACYSJaRHT2olivXXB93YLHG8fNuW1IdCzmXSSWslv9usrpsfpBB\/jFyIKXRFXmfSeSOAdhA8vc3uYhX4zcZPy4TUToH9k9Fj1Wc66XLqI8TpQOGN7X9b8H+uaGZpGHpfK0WpAovnRatx4lv\/SRdPzBczHM8v5Od3nDfmBZN+AYJY\/kqK5HXzWkRWrJrrNw\/8qbXkxiEI+ePQtcG9iOQRF\/9ARsP2hiPkqNqLNc9fWZURm1EQW\/1BWv2yDtlaG1dxkSwV8F7hodOdR6ZwUsLM8UZ4mBwfBVMZTjKtkVDmw4EhuzqLjxynRNLemtzQWmrjErs1I8Gf9hqi6rlt01piw8tNmv6CpnxOudHFAijDy\/s2F5sRxmNFYp9lVEQllbmyNcypJaZ8Jmr0GB5jAg87MWL\/bWhC1zrabuIunfSUhYXfV+81fdGuQVhKakCvHJKZC0yNBO\/qPydnVscPB3cpAhcQyObKpR9zoBr1KhDoToIWvItdStvoME5yd2hojeW8Ig==","x5t":"Vwh6__XS7Njdj5P_qarFwUQ7FMY","x5t#256":"pxAUjUy9eAG9XtQDtDQvN59IO8kIHRY_OP4QcosGJME","use":"sig"}');
        $endpoint->process();
    }
}
