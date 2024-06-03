<?php

namespace SPID_CIE_OIDC_PHP\Federation;

use SPID_CIE_OIDC_PHP\Core\Util;
use SPID_CIE_OIDC_PHP\Core\JWT;
use SPID_CIE_OIDC_PHP\OIDC\RP\Database;

class ResolveEndpoint
{
    /**
     *  creates a new EntityStatement instance
     *
     * @param  string $sub    entity
     * @param  string $anchor Trust Anchor
     * @throws Exception
     * @return ResolveEndpoint
     */
    public function __construct($sub = null, $anchor = null)
    {
        if ($sub != null && $anchor != null) {
            $this->sub = $sub;
            $this->anchor = $anchor;
            //$this->payload = JWT::getJWSPayload($this->token);
            //$this->validate($token);
        }
    }
    public static function resolve($config, $db, $sub, $anchor)
    {

        $key = $config['cert_private_fed_sig'];
        $key_jwk = JWT::getJWKFromJSON(file_get_contents($key));

        $header = array(
            "typ" => "entity-statement+jwt",
            "alg" => "RS256",
            "kid" => $key_jwk->get('kid')
        );


        $jws = JWT::makeJWS($header, array("test"), $key_jwk);

        return $jws;
    }
}
