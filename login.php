<?php
// login.php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: projects.php");
    exit;
}

// Database connection
require_once 'config/Database.php';
require_once 'includes/User.php';

$database = new Database();
$db = $database->getConnection();

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
    } else {
        // Check if username exists
        $user = new User($db);
        $user->username = $username;
        
        if ($user->usernameExists()) {
            // Verify password
            if (password_verify($password, $user->password)) {
                // Password is correct, create session
                $_SESSION['user_id'] = $user->id;
                $_SESSION['username'] = $user->username;
                $_SESSION['email'] = $user->email;
                $_SESSION['first_name'] = $user->first_name;
                $_SESSION['last_name'] = $user->last_name;
                
                // Redirect to projects page
                header("Location: projects.php");
                exit;
            } else {
                $error_message = "Invalid password.";
            }
        } else {
            $error_message = "Username not found.";
        }
    }
}

// Include layout template
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-5 col-md-7">
        <div class="card">
            <div class="card-header text-center py-4">
                <h4 class="mb-0">Login to Grant Genie</h4>
            </div>
            <div class="card-body p-4">
                <?php if (isset($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" required autofocus value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="" id="remember-me">
                        <label class="form-check-label" for="remember-me">
                            Remember me
                        </label>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-4">
                    <p class="mb-0">Don't have an account? <a href="register.php">Register</a></p>
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