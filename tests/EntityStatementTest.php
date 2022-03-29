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
        $config = json_decode(file_get_contents(__DIR__ . '/../config/config.json'));
        $metadata = EntityStatement::makeFromConfig($config);
        $this->assertNotEmpty($metadata, "EntityStatement cannot be empty");
    }

    /**
     * @covers SPID_CIE_OIDC_PHP\Federation\EntityStatement::applyPolicyModifierValue
     */
    public function test_applyPolicyModifierValue()
    {
        $es = new EntityStatement();
        $config = json_decode(json_encode(array(
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
        $es = new EntityStatement();
        $config = json_decode(json_encode(array(
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
