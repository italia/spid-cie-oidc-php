<?php

namespace SPID_CIE_OIDC_PHP\Core;

use SPID_CIE_OIDC_PHP\Core\Util;

class Database
{
    public function __construct($db_file)
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


    public function createRequest($op_id, $redirect_uri, $state = '', $acr = '', $user_attributes = '')
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

    public function getRequest($req_id)
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

    public function exec($sql, $values = array())
    {
        $stmt = $this->db->prepare($sql);
        foreach ($values as $key => $value) {
            $stmt->bindValue($key, $value, SQLITE3_TEXT);
        }
        $result = $stmt->execute();
        return $result;
    }

    public function dump($table)
    {
        return $this->query("SELECT * FROM " . $table);
    }

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
