<?php

use PHPUnit\Framework\TestCase as TestCase;
use SPID_CIE_OIDC_PHP\Federation\EntityStatement;

/**
 * @covers SPID_CIE_OIDC_PHP\Federation\EntityStatement
 */
class EntityStatementTest extends TestCase
{
    /**
     * @covers SPID_CIE_OIDC_PHP\Federation\EntityStatement::makeFromConfig
     */
    public function test_makeFromConfig()
    {
        $config = json_decode(file_get_contents(__DIR__ . '/../config/config.json'), true);
        $config = $config['rp_proxy_clients']['default'];
        $metadata = EntityStatement::makeFromConfig($config);
        $this->assertNotEmpty($metadata, "EntityStatement cannot be empty");
    }

    /**
     * @covers SPID_CIE_OIDC_PHP\Federation\EntityStatement::applyPolicyModifierValue
     */
    public function test_applyPolicyModifierValue()
    {
        $es = new EntityStatement(null, "https://iss");
        $config = json_decode(json_encode(array(
            "iss" => "https://iss",
           "metadata" => array(
               "openid_provider" => array(
                   "subject_types_supported" => array(
                       "pairwise",
                       "public"
                   )
               )
           )
        )));
        $es->initFromObject($config);

        $this->assertEquals($config, $es->getPayload());

        $method = $this->getPrivateMethod('EntityStatement', 'applyPolicyModifierValue');

        $method->invokeArgs($es, array(
           'openid_provider',
           'subject_types_supported',
           array('pairwise')
        ));

        $new_config = json_decode(json_encode(array(
            "iss" => "https://iss",
           "metadata" => array(
               "openid_provider" => array(
                   "subject_types_supported" => array(
                       "pairwise"
                   )
               )
           )
        )));

        $this->assertNotEquals($config, $es->getPayload());
        $this->assertEquals($new_config, $es->getPayload());
    }

    /**
     * @covers SPID_CIE_OIDC_PHP\Federation\EntityStatement::applyPolicyModifierAdd
     */
    public function test_applyPolicyModifierAdd()
    {
        $es = new EntityStatement(null, "https://iss");
        $config = json_decode(json_encode(array(
            "iss" => "https://iss",
           "metadata" => array(
               "openid_provider" => array(
                   "subject_types_supported" => array(
                       "pairwise"
                   )
               )
           )
        )));
        $es->initFromObject($config);

        $this->assertEquals($config, $es->getPayload());

        $method = $this->getPrivateMethod('EntityStatement', 'applyPolicyModifierAdd');

        $method->invokeArgs($es, array(
           'openid_provider',
           'subject_types_supported',
           array('public')
        ));

        $new_config = json_decode(json_encode(array(
            "iss" => "https://iss",
           "metadata" => array(
               "openid_provider" => array(
                   "subject_types_supported" => array(
                       "pairwise", "public"
                   )
               )
           )
        )));

        $this->assertNotEquals($config, $es->getPayload());
        $this->assertEquals($new_config, $es->getPayload());
    }

    /**
     * @covers SPID_CIE_OIDC_PHP\Federation\EntityStatement::applyPolicyModifierDefault
     */
    public function test_applyPolicyModifierDefault()
    {
        $es = new EntityStatement(null, "https://iss");
        $config = json_decode(json_encode(array(
            "iss" => "https://iss",
           "metadata" => array(
               "openid_provider" => array(
                   "logo_uri" => null,
                   "organization_name" => "Organization Name",
                   "op_policy_uri" => ""
               )
           )
        )));
        $es->initFromObject($config);

        $this->assertEquals($config, $es->getPayload());

        $method = $this->getPrivateMethod('EntityStatement', 'applyPolicyModifierDefault');

        $method->invokeArgs($es, array(
           'openid_provider',
           'logo_uri',
           'https://logo_default'
        ));

        $method->invokeArgs($es, array(
           'openid_provider',
           'organization_name',
           'The organization name should not be overwrited'
        ));

        $method->invokeArgs($es, array(
           'openid_provider',
           'op_policy_uri',
           'https://policy_default'
        ));

        $new_config = json_decode(json_encode(array(
            "iss" => "https://iss",
           "metadata" => array(
               "openid_provider" => array(
                   "logo_uri" => "https://logo_default",
                   "organization_name" => "Organization Name",
                   "op_policy_uri" => "https://policy_default"
               )
           )
        )));

        $this->assertNotEquals($config, $es->getPayload());
        $this->assertEquals($new_config, $es->getPayload());
    }

