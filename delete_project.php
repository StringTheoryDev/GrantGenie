<?php
// delete_project.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "Please log in to delete projects.";
    $_SESSION['message_type'] = "warning";
    header("Location: login.php");
    exit;
}

// Check if project ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['message'] = "No project specified for deletion.";
    $_SESSION['message_type'] = "danger";
    header("Location: projects.php");
    exit;
}

// Database connection
require_once 'config/Database.php';
require_once 'includes/Project.php';
require_once 'includes/BudgetItem.php';

$database = new Database();
$db = $database->getConnection();

// Get project details
$project = new Project($db);
$project->id = $_GET['id'];

// Verify project exists and belongs to user
if (!$project->readOne() || $project->user_id != $_SESSION['user_id']) {
    $_SESSION['message'] = "Project not found or access denied.";
    $_SESSION['message_type'] = "danger";
    header("Location: projects.php");
    exit;
}

// Check if confirmation was given or this is the first request
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    // Delete all budget items first
    $budgetItem = new BudgetItem($db);
    $budgetItem->project_id = $project->id;
    $budgetItem->deleteByProject();
    
    // Then delete the project
    if ($project->delete()) {
        $_SESSION['message'] = "Project deleted successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Failed to delete project.";
        $_SESSION['message_type'] = "danger";
    }
    
    // Redirect to projects page
    header("Location: projects.php");
    exit;
}

// Include layout template for confirmation page
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="card-title mb-0">Confirm Deletion</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone.
                </div>
                
                <p>Are you sure you want to delete the project "<strong><?php echo htmlspecialchars($project->title); ?></strong>"?</p>
                <p>All associated budget items and data will be permanently removed.</p>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="projects.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                    <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $project->id . "&confirm=yes"); ?>" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-2"></i>Delete Project
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Include the base template
require_once 'includes/template.php';
?>