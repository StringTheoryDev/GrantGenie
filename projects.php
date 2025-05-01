<?php
// projects.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "Please log in to view your projects.";
    $_SESSION['message_type'] = "warning";
    header("Location: login.php");
    exit;
}

// Database connection
require_once 'config/Database.php';
require_once 'includes/Project.php';
require_once 'includes/BudgetItem.php';

$database = new Database();
$db = $database->getConnection();

// Get user's projects
$project = new Project($db);
$project->user_id = $_SESSION['user_id'];
$stmt = $project->readByUser();

// Include layout template
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>My Grant Projects</h1>
    <a href="create_project.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Create New Project
    </a>
</div>

<?php if ($stmt->rowCount() === 0): ?>
<div class="card">
    <div class="card-body text-center py-5">
        <div class="mb-4">
            <i class="fas fa-folder-open fa-4x text-muted"></i>
        </div>
        <h3>No Projects Yet</h3>
        <p class="lead mb-4">You haven't created any grant projects yet.</p>
        <a href="create_project.php" class="btn btn-primary btn-lg">
            <i class="fas fa-plus me-2"></i>Create Your First Project
        </a>
    </div>
</div>
<?php else: ?>
<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
    <?php 
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
        // Check if this project has a budget
        $budgetItem = new BudgetItem($db);
        $budgetItem->project_id = $row['id'];
        $budgetStmt = $budgetItem->readByProject();
        $hasBudget = $budgetStmt->rowCount() > 0;
        
        // Get total budget if available
        $totalBudget = $hasBudget ? $budgetItem->getTotalByProject() : 0;
        
        // Determine project status
        $status = "draft";
        $statusText = "Draft";
        $statusColor = "warning";
        
        if ($hasBudget) {
            $status = "budget";
            $statusText = "Budget Created";
            $statusColor = "success";
        }
    ?>
    <div class="col">
        <div class="card h-100 dashboard-card dashboard-card-primary">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><?php echo htmlspecialchars($row['title']); ?></h5>
                <span class="badge bg-<?php echo $statusColor; ?>"><?php echo $statusText; ?></span>
            </div>
            <div class="card-body">
                <p class="card-text mb-3">
                    <?php 
                    $description = $row['description'];
                    echo strlen($description) > 100 
                        ? htmlspecialchars(substr($description, 0, 100)) . '...' 
                        : htmlspecialchars($description); 
                    ?>
                </p>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between text-muted mb-2">
                        <small>Grant Type:</small>
                        <small class="fw-bold"><?php echo htmlspecialchars($row['grant_type']); ?></small>
                    </div>
                    <div class="d-flex justify-content-between text-muted mb-2">
                        <small>Duration:</small>
                        <small class="fw-bold"><?php echo htmlspecialchars($row['duration_years']); ?> Years</small>
                    </div>
                    <div class="d-flex justify-content-between text-muted">
                        <small>Created:</small>
                        <small class="fw-bold"><?php echo date('M j, Y', strtotime($row['created_at'])); ?></small>
                    </div>
                </div>
                
                <?php if ($hasBudget): ?>
                <div class="alert alert-success d-flex align-items-center mb-3">
                    <div>
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Budget:</strong> $<?php echo number_format($totalBudget, 2); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-transparent">
                <div class="d-flex justify-content-between">
                    <?php if ($hasBudget): ?>
                    <a href="edit_budget.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit me-1"></i>Edit Budget
                    </a>
                    <div>
                        <a href="export_budget.php?id=<?php echo $row['id']; ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-file-excel me-1"></i>Export
                        </a>
                        <div class="dropdown d-inline-block">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton<?php echo $row['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton<?php echo $row['id']; ?>">
                                <li><a class="dropdown-item" href="project_description.php?id=<?php echo $row['id']; ?>">Edit Description</a></li>
                                <li><a class="dropdown-item" href="generate_budget.php?id=<?php echo $row['id']; ?>">Regenerate Budget</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger delete-confirm" href="delete_project.php?id=<?php echo $row['id']; ?>">Delete Project</a></li>
                            </ul>
                        </div>
                    </div>
                    <?php else: ?>
                    <a href="project_description.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-arrow-right me-1"></i>Continue
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton<?php echo $row['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton<?php echo $row['id']; ?>">
                            <li><a class="dropdown-item text-danger delete-confirm" href="delete_project.php?id=<?php echo $row['id']; ?>">Delete Project</a></li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();

// Include the base template
require_once 'includes/template.php';
?>