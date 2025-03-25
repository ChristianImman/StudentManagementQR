<?php
class User
{
    private $conn;
    private $table_name = "admins"; // Table for admin users

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function login($username, $password)
    {
        // Prepare the SQL statement
        $query = "SELECT * FROM " . $this->table_name . " WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            die("SQL Error: " . $this->conn->errorInfo()[2]);
        }

        // Bind parameters using named placeholders
        $stmt->bindValue(':username', $username);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if user exists
        if ($result) {
            // Debugging output
            echo "User  found: " . $result['username'] . "<br>";
            // Verify the password
            if (password_verify($password, $result['password'])) {
                return true; // Login successful
            } else {
                echo "Incorrect password.<br>";
                return false; // Incorrect password
            }
        } else {
            echo "User  not found.<br>";
            return false; // User not found
        }
    }

    public function addUser($username, $password, $first_name, $last_name, $email_address, $date_of_birth)
    {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare the SQL statement
        $query = "INSERT INTO " . $this->table_name . " (username, password, first_name, last_name, email_address, date_of_birth) VALUES (:username, :password, :first_name, :last_name, :email_address, :date_of_birth)";
        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            die("SQL Error: " . $this->conn->errorInfo()[2]);
        }

        // Bind parameters using named placeholders
        $stmt->bindValue(':username', $username);
        $stmt->bindValue(':password', $hashed_password);
        $stmt->bindValue(':first_name', $first_name);
        $stmt->bindValue(':last_name', $last_name);
        $stmt->bindValue(':email_address', $email_address);
        $stmt->bindValue(':date_of_birth', $date_of_birth);

        // Execute the statement
        if ($stmt->execute()) {
            echo "User  added successfully.<br>";
            return true; // User added successfully
        } else {
            echo "Error adding user: " . $stmt->errorInfo()[2] . "<br>";
            return false; // Error adding user
        }
    }

    public function usernameExists($username)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            die("SQL Error: " . $this->conn->errorInfo()[2]);
        }

        // Bind parameters using named placeholders
        $stmt->bindValue(':username', $username);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result !== false; // Returns true if the username exists, false otherwise
    }
}
