<?php
// register.php
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

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $institution = $_POST['institution'];
    
    // Validate input
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters.";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    // If no validation errors, check if username or email already exists
    if (empty($errors)) {
        $user = new User($db);
        
        // Check username
        $user->username = $username;
        if ($user->usernameExists()) {
            $errors[] = "Username already exists.";
        }
        
        // Check email
        $user->email = $email;
        if ($user->emailExists()) {
            $errors[] = "Email already in use.";
        }
    }
    
    // If still no errors, create the user
    if (empty($errors)) {
        $user = new User($db);
        $user->username = $username;
        $user->password = $password;
        $user->email = $email;
        $user->first_name = $first_name;
        $user->last_name = $last_name;
        $user->institution = $institution;
        
        if ($user->create()) {
            // Registration successful, set session variables
            $_SESSION['user_id'] = $user->id;
            $_SESSION['username'] = $user->username;
            $_SESSION['email'] = $user->email;
            $_SESSION['first_name'] = $user->first_name;
            $_SESSION['last_name'] = $user->last_name;
            
            // Redirect to projects page
            $_SESSION['message'] = "Registration successful! Welcome to Grant Genie.";
            $_SESSION['message_type'] = "success";
            header("Location: projects.php");
            exit;
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}

// Include layout template
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
        <div class="card">
            <div class="card-header text-center py-4">
                <h4 class="mb-0">Create an Account</h4>
            </div>
            <div class="card-body p-4">
                <?php if (isset($errors) && !empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <h5 class="alert-heading">Please correct the following errors:</h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                            <div class="form-text">Choose a unique username (at least 3 characters).</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                            <div class="form-text">We'll never share your email with anyone else.</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">At least 6 characters.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="confirm-password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm-password" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first-name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first-name" name="first_name" value="<?php echo isset($first_name) ? htmlspecialchars($first_name) : ''; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="last-name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last-name" name="last_name" value="<?php echo isset($last_name) ? htmlspecialchars($last_name) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="institution" class="form-label">Institution</label>
                        <input type="text" class="form-control" id="institution" name="institution" value="<?php echo isset($institution) ? htmlspecialchars($institution) : ''; ?>">
                        <div class="form-text">University, research center, or organization.</div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="terms" required>
                        <label class="form-check-label" for="terms">I agree to the terms and conditions</label>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>Register
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-4">
                    <p class="mb-0">Already have an account? <a href="login.php">Login</a></p>
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