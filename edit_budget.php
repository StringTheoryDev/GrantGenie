<?php
// edit_budget.php
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
require_once 'includes/BudgetValidator.php';

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

// Get budget items
$budgetItem = new BudgetItem($db);
$budgetItem->project_id = $project->id;
$stmt = $budgetItem->readByProject();

// Organize items by year and category
$itemsByYear = [];
$categories = [];
$years = range(1, $project->duration_years);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $year = $row['year'];
    $category = $row['category'];
    
    if (!isset($itemsByYear[$year])) {
        $itemsByYear[$year] = [];
    }
    
    if (!isset($itemsByYear[$year][$category])) {
        $itemsByYear[$year][$category] = [];
    }
    
    $itemsByYear[$year][$category][] = $row;
    
    if (!in_array($category, $categories)) {
        $categories[] = $category;
    }
}

// Sort categories alphabetically
sort($categories);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_budget'])) {
    // Update existing items
    if (isset($_POST['item_id'])) {
        foreach ($_POST['item_id'] as $index => $id) {
            $updateItem = new BudgetItem($db);
            $updateItem->id = $id;
            $updateItem->category = $_POST['category'][$index];
            $updateItem->item_name = $_POST['item_name'][$index];
            $updateItem->description = $_POST['description'][$index];
            $updateItem->year = $_POST['year'][$index];
            $updateItem->amount = $_POST['amount'][$index];
            $updateItem->quantity = $_POST['quantity'][$index];
            $updateItem->justification = $_POST['justification'][$index];
            
            $updateItem->update();
        }
    }
    
    // Add new items
    if (isset($_POST['new_item_name'])) {
        foreach ($_POST['new_item_name'] as $index => $name) {
            if (!empty($name)) {
                $newItem = new BudgetItem($db);
                $newItem->project_id = $project->id;
                $newItem->category = $_POST['new_category'][$index];
                $newItem->item_name = $name;
                $newItem->description = $_POST['new_description'][$index];
                $newItem->year = $_POST['new_year'][$index];
                $newItem->amount = $_POST['new_amount'][$index];
                $newItem->quantity = $_POST['new_quantity'][$index];
                $newItem->justification = $_POST['new_justification'][$index];
                $newItem->is_edited = true;
                
                $newItem->create();
            }
        }
    }
    
    // Delete items marked for deletion
    if (isset($_POST['delete_item'])) {
        foreach ($_POST['delete_item'] as $id) {
            $deleteItem = new BudgetItem($db);
            $deleteItem->id = $id;
            $deleteItem->delete();
        }
    }
    
    $_SESSION['message'] = "Budget updated successfully!";
    $_SESSION['message_type'] = "success";
    header("Location: edit_budget.php?id=" . $project->id);
    exit;
}

// Include layout template
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?php echo htmlspecialchars($project->title); ?> - Budget</h1>
    <div>
        <a href="export_budget.php?id=<?php echo $project->id; ?>" class="btn btn-success me-2">
            <i class="fas fa-file-excel me-2"></i>Export to Excel
        </a>
        <a href="projects.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Projects
        </a>
    </div>
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
    <div class="wizard-step active">
        <div class="wizard-step-number">4</div>
        <div class="wizard-step-label">Edit & Validate</div>
    </div>
    <div class="wizard-step">
        <div class="wizard-step-number">5</div>
        <div class="wizard-step-label">Export</div>
    </div>
</div>

