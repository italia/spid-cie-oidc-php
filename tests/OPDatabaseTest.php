<?php

use PHPUnit\Framework\TestCase as TestCase;
use SPID_CIE_OIDC_PHP\OIDC\OP\Database;

/**
 * @covers SPID_CIE_OIDC_PHP\OIDC\OP\Database
 */
class OPDatabaseTest extends TestCase
{
    /**
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\Database::createRequest
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\Database::updateRequest
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\Database::getRequest
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\Database::getRequestByCode
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\Database::getRequestByIdToken
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\Database::getRequestByClientID
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\Database::createAuthorizationCode
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\Database::checkAuthorizationCode
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\Database::saveIdToken
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\Database::checkIdToken
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\Database::createAccessToken
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\Database::saveAccessToken
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\Database::checkAccessToken
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\Database::saveUserinfo
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\Database::getUserinfo
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\Database::deleteRequest
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\Database::query
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\Database::exec
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\Database::dump
     * @covers SPID_CIE_OIDC_PHP\OIDC\OP\Database::log
     * @runInSeparateProcess
     */
    public function test_OPDatabase()
    {
        // clean old tests
        if (file_exists("tests/tests.sqlite")) {
            unlink("tests/tests.sqlite");
        }

        $database = new Database("tests/tests.sqlite");

        $client_id = "client_id";
        $redirect_uri = "redirect_uri";
        $state = "state";
        $nonce = "nonce";
        $req_id = $database->createRequest($client_id, $redirect_uri, $state, $nonce);

        $this->assertNotNull($req_id);
        $this->assertNotEmpty($req_id);

        $req_id2 = $database->updateRequest($client_id, $redirect_uri, $state, $nonce);

        $this->assertEquals($req_id, $req_id2);

        $client_id = "client_id_not_present";

        $req_id3 = $database->updateRequest($client_id, $redirect_uri, $state, $nonce);

        $this->assertNull($req_id3);

        $redirect_uri = "redirect_uri_not_present";

        $req_id4 = $database->updateRequest($client_id, $redirect_uri, $state, $nonce);

        $this->assertNull($req_id4);

        $request = $database->getRequest($req_id2);

        $this->assertEquals($request['client_id'], "client_id");
        $this->assertEquals($request['redirect_uri'], "redirect_uri");
        $this->assertEquals($request['state'], "state");
        $this->assertEquals($request['nonce'], "nonce");

        $request2 = $database->getRequestByClientID('client_id')[0];

        $this->assertEquals($request2['client_id'], "client_id");
        $this->assertEquals($request2['redirect_uri'], "redirect_uri");
        $this->assertEquals($request2['state'], "state");
        $this->assertEquals($request2['nonce'], "nonce");

        $code = $database->createAuthorizationCode($req_id);

        $this->assertNotNull($code);
        $this->assertNotEmpty($code);

        $request3 = $database->getRequestByCode($code);

        $this->assertEquals($request3['client_id'], "client_id");
        $this->assertEquals($request3['redirect_uri'], "redirect_uri");
        $this->assertEquals($request3['state'], "state");
        $this->assertEquals($request3['nonce'], "nonce");

        $codeEsists = $database->checkAuthorizationCode("client_id", "redirect_uri", $code);

        $this->assertTrue($codeEsists);

        $id_token = "IDToken";

        $database->saveIdToken($req_id, $id_token);

        $idtokenExists = $database->checkIdToken($id_token);

        $this->assertTrue($idtokenExists);

        $request4 = $database->getRequestByIdToken($id_token);

        $this->assertEquals($req_id, $request4['req_id']);

        $access_token = $database->createAccessToken($code);

        $accessTokenExists = $database->checkAccessToken($access_token);

        $this->assertTrue($accessTokenExists);

        $access_token2 = "ACCESSTOKEN";

        $database->saveAccessToken($req_id, $access_token2);

        $accessToken2Exists = $database->checkAccessToken($access_token2);

        $this->assertTrue($accessToken2Exists);

        $userinfo = array(
            "name" => "NAME",
            "familyName" => "FAMILY NAME"
        );

        $database->saveUserinfo($req_id, $userinfo);

        $userinfo2 = $database->getUserinfo($access_token2);

        $this->assertEquals($userinfo2->name, "NAME");
        $this->assertEquals($userinfo2->familyName, "FAMILY NAME");

        $database->deleteRequest($req_id);

        $accessToken2Exists = $database->checkAccessToken($access_token2);

        $this->assertFalse($accessToken2Exists);

        $dump = $database->dump('token');

        $this->assertNotNull($dump);

        try {
            $log = $database->log('context', 'tag', '$value');
        } catch (\Exception $e) {
            $this->fail();
        }
    }
}
