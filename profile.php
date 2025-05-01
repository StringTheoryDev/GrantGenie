<?php
// profile.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "Please log in to access your profile.";
    $_SESSION['message_type'] = "warning";
    header("Location: login.php");
    exit;
}

// Database connection
require_once 'config/Database.php';
require_once 'includes/User.php';
require_once 'includes/Project.php';

$database = new Database();
$db = $database->getConnection();

// Get user details
$user = new User($db);
$user->id = $_SESSION['user_id'];
$user->username = $_SESSION['username'];
$user->email = $_SESSION['email'];
$user->first_name = $_SESSION['first_name'];
$user->last_name = $_SESSION['last_name'];

// Get user's projects count
$project = new Project($db);
$project->user_id = $_SESSION['user_id'];
$stmt = $project->readByUser();
$projectCount = $stmt->rowCount();

// Count projects with budgets
$budgetCount = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $query = "SELECT COUNT(*) FROM budget_items WHERE project_id = ?";
    $budgetStmt = $db->prepare($query);
    $budgetStmt->bindParam(1, $row['id']);
    $budgetStmt->execute();
    $count = $budgetStmt->fetchColumn();
    
    if ($count > 0) {
        $budgetCount++;
    }
}

// Process profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $user->email = $_POST['email'];
    $user->first_name = $_POST['first_name'];
    $user->last_name = $_POST['last_name'];
    $user->institution = $_POST['institution'];
    
    if ($user->update()) {
        // Update session variables
        $_SESSION['email'] = $user->email;
        $_SESSION['first_name'] = $user->first_name;
        $_SESSION['last_name'] = $user->last_name;
        
        $success_message = "Profile updated successfully.";
    } else {
        $error_message = "Failed to update profile.";
    }
}

// Process password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $password_error = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $password_error = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $password_error = "New password must be at least 6 characters.";
    } else {
        // Verify current password
        $query = "SELECT password FROM users WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $_SESSION['user_id']);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($current_password, $row['password'])) {
            // Current password is correct, update to new password
            $user->password = $new_password;
            if ($user->updatePassword()) {
                $password_success = "Password changed successfully.";
            } else {
                $password_error = "Failed to update password.";
            }
        } else {
            $password_error = "Current password is incorrect.";
        }
    }
}

// Include layout template
ob_start();
?>

<h1 class="mb-4">Your Profile</h1>

<div class="row">
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Account Information</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="mb-3">
                        <i class="fas fa-user-circle fa-5x text-primary"></i>
                    </div>
                    <h4><?php echo htmlspecialchars($_SESSION['username']); ?></h4>
                    <p class="text-muted mb-0">
                        <?php 
                        if (!empty($_SESSION['first_name']) && !empty($_SESSION['last_name'])) {
                            echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
                        } 
                        ?>
                    </p>
                </div>
                
                <hr>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between text-muted mb-2">
                        <span>Email:</span>
                        <span class="fw-bold"><?php echo htmlspecialchars($_SESSION['email']); ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between text-muted mb-2">
                        <span>Projects:</span>
                        <span class="fw-bold"><?php echo $projectCount; ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between text-muted">
                        <span>Created Budgets:</span>
                        <span class="fw-bold"><?php echo $budgetCount; ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Change Password</h5>
            </div>
            <div class="card-body">
                <?php if (isset($password_error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $password_error; ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($password_success)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $password_success; ?>
                </div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="mb-3">
                        <label for="current-password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current-password" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new-password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new-password" name="new_password" required>
                        <div class="form-text">At least 6 characters.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm-password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm-password" name="confirm_password" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" name="change_password" class="btn btn-outline-primary">
                            <i class="fas fa-key me-2"></i>Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Edit Profile</h5>
            </div>
            <div class="card-body">
                <?php if (isset($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success_message; ?>
                </div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" disabled>
                        <div class="form-text">Username cannot be changed.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first-name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first-name" name="first_name" value="<?php echo htmlspecialchars($_SESSION['first_name']); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="last-name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last-name" name="last_name" value="<?php echo htmlspecialchars($_SESSION['last_name']); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="institution" class="form-label">Institution</label>
                        <input type="text" class="form-control" id="institution" name="institution" value="<?php echo htmlspecialchars($user->institution); ?>">
                        <div class="form-text">University, research center, or organization.</div>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Activity</h5>
            </div>
            <div class="card-body">
                <?php
                // Get recent projects
                $query = "SELECT * FROM projects WHERE user_id = ? ORDER BY updated_at DESC LIMIT 5";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $_SESSION['user_id']);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0):
                ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Grant Type</th>
                            <th>Last Updated</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['grant_type']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($row['updated_at'])); ?></td>
                            <td>
                                <?php
                                // Check if this project has a budget
                                $budgetQuery = "SELECT COUNT(*) FROM budget_items WHERE project_id = ?";
                                $budgetStmt = $db->prepare($budgetQuery);
                                $budgetStmt->bindParam(1, $row['id']);
                                $budgetStmt->execute();
                                $hasBudget = $budgetStmt->fetchColumn() > 0;
                                
                                if ($hasBudget):
                                ?>
                                <a href="edit_budget.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php else: ?>
                                <a href="project_description.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="text-center py-3">
                    <p class="text-muted mb-0">No recent activity.</p>
                </div>
                <?php endif; ?>
                
                <div class="text-center mt-3">
                    <a href="projects.php" class="btn btn-outline-primary">
                        <i class="fas fa-folder me-2"></i>View All Projects
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