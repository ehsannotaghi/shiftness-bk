<?php
// Database configuration
class Database {
    private $host = "dpg-d4hl4np5pdvs739ae7g0-a";
    private $db_name = "shiftness_db";
    private $username = "shiftness_db_user";
    private $password = "mX10R716zy0oPItryL8w2pj2lix7Z9r6";
    private $port = "5432";
    public $conn;

    // Get database connection
    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>

