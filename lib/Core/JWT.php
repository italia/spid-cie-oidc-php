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

namespace SPID_CIE_OIDC_PHP\Core;

use Jose\Component\Core\JWK;
use Jose\Component\Core\JWKSet;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSLoader;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use Jose\Component\Signature\Serializer\CompactSerializer as JWSSerializer;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\JWELoader;
use Jose\Component\Encryption\Serializer\JWESerializerManager;
use Jose\Component\Encryption\Serializer\CompactSerializer as JWESerializer;
use Jose\Component\Encryption\Compression\CompressionMethodManager;
use Jose\Component\Encryption\Compression\Deflate;
// Signature Algorithms - HMAC with SHA-2 Functions
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\Algorithm\HS384;
use Jose\Component\Signature\Algorithm\HS512;
// Signature Algorithms - RSASSA-PKCS1 v1_5
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\Algorithm\RS384;
use Jose\Component\Signature\Algorithm\RS512;
// Signature Algorithms - RSASSA-PSS
use Jose\Component\Signature\Algorithm\PS256;
use Jose\Component\Signature\Algorithm\PS384;
use Jose\Component\Signature\Algorithm\PS512;
// Signature Algorithms - Elliptic Curve Digital Signature Algorithm (ECDSA)
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\Algorithm\ES384;
use Jose\Component\Signature\Algorithm\ES512;
// Key Encryption Algorithm
use Jose\Component\Encryption\Algorithm\KeyEncryption\A256KW;
use Jose\Component\Encryption\Algorithm\KeyEncryption\A256GCMKW;
use Jose\Component\Encryption\Algorithm\KeyEncryption\PBES2HS256A128KW;
use Jose\Component\Encryption\Algorithm\KeyEncryption\PBES2HS384A192KW;
use Jose\Component\Encryption\Algorithm\KeyEncryption\PBES2HS512A256KW;
use Jose\Component\Encryption\Algorithm\KeyEncryption\RSAOAEP;
use Jose\Component\Encryption\Algorithm\KeyEncryption\RSAOAEP256;
// Content Encryption Algorithm
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256GCM;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A128CBCHS256;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A192CBCHS384;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256CBCHS512;

const DEFAULT_TOKEN_EXPIRATION_TIME = 1200;

/**
 *  Provides functions to create and parse JWS and JWE
 *
 *  * [JSON Web Signature (JWS) - RFC7515](https://datatracker.ietf.org/doc/html/rfc7515)
 *  * [JSON Web Encryption (JWE) - RFC7516](https://datatracker.ietf.org/doc/html/rfc7516)
 */
class JWT
{
    /**
     *  get a private key JWK object from a private key PEM file
     *
     * @param string $file path of the private key PEM file
     * @throws Exception
     * @return object JWK object
     */
    public static function getKeyJWK(string $file)
    {
        $jwk = JWKFactory::createFromKeyFile($file);
        return $jwk;
    }

    /**
     *  get a JWK object from JSON string
     *
     * @param string $json JSON string of the JWK
     * @throws Exception
     * @return object JWK object
     */
    public static function getJWKFromJSON(string $json)
    {
        $jwk_obj = JWK::createFromJson($json);
        return $jwk_obj;
    }

    /**
     *  get a public cert JWK object from a public cert PEM file
     *
     * @param string $file path of the public cert PEM file
     * @param string $use the use of certificate [sig|enc]
     * @throws Exception
     * @return object JWK object
     */
    public static function getCertificateJWK(string $file, string $use = 'sig')
    {
        $jwk_obj = JWKFactory::createFromCertificateFile($file, ['use' => $use]);

        // fix \n json_encode issue
        $x5c    = $jwk_obj->get('x5c')[0];
        $x5c    = preg_replace("/\s+/", "", $x5c);

        $x5cData = openssl_x509_parse(file_get_contents($file), false);
        $organizationIdentifier = $x5cData['issuer']['organizationIdentifier'];
        $serialNumber = $x5cData['serialNumber'];
        $kid = hash('sha256', $organizationIdentifier . '.' . $serialNumber);

        $jwk = array(
            'kid'       => $kid,
            'kty'       => $jwk_obj->get('kty'),
            'n'         => $jwk_obj->get('n'),
            'e'         => $jwk_obj->get('e'),
            'x5c'       => $x5c,
            'x5t'       => $jwk_obj->get('x5t'),
            'x5t#256'   => $jwk_obj->get('x5t#256'),
            'use'       => $jwk_obj->get('use')
        );

        return $jwk;
    }

