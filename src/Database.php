<?php

namespace App;

class Database
{
    private $connection;
    private $host;
    private $db;
    private $user;
    private $password;
    private $port;

    public function __construct()
    {
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->db = $_ENV['DB_NAME'] ?? 'shiftness_db';
        $this->user = $_ENV['DB_USER'] ?? 'shiftness_db_user';
        $this->password = $_ENV['DB_PASSWORD'] ?? '';
        $this->port = $_ENV['DB_PORT'] ?? 5432;
    }

    public function connect()
    {
        try {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->db}";
            $this->connection = new \PDO($dsn, $this->user, $this->password);
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return $this->connection;
        } catch (\PDOException $e) {
            die('Connection Error: ' . $e->getMessage());
        }
    }

    public function getConnection()
    {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }
}
