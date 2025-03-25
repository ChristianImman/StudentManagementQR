<?php
class Database {
    private $servername = "localhost"; // Change if necessary
    private $username = "root"; // Your MySQL username
    private $password = ""; // Your MySQL password
    private $dbname = "student_records"; // Change this to your actual database name
    public $conn;

    public function __construct() {
        $this->connect();
    }

    public function connect() {
        try {
            // Create a new PDO connection
            $this->conn = new PDO("mysql:host=$this->servername;dbname=$this->dbname", $this->username, $this->password);
            // Set the PDO error mode to exception
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
            exit(); // Exit if the connection fails
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function closeConnection() {
        $this->conn = null; // Close the connection
    }
}
?>