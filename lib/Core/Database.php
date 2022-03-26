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

use SPID_CIE_OIDC_PHP\Core\Util;

/**
 *  Provides functions to saves and retrieves data from a SQLite storage database
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
    public function __construct(string $db_file)
    {
        $this->db = new \SQLite3($db_file);
        if (!$this->db) {
            die("Error while connecting to db.sqlite");
        }

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS log (
                timestamp       DATETIME DEFAULT (datetime('now')) NOT NULL,
                tag             STRING,
                value           STRING
            );

            CREATE TABLE IF NOT EXISTS request (
                req_id          INTEGER PRIMARY KEY AUTOINCREMENT,
                timestamp       DATETIME DEFAULT (datetime('now')) NOT NULL,
                op_id           STRING,
                redirect_uri    STRING,
                state           STRING,
                acr             STRING,
                user_attributes STRING,
                nonce           STRING,
                code_verifier   STRING
            );
        ");

        $this->db->exec("
            DELETE FROM log WHERE timestamp <= datetime('now', '-60 minutes');
            DELETE FROM request WHERE timestamp <= datetime('now', '-2 years');
        ");
    }

    /**
     *  creates a record for an authentication request
     *
     * @param string $op_id client_id of provider to which send the request
     * @param string $redirect_uri URL to which return after authentication
     * @param string $state value of the state param sent with the request
     * @param int[] $acr array of int values of the acr params to sent with the request
     * @param string[] $user_attributes array of string values of the user attributes to query with the request
     * @throws Exception
     * @return string the request id
     */
    public function createRequest(string $op_id, string $redirect_uri, string $state = '', array $acr = [], array $user_attributes = [])
    {
        $stmt = $this->db->prepare("
            INSERT INTO request(op_id, redirect_uri, state, acr, user_attributes, nonce, code_verifier) 
            VALUES(:op_id, :redirect_uri, :state, :acr, :user_attributes, :nonce, :code_verifier);
        ");
        $stmt->bindValue(':op_id', $op_id, SQLITE3_TEXT);
        $stmt->bindValue(':redirect_uri', $redirect_uri, SQLITE3_TEXT);
        $stmt->bindValue(':state', $state, SQLITE3_TEXT);
        $stmt->bindValue(':acr', json_encode($acr), SQLITE3_TEXT);
        $stmt->bindValue(':user_attributes', json_encode($user_attributes), SQLITE3_TEXT);
        $stmt->bindValue(':nonce', Util::getRandomCode(64), SQLITE3_TEXT);
        $stmt->bindValue(':code_verifier', Util::getRandomCode(128), SQLITE3_TEXT);
        $stmt->execute();
        $req_id = $this->db->lastInsertRowid();
        return $req_id;
    }

    /**
     *  get a saved request
     *
     * @param string $req_id the id of the request
     * @throws Exception
     * @return array data of the request: req_id, timestamp, op_id, redirect_uri, state, acr, user_attributes, none, code_verifier
     */
    public function getRequest(string $req_id)
    {
        $result = $this->query(
            "
            SELECT * FROM request
            WHERE req_id = :req_id;",
            array(":req_id" => $req_id)
        );

        $data = null;
        if (count($result) == 1) {
            $data = $result[0];
            $data['acr'] = json_decode($data['acr']);
            $data['user_attributes'] = json_decode($data['user_attributes']);
        }

        return $data;
    }

    /**
     *  executes a SQL query to retrieve values (SELECT)
     *
     * @param string $sql the SQL prepared query to execute (es. SELECT * FROM request WHERE code_verifier = :code_verifier)
     * @param string[] $values values to bind on the query
     * @throws Exception
     * @return array result of the query
     */
    public function query(string $sql, array $values = array())
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
    public function exec(string $sql, array $values = array())
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
     * @param string $tag tag for the log record
     * @param mixed $value value for the log record
     * @throws Exception
     * @return array result of the save
     */
    public function log($tag, $value)
    {
        $this->exec("
            INSERT INTO log(tag, value)
            VALUES(:tag, :value);
        ", array(
            ":tag" => $tag,
            ":value" => json_encode($value)
        ));
    }
}
