<?php

namespace SPID_CIE_OIDC_PHP\OIDC;

use SPID_CIE_OIDC_PHP\Core\JWT;
use GuzzleHttp\Client;

class UserinfoRequest
{
    public function __construct($config, $op_metadata)
    {
        $this->config = $config;
        $this->op_metadata = $op_metadata;
    }

    public function send($userinfo_endpoint, $access_token)
    {
        $client = new Client([
            'allow_redirects' => true,
            'timeout' => 15,
            'debug' => false,
            'http_errors' => false
        ]);

        $response = $client->get($userinfo_endpoint, ['headers' => [ 'Authorization' => 'Bearer ' . $access_token ]]);
        $code = $response->getStatusCode();
        if ($code != 200) {
            $reason = $response->getReasonPhrase();
            throw new \Exception($reason);
        }

        $jwe = $response->getBody()->getContents();

        $file_key = $this->config->rp_cert_private;
        $jws = JWT::decryptJWE($jwe, $file_key);

        $file_cert = $this->config->rp_cert_public;
        $decrypted = $jws->getPayload();
        $decrypted = str_replace("\"", "", $decrypted);

        // verify response against OP public key
        // TODO : select key by kid
        $key = $this->op_metadata->jwks->keys[0];
        $jwk = JWT::getJWKFromJSON(json_encode($key));
        if (!JWT::isVerified($decrypted, $jwk)) {
            throw new \Exception("Impossibile stabilire l'autenticità della risposta");
        }

        $payload = JWT::getJWSPayload($decrypted);

        return json_decode($payload);
    }
}
