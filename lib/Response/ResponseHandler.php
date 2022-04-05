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
 * @author     Michele D'Amico <michele.damico@linfaservice.it>
 * @license    http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */

namespace SPID_CIE_OIDC_PHP\Response;

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use Jose\Component\Signature\Serializer\CompactSerializer as JWSSerializer;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\JWSLoader;
use Jose\Component\Encryption\Algorithm\KeyEncryption\A256KW;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256CBCHS512;
use Jose\Component\Encryption\Compression\CompressionMethodManager;
use Jose\Component\Encryption\Compression\Deflate;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Encryption\Serializer\JWESerializerManager;
use Jose\Component\Encryption\Serializer\CompactSerializer as JWESerializer;
use Jose\Component\Encryption\JWEDecrypter;

/**
 *  Superclass for all classes that codes how to send a response after successfull authentication
 *
 *  to enable a ResponseHandler extended class set the parameter rp_response_handler to the name of the class (Es. "&bsol;SPID_CIE_OIDC_PHP&bsol;Response&bsol;MyResponse") into the config.json file
 */
abstract class ResponseHandler
{
    /**
     *  creates a new <ResponseHandler> instance
     *
     * @param array $config base configuration
     * @throws Exception
     * @return ResponseHandler
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     *  send the response
     *
     * @param string $redirect_uri URL where send te response
     * @param object $data data within the response
     * @param string $state state to be returned
     * @throws Exception
     */
    abstract public function sendResponse(string $redirect_uri, object $data, string $state);
}
