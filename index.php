<?php
// index.php
session_start();

// Include layout template
ob_start();
?>

<!-- Hero Section -->
<section class="py-5 text-center">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <h1 class="display-4 fw-bold mb-4">Welcome to Grant Genie</h1>
            <p class="lead mb-4">The AI-powered budget generator for NSF and NIH grant proposals. Create compliant research budgets effortlessly.</p>
            
            <div class="d-flex justify-content-center gap-3">
                <?php if(isset($_SESSION['user_id'])): ?>
                <a href="create_project.php" class="btn btn-primary btn-lg px-4">
                    <i class="fas fa-plus me-2"></i>Create New Project
                </a>
                <a href="projects.php" class="btn btn-outline-primary btn-lg px-4">
                    <i class="fas fa-folder me-2"></i>My Projects
                </a>
                <?php else: ?>
                <a href="register.php" class="btn btn-primary btn-lg px-4">
                    <i class="fas fa-user-plus me-2"></i>Sign Up
                </a>
                <a href="login.php" class="btn btn-outline-primary btn-lg px-4">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100 dashboard-card dashboard-card-primary">
                <div class="card-body">
                    <div class="mb-3">
                        <i class="fas fa-robot fa-3x text-primary"></i>
                    </div>
                    <h3 class="card-title">AI-Powered Generation</h3>
                    <p class="card-text">Simply describe your grant project or upload your proposal, and our AI will generate a complete budget adhering to NSF and NIH guidelines.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card h-100 dashboard-card dashboard-card-success">
                <div class="card-body">
                    <div class="mb-3">
                        <i class="fas fa-check-circle fa-3x text-success"></i>
                    </div>
                    <h3 class="card-title">Compliance Validation</h3>
                    <p class="card-text">Real-time validation ensures your budget adheres to all agency guidelines, with clear warnings and suggestions when issues are detected.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card h-100 dashboard-card dashboard-card-warning">
                <div class="card-body">
                    <div class="mb-3">
                        <i class="fas fa-edit fa-3x text-warning"></i>
                    </div>
                    <h3 class="card-title">Fully Editable</h3>
                    <p class="card-text">Customize any aspect of your generated budget with our intuitive editor, maintaining compliance while tailoring it to your specific needs.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="py-5">
    <h2 class="text-center mb-5">How It Works</h2>
    
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="wizard-steps">
                <div class="wizard-step active">
                    <div class="wizard-step-number">1</div>
                    <div class="wizard-step-label">Create Project</div>
                </div>
                <div class="wizard-step">
                    <div class="wizard-step-number">2</div>
                    <div class="wizard-step-label">Describe or Upload</div>
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
            
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title">1. Create Your Project</h4>
                    <p class="card-text">Start by creating a new project with basic information like title, grant type, and duration.</p>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title">2. Describe Your Research</h4>
                    <p class="card-text">Enter a description of your research project or upload your grant proposal document.</p>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title">3. Generate Your Budget</h4>
                    <p class="card-text">Our AI analyzes your project description and automatically generates a complete budget adhering to agency guidelines.</p>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title">4. Edit and Validate</h4>
                    <p class="card-text">Review and customize your budget with real-time validation to ensure compliance with all guidelines.</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">5. Export Your Budget</h4>
                    <p class="card-text">Download your completed budget as an Excel spreadsheet ready for submission with your grant proposal.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="py-5 bg-light mt-5">
    <div class="container">
        <h2 class="text-center mb-5">What Researchers Say</h2>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="mb-3 text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="card-text">"Grant Genie saved me hours of work and helped ensure my budget was compliant with all NSF guidelines. The AI suggestions were spot-on!"</p>
                        <div class="d-flex align-items-center mt-3">
                            <div class="ms-2">
                                <h5 class="mb-0">Dr. Sarah Johnson</h5>
                                <small class="text-muted">Biology Professor, Stanford University</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="mb-3 text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="card-text">"As a first-time grant applicant, I was overwhelmed by the budget requirements. Grant Genie made the process intuitive and straightforward."</p>
                        <div class="d-flex align-items-center mt-3">
                            <div class="ms-2">
                                <h5 class="mb-0">Dr. Michael Chen</h5>
                                <small class="text-muted">Assistant Professor, UC Berkeley</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="mb-3 text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                        <p class="card-text">"The validation feature caught several issues that would have been problematic during review. Grant Genie is now an essential part of our grant preparation process."</p>
                        <div class="d-flex align-items-center mt-3">
                            <div class="ms-2">
                                <h5 class="mb-0">Dr. Emily Rodriguez</h5>
                                <small class="text-muted">Research Director, Johns Hopkins</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5 text-center">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h2 class="mb-4">Ready to Simplify Your Grant Budget Process?</h2>
            
            <?php if(isset($_SESSION['user_id'])): ?>
            <a href="create_project.php" class="btn btn-primary btn-lg px-5">
                <i class="fas fa-magic me-2"></i>Create Your First Budget
            </a>
            <?php else: ?>
            <a href="register.php" class="btn btn-primary btn-lg px-5">
                <i class="fas fa-user-plus me-2"></i>Get Started Today
            </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();

// Include the base template
require_once 'includes/template.php';
?>