    /**
     * @covers SPID_CIE_OIDC_PHP\Federation\EntityStatement::applyPolicyModifierOneOf
     */
    public function test_applyPolicyModifierOneOf()
    {
        $config1 = json_decode(json_encode(array(
            "iss" => "https://iss",
           "metadata" => array(
               "openid_provider" => array(
                   "id_token_signing_alg" => "ES384"
               )
           )
        )));

        $config2 = json_decode(json_encode(array(
            "iss" => "https://iss",
           "metadata" => array(
               "openid_provider" => array(
                   "id_token_signing_alg" => "ES256"
               )
           )
        )));

        $method = $this->getPrivateMethod('EntityStatement', 'applyPolicyModifierOneOf');

        $es1 = new EntityStatement(null, "https://iss");
        $es1->initFromObject($config1);
        $this->assertEquals($config1, $es1->getPayload());

        try {
            $method->invokeArgs($es1, array(
                'openid_provider',
                'id_token_signing_alg',
                ['ES256', 'ES384']
            ));
        } catch (\Exception $e) {
            $this->fail("Must not be throw exception");
        }

        $es2 = new EntityStatement(null, "https://iss");
        $es2->initFromObject($config2);
        $this->assertEquals($config2, $es2->getPayload());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Failed trust policy (id_token_signing_alg must be one of [\"ES384\",\"ES512\"])");

        $method->invokeArgs($es2, array(
           'openid_provider',
           'id_token_signing_alg',
           ['ES384', 'ES512']
        ));
    }

    /**
     * @covers SPID_CIE_OIDC_PHP\Federation\EntityStatement::applyPolicyModifierSubsetOf
     */
    public function test_applyPolicyModifierSubsetOf()
    {
        $config1 = json_decode(json_encode(array(
            "iss" => "https://iss",
           "metadata" => array(
               "openid_provider" => array(
                   "token_endpoint_auth_signing_alg_values_supported" => array(
                       "ES256",
                       "ES384",
                       "ES512"
                   )
               )
           )
        )));

        $config2 = json_decode(json_encode(array(
            "iss" => "https://iss",
           "metadata" => array(
               "openid_provider" => array(
                   "token_endpoint_auth_signing_alg_values_supported" => array(
                       "RS256",
                       "RS384",
                       "RS512",
                       "ES256",
                       "ES384",
                       "ES512"
                   )
               )
           )
        )));

        $method = $this->getPrivateMethod('EntityStatement', 'applyPolicyModifierSubsetOf');

        $es1 = new EntityStatement(null, "https://iss");
        $es1->initFromObject($config1);
        $this->assertEquals($config1, $es1->getPayload());

        try {
            $method->invokeArgs($es1, array(
                'openid_provider',
                'token_endpoint_auth_signing_alg_values_supported',
                ["RS512", "ES256", "ES384", "ES512"]
            ));
        } catch (\Exception $e) {
            $this->fail("Must not be throw exception");
        }

        $es2 = new EntityStatement(null, "https://iss");
        $es2->initFromObject($config2);
        $this->assertEquals($config2, $es2->getPayload());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Failed trust policy (token_endpoint_auth_signing_alg_values_supported must be subset of [\"RS512\",\"ES256\",\"ES384\",\"ES512\"])");

        $method->invokeArgs($es2, array(
           'openid_provider',
           'token_endpoint_auth_signing_alg_values_supported',
           ["RS512", "ES256", "ES384", "ES512"]
        ));
    }

    /**
     * @covers SPID_CIE_OIDC_PHP\Federation\EntityStatement::applyPolicyModifierSupersetOf
     */
    public function test_applyPolicyModifierSupersetOf()
    {
        $config1 = json_decode(json_encode(array(
            "iss" => "https://iss",
           "metadata" => array(
               "openid_provider" => array(
                   "token_endpoint_auth_signing_alg_values_supported" => array(
                       "RS256",
                       "RS384",
                       "RS512",
                       "ES256",
                       "ES384",
                       "ES512"
                   )
               )
           )
        )));

        $config2 = json_decode(json_encode(array(
            "iss" => "https://iss",
           "metadata" => array(
               "openid_provider" => array(
                   "token_endpoint_auth_signing_alg_values_supported" => array(
                       "ES256",
                       "ES384",
                       "ES512"
                   )
               )
           )
        )));

        $method = $this->getPrivateMethod('EntityStatement', 'applyPolicyModifierSupersetOf');

        $es1 = new EntityStatement(null, "https://iss");
        $es1->initFromObject($config1);
        $this->assertEquals($config1, $es1->getPayload());

        try {
            $method->invokeArgs($es1, array(
                'openid_provider',
                'token_endpoint_auth_signing_alg_values_supported',
                ["RS512", "ES256", "ES384", "ES512"]
            ));
        } catch (\Exception $e) {
            $this->fail("Must not be throw exception");
        }

        $es2 = new EntityStatement(null, "https://iss");
        $es2->initFromObject($config2);
        $this->assertEquals($config2, $es2->getPayload());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Failed trust policy (token_endpoint_auth_signing_alg_values_supported must be superset of [\"RS512\",\"ES256\",\"ES384\",\"ES512\"])");

        $method->invokeArgs($es2, array(
           'openid_provider',
           'token_endpoint_auth_signing_alg_values_supported',
           ["RS512", "ES256", "ES384", "ES512"]
        ));
    }

    /**
     * @covers SPID_CIE_OIDC_PHP\Federation\EntityStatement::applyPolicyModifierEssential
     */
    public function test_applyPolicyModifierEssential()
    {
        $config = json_decode(json_encode(array(
            "iss" => "https://iss",
           "metadata" => array(
               "openid_provider" => array(
                   "authorization_endpoint" => ""
               )
           )
        )));

        $method = $this->getPrivateMethod('EntityStatement', 'applyPolicyModifierEssential');

        $es = new EntityStatement(null, "https://iss");
        $es->initFromObject($config);
        $this->assertEquals($config, $es->getPayload());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Failed trust policy (authorization_endpoint must have a value)");

        $method->invokeArgs($es, array(
           'openid_provider',
           'authorization_endpoint',
           true
        ));
    }



    /**
     * getPrivateMethod
     *
     * @param string $className
     * @param string $methodName
     * @return ReflectionMethod
     */
    public function getPrivateMethod(string $className, string $methodName)
    {
        $reflector = new ReflectionClass('\\SPID_CIE_OIDC_PHP\\Federation\\' . $className);
        $method = $reflector->getMethod($methodName);
        $method->setAccessible(true);
        return $method;
    }
}
