<?php
// generate_budget.php
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

// Check if budget already exists
$budgetItem = new BudgetItem($db);
$budgetItem->project_id = $project->id;
$budgetExists = false;

$stmt = $budgetItem->readByProject();
if ($stmt->rowCount() > 0) {
    $budgetExists = true;
}

// Include layout template
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?php echo htmlspecialchars($project->title); ?></h1>
    <a href="projects.php" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left me-2"></i>Back to Projects
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
    <div class="wizard-step active">
        <div class="wizard-step-number">3</div>
        <div class="wizard-step-label">Generate Budget</div>
    </div>
    <div class="wizard-step">
        <div class="wizard-step-number">4</div>
        <div class="wizard-step-label">Edit & Validate</div>
    </div>
    <div class="wizard-step">
        <div class="wizard-step-number">5</div>
        <div class="wizard-step-label">Export</div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Generate Budget</h5>
            </div>
            <div class="card-body">
                <div class="project-description mb-4">
                    <h6>Project Description</h6>
                    <div class="p-3 bg-light rounded">
                        <?php echo nl2br(htmlspecialchars($project->description)); ?>
                    </div>
                </div>
                
                <?php if ($budgetExists): ?>
                <div class="alert alert-info" role="alert">
                    <h5><i class="fas fa-info-circle me-2"></i>Budget Already Generated</h5>
                    <p>A budget has already been generated for this project. You can edit it or generate a new one.</p>
                    <div class="mt-3">
                        <a href="edit_budget.php?id=<?php echo $project->id; ?>" class="btn btn-primary me-2">
                            <i class="fas fa-edit me-2"></i>Edit Existing Budget
                        </a>
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#regenerateModal">
                            <i class="fas fa-sync-alt me-2"></i>Generate New Budget
                        </button>
                    </div>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <div class="mb-4">
                        <i class="fas fa-magic fa-4x text-primary"></i>
                    </div>
                    <h4 class="mb-3">Ready to Generate Your Budget</h4>
                    <p class="mb-4">Our AI will analyze your project description and generate a comprehensive budget based on <?php echo htmlspecialchars($project->grant_type); ?> guidelines.</p>
                    
                    <button type="button" id="generate-budget-btn" class="btn btn-primary btn-lg" data-project-id="<?php echo $project->id; ?>">
                        <i class="fas fa-robot me-2"></i>Generate Budget
                    </button>
                </div>
                
                <div class="alert alert-light border mt-4">
                    <h6><i class="fas fa-lightbulb me-2 text-warning"></i>What happens next?</h6>
                    <p class="mb-1">When you click "Generate Budget", our AI will:</p>
                    <ol class="mb-0">
                        <li>Analyze your project description to identify budget needs</li>
                        <li>Create budget line items across appropriate categories</li>
                        <li>Apply <?php echo htmlspecialchars($project->grant_type); ?> guidelines for compliant allocations</li>
                        <li>Generate justifications for each budget item</li>
                    </ol>
                </div>
                <?php endif; ?>
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
                </ul>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo htmlspecialchars($project->grant_type); ?> Budget Categories</h5>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <?php if ($project->grant_type === 'NSF'): ?>
                    <li class="mb-2"><strong>Personnel:</strong> Salaries, wages, and fringe benefits</li>
                    <li class="mb-2"><strong>Equipment:</strong> Items over $5,000 with 1+ year useful life</li>
                    <li class="mb-2"><strong>Travel:</strong> Domestic and international travel expenses</li>
                    <li class="mb-2"><strong>Participant Support:</strong> Stipends, travel, and subsistence</li>
                    <li class="mb-2"><strong>Materials and Supplies:</strong> Consumable items</li>
                    <li class="mb-2"><strong>Publication Costs:</strong> Journal and publishing fees</li>
                    <li class="mb-2"><strong>Consultant Services:</strong> Professional services</li>
                    <li><strong>Indirect Costs:</strong> F&A costs based on MTDC</li>
                    <?php else: ?>
                    <li class="mb-2"><strong>Personnel:</strong> Salaries, wages, and fringe benefits</li>
                    <li class="mb-2"><strong>Equipment:</strong> Items over $5,000 with 1+ year useful life</li>
                    <li class="mb-2"><strong>Travel:</strong> Domestic and international travel expenses</li>
                    <li class="mb-2"><strong>Materials and Supplies:</strong> Consumable items</li>
                    <li class="mb-2"><strong>Consultant Costs:</strong> Professional services</li>
                    <li class="mb-2"><strong>Alterations and Renovations:</strong> Facility modifications</li>
                    <li class="mb-2"><strong>Other Expenses:</strong> Miscellaneous costs</li>
                    <li><strong>Indirect Costs:</strong> F&A costs based on negotiated rate</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Regenerate Budget Modal -->
<?php if ($budgetExists): ?>
<div class="modal fade" id="regenerateModal" tabindex="-1" aria-labelledby="regenerateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="regenerateModalLabel">Regenerate Budget</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This will delete your existing budget and create a new one. All your customizations will be lost.
                </div>
                <p>Are you sure you want to generate a new budget for this project?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="generate-budget-btn" class="btn btn-warning" data-project-id="<?php echo $project->id; ?>" data-regenerate="true">
                    <i class="fas fa-sync-alt me-2"></i>Regenerate Budget
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();

// Include the base template
require_once 'includes/template.php';
?>