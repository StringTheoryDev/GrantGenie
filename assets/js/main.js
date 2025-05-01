// assets/js/main.js

$(document).ready(function() {
    // Initialize tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    
    // Budget item dynamic forms
    $('.add-budget-item').on('click', function() {
        const category = $(this).data('category');
        const year = $(this).data('year');
        const template = $('#budget-item-template').html();
        const newItem = template
            .replace(/\{category\}/g, category)
            .replace(/\{year\}/g, year)
            .replace(/\{index\}/g, Date.now()); // Use timestamp as unique index
        
        $(`#items-${category}-${year}`).append(newItem);
        
        // Re-calculate totals
        calculateTotals();
    });
    
    // Remove budget item
    $(document).on('click', '.remove-budget-item', function() {
        $(this).closest('.budget-item-row').remove();
        
        // Re-calculate totals
        calculateTotals();
    });
    
    // Calculate totals when amount or quantity changes
    $(document).on('change', '.budget-amount, .budget-quantity', function() {
        calculateTotals();
    });
    
    // Function to calculate all totals
    function calculateTotals() {
        // Calculate item totals
        $('.budget-item-row').each(function() {
            const amount = parseFloat($(this).find('.budget-amount').val()) || 0;
            const quantity = parseInt($(this).find('.budget-quantity').val()) || 0;
            const total = amount * quantity;
            
            $(this).find('.item-total').text('$' + total.toFixed(2));
        });
        
        // Calculate category totals
        const years = $('.budget-year-tab').map(function() {
            return $(this).data('year');
        }).get();
        
        const categories = $('.category-row').map(function() {
            return $(this).data('category');
        }).get();
        
        // For each year and category
        years.forEach(year => {
            let yearTotal = 0;
            
            categories.forEach(category => {
                let categoryTotal = 0;
                
                // Sum all items in this category for this year
                $(`#items-${category}-${year} .budget-item-row`).each(function() {
                    const amount = parseFloat($(this).find('.budget-amount').val()) || 0;
                    const quantity = parseInt($(this).find('.budget-quantity').val()) || 0;
                    categoryTotal += amount * quantity;
                });
                
                // Update category total
                $(`#total-${category}-${year}`).text('$' + categoryTotal.toFixed(2));
                
                yearTotal += categoryTotal;
            });
            
            // Update year total
            $(`#total-year-${year}`).text('$' + yearTotal.toFixed(2));
        });
        
        // Calculate overall total
        let grandTotal = 0;
        $('.year-total').each(function() {
            const total = parseFloat($(this).text().replace('$', '')) || 0;
            grandTotal += total;
        });
        
        $('#grand-total').text('$' + grandTotal.toFixed(2));
    }
    
    // Budget validation
    $('.budget-form').on('submit', function(e) {
        if ($(this).hasClass('skip-validation')) {
            return true;
        }
        
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('[type="submit"]');
        const originalBtnText = submitBtn.html();
        
        // Show loading state
        submitBtn.html('<span class="loading-spinner me-2"></span>Validating...');
        submitBtn.prop('disabled', true);
        
        // Send data for validation
        $.ajax({
            url: 'api/validate_budget.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                // Clear previous warnings
                $('.validation-warning').remove();
                
                if (response.valid) {
                    // No validation errors, submit the form
                    form.addClass('skip-validation');
                    form.submit();
                } else {
                    // Show validation warnings
                    const warningsContainer = $('<div class="validation-warnings mb-4"></div>');
                    
                    response.errors.forEach(error => {
                        const warning = $(`
                            <div class="validation-warning">
                                <h5>${error.item_name} (${error.category}, Year ${error.year})</h5>
                                <ul class="mb-0">
                                    ${error.errors.map(err => `
                                        <li>
                                            <strong>${err.rule_name}:</strong> ${err.error_message}
                                            ${err.suggestion ? `<div class="suggestion mt-1"><i class="fas fa-lightbulb me-1"></i>${err.suggestion}</div>` : ''}
                                        </li>
                                    `).join('')}
                                </ul>
                            </div>
                        `);
                        
                        warningsContainer.append(warning);
                    });
                    
                    form.prepend(warningsContainer);
                    
                    // Scroll to warnings
                    $('html, body').animate({
                        scrollTop: warningsContainer.offset().top - 100
                    }, 500);
                    
                    // Add override button
                    if (!$('#override-validation').length) {
                        const overrideBtn = $(`
                            <button type="button" id="override-validation" class="btn btn-warning ms-2">
                                <i class="fas fa-exclamation-triangle me-1"></i>Override Validation
                            </button>
                        `);
                        
                        submitBtn.after(overrideBtn);
                        
                        // Override validation when clicked
                        overrideBtn.on('click', function() {
                            form.addClass('skip-validation');
                            form.submit();
                        });
                    }
                }
                
                // Restore button
                submitBtn.html(originalBtnText);
                submitBtn.prop('disabled', false);
            },
            error: function(xhr, status, error) {
                console.error('Validation error:', error);
                
                // Show error message
                const errorMsg = $(`
                    <div class="alert alert-danger mb-4">
                        An error occurred during validation. Please try again.
                    </div>
                `);
                
                form.prepend(errorMsg);
                
                // Restore button
                submitBtn.html(originalBtnText);
                submitBtn.prop('disabled', false);
            }
        });
    });
    
    // Handle the file upload for project description
    $('#grant-objective-file').on('change', function() {
        const fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
        
        // If file is selected, show the upload button
        if (fileName) {
            $('#upload-file-btn').removeClass('d-none');
        } else {
            $('#upload-file-btn').addClass('d-none');
        }
    });
    
    // File upload processing
    $('#upload-file-btn').on('click', function() {
        const fileInput = $('#grant-objective-file')[0];
        
        if (fileInput.files.length === 0) {
            return;
        }
        
        const file = fileInput.files[0];
        const formData = new FormData();
        formData.append('file', file);
        
        // Show loading state
        const button = $(this);
        const originalText = button.html();
        button.html('<span class="loading-spinner me-2"></span>Processing...');
        button.prop('disabled', true);
        
        // Upload and process file
        $.ajax({
            url: 'api/process_file.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    
                    if (data.success) {
                        // File processed successfully, update the textarea with the content
                        $('#project-description').val(data.content);
                        
                        // Show success message
                        const successMsg = $(`
                            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                                File processed successfully!
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `);
                        
                        $('#file-upload-container').after(successMsg);
                    } else {
                        // Show error message
                        const errorMsg = $(`
                            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                                ${data.message || 'An error occurred while processing the file.'}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `);
                        
                        $('#file-upload-container').after(errorMsg);
                    }
                } catch (e) {
                    // Show error message
                    const errorMsg = $(`
                        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                            An error occurred while processing the file.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `);
                    
                    $('#file-upload-container').after(errorMsg);
                }
                
                // Restore button
                button.html(originalText);
                button.prop('disabled', false);
            },
            error: function(xhr, status, error) {
                // Show error message
                const errorMsg = $(`
                    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                        An error occurred while uploading the file.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
                
                $('#file-upload-container').after(errorMsg);
                
                // Restore button
                button.html(originalText);
                button.prop('disabled', false);
            }
        });
    });
    
    // Show confirmation modal for delete actions
    $('.delete-confirm').on('click', function(e) {
        e.preventDefault();
        
        const targetUrl = $(this).attr('href');
        
        if (confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
            window.location.href = targetUrl;
        }
    });
    
    // Initialize budget generation
    $('#generate-budget-btn').on('click', function() {
        const button = $(this);
        const originalText = button.html();
        button.html('<span class="loading-spinner me-2"></span>Generating Budget...');
        button.prop('disabled', true);
        
        $.ajax({
            url: 'api/generate_budget.php',
            type: 'POST',
            data: {
                project_id: button.data('project-id')
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Redirect to the budget editor
                    window.location.href = 'edit_budget.php?id=' + button.data('project-id');
                } else {
                    // Show error message
                    const errorMsg = $(`
                        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                            ${response.message || 'An error occurred while generating the budget.'}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `);
                    
                    button.parent().after(errorMsg);
                    
                    // Restore button
                    button.html(originalText);
                    button.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                // Show error message
                const errorMsg = $(`
                    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                        An error occurred while generating the budget. Please try again.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
                
                button.parent().after(errorMsg);
                
                // Restore button
                button.html(originalText);
                button.prop('disabled', false);
            }
        });
    });
});