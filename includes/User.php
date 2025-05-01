<?php
// includes/User.php
class User {
    // Database connection and table name
    private $conn;
    private $table_name = "users";

    // Object properties
    public $id;
    public $username;
    public $password;
    public $email;
    public $first_name;
    public $last_name;
    public $institution;
    public $created_at;

    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create user
    public function create() {
        // Query to insert record
        $query = "INSERT INTO " . $this->table_name . "
                  SET 
                    username=:username, 
                    password=:password, 
                    email=:email, 
                    first_name=:first_name, 
                    last_name=:last_name, 
                    institution=:institution";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->institution = htmlspecialchars(strip_tags($this->institution));

        // Hash the password
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);

        // Bind values
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":password", $password_hash);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":institution", $this->institution);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Check if username exists
    public function usernameExists() {
        // Query to check username
        $query = "SELECT id, username, password, email, first_name, last_name, institution
                  FROM " . $this->table_name . "
                  WHERE username = ?
                  LIMIT 0,1";

        // Prepare the query
        $stmt = $this->conn->prepare($query);

        // Bind username value
        $stmt->bindParam(1, $this->username);

        // Execute query
        $stmt->execute();

        // Get number of rows
        $num = $stmt->rowCount();

        // If username exists, assign values to object properties
        if($num > 0) {
            // Get record details
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Assign values to object properties
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->password = $row['password']; // This is already hashed
            $this->email = $row['email'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->institution = $row['institution'];

            return true;
        }

        return false;
    }

    // Check if email exists
    public function emailExists() {
        // Query to check email
        $query = "SELECT id, username, password, email, first_name, last_name, institution
                  FROM " . $this->table_name . "
                  WHERE email = ?
                  LIMIT 0,1";

        // Prepare the query
        $stmt = $this->conn->prepare($query);

        // Bind email value
        $stmt->bindParam(1, $this->email);

        // Execute query
        $stmt->execute();

        // Get number of rows
        $num = $stmt->rowCount();

        // If email exists, assign values to object properties
        if($num > 0) {
            // Get record details
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Assign values to object properties
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->password = $row['password']; // This is already hashed
            $this->email = $row['email'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->institution = $row['institution'];

            return true;
        }

        return false;
    }

    // Update user profile
    public function update() {
        // Query
        $query = "UPDATE " . $this->table_name . "
                  SET 
                    email = :email,
                    first_name = :first_name,
                    last_name = :last_name,
                    institution = :institution
                  WHERE 
                    id = :id";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->institution = htmlspecialchars(strip_tags($this->institution));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind parameters
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':institution', $this->institution);
        $stmt->bindParam(':id', $this->id);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Update user password
    public function updatePassword() {
        // Query
        $query = "UPDATE " . $this->table_name . "
                  SET 
                    password = :password
                  WHERE 
                    id = :id";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Hash the password
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);

        // Bind parameters
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':id', $this->id);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }
}
?>