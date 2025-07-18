<?php
class Database
{
    private $servername;
    private $username;
    private $password;
    private $dbname;
    public $conn;

    public function __construct()
    {
        
        $this->servername = getenv('DB_SERVER') ?: 'localhost';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASS') ?: '';
        $this->dbname = getenv('DB_NAME') ?: 'student_records';
        $this->connect();
    }

    public function connect()
    {
        try {
            
            $this->conn = new PDO("mysql:host=$this->servername;dbname=$this->dbname", $this->username, $this->password);
            
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            
            $this->conn->exec("set names utf8mb4");
        } catch (PDOException $e) {
            
            error_log("Connection failed: " . $e->getMessage(), 3, '../../php/logfile.log');
            echo "Unable to connect to the database. Please try again later.";
            exit();
        }
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function closeConnection()
    {
        
        $this->conn = null;
    }
}
?>
