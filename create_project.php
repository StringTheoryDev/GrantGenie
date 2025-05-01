<?php
// create_project.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "Please log in to create a project.";
    $_SESSION['message_type'] = "warning";
    header("Location: login.php");
    exit;
}

// Database connection
require_once 'config/Database.php';
require_once 'includes/Project.php';

$database = new Database();
$db = $database->getConnection();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project = new Project($db);
    
    // Set project properties
    $project->user_id = $_SESSION['user_id'];
    $project->title = $_POST['title'];
    $project->description = $_POST['description'];
    $project->grant_type = $_POST['grant_type'];
    $project->duration_years = $_POST['duration_years'];
    
    // Create project
    if ($project->create()) {
        $_SESSION['message'] = "Project created successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: project_description.php?id=" . $project->id);
        exit;
    } else {
        $error_message = "Unable to create project.";
    }
}

// Include layout template
ob_start();
?>

<h1 class="mb-4">Create New Project</h1>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Project Details</h5>
    </div>
    <div class="card-body">
        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="mb-3">
                <label for="title" class="form-label">Project Title</label>
                <input type="text" class="form-control" id="title" name="title" required>
                <div class="form-text">Enter a descriptive title for your grant project.</div>
            </div>
            
            <div class="mb-3">
                <label for="grant_type" class="form-label">Grant Type</label>
                <select class="form-select" id="grant_type" name="grant_type" required>
                    <option value="">Select a grant type</option>
                    <option value="NSF">National Science Foundation (NSF)</option>
                    <option value="NIH">National Institutes of Health (NIH)</option>
                </select>
                <div class="form-text">Select the funding agency for your grant proposal.</div>
            </div>
            
            <div class="mb-3">
                <label for="duration_years" class="form-label">Project Duration (Years)</label>
                <select class="form-select" id="duration_years" name="duration_years" required>
                    <option value="1">1 Year</option>
                    <option value="2">2 Years</option>
                    <option value="3">3 Years</option>
                    <option value="4">4 Years</option>
                    <option value="5" selected>5 Years</option>
                </select>
                <div class="form-text">Select the planned duration of your research project.</div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Brief Project Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                <div class="form-text">Provide a brief overview of your project. You can add more details in the next step.</div>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Create Project
            </button>
            <a href="projects.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5 class="card-title mb-0">What Happens Next?</h5>
    </div>
    <div class="card-body">
        <div class="wizard-steps">
            <div class="wizard-step active">
                <div class="wizard-step-number">1</div>
                <div class="wizard-step-label">Create Project</div>
            </div>
            <div class="wizard-step">
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
        
        <div class="mt-4">
            <p>After creating your project, you'll be able to:</p>
            <ol>
                <li>Enter a detailed project description or upload your grant proposal document</li>
                <li>Let our AI generate a complete budget based on your description</li>
                <li>Review and customize the budget with real-time validation</li>
                <li>Export the final budget as an Excel spreadsheet</li>
            </ol>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Include the base template
require_once 'includes/template.php';
?>