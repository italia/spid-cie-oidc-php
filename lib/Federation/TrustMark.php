<?php

/**
 * spid-cie-oidc-php
 * https://github.com/italia/spid-cie-oidc-php
 *
 * 2022 Michele D'Amico (damikael)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author  Michele D'Amico <michele.damico@linfaservice.it>
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */

namespace SPID_CIE_OIDC_PHP\Federation;

use SPID_CIE_OIDC_PHP\Core\Util;
use SPID_CIE_OIDC_PHP\Core\JWT;
use SPID_CIE_OIDC_PHP\OIDC\RP\Database;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer as JWSSerializer;
use Jose\Component\Signature\Algorithm\RS256;


/**
 *  Make TrustMark
 *
 *  [OpenID Connect Federation Trust Mark](https://openid.net/specs/openid-connect-federation-1_0-29.html#name-trust-marks)
 */
class TrustMark
{
    private array $config;
    private string $sub;
    private string $id;
    private string $organization_type;
    private array $id_code;
    private string $email;
    private string $organization_name;
    private $sa_profile;

    /**
     *  creates a new TrustMark instance
     *
     * @param  array    $config       base configuration
     * @param  string   $sub          sub of the trust mark
     * @param  string   $id           id of the trust mark
     * @throws Exception
     * @return TrustMark
     */
    public function __construct(array $config, string $sub, string $id, string $organization_type, array $id_code, string $email, string $organization_name, $sa_profile)
    {
        $this->config = $config;
        $this->sub = $sub;
        $this->id = $id;
        $this->organization_type = $organization_type;
        $this->id_code = $id_code; 
        $this->email = $email;
        $this->organization_name = $organization_name;
        $this->sa_profile = $sa_profile ?? null;
    }

    /**
     *  make the trust mark
     *
     * @throws             Exception
     * @return             mixed
     * @codeCoverageIgnore
     */
    public function makeJwt() {

        $iat        = new \DateTimeImmutable();
        $jwk_pem    = $this->config['cert_private_fed'];

        $data = [
            'iss' => $this->config['client_id'],
            'sub' => $this->sub,
            'id' => $this->id,
            'iat' => strtotime("-2 seconds"),
            'logo_uri' => $this->config['client_id'] . '/tm/' . base64_encode($this->id) . '.png',
            'exp' => strtotime("+1 years"),
            'ref' => $this->config['client_id'] . '/tm/' . base64_encode($this->id) . '.txt',
            'organization_type' => $this->organization_type,
            'id_code' => $this->id_code,
            'email' => $this->email,
            'organization_name' => $this->organization_name,
        ]; 

        if($this->sa_profile) $data['sa_profile'] = $this->sa_profile;

        $algorithmManager = new AlgorithmManager([new RS256()]);
        $jwk = JWT::getKeyJWK($jwk_pem);
        $jwsBuilder = new JWSBuilder($algorithmManager);
        $jws = $jwsBuilder
            ->create()
            ->withPayload(json_encode($data))
            ->addSignature($jwk, ['alg' => 'RS256'])
            ->build();

        $serializer = new JWSSerializer();
        $trustmark = $serializer->serialize($jws, 0);

        return $trustmark;
    }
}
