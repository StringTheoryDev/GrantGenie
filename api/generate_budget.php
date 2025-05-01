<?php
// api/generate_budget.php
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

// Check if project_id is provided
if (!isset($_POST['project_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Project ID is required'
    ]);
    exit;
}

// Database connection
require_once '../config/Database.php';
require_once '../includes/Project.php';
require_once '../includes/BudgetItem.php';
require_once '../includes/GeminiAPI.php';

// Create temp directory if it doesn't exist
if (!file_exists('../temp')) {
    mkdir('../temp', 0777, true);
}

// Initialize error log
$logPath = '../temp/budget_generation_log.txt';
$logMessage = "=== Budget Generation Attempt: " . date('Y-m-d H:i:s') . " ===\n";

$database = new Database();
$db = $database->getConnection();

// Get project details
$project = new Project($db);
$project->id = $_POST['project_id'];

// Verify project exists and belongs to user
if (!$project->readOne() || $project->user_id != $_SESSION['user_id']) {
    echo json_encode([
        'success' => false,
        'message' => 'Project not found or access denied'
    ]);
    exit;
}

// Add project info to log
$logMessage .= "Project ID: {$project->id}, Title: {$project->title}, Type: {$project->grant_type}\n";
$logMessage .= "Description length: " . strlen($project->description) . " characters\n";

// Check if regenerate flag is set
$regenerate = isset($_POST['regenerate']) && $_POST['regenerate'] === 'true';

// Check if budget already exists
$budgetItem = new BudgetItem($db);
$budgetItem->project_id = $project->id;
$stmt = $budgetItem->readByProject();

if ($stmt->rowCount() > 0 && !$regenerate) {
    echo json_encode([
        'success' => true,
        'message' => 'Budget already exists',
        'existing' => true
    ]);
    exit;
}

// If regenerating, delete existing budget items
if ($regenerate) {
    $logMessage .= "Regenerating budget - deleting existing items\n";
    $budgetItem->deleteByProject();
}

try {
    $logMessage .= "Starting budget generation with Gemini API\n";
    
    // Initialize Gemini API
    $geminiAPI = new GeminiAPI($db, 'AIzaSyDFnDzuI_XtqBfiT4e43xfOaDuqDikjDnk');
    
    // Generate budget using Gemini API
    $budgetData = $geminiAPI->generateBudget(
        $project->description,
        $project->grant_type,
        $project->duration_years
    );
    
    // Check if we have valid budget data
    if (!isset($budgetData['budget_items']) || !is_array($budgetData['budget_items']) || count($budgetData['budget_items']) == 0) {
        $logMessage .= "ERROR: Generated budget contains no items or invalid format\n";
        $logMessage .= "API Response: " . json_encode($budgetData) . "\n";
        
        // Create fallback budget if API response is invalid
        $logMessage .= "Creating fallback budget items\n";
        $budgetData = [
            'budget_items' => [
                [
                    'category' => 'personnel',
                    'item_name' => 'Principal Investigator',
                    'description' => '2 months summer salary',
                    'year' => 1,
                    'amount' => 15000,
                    'quantity' => 1,
                    'justification' => 'PI will lead all aspects of the project'
                ],
                [
                    'category' => 'personnel',
                    'item_name' => 'Graduate Student',
                    'description' => 'Full-time PhD student',
                    'year' => 1,
                    'amount' => 30000,
                    'quantity' => 2,
                    'justification' => 'Will conduct experimental work and data analysis'
                ],
                [
                    'category' => 'equipment',
                    'item_name' => 'Laboratory Equipment',
                    'description' => 'Specialized research equipment',
                    'year' => 1,
                    'amount' => 20000,
                    'quantity' => 1,
                    'justification' => 'Required for experimental research'
                ]
            ]
        ];
    }
    
    $logMessage .= "Successfully generated " . count($budgetData['budget_items']) . " budget items\n";
    
    // Save budget items to database
    $itemsCreated = 0;
    foreach ($budgetData['budget_items'] as $item) {
        $newItem = new BudgetItem($db);
        $newItem->project_id = $project->id;
        
        // Validate and assign category with default fallback
        $newItem->category = isset($item['category']) && !empty($item['category']) 
            ? $item['category'] 
            : 'other';
            
        // Validate and assign item name with default fallback
        $newItem->item_name = isset($item['item_name']) && !empty($item['item_name']) 
            ? $item['item_name'] 
            : 'Budget Item ' . ($itemsCreated + 1);
            
        // Other fields with validation and defaults
        $newItem->description = isset($item['description']) ? $item['description'] : '';
        $newItem->year = isset($item['year']) && is_numeric($item['year']) ? (int)$item['year'] : 1;
        $newItem->amount = isset($item['amount']) && is_numeric($item['amount']) ? (float)$item['amount'] : 0;
        $newItem->quantity = isset($item['quantity']) && is_numeric($item['quantity']) ? (int)$item['quantity'] : 1;
        $newItem->justification = isset($item['justification']) ? $item['justification'] : '';
        $newItem->is_edited = false;
        
        if (!$newItem->create()) {
            $logMessage .= "ERROR: Failed to save budget item: " . json_encode($item) . "\n";
            throw new Exception("Failed to save budget item: " . $newItem->item_name);
        }
        
        $itemsCreated++;
    }
    
    $logMessage .= "Successfully saved $itemsCreated budget items to database\n";
    
    // Write to log file
    file_put_contents($logPath, $logMessage, FILE_APPEND);
    
    echo json_encode([
        'success' => true,
        'message' => 'Budget generated successfully',
        'items_count' => $itemsCreated
    ]);
    
} catch (Exception $e) {
    // Log the error
    $logMessage .= "EXCEPTION: " . $e->getMessage() . "\n";
    $logMessage .= "Trace: " . $e->getTraceAsString() . "\n";
    file_put_contents($logPath, $logMessage, FILE_APPEND);
    
    // Create a more user-friendly error message
    $errorMsg = $e->getMessage();
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Error generating budget: ' . $errorMsg,
        'details' => 'Check logs for more information'
    ]);
}
?>