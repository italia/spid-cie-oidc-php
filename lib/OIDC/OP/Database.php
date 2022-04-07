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

namespace SPID_CIE_OIDC_PHP\OIDC\OP;

use SPID_CIE_OIDC_PHP\Core\Util;

/**
 *  Provides functions to saves and retrieves data from a SQLite storage database for OP
 */
class Database
{
    /**
     *  creates a new Database instance
     *
     * @param string $db_file path of sqlite file
     * @throws Exception
     * @return Database
     */
    public function __construct($db_file)
    {
        $this->db = new \SQLite3($db_file);
        if (!$this->db) {
            die("Error while connecting to db.sqlite");
        }

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS token (
                req_id          INTEGER PRIMARY KEY AUTOINCREMENT,
                req_timestamp   DATETIME DEFAULT (datetime('now')) NOT NULL,
                client_id       STRING NOT NULL,
                redirect_uri    STRING NOT NULL,
                code            STRING UNIQUE,
                auth_timestamp  DATETIME,
                id_token        STRING UNIQUE,
                access_token    STRING UNIQUE,
                token_timestamp DATETIME,
                state           STRING,
                userinfo        STRING,
                nonce           STRING
            );

            CREATE TABLE IF NOT EXISTS log (
                timestamp       DATETIME DEFAULT (datetime('now')) NOT NULL,
                context         STRING,
                tag             STRING,
                value           STRING,
                severity        STRING
            );
        ");

        $this->db->exec("
            DELETE FROM token WHERE req_timestamp <= datetime('now', '-30 minutes');
            DELETE FROM log WHERE timestamp <= datetime('now', '-60 minutes');
        ");
    }

    /**
     *  creates a record from an authentication request
     *
     * @param string $client_id id of the client
     * @param string $redirect_uri URL to which return after authentication
     * @param string $state value of the state param sent with the request
     * @param string $nonce value of the nonce param sent with the request
     * @throws Exception
     * @return string the request id
     */
    public function createRequest(string $client_id, string $redirect_uri, string $state = '', string $nonce = '')
    {
        $code = uniqid();
        $stmt = $this->db->prepare("
            INSERT INTO token(client_id, redirect_uri, state, nonce) 
            VALUES(:client_id, :redirect_uri, :state, :nonce);
        ");
        $stmt->bindValue(':client_id', $client_id, SQLITE3_TEXT);
        $stmt->bindValue(':redirect_uri', $redirect_uri, SQLITE3_TEXT);
        $stmt->bindValue(':state', $state, SQLITE3_TEXT);
        $stmt->bindValue(':nonce', $nonce, SQLITE3_TEXT);
        $stmt->execute();
        $req_id = $this->db->lastInsertRowid();
        return $req_id;
    }

    /**
     *  updates a record of an authentication request
     *
     * @param string $client_id id of the client
     * @param string $redirect_uri URL to which return after authentication
     * @param string $state value of the state param sent with the request
     * @param string $nonce value of the nonce param sent with the request
     * @throws Exception
     * @return string the request id
     */
    public function updateRequest(string $client_id, string $redirect_uri, string $state = '', string $nonce = '')
    {
        $req_id = null;
        $result = $this->query("
            SELECT req_id FROM token 
            WHERE client_id=:client_id 
            AND redirect_uri=:redirect_uri
            AND req_timestamp > datetime('now', '-30 minutes')
            ORDER BY req_timestamp DESC
            LIMIT 1;
        ", array(
            ":client_id" => $client_id,
            ":redirect_uri" => $redirect_uri
        ));

        if (count($result) == 1) {
            $req_id = $result[0]['req_id'];
            $stmt = $this->db->prepare("
                UPDATE token 
                SET state=:state, nonce=:nonce
                WHERE req_id=:req_id;
            ");
            $stmt->bindValue(':state', $state, SQLITE3_TEXT);
            $stmt->bindValue(':nonce', $nonce, SQLITE3_TEXT);
            $stmt->bindValue(':req_id', $req_id, SQLITE3_TEXT);
            $stmt->execute();
        }
        return $req_id;
    }

    /**
     *  retrieve a record of an authentication request
     *
     * @param string $req_id id of the authentication request
     * @throws Exception
     * @return array the request
     */
    public function getRequest(string $req_id)
    {
        $result = $this->query(
            "
            SELECT client_id, redirect_uri, state, nonce FROM token
            WHERE req_id = :req_id;",
            array(":req_id" => $req_id)
        );

        return array(
            "client_id"     => $result[0]['client_id'],
            "redirect_uri"  => $result[0]['redirect_uri'],
            "state"         => $result[0]['state'],
            "nonce"         => $result[0]['nonce'],
        );
    }

    /**
     *  retrieve a record of an authentication request by authcode
     *
     * @param string $code the authcode of the authentication request
     * @throws Exception
     * @return array the request
     */
    public function getRequestByCode(string $code)
    {
        $result = $this->query(
            "
            SELECT req_id, client_id, redirect_uri, state, nonce FROM token
            WHERE code = :code;",
            array(":code" => $code)
        );

        return array(
            "req_id"        => $result[0]['req_id'],
            "client_id"     => $result[0]['client_id'],
            "redirect_uri"  => $result[0]['redirect_uri'],
            "state"         => $result[0]['state'],
            "nonce"         => $result[0]['nonce'],
        );
    }

    /**
     *  retrieve a record of an authentication request by id_token
     *
     * @param string $id_token the id_token of the authentication request
     * @throws Exception
     * @return array the request
     */
    public function getRequestByIdToken(string $id_token)
    {
        $result = $this->query(
            "
            SELECT req_id, client_id, redirect_uri, state, nonce FROM token
            WHERE id_token = :id_token;",
            array(":id_token" => $id_token)
        );

        return array(
            "req_id"        => $result[0]['req_id'],
            "client_id"     => $result[0]['client_id'],
            "redirect_uri"  => $result[0]['redirect_uri'],
            "state"         => $result[0]['state'],
            "nonce"         => $result[0]['nonce'],
        );
    }

    /**
     *  retrieve all records of authentication requests by client_id
     *
     * @param string $client_id the client_id
     * @throws Exception
     * @return array the requests
     */
    public function getRequestByClientID(string $client_id)
    {
        $result = $this->query(
            "
            SELECT req_id, client_id, redirect_uri, state, nonce FROM token
            WHERE client_id = :client_id;",
            array(":client_id" => $client_id)
        );

        return $result;
    }

    /**
     *  update a request record generating the authcode for the request
     *
     * @param string $req_id the request id
     * @throws Exception
     * @return string the generated authcode
     */
    public function createAuthorizationCode(string $req_id)
    {
        $code = uniqid();
        $stmt = $this->db->prepare("
            UPDATE token 
            SET code=:code, auth_timestamp=datetime('now')
            WHERE req_id=:req_id;
        ");
        $stmt->bindValue(':code', $code, SQLITE3_TEXT);
        $stmt->bindValue(':req_id', $req_id, SQLITE3_TEXT);
        $stmt->execute();
        return $code;
    }

    /**
     *  check if exists a request with the specified authorization code for that client_id and redirect_uri
     *
     * @param string $client_id the client id
     * @param string $redirect_uri the redirect uri
     * @param string $code the authroization code
     * @throws Exception
     * @return boolean true if the auth code exists
     */
    public function checkAuthorizationCode(string $client_id, string $redirect_uri, string $code)
    {
        $check = false;
        $result = $this->query("
            SELECT req_id FROM token 
            WHERE client_id=:client_id 
            AND redirect_uri=:redirect_uri
            AND code=:code;
        ", array(
            ":client_id" => $client_id,
            ":redirect_uri" => $redirect_uri,
            ":code" => $code
        ));

        if (count($result) == 1) {
            $check = true;
        }
        return $check;
    }

    /**
     *  save id_token for the request
     *
     * @param string $req_id the request id
     * @param string $id_token the id_token to save
     * @throws Exception
     */
    public function saveIdToken(string $req_id, string $id_token)
    {
        $this->exec(
            "UPDATE token SET id_token=:id_token WHERE req_id=:req_id",
            array(
                ":id_token" => $id_token,
                ":req_id" => $req_id
            )
        );
    }

    /**
     *  check if the id_token exists
     *
     * @param string $id_token the id_token
     * @throws Exception
     * @return boolean true if the id_token exists
     */
    public function checkIdToken(string $id_token)
    {
        $check = false;
        $result = $this->query("
            SELECT req_id FROM token 
            WHERE id_token=:id_token;
        ", array(
            ":id_token" => $id_token
        ));

        if (count($result) == 1) {
            $check = true;
        }
        return $check;
    }

    /**
     *  create a new access token for the request identified by authorization code
     *
     * @param string $code the authcode
     * @throws Exception
     * @return string the generated access_token
     */
    public function createAccessToken(string $code)
    {
        $access_token = uniqid();
        $stmt = $this->db->prepare("
            UPDATE token
            SET access_token=:access_token, token_timestamp=datetime('now')
            WHERE code=:code;
        ");
        $stmt->bindValue(':access_token', $access_token, SQLITE3_TEXT);
        $stmt->bindValue(':code', $code, SQLITE3_TEXT);
        $stmt->execute();
        return $access_token;
    }

    /**
     *  save the provided access_token for the request
     *
     * @param string $req_id the request id
     * @param string $access_token the access_token
     * @throws Exception
     */
    public function saveAccessToken(string $req_id, string $access_token)
    {
        $this->exec(
            "UPDATE token SET access_token=:access_token WHERE req_id=:req_id",
            array(
                ":access_token" => $access_token,
                ":req_id" => $req_id
            )
        );
    }

    /**
     *  check if the access_token exists
     *
     * @param string $access_token the access_token
     * @throws Exception
     * @return boolean true if the access_token exists
     */
    public function checkAccessToken(string $access_token)
    {
        $check = false;
        $result = $this->query("
            SELECT req_id FROM token 
            WHERE access_token=:access_token;
        ", array(
            ":access_token" => $access_token
        ));

        if (count($result) == 1) {
            $check = true;
        }
        return $check;
    }

    /**
     *  save user info for the request
     *
     * @param string $req_id the request id
     * @param array $userinfo the user info
     * @throws Exception
     */
    public function saveUserinfo(string $req_id, array $userinfo)
    {
        $this->exec(
            "UPDATE token SET userinfo=:userinfo WHERE req_id=:req_id",
            array(
                ":userinfo" => json_encode($userinfo),
                ":req_id" => $req_id
            )
        );
    }

    /**
     *  get user info for the access_token
     *
     * @param string $access_token the access_token
     * @return array $userinfo the user info
     * @throws Exception
     */
    public function getUserinfo(string $access_token)
    {
        $userinfo = $this->query(
            "SELECT userinfo FROM token WHERE access_token=:access_token",
            array(":access_token" => $access_token)
        );
        return json_decode($userinfo[0]['userinfo']);
    }

    /**
     *  delete the request
     *
     * @param string $req_id the request id
     * @throws Exception
     */
    public function deleteRequest(string $req_id)
    {
        return $this->exec(
            "DELETE FROM token WHERE req_id=:req_id",
            array(":req_id" => $req_id)
        );
    }

    /**
     *  executes a SQL query to retrieve values (SELECT)
     *
     * @param string $sql the SQL prepared query to execute (es. SELECT * FROM request WHERE code_verifier = :code_verifier)
     * @param string[] $values values to bind on the query
     * @throws Exception
     * @return array result of the query
     */
    public function query($sql, $values = array())
    {
        $result = array();
        $stmt = $this->db->prepare($sql);
        foreach ($values as $key => $value) {
            $stmt->bindValue($key, $value, SQLITE3_TEXT);
        }
        $query = $stmt->execute();
        while ($row = $query->fetchArray(SQLITE3_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    /**
     *  executes a SQL query to upsert values (INSERT, UPDATE)
     *
     * @param string $sql the SQL prepared query to execute
     * @param string[] $values values to bind on the query
     * @throws Exception
     * @return array result of the query
     */
    public function exec($sql, $values = array())
    {
        $stmt = $this->db->prepare($sql);
        foreach ($values as $key => $value) {
            $stmt->bindValue($key, $value, SQLITE3_TEXT);
        }
        $result = $stmt->execute();
        return $result;
    }

    /**
     *  executes a dump of a table
     *
     * @param string $table the name of the table to dump
     * @throws Exception
     * @return array result of the dump
     */
    public function dump($table)
    {
        return $this->query("SELECT * FROM " . $table);
    }

    /**
     *  saves a record on the log table
     *
     * @param string $context context for the log record
     * @param string $tag tag for the log record
     * @param mixed $value value for the log record
     * @param string $severity [DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL]
     * @throws Exception
     */
    public function log(string $context, string $tag, $value = '', string $severity = 'INFO')
    {
        $severity_available = ['DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL'];
        if (!in_array($severity, $severity_available)) {
            $this->log("Severity " . $severity . " not allowed, severity MUST be one of: " . json_encode($severity_available) . ". Changed to DEBUG");
            $severity = 'DEBUG';
        }
        $this->exec("
             INSERT INTO log(context, tag, value, severity)
             VALUES(:context, :tag, :value, :severity);
         ", array(
            ":context" => $context,
            ":tag" => $tag,
            ":value" => json_encode($value),
            ":severity" => $severity
        ));
    }
}
