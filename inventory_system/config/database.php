<?php
class Database {
    private $host = "localhost";
    private $db_name = "inventory_system";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
            return $this->conn;
        } catch(PDOException $e) {
            error_log("Connection error: " . $e->getMessage());
            echo "<h2>Database Connection Error</h2>";
            echo "<p>Error: " . $e->getMessage() . "</p>";
            echo "<p>Please check:</p>";
            echo "<ul>";
            echo "<li>MySQL is running in XAMPP</li>";
            echo "<li>Database 'inventory_system' exists</li>";
            echo "<li><a href='setup_fixed.php'>Click here to run setup</a></li>";
            echo "</ul>";
            return null;
        }
    }
}
?>