<?php

namespace src\app;

use MySQLi;

class Database
{

    private static ?Database $db = null;
    private ?object $connection;

    function __construct()
    {
        $this->connection = new MySQLi($_ENV['DB_SERVER'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
        if ($this->connection->connect_error) {
            error_log("Connection failed: " . $this->connection->connect_error);
            die("Database connection failed");
        }
    }

    function __destruct()
    {
        $this->connection->close();
    }

    public static function getConnection(): MySQLi|null
    {
        if (self::$db == null) self::$db = new Database();
        return self::$db->connection;
    }

    public static function insert(string $sql, string $types = '', array $parameters = [])
    {
        $connection = Database::getConnection();
        $stmt = $connection->prepare($sql);
        $stmt->bind_param($types, ...$parameters);
        $stmt->execute();
    }

}
