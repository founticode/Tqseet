<?php

require_once "user_errors.php";
require_once "dev_logs.php";

class Database {

    private $host = "localhost";
    private $user = "root";
    private $password = "";
    private $dbname = "tqseet_db";
    private $port = 3307;

    public function connect() {

        // ✅ Enable exceptions
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {

            $conn = new mysqli(
                $this->host,
                $this->user,
                $this->password,
                $this->dbname,
                $this->port
            );

            return $conn;

        } catch (mysqli_sql_exception $e) {

            logError("DB ERROR: " . $e->getMessage());

            die(userError("db_error"));
        }
    }
}