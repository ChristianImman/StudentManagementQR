<?php
class User
{
    private $conn;
    private $table_name = "admins";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function login($username, $password)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            die("SQL Error: " . $this->conn->errorInfo()[2]);
        }

        $stmt->bindValue(':username', $username);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            if (password_verify($password, $result['password'])) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function addUser($username, $password, $first_name, $last_name, $email_address, $date_of_birth, $registrar_id)
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT); 

        $query = "INSERT INTO " . $this->table_name . " (username, password, first_name, last_name, email_address, date_of_birth, registrar_id) 
                  VALUES (:username, :password, :first_name, :last_name, :email_address, :date_of_birth, :registrar_id)";
        
        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            die("SQL Error: " . $this->conn->errorInfo()[2]);
        }

        $stmt->bindValue(':username', $username);
        $stmt->bindValue(':password', $hashedPassword); 
        $stmt->bindValue(':first_name', $first_name);
        $stmt->bindValue(':last_name', $last_name);
        $stmt->bindValue(':email_address', $email_address);
        $stmt->bindValue(':date_of_birth', $date_of_birth);
        $stmt->bindValue(':registrar_id', $registrar_id); 

        return $stmt->execute();
    }

    public function usernameExists($username)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            die("SQL Error: " . $this->conn->errorInfo()[2]);
        }

        $stmt->bindValue(':username', $username);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result !== false;
    }

    public function authenticate($username, $password)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                return $user;
            }
        }

        return false;
    }
}
?>