<!-- Budget Summary Card -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Budget Summary</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Category</th>
                        <?php foreach ($years as $year): ?>
                        <th>Year <?php echo $year; ?></th>
                        <?php endforeach; ?>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $yearTotals = array_fill(1, count($years), 0);
                    $grandTotal = 0;
                    
                    foreach ($categories as $category): 
                        $categoryTotals = array_fill(1, count($years), 0);
                        $categoryTotal = 0;
                    ?>
                    <tr>
                        <td><strong><?php echo ucfirst($category); ?></strong></td>
                        
                        <?php foreach ($years as $year): 
                            $total = 0;
                            
                            if (isset($itemsByYear[$year][$category])) {
                                foreach ($itemsByYear[$year][$category] as $item) {
                                    $total += $item['amount'] * $item['quantity'];
                                }
                            }
                            
                            $categoryTotals[$year] = $total;
                            $yearTotals[$year] += $total;
                            $categoryTotal += $total;
                        ?>
                        <td class="text-end">$<?php echo number_format($total, 2); ?></td>
                        <?php endforeach; ?>
                        
                        <td class="text-end fw-bold">$<?php echo number_format($categoryTotal, 2); ?></td>
                    </tr>
                    <?php 
                        $grandTotal += $categoryTotal;
                    endforeach; 
                    ?>
                    
                    <tr class="table-light">
                        <td><strong>Total</strong></td>
                        
                        <?php foreach ($years as $year): ?>
                        <td class="text-end fw-bold">$<?php echo number_format($yearTotals[$year], 2); ?></td>
                        <?php endforeach; ?>
                        
                        <td class="text-end fw-bold">$<?php echo number_format($grandTotal, 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Budget Editor -->
<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="budgetTabs" role="tablist">
            <?php foreach ($years as $index => $year): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo ($index === 0) ? 'active' : ''; ?>" 
                        id="year<?php echo $year; ?>-tab" 
                        data-bs-toggle="tab" 
                        data-bs-target="#year<?php echo $year; ?>" 
                        type="button" 
                        role="tab" 
                        aria-controls="year<?php echo $year; ?>" 
                        aria-selected="<?php echo ($index === 0) ? 'true' : 'false'; ?>">
                    Year <?php echo $year; ?>
                </button>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="card-body">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $project->id); ?>" method="post" class="budget-form">
            <div class="tab-content" id="budgetTabsContent">
                <?php foreach ($years as $index => $year): ?>
                <div class="tab-pane fade <?php echo ($index === 0) ? 'show active' : ''; ?>" 
                     id="year<?php echo $year; ?>" 
                     role="tabpanel" 
                     aria-labelledby="year<?php echo $year; ?>-tab"
                     data-year="<?php echo $year; ?>">
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4>Year <?php echo $year; ?> Budget</h4>
                        <div class="year-total-container">
                            <span class="fw-bold">Total: </span>
                            <span id="total-year-<?php echo $year; ?>" class="year-total">$<?php echo number_format($yearTotals[$year], 2); ?></span>
                        </div>
                    </div>
                    
                    <div class="accordion" id="accordion-year<?php echo $year; ?>">
                        <?php foreach ($categories as $catIndex => $category): ?>
                        <div class="accordion-item category-row" data-category="<?php echo $category; ?>">
                            <h2 class="accordion-header" id="heading-<?php echo $category; ?>-<?php echo $year; ?>">
                                <button class="accordion-button <?php echo ($catIndex !== 0) ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $category; ?>-<?php echo $year; ?>" aria-expanded="<?php echo ($catIndex === 0) ? 'true' : 'false'; ?>" aria-controls="collapse-<?php echo $category; ?>-<?php echo $year; ?>">
                                    <div class="d-flex justify-content-between align-items-center w-100">
                                        <span><?php echo ucfirst($category); ?></span>
                                        <span id="total-<?php echo $category; ?>-<?php echo $year; ?>" class="badge bg-primary rounded-pill ms-2">
                                            $<?php 
                                            $total = 0;
                                            if (isset($itemsByYear[$year][$category])) {
                                                foreach ($itemsByYear[$year][$category] as $item) {
                                                    $total += $item['amount'] * $item['quantity'];
                                                }
                                            }
                                            echo number_format($total, 2); 
                                            ?>
                                        </span>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse-<?php echo $category; ?>-<?php echo $year; ?>" class="accordion-collapse collapse <?php echo ($catIndex === 0) ? 'show' : ''; ?>" aria-labelledby="heading-<?php echo $category; ?>-<?php echo $year; ?>" data-bs-parent="#accordion-year<?php echo $year; ?>">
                                <div class="accordion-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead>
                                                <tr>
                                                    <th style="width: 25%;">Item</th>
                                                    <th style="width: 20%;">Description</th>
                                                    <th style="width: 10%;">Amount</th>
                                                    <th style="width: 10%;">Quantity</th>
                                                    <th style="width: 10%;">Total</th>
                                                    <th style="width: 20%;">Justification</th>
                                                    <th style="width: 5%;"></th>
                                                </tr>
                                            </thead>
                                            <tbody id="items-<?php echo $category; ?>-<?php echo $year; ?>">
                                                <?php 
                                                if (isset($itemsByYear[$year][$category])) {
                                                    foreach ($itemsByYear[$year][$category] as $item) {
                                                        $itemTotal = $item['amount'] * $item['quantity'];
                                                ?>
                                                <tr class="budget-item-row">
                                                    <td>
                                                        <input type="hidden" name="item_id[]" value="<?php echo $item['id']; ?>">
                                                        <input type="hidden" name="category[]" value="<?php echo $category; ?>">
                                                        <input type="hidden" name="year[]" value="<?php echo $year; ?>">
                                                        <input type="text" class="form-control form-control-sm" name="item_name[]" value="<?php echo htmlspecialchars($item['item_name']); ?>" required>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm" name="description[]" value="<?php echo htmlspecialchars($item['description']); ?>">
                                                    </td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text">$</span>
                                                            <input type="number" class="form-control form-control-sm budget-amount" name="amount[]" min="0" step="0.01" value="<?php echo $item['amount']; ?>" required>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control form-control-sm budget-quantity" name="quantity[]" min="1" value="<?php echo $item['quantity']; ?>" required>
                                                    </td>
                                                    <td>
                                                        <span class="item-total">$<?php echo number_format($itemTotal, 2); ?></span>
                                                    </td>
                                                    <td>
                                                        <textarea class="form-control form-control-sm" name="justification[]" rows="1"><?php echo htmlspecialchars($item['justification']); ?></textarea>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-outline-danger remove-budget-item" title="Remove item" data-bs-toggle="tooltip">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                        <input type="checkbox" name="delete_item[]" value="<?php echo $item['id']; ?>" class="d-none">
                                                    </td>
                                                </tr>
                                                <?php 
                                                    }
                                                }
                                                ?>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="7">
                                                        <button type="button" class="btn btn-sm btn-outline-primary add-budget-item" data-category="<?php echo $category; ?>" data-year="<?php echo $year; ?>">
                                                            <i class="fas fa-plus me-1"></i>Add Item
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="d-flex justify-content-between mt-4">
                <a href="generate_budget.php?id=<?php echo $project->id; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back
                </a>
                <div>
                    <button type="submit" name="save_budget" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Budget
                    </button>
                    <a href="export_budget.php?id=<?php echo $project->id; ?>" class="btn btn-success ms-2">
                        <i class="fas fa-file-excel me-2"></i>Export to Excel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Budget Item Template (Hidden) -->
<template id="budget-item-template">
    <tr class="budget-item-row">
        <td>
            <input type="hidden" name="new_category[]" value="{category}">
            <input type="hidden" name="new_year[]" value="{year}">
            <input type="text" class="form-control form-control-sm" name="new_item_name[]" placeholder="Item name" required>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm" name="new_description[]" placeholder="Description">
        </td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text">$</span>
                <input type="number" class="form-control form-control-sm budget-amount" name="new_amount[]" min="0" step="0.01" value="0.00" required>
            </div>
        </td>
        <td>
            <input type="number" class="form-control form-control-sm budget-quantity" name="new_quantity[]" min="1" value="1" required>
        </td>
        <td>
            <span class="item-total">$0.00</span>
        </td>
        <td>
            <textarea class="form-control form-control-sm" name="new_justification[]" rows="1" placeholder="Justification"></textarea>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger remove-budget-item" title="Remove item" data-bs-toggle="tooltip">
                <i class="fas fa-times"></i>
            </button>
        </td>
    </tr>
</template>

<?php
$content = ob_get_clean();

// Include the base template
require_once 'includes/template.php';
?>