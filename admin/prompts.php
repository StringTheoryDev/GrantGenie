<?php
// admin/prompts.php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

// Database connection
require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

// Handle form submissions
$message = '';
$message_type = '';

// Add new prompt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_prompt'])) {
    $prompt_name = $_POST['prompt_name'];
    $prompt_text = $_POST['prompt_text'];
    $grant_type = $_POST['grant_type'];
    
    // Validate input
    if (empty($prompt_name) || empty($prompt_text) || empty($grant_type)) {
        $message = "All fields are required.";
        $message_type = "danger";
    } else {
        // Insert prompt
        $query = "INSERT INTO ai_prompts (prompt_name, prompt_text, grant_type) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $prompt_name);
        $stmt->bindParam(2, $prompt_text);
        $stmt->bindParam(3, $grant_type);
        
        if ($stmt->execute()) {
            $message = "Prompt added successfully.";
            $message_type = "success";
        } else {
            $message = "Failed to add prompt.";
            $message_type = "danger";
        }
    }
}

// Update prompt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_prompt'])) {
    $prompt_id = $_POST['prompt_id'];
    $prompt_name = $_POST['prompt_name'];
    $prompt_text = $_POST['prompt_text'];
    $grant_type = $_POST['grant_type'];
    
    // Validate input
    if (empty($prompt_id) || empty($prompt_name) || empty($prompt_text) || empty($grant_type)) {
        $message = "All fields are required.";
        $message_type = "danger";
    } else {
        // Update prompt
        $query = "UPDATE ai_prompts SET prompt_name = ?, prompt_text = ?, grant_type = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $prompt_name);
        $stmt->bindParam(2, $prompt_text);
        $stmt->bindParam(3, $grant_type);
        $stmt->bindParam(4, $prompt_id);
        
        if ($stmt->execute()) {
            $message = "Prompt updated successfully.";
            $message_type = "success";
        } else {
            $message = "Failed to update prompt.";
            $message_type = "danger";
        }
    }
}

// Delete prompt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_prompt'])) {
    $prompt_id = $_POST['prompt_id'];
    
    // Validate input
    if (empty($prompt_id)) {
        $message = "Prompt ID is required.";
        $message_type = "danger";
    } else {
        // Delete prompt
        $query = "DELETE FROM ai_prompts WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $prompt_id);
        
        if ($stmt->execute()) {
            $message = "Prompt deleted successfully.";
            $message_type = "success";
        } else {
            $message = "Failed to delete prompt.";
            $message_type = "danger";
        }
    }
}

// Get all prompts
$query = "SELECT * FROM ai_prompts ORDER BY grant_type, prompt_name";
$stmt = $db->prepare($query);
$stmt->execute();
$prompts = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Prompts - Grant Genie Admin</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }
        .nav-link {
            color: #333;
        }
        .nav-link.active {
            background-color: #e9ecef;
            color: #4e73df;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-magic me-2"></i>Grant Genie Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-shield me-1"></i><?php echo $_SESSION['admin_username']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="../index.php" target="_blank">View Site</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users me-2"></i>Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="projects.php">
                                <i class="fas fa-folder me-2"></i>Projects
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="prompts.php">
                                <i class="fas fa-robot me-2"></i>AI Prompts
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="rules.php">
                                <i class="fas fa-check-circle me-2"></i>Budget Rules
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="settings.php">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">AI Prompts Management</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPromptModal">
                        <i class="fas fa-plus me-1"></i>Add New Prompt
                    </button>
                </div>
                
                <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">AI Prompts</h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Info:</strong> AI prompts are used to generate budget items based on project descriptions. 
                            Each prompt can be specific to a grant type (NSF, NIH, etc.).
                        </div>
                        
                        <?php if (count($prompts) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Grant Type</th>
                                        <th>Updated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($prompts as $prompt): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($prompt['prompt_name']); ?></td>
                                        <td><?php echo htmlspecialchars($prompt['grant_type']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($prompt['updated_at'])); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info edit-prompt" 
                                                data-id="<?php echo $prompt['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($prompt['prompt_name']); ?>"
                                                data-type="<?php echo htmlspecialchars($prompt['grant_type']); ?>"
                                                data-text="<?php echo htmlspecialchars($prompt['prompt_text']); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger delete-prompt" 
                                                data-id="<?php echo $prompt['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($prompt['prompt_name']); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-muted mb-0">No prompts found. Click the "Add New Prompt" button to create one.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Footer -->
                <footer class="bg-light text-center text-lg-start mt-4">
                    <div class="text-center p-3">
                        &copy; 2025 Grant Genie Admin Panel
                    </div>
                </footer>
            </div>
        </div>
    </div>

    <!-- Add Prompt Modal -->
    <div class="modal fade" id="addPromptModal" tabindex="-1" aria-labelledby="addPromptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPromptModalLabel">Add New AI Prompt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="prompt_name" class="form-label">Prompt Name</label>
                            <input type="text" class="form-control" id="prompt_name" name="prompt_name" required>
                            <div class="form-text">A descriptive name for this prompt (e.g., "budget_generation").</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="grant_type" class="form-label">Grant Type</label>
                            <select class="form-select" id="grant_type" name="grant_type" required>
                                <option value="">Select a grant type</option>
                                <option value="NSF">National Science Foundation (NSF)</option>
                                <option value="NIH">National Institutes of Health (NIH)</option>
                                <option value="ALL">All Grant Types</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="prompt_text" class="form-label">Prompt Text</label>
                            <textarea class="form-control" id="prompt_text" name="prompt_text" rows="10" required></textarea>
                            <div class="form-text">
                                Include placeholders like {grant_type} and {duration_years} that will be replaced with actual values.
                                Format the prompt to generate JSON output with budget items.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_prompt" class="btn btn-primary">Add Prompt</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Prompt Modal -->
    <div class="modal fade" id="editPromptModal" tabindex="-1" aria-labelledby="editPromptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPromptModalLabel">Edit AI Prompt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <input type="hidden" id="edit_prompt_id" name="prompt_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_prompt_name" class="form-label">Prompt Name</label>
                            <input type="text" class="form-control" id="edit_prompt_name" name="prompt_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_grant_type" class="form-label">Grant Type</label>
                            <select class="form-select" id="edit_grant_type" name="grant_type" required>
                                <option value="">Select a grant type</option>
                                <option value="NSF">National Science Foundation (NSF)</option>
                                <option value="NIH">National Institutes of Health (NIH)</option>
                                <option value="ALL">All Grant Types</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_prompt_text" class="form-label">Prompt Text</label>
                            <textarea class="form-control" id="edit_prompt_text" name="prompt_text" rows="10" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_prompt" class="btn btn-primary">Update Prompt</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Prompt Modal -->
    <div class="modal fade" id="deletePromptModal" tabindex="-1" aria-labelledby="deletePromptModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deletePromptModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the prompt "<span id="delete_prompt_name"></span>"?</p>
                    <p>This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <input type="hidden" id="delete_prompt_id" name="prompt_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_prompt" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Edit prompt button
            $('.edit-prompt').click(function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                var type = $(this).data('type');
                var text = $(this).data('text');
                
                $('#edit_prompt_id').val(id);
                $('#edit_prompt_name').val(name);
                $('#edit_grant_type').val(type);
                $('#edit_prompt_text').val(text);
                
                $('#editPromptModal').modal('show');
            });
            
            // Delete prompt button
            $('.delete-prompt').click(function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                
                $('#delete_prompt_id').val(id);
                $('#delete_prompt_name').text(name);
                
                $('#deletePromptModal').modal('show');
            });
        });
    </script>
</body>
</html>