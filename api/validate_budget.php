<?php
// api/validate_budget.php
header('Content-Type: application/json');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

// Database connection
require_once '../config/Database.php';
require_once '../includes/Project.php';
require_once '../includes/BudgetItem.php';
require_once '../includes/BudgetValidator.php';

$database = new Database();
$db = $database->getConnection();

// Check if project_id is provided in POST data
$projectId = null;

// Extract project ID from the form data
if (isset($_POST['item_id']) && is_array($_POST['item_id']) && count($_POST['item_id']) > 0) {
    // First, get an existing item ID to find the project
    $itemId = $_POST['item_id'][0];
    
    // Get the item's project ID
    $query = "SELECT project_id FROM budget_items WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $itemId);
    $stmt->execute();
    
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $projectId = $row['project_id'];
    }
} elseif (isset($_POST['project_id'])) {
    $projectId = $_POST['project_id'];
}

if (!$projectId) {
    echo json_encode([
        'success' => false,
        'message' => 'Project ID is required'
    ]);
    exit;
}

// Get project details
$project = new Project($db);
$project->id = $projectId;

// Verify project exists and belongs to user
if (!$project->readOne() || $project->user_id != $_SESSION['user_id']) {
    echo json_encode([
        'success' => false,
        'message' => 'Project not found or access denied'
    ]);
    exit;
}

try {
    // Create temporary budget items from the form data
    $tempItems = [];
    
    // Process existing items
    if (isset($_POST['item_id'])) {
        foreach ($_POST['item_id'] as $index => $id) {
            $item = new BudgetItem($db);
            $item->id = $id;
            $item->project_id = $projectId;
            $item->category = $_POST['category'][$index];
            $item->item_name = $_POST['item_name'][$index];
            $item->description = $_POST['description'][$index] ?? '';
            $item->year = $_POST['year'][$index];
            $item->amount = $_POST['amount'][$index];
            $item->quantity = $_POST['quantity'][$index];
            $item->justification = $_POST['justification'][$index] ?? '';
            
            $tempItems[] = $item;
        }
    }
    
    // Process new items
    if (isset($_POST['new_item_name'])) {
        foreach ($_POST['new_item_name'] as $index => $name) {
            if (!empty($name)) {
                $item = new BudgetItem($db);
                $item->project_id = $projectId;
                $item->category = $_POST['new_category'][$index];
                $item->item_name = $name;
                $item->description = $_POST['new_description'][$index] ?? '';
                $item->year = $_POST['new_year'][$index];
                $item->amount = $_POST['new_amount'][$index];
                $item->quantity = $_POST['new_quantity'][$index];
                $item->justification = $_POST['new_justification'][$index] ?? '';
                
                $tempItems[] = $item;
            }
        }
    }
    
    // Initialize validator
    $validator = new BudgetValidator($db);
    
    // Validate each item
    $errors = [];
    foreach ($tempItems as $item) {
        $itemErrors = $validator->validateItem($item, $project->grant_type);
        
        if (!empty($itemErrors)) {
            $errors[] = [
                'item_id' => $item->id ?? 'new',
                'item_name' => $item->item_name,
                'category' => $item->category,
                'year' => $item->year,
                'errors' => $itemErrors
            ];
        }
    }
    
    if (empty($errors)) {
        echo json_encode([
            'valid' => true,
            'message' => 'Budget validation passed'
        ]);
    } else {
        echo json_encode([
            'valid' => false,
            'errors' => $errors
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'valid' => false,
        'message' => 'Error validating budget: ' . $e->getMessage()
    ]);
}
?>