    /**
     *  get a public cert JWK object from an object
     *
     * @param array $values array containing JWK values
     * @throws Exception
     * @return object JWK object
     */
    public static function getJWKSFromValues(array $values)
    {
        $jwks_obj = JWKSet::createFromKeyData($values);
        return $jwks_obj;
    }

    /**
     *  create a signed JWT (JWS) from given values
     *
     * @param array $header associative array for header
     * @param array $payload associative array for payload
     * @param object $jwk JWK object to use for signing JWS
     * @throws Exception
     * @return string of the JWS token
     */
    public static function makeJWS(array $header, array $payload, object $jwk): string
    {
        //$jwk = self::getKeyJWK($file);
        $algorithmManager = JWT::getSigAlgManager($header['alg']);
        $jwsBuilder = new JWSBuilder($algorithmManager);
        $jws = $jwsBuilder
            ->create()
            ->withPayload(json_encode($payload))
            ->addSignature($jwk, $header)
            ->build();

        $serializer = new JWSSerializer();
        $token = $serializer->serialize($jws, 0);
        //$token = Util::fixPadding($token);
        return $token;
    }

    /**
     *  get the payload of the JWS token
     *
     * @param string $token JWS token
     * @throws Exception
     * @return object payload string of the JWS token
     */
    public static function getJWSPayload(string $token)
    {
        $serializerManager = new JWSSerializerManager([ new JWSSerializer() ]);
        $jws = $serializerManager->unserialize($token);
        $payload = json_decode($jws->getPayload());
        return $payload;
    }

    /**
     *  verify the signature of the JWS token
     *
     * @param string $token JWS token
     * @param object $jwks JWK SET to which verify the signature of the token
     * @throws Exception
     * @return boolean true if the signature is verified
     */
    public static function isSignatureVerified(string $token, object $jwks_obj)
    {
        $jwks = JWKSet::createFromJson(json_encode($jwks_obj));
        $algorithmManager = JWT::getSigAlgManager();
        $jwsVerifier = new JWSVerifier($algorithmManager);
        $serializerManager = new JWSSerializerManager([ new JWSSerializer() ]);
        $jws = $serializerManager->unserialize($token);

        $isSignatureVerified = $jwsVerifier->verifyWithKeySet($jws, $jwks, 0);
        return $isSignatureVerified;
    }

    /**
     *  verify if token is not expired and other stuff...
     *
     * @param string $token JWS token
     * @throws Exception
     * @return boolean true if the the token is valid
     */
    public static function isValid(string $token)
    {

        $isValid = true;
        $payload = self::getJWSPayload($token);

        // max clock skew 5min
        if ($payload->iat >= strtotime('+5 minutes')) {
            $isValid = false;
        }

        // max clock skew 5min
        if ($payload->exp <= strtotime('+5 minutes')) {
            $isValid = false;
        }

        // add other validations here

        return $isValid;
    }

    /**
     *  encrypt the token and return the JWE token
     *
     * @param array $data the data to be encrypted
     * @param string $file path to PEM file of the public key to wich encrypt the JWE
     * @throws Exception
     * @return string the JWE token
     */
    public static function encrypt(array $data, string $file)
    {
        $payload = json_encode($data);
        $jwk_obj = JWKFactory::createFromCertificateFile($file, ['use' => 'enc']);

        // The key encryption algorithm manager with the A256KW algorithm.
        $keyEncryptionAlgorithmManager = self::getKeyEncAlgManager();

        // The content encryption algorithm manager with the A256CBC-HS256 algorithm.
        $contentEncryptionAlgorithmManager = self::getContentEncAlgManager();

        // The compression method manager with the DEF (Deflate) method.
        $compressionMethodManager = new CompressionMethodManager([
           new Deflate(),
        ]);

        // We instantiate our JWE Builder.
        $jweBuilder = new JWEBuilder(
            $keyEncryptionAlgorithmManager,
            $contentEncryptionAlgorithmManager,
            $compressionMethodManager
        );

        $jwe = $jweBuilder
           ->create()
           ->withPayload($payload)
           ->withSharedProtectedHeader([
               'alg' => 'RSA-OAEP',
               'enc' => 'A256CBC-HS512',
               'zip' => 'DEF'
           ])
           ->addRecipient($jwk_obj)
           ->build();

        $serializer = new JWESerializer();
        $token = $serializer->serialize($jwe, 0);

        return $token;
    }


