<?php
// includes/BudgetItem.php
class BudgetItem {
    // Database connection and table name
    private $conn;
    private $table_name = "budget_items";

    // Object properties
    public $id;
    public $project_id;
    public $category;
    public $item_name;
    public $description;
    public $year;
    public $amount;
    public $quantity;
    public $justification;
    public $is_edited;
    public $created_at;
    public $updated_at;

    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create budget item
    public function create() {
        // Query to insert record
        $query = "INSERT INTO " . $this->table_name . "
                  SET 
                    project_id = :project_id, 
                    category = :category, 
                    item_name = :item_name, 
                    description = :description, 
                    year = :year, 
                    amount = :amount, 
                    quantity = :quantity, 
                    justification = :justification, 
                    is_edited = :is_edited";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->project_id = htmlspecialchars(strip_tags($this->project_id));
        $this->category = htmlspecialchars(strip_tags($this->category));
        $this->item_name = htmlspecialchars(strip_tags($this->item_name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->year = htmlspecialchars(strip_tags($this->year));
        $this->amount = htmlspecialchars(strip_tags($this->amount));
        $this->quantity = htmlspecialchars(strip_tags($this->quantity));
        $this->justification = htmlspecialchars(strip_tags($this->justification));
        $this->is_edited = htmlspecialchars(strip_tags($this->is_edited));

        // Bind values
        $stmt->bindParam(":project_id", $this->project_id);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":item_name", $this->item_name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":year", $this->year);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":quantity", $this->quantity);
        $stmt->bindParam(":justification", $this->justification);
        $stmt->bindParam(":is_edited", $this->is_edited);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Read budget items by project id
    public function readByProject() {
        // Query
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE project_id = :project_id 
                  ORDER BY year, category, item_name";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind parameter
        $stmt->bindParam(':project_id', $this->project_id);

        // Execute query
        $stmt->execute();

        return $stmt;
    }

    // Read budget items by project id and year
    public function readByProjectAndYear() {
        // Query
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE project_id = :project_id AND year = :year 
                  ORDER BY category, item_name";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind parameters
        $stmt->bindParam(':project_id', $this->project_id);
        $stmt->bindParam(':year', $this->year);

        // Execute query
        $stmt->execute();

        return $stmt;
    }

    // Update budget item
    public function update() {
        // Query
        $query = "UPDATE " . $this->table_name . "
                  SET 
                    category = :category, 
                    item_name = :item_name, 
                    description = :description, 
                    year = :year, 
                    amount = :amount, 
                    quantity = :quantity, 
                    justification = :justification, 
                    is_edited = true
                  WHERE 
                    id = :id";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->category = htmlspecialchars(strip_tags($this->category));
        $this->item_name = htmlspecialchars(strip_tags($this->item_name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->year = htmlspecialchars(strip_tags($this->year));
        $this->amount = htmlspecialchars(strip_tags($this->amount));
        $this->quantity = htmlspecialchars(strip_tags($this->quantity));
        $this->justification = htmlspecialchars(strip_tags($this->justification));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind parameters
        $stmt->bindParam(':category', $this->category);
        $stmt->bindParam(':item_name', $this->item_name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':year', $this->year);
        $stmt->bindParam(':amount', $this->amount);
        $stmt->bindParam(':quantity', $this->quantity);
        $stmt->bindParam(':justification', $this->justification);
        $stmt->bindParam(':id', $this->id);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Delete budget item
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

    // Delete all budget items for a project
    public function deleteByProject() {
        // Query
        $query = "DELETE FROM " . $this->table_name . " WHERE project_id = ?";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->project_id = htmlspecialchars(strip_tags($this->project_id));

        // Bind project id
        $stmt->bindParam(1, $this->project_id);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Get total budget amount by project
    public function getTotalByProject() {
        // Query
        $query = "SELECT SUM(amount * quantity) as total FROM " . $this->table_name . " 
                  WHERE project_id = ?";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind parameter
        $stmt->bindParam(1, $this->project_id);

        // Execute query
        $stmt->execute();

        // Fetch result
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['total'];
    }

    // Get total budget amount by project and year
    public function getTotalByProjectAndYear() {
        // Query
        $query = "SELECT SUM(amount * quantity) as total FROM " . $this->table_name . " 
                  WHERE project_id = ? AND year = ?";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind parameters
        $stmt->bindParam(1, $this->project_id);
        $stmt->bindParam(2, $this->year);

        // Execute query
        $stmt->execute();

        // Fetch result
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['total'];
    }
}
?>