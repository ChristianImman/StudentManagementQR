<?php
class Database
{
    private $servername = "localhost";
    private $username = "root";
    private $password = "";
    private $dbname = "student_records";
    public $conn;

    public function __construct()
    {
        $this->connect();
    }

    public function connect()
    {
        try {
            $this->conn = new PDO("mysql:host=$this->servername;dbname=$this->dbname", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log("Connection failed: " . $e->getMessage()); 
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
