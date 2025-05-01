<?php
// export_budget.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "Please log in to access this page.";
    $_SESSION['message_type'] = "warning";
    header("Location: login.php");
    exit;
}

// Check if project ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['message'] = "No project specified.";
    $_SESSION['message_type'] = "danger";
    header("Location: projects.php");
    exit;
}

// Database connection
require_once 'config/Database.php';
require_once 'includes/Project.php';
require_once 'includes/BudgetItem.php';
require_once 'includes/ExcelExporter.php';

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

// Check if download is requested
if (isset($_GET['download'])) {
    // Create Excel exporter
    $exporter = new ExcelExporter($db);
    
    // Generate Excel file
    $filename = $exporter->exportBudget($project->id);
    
    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize('../temp/' . $filename));
    header('Cache-Control: max-age=0');
    
    // Output file
    readfile('../temp/' . $filename);
    
    // Delete temporary file
    unlink('../temp/' . $filename);
    exit;
}

// Get budget items
$budgetItem = new BudgetItem($db);
$budgetItem->project_id = $project->id;
$stmt = $budgetItem->readByProject();

// Check if budget exists
if ($stmt->rowCount() === 0) {
    $_SESSION['message'] = "No budget found for this project.";
    $_SESSION['message_type'] = "warning";
    header("Location: generate_budget.php?id=" . $project->id);
    exit;
}

// Include layout template
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?php echo htmlspecialchars($project->title); ?> - Export Budget</h1>
    <a href="edit_budget.php?id=<?php echo $project->id; ?>" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left me-2"></i>Back to Budget
    </a>
</div>

<div class="wizard-steps mb-4">
    <div class="wizard-step completed">
        <div class="wizard-step-number"><i class="fas fa-check"></i></div>
        <div class="wizard-step-label">Create Project</div>
    </div>
    <div class="wizard-step completed">
        <div class="wizard-step-number"><i class="fas fa-check"></i></div>
        <div class="wizard-step-label">Project Description</div>
    </div>
    <div class="wizard-step completed">
        <div class="wizard-step-number"><i class="fas fa-check"></i></div>
        <div class="wizard-step-label">Generate Budget</div>
    </div>
    <div class="wizard-step completed">
        <div class="wizard-step-number"><i class="fas fa-check"></i></div>
        <div class="wizard-step-label">Edit & Validate</div>
    </div>
    <div class="wizard-step active">
        <div class="wizard-step-number">5</div>
        <div class="wizard-step-label">Export</div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Export Options</h5>
            </div>
            <div class="card-body">
                <div class="text-center py-4">
                    <div class="mb-4">
                        <i class="fas fa-file-excel fa-5x text-success"></i>
                    </div>
                    <h4 class="mb-3">Your Budget is Ready to Export</h4>
                    <p class="mb-4">Download your budget as an Excel spreadsheet for use in your grant proposal.</p>
                    
                    <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $project->id . "&download=1"); ?>" class="btn btn-success btn-lg">
                        <i class="fas fa-download me-2"></i>Download Excel Spreadsheet
                    </a>
                </div>
                
                <div class="alert alert-info mt-4">
                    <h6><i class="fas fa-info-circle me-2"></i>What's Included in the Download</h6>
                    <p class="mb-0">Your Excel spreadsheet will include:</p>
                    <ul class="mb-0">
                        <li>A summary sheet with an overview of all budget categories and years</li>
                        <li>Detailed sheets for each year of your project</li>
                        <li>All item details including amounts, quantities, and justifications</li>
                        <li>Automatically calculated totals for categories and years</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Project Details</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Grant Type:</span>
                        <span class="fw-bold"><?php echo htmlspecialchars($project->grant_type); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Duration:</span>
                        <span class="fw-bold"><?php echo htmlspecialchars($project->duration_years); ?> Years</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Created:</span>
                        <span class="fw-bold"><?php echo date('M j, Y', strtotime($project->created_at)); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Last Updated:</span>
                        <span class="fw-bold"><?php echo date('M j, Y', strtotime($project->updated_at)); ?></span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Budget Summary</h5>
            </div>
            <div class="card-body">
                <?php
                // Calculate total budget
                $totalBudget = $budgetItem->getTotalByProject();
                
                // Get counts by category
                $query = "SELECT category, COUNT(*) as count FROM budget_items WHERE project_id = ? GROUP BY category";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $project->id);
                $stmt->execute();
                
                $categories = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $categories[$row['category']] = $row['count'];
                }
                ?>
                
                <div class="text-center mb-3">
                    <h3>$<?php echo number_format($totalBudget, 2); ?></h3>
                    <div class="text-muted">Total Budget</div>
                </div>
                
                <hr>
                
                <div class="mb-3">
                    <h6>Items by Category</h6>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($categories as $category => $count): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?php echo ucfirst($category); ?>
                            <span class="badge bg-primary rounded-pill"><?php echo $count; ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between mt-4">
    <a href="edit_budget.php?id=<?php echo $project->id; ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Budget
    </a>
    <a href="projects.php" class="btn btn-primary">
        <i class="fas fa-folder me-2"></i>Go to Projects
    </a>
</div>

<?php
$content = ob_get_clean();

// Include the base template
require_once 'includes/template.php';
?>