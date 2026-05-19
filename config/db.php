<?php

require_once "user_errors.php";
require_once "dev_logs.php";

class Database {

    private $host = "localhost";
    private $user = "root";
    private $password = "";
    private $dbname = "tqseet_db";
    public function connect() {

        // ✅ Enable exceptions
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        // Try primary port 3306 first
        try {
            return new mysqli($this->host, $this->user, $this->password, $this->dbname, 3306);
        } catch (mysqli_sql_exception $e) {
            // If host is localhost/127.0.0.1, try school laptop fallback port 3307
            if ($this->host === "localhost" || $this->host === "127.0.0.1") {
                try {
                    return new mysqli($this->host, $this->user, $this->password, $this->dbname, 3307);
                } catch (mysqli_sql_exception $fallbackException) {
                    logError("DB ERROR (both 3306/3307 failed): " . $fallbackException->getMessage());
                    die(userError("db_error"));
                }
            } else {
                // In production/hosting environments, fail immediately without fallback
                logError("DB ERROR: " . $e->getMessage());
                die(userError("db_error"));
            }
        }
    }
}