    /**
     *  descrypts the token and return the embedded JWS
     *
     * @param string $token the JWE token to be decrypted
     * @param string $file path to PEM file of the private key to wich decrypt the JWE
     * @throws Exception
     * @return object the decrypted JWS object inside the JWE
     */
    public static function decryptJWE(string $token, string $file)
    {

        $keyEncryptionAlgorithmManager = JWT::getKeyEncAlgManager();
        $contentEncryptionAlgorithmManager = JWT::getContentEncAlgManager();

        $compressionMethodManager = new CompressionMethodManager([
            new Deflate(),
        ]);

        $jweDecrypter = new JWEDecrypter(
            $keyEncryptionAlgorithmManager,
            $contentEncryptionAlgorithmManager,
            $compressionMethodManager
        );

        $serializerManager = new JWESerializerManager([
            new JWESerializer(),
        ]);

        $headerCheckerManager = null;

        $jweLoader = new JWELoader(
            $serializerManager,
            $jweDecrypter,
            $headerCheckerManager
        );

        $jwk = JWKFactory::createFromKeyFile($file);
        $jws = $jweLoader->loadAndDecryptWithKey($token, $jwk, $recipient);

        return $jws;
    }


    /**
     *  getSigAlgClassMap
     *
     * @throws Exception
     * @return object
     */
    private static function getSigAlgClassMap()
    {
        $algMap = json_decode(file_get_contents(__DIR__ . '/../../config/alg-sig.json'));
        return $algMap;
    }

    /**
     *  getKeyEncAlgClassMap
     *
     * @throws Exception
     * @return object
     */
    private static function getKeyEncAlgClassMap()
    {
        $algMap = json_decode(file_get_contents(__DIR__ . '/../../config/alg-key-enc.json'));
        return $algMap;
    }

    /**
     *  getContentEncAlgClassMap
     *
     * @throws Exception
     * @return object
     */
    private static function getContentEncAlgClassMap()
    {
        $algMap = json_decode(file_get_contents(__DIR__ . '/../../config/alg-content-enc.json'));
        return $algMap;
    }

    /**
     *  getAlgManager
     *
     *  if $alg is set to an algorithm string, return manager for that,
     *  else $alg is null, return manager for all supported algorithms
     *
     * @param object $algClassMap class map of available algorithms
     * @param string $alg algorithm to use or null
     * @throws Exception
     * @return object
     */
    private static function getAlgManager(object $algClassMap, string $alg = null)
    {
        $algList = array();
        if ($alg != null && property_exists($algClassMap, $alg)) {
            $algClass = $algClassMap->{$alg};
            $algList[] = new $algClass();
        } elseif ($alg != null && !property_exists($algClassMap, $alg)) {
            throw new \Exception("Algorithm not supported");
        } else {
            foreach ($algClassMap as $algClass) {
                $algList[] = new $algClass();
            }
        }

        $algorithmManager = new AlgorithmManager($algList);
        return $algorithmManager;
    }

    /**
     *  getSigAlgManager
     *
     * @param string $alg algorithm to use or null
     * @throws Exception
     * @return object
     */
    private static function getSigAlgManager(string $alg = null)
    {
        $algClassMap = JWT::getSigAlgClassMap();
        $algorithmManager = JWT::getAlgManager($algClassMap, $alg);
        return $algorithmManager;
    }

    /**
     *  getKeyEncAlgManager
     *
     * @param string $alg algorithm to use or null
     * @throws Exception
     * @return object
     */
    private static function getKeyEncAlgManager(string $alg = null)
    {
        $algClassMap = JWT::getKeyEncAlgClassMap();
        $algorithmManager = JWT::getAlgManager($algClassMap, $alg);
        return $algorithmManager;
    }

    /**
     *  getContentEncAlgManager
     *
     * @param string $alg algorithm to use or null
     * @throws Exception
     * @return object
     */
    private static function getContentEncAlgManager(string $alg = null)
    {
        $algClassMap = JWT::getContentEncAlgClassMap();
        $algorithmManager = JWT::getAlgManager($algClassMap, $alg);
        return $algorithmManager;
    }
}
