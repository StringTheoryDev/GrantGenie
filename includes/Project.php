<?php
// includes/Project.php
class Project {
    // Database connection and table name
    private $conn;
    private $table_name = "projects";

    // Object properties
    public $id;
    public $user_id;
    public $title;
    public $description;
    public $grant_type;
    public $duration_years;
    public $created_at;
    public $updated_at;

    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create project
    public function create() {
        // Query to insert record
        $query = "INSERT INTO " . $this->table_name . "
                  SET 
                    user_id=:user_id, 
                    title=:title, 
                    description=:description, 
                    grant_type=:grant_type, 
                    duration_years=:duration_years";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->grant_type = htmlspecialchars(strip_tags($this->grant_type));
        $this->duration_years = htmlspecialchars(strip_tags($this->duration_years));

        // Bind values
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":grant_type", $this->grant_type);
        $stmt->bindParam(":duration_years", $this->duration_years);

        // Execute query
        if($stmt->execute()) {
            // Get the ID of the newly created project
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    // Read a single project
    public function readOne() {
        // Query to read single record
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ?";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Bind id of product to be read
        $stmt->bindParam(1, $this->id);

        // Execute query
        $stmt->execute();

        // Get retrieved row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Set values to object properties
        if($row) {
            $this->user_id = $row['user_id'];
            $this->title = $row['title'];
            $this->description = $row['description'];
            $this->grant_type = $row['grant_type'];
            $this->duration_years = $row['duration_years'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }

        return false;
    }

    // Read all projects for a user
    public function readByUser() {
        // Query to get projects
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = ? ORDER BY created_at DESC";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind user ID
        $stmt->bindParam(1, $this->user_id);

        // Execute query
        $stmt->execute();

        return $stmt;
    }

    // Update project
    public function update() {
        // Query
        $query = "UPDATE " . $this->table_name . "
                  SET 
                    title = :title, 
                    description = :description, 
                    grant_type = :grant_type, 
                    duration_years = :duration_years
                  WHERE 
                    id = :id";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->grant_type = htmlspecialchars(strip_tags($this->grant_type));
        $this->duration_years = htmlspecialchars(strip_tags($this->duration_years));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind parameters
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':grant_type', $this->grant_type);
        $stmt->bindParam(':duration_years', $this->duration_years);
        $stmt->bindParam(':id', $this->id);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Delete project
    public function delete() {
        // Query
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind id
        $stmt->bindParam(1, $this->id);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }
}
?>