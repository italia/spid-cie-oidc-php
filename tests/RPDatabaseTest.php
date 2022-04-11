<?php

use PHPUnit\Framework\TestCase as TestCase;
use SPID_CIE_OIDC_PHP\OIDC\RP\Database;

/**
 * @covers SPID_CIE_OIDC_PHP\OIDC\RP\Database
 */
class RPDatabaseTest extends TestCase
{
    /**
     * @covers SPID_CIE_OIDC_PHP\OIDC\RP\Database::saveToStore
     * @covers SPID_CIE_OIDC_PHP\OIDC\RP\Database::getFromStore
     * @covers SPID_CIE_OIDC_PHP\OIDC\RP\Database::getFromStoreByURL
     * @covers SPID_CIE_OIDC_PHP\OIDC\RP\Database::createRequest
     * @covers SPID_CIE_OIDC_PHP\OIDC\RP\Database::getRequest
     * @runInSeparateProcess
     */
    public function test_Store()
    {
        // clean old tests
        if (file_exists("tests/tests.sqlite")) {
            unlink("tests/tests.sqlite");
        }

        $database = new Database("tests/tests.sqlite");

        $type = 'openid-federation';
        $url = 'https://iss/.well-known/openid-federation';
        $object = array(
            "jti" => uniqid(),
            "iss" => "https://iss",
            "sub" => "https://sub",
            "aud" => "https://aud",
            "iat" => strtotime("now"),
            "exp" => strtotime("+24 hours"),
        );

        $iss = $object['iss'];
        $iat = $object['iat'];
        $exp = $object['exp'];

        $id = $database->saveToStore($iss, $type, $url, $iat, $exp, $object);
        $this->assertNotNull($id);

        $object2 = $database->getFromStore('https://iss_not_saved', $type);
        $this->assertNull($object2);

        $object3 = $database->getFromStore($iss, 'unknown_type');
        $this->assertNull($object3);

        $object4 = $database->getFromStore($iss, $type);
        $this->assertEquals((object) $object, (object) $object4);

        $object5 = array(
            "jti" => uniqid(),
            "iss" => "https://iss",
            "sub" => "https://sub",
            "aud" => "https://aud",
            "iat" => strtotime("now"),
            "exp" => strtotime("+48 hours"),
        );

        $database->saveToStore($iss, $type, $url, $iat, $exp, $object5);

        $object6 = $database->getFromStore($iss, $type);
        $this->assertEquals((object) $object6, (object) $object5);
        $this->assertNotEquals($object6, $object);

        $object7 = $database->getFromStoreByURL('fake_url');
        $this->assertNull($object7);

        $object8 = $database->getFromStoreByURL($url);
        $this->assertEquals((object) $object8, (object) $object5);
        $this->assertNotEquals($object8, $object);


        $ta_id = "http://trust-anchor.org/";
        $op_id = "http://provider.org/";
        $redirect_uri = "http://relying-party.org/redirect_uri";
        $state = "STATE";
        $acr = [2, 1];
        $user_attributes = ["fiscalNumber", "name", "familyName"];

        $req_id = $database->createRequest($ta_id, $op_id, $redirect_uri, $state, $acr, $user_attributes);
        $request = $database->getRequest($req_id);

        $this->assertEquals($request['ta_id'], $ta_id);
        $this->assertEquals($request['op_id'], $op_id);
        $this->assertEquals($request['redirect_uri'], $redirect_uri);
        $this->assertEquals($request['state'], $state);
        $this->assertEquals($request['acr'], $acr);
        $this->assertEquals($request['user_attributes'], $user_attributes);


        try {
            $log = $database->log('context', 'tag', '$value');
        } catch (\Exception $e) {
            $this->fail();
        }
    }
}
