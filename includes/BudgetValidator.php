<?php
// includes/BudgetValidator.php
class BudgetValidator {
    // Database connection
    private $conn;
    private $rules_table = "budget_rules";
    
    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Validate a budget item against rules
    public function validateItem($item, $grantType) {
        $errors = [];
        
        // Get all relevant rules for this item's category and grant type
        $query = "SELECT * FROM " . $this->rules_table . " 
                  WHERE grant_type = ? AND category = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $grantType);
        $stmt->bindParam(2, $item->category);
        $stmt->execute();
        
        // Check each rule
        while ($rule = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $validationResult = $this->checkRule($item, $rule);
            if ($validationResult !== true) {
                $errors[] = [
                    'rule_name' => $rule['rule_name'],
                    'error_message' => $rule['error_message'],
                    'suggestion' => $rule['suggestion']
                ];
            }
        }
        
        return $errors;
    }
    
    // Check a specific rule against an item
    private function checkRule($item, $rule) {
        switch ($rule['validation_type']) {
            case 'min':
                if ($item->amount < floatval($rule['validation_value'])) {
                    return false;
                }
                break;
                
            case 'max':
                if ($item->amount > floatval($rule['validation_value'])) {
                    return false;
                }
                break;
                
            case 'percentage':
                // Percentage rules would require context from the full budget
                // Implementation would depend on specific requirements
                break;
                
            case 'required':
                if ($rule['validation_value'] == 'justification' && empty($item->justification)) {
                    return false;
                }
                break;
                
            case 'forbidden':
                // Forbidden rules would need context
                // Implementation would depend on specific requirements
                break;
        }
        
        return true;
    }
    
    // Validate an entire project budget
    public function validateProject($projectId, $grantType) {
        $projectErrors = [];
        
        // Create budget item object
        require_once 'BudgetItem.php';
        $budgetItem = new BudgetItem($this->conn);
        $budgetItem->project_id = $projectId;
        
        // Get all budget items for this project
        $stmt = $budgetItem->readByProject();
        
        // Check each item
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Create a temporary object to hold the current item
            $currentItem = new BudgetItem($this->conn);
            $currentItem->id = $row['id'];
            $currentItem->project_id = $row['project_id'];
            $currentItem->category = $row['category'];
            $currentItem->item_name = $row['item_name'];
            $currentItem->description = $row['description'];
            $currentItem->year = $row['year'];
            $currentItem->amount = $row['amount'];
            $currentItem->quantity = $row['quantity'];
            $currentItem->justification = $row['justification'];
            
            // Validate the item
            $itemErrors = $this->validateItem($currentItem, $grantType);
            
            if (!empty($itemErrors)) {
                $projectErrors[] = [
                    'item_id' => $currentItem->id,
                    'item_name' => $currentItem->item_name,
                    'category' => $currentItem->category,
                    'year' => $currentItem->year,
                    'errors' => $itemErrors
                ];
            }
        }
        
        // Add project-level validation here if needed
        // For example, checking total budget constraints, etc.
        
        return $projectErrors;
    }
}
?>