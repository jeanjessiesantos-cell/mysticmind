<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mysticmind');

function getDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            error_log("DB Connection failed: " . $conn->connect_error);
            throw new RuntimeException('Database connection failed.');
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}