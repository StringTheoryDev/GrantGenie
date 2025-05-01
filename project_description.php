<?php
// project_description.php
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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update project description
    $project->description = $_POST['project_description'];
    
    if ($project->update()) {
        $_SESSION['message'] = "Project description updated successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: generate_budget.php?id=" . $project->id);
        exit;
    } else {
        $error_message = "Unable to update project description.";
    }
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
    <div class="wizard-step active">
        <div class="wizard-step-number">2</div>
        <div class="wizard-step-label">Project Description</div>
    </div>
    <div class="wizard-step">
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
                <h5 class="card-title mb-0">Project Description</h5>
            </div>
            <div class="card-body">
                <?php if (isset($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $project->id); ?>" method="post">
                    <div class="mb-3">
                        <label for="project-description" class="form-label">Detailed Project Description</label>
                        <p class="text-muted small">Provide a comprehensive description of your research project. The more details you include, the more accurate your AI-generated budget will be.</p>
                        <textarea class="form-control" id="project-description" name="project_description" rows="12" required><?php echo htmlspecialchars($project->description); ?></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="projects.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-arrow-right me-2"></i>Continue to Budget Generation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Upload Grant Proposal</h5>
            </div>
            <div class="card-body">
                <p>Alternatively, you can upload your grant proposal document and we'll extract the project description automatically.</p>
                
                <div id="file-upload-container">
                    <div class="mb-3">
                        <label for="grant-objective-file" class="form-label">Upload File</label>
                        <input class="form-control" type="file" id="grant-objective-file" accept=".pdf,.doc,.docx,.txt">
                        <div class="form-text">Supported formats: PDF, Word, and plain text.</div>
                    </div>
                    
                    <button type="button" id="upload-file-btn" class="btn btn-outline-primary d-none">
                        <i class="fas fa-upload me-2"></i>Process File
                    </button>
                </div>
            </div>
        </div>
        
        <div class="card">
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
        
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Tips for Better Results</h5>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li class="mb-2">Include specific research activities that require funding</li>
                    <li class="mb-2">Mention any specialized equipment or materials needed</li>
                    <li class="mb-2">Describe the research team composition (e.g., PI, postdocs, students)</li>
                    <li class="mb-2">Note any travel requirements for conferences or fieldwork</li>
                    <li>Specify any project-specific requirements or constraints</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Include the base template
require_once 'includes/template.php';
?>