<?php

namespace src\app;

use mysqli;

class Database
{

    private ?object $conn;

    function __construct()
    {
        $this->conn = new mysqli($_ENV['DB_SERVER'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
        if ($this->conn->connect_error) {
            error_log("Connection failed: " . $this->conn->connect_error);
            die("Database connection failed");
        }
    }

    function insert(string $sql, string $types = '', array $parameters = [])
    {
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$parameters);
        $stmt->execute();
    }

}
