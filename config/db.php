<?php

require_once "user_errors.php";
require_once "dev_logs.php";

class Database {

    private $host = "127.0.0.1"; // Use IP to bypass Windows localhost IPv6 delay
    private $user = "root";
    private $password = "";
    private $dbname = "tqseet_db";

    public function connect() {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            // Attempt standard connection first
            return new mysqli($this->host, $this->user, $this->password, $this->dbname);
        } catch (mysqli_sql_exception $e) {
            // Fallback for laptops running MySQL on 3307
            try {
                return new mysqli($this->host, $this->user, $this->password, $this->dbname, 3307);
            } catch (mysqli_sql_exception $fallbackException) {
                logError("DB ERROR: " . $fallbackException->getMessage());
                die(userError("db_error"));
            }
        }
    }
}