<?php
  $page_title = 'Break-Even Analyzer';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
  page_require_level(1);
  
  // Fetch all products
  $products = find_all('products');
  
  // Debug: Check if products exist
  if(empty($products)) {
    $msg = 'No products found in database. Please add products first.';
  }
  
?>

<?php include_once('layouts/header.php'); ?>

<!-- Add Chart.js CDN with fallback -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
<script>
  // Debug script for Chart.js loading
  window.addEventListener('load', function() {
    console.log('Page loaded, Chart.js available:', typeof Chart !== 'undefined');
    if (typeof Chart === 'undefined') {
      console.error('Chart.js failed to load from CDN');
    } else {
      console.log('Chart.js version:', Chart.version || 'Unknown');
    }
  });

  // Form functionality
  document.addEventListener('DOMContentLoaded', function() {
    const resetBtn = document.getElementById('resetBtn');
    const form = document.querySelector('form');
    const productSelect = document.getElementById('product_id');
    
    // Reset button functionality
    if (resetBtn && form) {
      resetBtn.addEventListener('click', function() {
        // Clear all form fields manually
        const fixedCosts = document.getElementById('fixed_costs');
        const variableCost = document.getElementById('variable_cost');
        const sellingPrice = document.getElementById('selling_price');
        
        if (productSelect) productSelect.value = '';
        if (fixedCosts) fixedCosts.value = '';
        if (variableCost) variableCost.value = '';
        if (sellingPrice) {
          sellingPrice.value = '';
          sellingPrice.classList.remove('success', 'error');
        }
        
        // Clear any results sections
        const resultsSection = document.querySelector('.results-section');
        if (resultsSection) {
          resultsSection.remove();
        }
        
        // Clear any chart containers
        const chartContainer = document.querySelector('.chart-container');
        if (chartContainer) {
          chartContainer.remove();
        }
        
        // Clear any alert messages
        const alerts = document.querySelectorAll('.alert-enhanced');
        alerts.forEach(alert => alert.remove());
        
        // Clear any price load messages
        const priceMessages = document.querySelectorAll('.price-load-message');
        priceMessages.forEach(message => message.remove());
        
        // Scroll to top of form
        form.scrollIntoView({ behavior: 'smooth' });
        
        console.log('Form reset successfully');
      });
    }
    
    // Product selection feedback and auto-populate selling price
    if (productSelect) {
      productSelect.addEventListener('change', function() {
        if (this.value) {
          console.log('Product selected:', this.options[this.selectedIndex].text);
          // Add visual feedback
          this.style.borderColor = 'var(--primary-color)';
          this.parentElement.querySelector('.custom-select-arrow').style.color = 'var(--primary-color)';
          
          // Auto-populate selling price
          loadProductPrice(this.value);
        } else {
          this.style.borderColor = 'var(--border-color)';
          this.parentElement.querySelector('.custom-select-arrow').style.color = 'var(--text-secondary)';
          
          // Clear selling price when no product is selected
          const sellingPriceField = document.getElementById('selling_price');
          if (sellingPriceField) {
            sellingPriceField.value = '';
          }
        }
      });
      
      // Initialize visual state
      if (productSelect.value) {
        productSelect.style.borderColor = 'var(--primary-color)';
        productSelect.parentElement.querySelector('.custom-select-arrow').style.color = 'var(--primary-color)';
      }
    }
    
    // Function to load product price via AJAX
    function loadProductPrice(productId) {
      const sellingPriceField = document.getElementById('selling_price');
      if (!sellingPriceField) return;
      
      // Show loading state
      sellingPriceField.value = 'Loading...';
      sellingPriceField.disabled = true;
      
      // Make AJAX request
      fetch(`ajax/get_product_price.php?product_id=${productId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            sellingPriceField.value = data.sale_price;
            sellingPriceField.disabled = false;
            
            // Add visual feedback
            sellingPriceField.classList.remove('error');
            sellingPriceField.classList.add('success');
            
            // Show success message briefly
            showPriceLoadMessage(`Price loaded: LKR ${data.sale_price}`, 'success');
            
            console.log('Product price loaded:', data);
          } else {
            sellingPriceField.value = '';
            sellingPriceField.disabled = false;
            sellingPriceField.classList.remove('success');
            sellingPriceField.classList.add('error');
            
            showPriceLoadMessage('Failed to load product price', 'error');
            console.error('Error loading product price:', data.message);
          }
        })
        .catch(error => {
          sellingPriceField.value = '';
          sellingPriceField.disabled = false;
          sellingPriceField.classList.remove('success');
          sellingPriceField.classList.add('error');
          
          showPriceLoadMessage('Error loading product price', 'error');
          console.error('AJAX error:', error);
        });
    }
    
    // Function to show price load message
    function showPriceLoadMessage(message, type) {
      // Remove any existing messages
      const existingMessage = document.querySelector('.price-load-message');
      if (existingMessage) {
        existingMessage.remove();
      }
      
      // Create new message
      const messageDiv = document.createElement('div');
      messageDiv.className = `price-load-message alert alert-${type === 'success' ? 'info' : 'danger'} alert-enhanced`;
      messageDiv.style.cssText = 'margin-top: 10px; padding: 10px; font-size: 14px;';
      messageDiv.innerHTML = `<i class="glyphicon glyphicon-${type === 'success' ? 'ok' : 'exclamation-sign'}"></i> ${message}`;
      
      // Insert after selling price field
      const sellingPriceField = document.getElementById('selling_price');
      if (sellingPriceField && sellingPriceField.parentNode) {
        sellingPriceField.parentNode.appendChild(messageDiv);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
          if (messageDiv.parentNode) {
            messageDiv.remove();
          }
        }, 3000);
      }
    }
  });
</script>

<style>
/* Import Google Fonts */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

/* CSS Variables for consistent theming */
:root {
  --primary-color:rgb(0, 0, 0);
  --primary-light: #3b82f6;
  --primary-dark:rgb(0, 0, 0);
  --secondary-color: #059669;
  --secondary-light: #10b981;
  --secondary-dark: #047857;
  --accent-color: #0ea5e9;
  --success-color: #10b981;
  --warning-color: #f59e0b;
  --danger-color: #ef4444;
  --background-color: #f8fafc;
  --card-bg: #ffffff;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --border-color: #e2e8f0;
  --shadow-light: 0 1px 3px rgba(0, 0, 0, 0.1);
  --shadow-medium: 0 4px 6px rgba(0, 0, 0, 0.1);
  --border-radius: 12px;
}

/* Global Styles */
* {
  box-sizing: border-box;
}

body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  line-height: 1.6;
  color: var(--text-primary);
  background-color: var(--background-color);
}

/* Clean Background */
.break-even-container {
  background: var(--background-color);
  min-height: 100vh;
  padding: 20px 0;
}

/* Clean Card Design */
.analysis-card {
  background: var(--card-bg);
  border-radius: var(--border-radius);
  box-shadow: var(--shadow-medium);
  margin-bottom: 30px;
  border: 1px solid var(--border-color);
}

/* Clean Card Header */
.card-header {
  background: var(--primary-color);
  color: white;
  border-radius: var(--border-radius) var(--border-radius) 0 0;
  padding: 25px;
  border: none;
}

.card-header h3 {
  margin: 0;
  font-weight: 700;
  font-size: 24px;
  display: flex;
  align-items: center;
  gap: 15px;
}

.card-header h3 i {
  font-size: 28px;
}

/* Clean Form Section */
.form-section {
  padding: 40px;
  background: var(--card-bg);
}

.form-group {
  margin-bottom: 25px;
}

.form-control {
  border: 2px solid var(--border-color);
  border-radius: var(--border-radius);
  padding: 12px 16px;
  font-size: 16px;
  font-weight: 500;
  background: var(--card-bg);
  width: 100%;
}

.form-control:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  outline: none;
}

.form-control:disabled {
  background-color: #f8f9fa;
  cursor: not-allowed;
  opacity: 0.7;
}

.form-control.success {
  border-color: var(--success-color);
  background-color: rgba(16, 185, 129, 0.1);
}

.form-control.error {
  border-color: var(--danger-color);
  background-color: rgba(239, 68, 68, 0.1);
}

.form-control option:checked {
  background-color: var(--primary-color);
  color: white;
}

.form-control option[selected] {
  background-color: var(--primary-color);
  color: white;
}

/* Custom Select Styling */
.custom-select-wrapper {
  position: relative;
  display: inline-block;
  width: 100%;
}

.custom-select {
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  background: var(--card-bg);
  border: 2px solid var(--border-color);
  border-radius: var(--border-radius);
  padding: 12px 40px 12px 16px;
  font-size: 16px;
  font-weight: 500;
  color: var(--text-primary);
  cursor: pointer;
  width: 100%;
  transition: all 0.3s ease;
}

.custom-select:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  outline: none;
}

.custom-select:hover {
  border-color: var(--primary-light);
}

.custom-select-arrow {
  position: absolute;
  top: 50%;
  right: 12px;
  transform: translateY(-50%);
  pointer-events: none;
  color: var(--text-secondary);
  font-size: 14px;
  transition: transform 0.3s ease;
}

.custom-select:focus + .custom-select-arrow {
  transform: translateY(-50%) rotate(180deg);
  color: var(--primary-color);
}

.custom-select option {
  padding: 10px;
  background: var(--card-bg);
  color: var(--text-primary);
}

.custom-select option:checked {
  background-color: var(--primary-color);
  color: white;
}

.custom-select option[selected] {
  background-color: var(--primary-color);
  color: white;
}

.form-label {
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 8px;
  font-size: 14px;
  display: block;
}

.text-muted {
  color: var(--text-secondary);
  font-size: 12px;
  margin-top: 5px;
  display: block;
}

/* Clean Button */
.btn-calculate {
  background: var(--primary-color);
  border: none;
  border-radius: var(--border-radius);
  padding: 12px 30px;
  font-weight: 600;
  font-size: 16px;
  color: white;
  cursor: pointer;
}

.btn-calculate:hover {
  background: var(--primary-dark);
  color: white;
}

.btn-calculate:disabled {
  background: var(--text-secondary);
  cursor: not-allowed;
}

/* Reset Button */
.btn-reset {
  background: var(--text-secondary);
  border: none;
  border-radius: var(--border-radius);
  padding: 12px 30px;
  font-weight: 600;
  font-size: 16px;
  color: white;
  cursor: pointer;
}

.btn-reset:hover {
  background: #374151;
  color: white;
}

/* Clean Results Section */
.results-section {
  padding: 40px;
  background: var(--card-bg);
  border-radius: 0 0 var(--border-radius) var(--border-radius);
}

/* Clean Metric Cards */
.metric-card {
  background: var(--card-bg);
  border-radius: var(--border-radius);
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: var(--shadow-light);
  border-left: 4px solid var(--primary-color);
  border: 1px solid var(--border-color);
}

.metric-value {
  font-size: 24px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 5px;
}

.metric-label {
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 500;
}

/* Clean Chart Container */
.chart-container {
  background: var(--card-bg);
  border-radius: var(--border-radius);
  padding: 30px;
  margin-top: 30px;
  box-shadow: var(--shadow-light);
  border: 1px solid var(--border-color);
}

.chart-container canvas {
  max-height: 400px !important;
  width: 100% !important;
}

.chart-title {
  font-size: 20px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 20px;
  text-align: center;
}

/* Clean Help Section */
.help-section {
  background: rgba(5, 150, 105, 0.1);
  border-radius: var(--border-radius);
  padding: 25px;
  margin-top: 30px;
  border: 1px solid rgba(5, 150, 105, 0.2);
}

.help-title {
  color: var(--secondary-color);
  font-weight: 600;
  margin-bottom: 15px;
  font-size: 18px;
}

.help-list {
  margin: 0;
  padding-left: 20px;
}

.help-list li {
  margin-bottom: 8px;
  color: var(--text-primary);
}

/* Clean Alerts */
.alert-enhanced {
  border-radius: var(--border-radius);
  border: none;
  padding: 20px;
  margin-bottom: 20px;
}

.alert-danger {
  background: var(--danger-color);
  color: white;
}

.alert-info {
  background: var(--secondary-color);
  color: white;
}

/* Loading Spinner */
.loading-spinner {
  display: inline-block;
  width: 20px;
  height: 20px;
  border: 3px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: white;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 768px) {
  .break-even-container {
    padding: 10px 0;
  }
  
  .form-section, .results-section {
    padding: 25px;
  }
  
  .metric-value {
    font-size: 20px;
  }
  
  .card-header h3 {
    font-size: 20px;
  }
  
  .btn-calculate {
    padding: 10px 25px;
    font-size: 14px;
  }
}

@media (max-width: 480px) {
  .analysis-card {
    margin: 10px;
    border-radius: 8px;
  }
  
  .form-section {
    padding: 20px;
  }
  
  .metric-card {
    padding: 15px;
  }
}
</style>

<div class="break-even-container">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-12">
        <?php echo display_msg($msg); ?>
      </div>
    </div>

<div class="row">
  <div class="col-md-12">
        <div class="analysis-card">
          <div class="card-header">
            <h3>
              <i class="glyphicon glyphicon-calculator"></i>
              Break-Even Analysis Tool
            </h3>
      </div>
          
          <div class="form-section">
        <form method="post" action="break_even_analyzer.php" class="clearfix">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="product_id" class="form-label">Select Product</label>
                    <div class="custom-select-wrapper">
                      <select class="custom-select" id="product_id" name="product_id" required>
                        <option value="">Choose a product to analyze</option>
                        <?php 
                        if($products && count($products) > 0): 
                          foreach($products as $product): 
                            $is_selected = '';
                            // Check if this product is selected (either from POST or GET)
                            $selected_product_id = isset($_POST['product_id']) ? $_POST['product_id'] : (isset($_GET['product_id']) ? $_GET['product_id'] : '');
                            if(!empty($selected_product_id) && $selected_product_id == $product['id']) {
                              $is_selected = 'selected';
                            }
                        ?>
                          <option value="<?php echo (int)$product['id']; ?>" <?php echo $is_selected; ?>>
                            <?php echo remove_junk($product['name']); ?>
                          </option>
                        <?php 
                          endforeach; 
                        else: 
                        ?>
                          <option value="" disabled>No products available</option>
                        <?php endif; ?>
                      </select>
                      <div class="custom-select-arrow">
                        <i class="glyphicon glyphicon-chevron-down"></i>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                    <label for="fixed_costs" class="form-label">Fixed Costs (LKR)</label>
                    <input type="number" class="form-control" id="fixed_costs" name="fixed_costs" step="0.01" min="0" required
                  value="<?php echo isset($_POST['fixed_costs']) ? $_POST['fixed_costs'] : ''; ?>"
                      placeholder="Rent, utilities, salaries">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                    <label for="variable_cost" class="form-label">Variable Cost per Unit (LKR)</label>
                    <input type="number" class="form-control" id="variable_cost" name="variable_cost" step="0.01" min="0" required
                  value="<?php echo isset($_POST['variable_cost']) ? $_POST['variable_cost'] : ''; ?>"
                      placeholder="Materials, labor per unit">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                    <label for="selling_price" class="form-label">Selling Price per Unit (LKR)</label>
                    <input type="number" class="form-control" id="selling_price" name="selling_price" step="0.01" min="0" required
                  value="<?php echo isset($_POST['selling_price']) ? $_POST['selling_price'] : ''; ?>"
                  placeholder="Price per unit">
              </div>
            </div>
          </div>

              <div class="form-group clearfix text-center">
                <button type="submit" name="calculate" class="btn btn-calculate" id="calculateBtn">
                  <span class="btn-text">
                    <i class="glyphicon glyphicon-calculator"></i> Calculate Break-Even Point
                  </span>
                  <span class="loading-spinner" style="display: none;"></span>
                </button>
                <button type="button" class="btn btn-reset" id="resetBtn" style="margin-left: 15px;">
                  <i class="glyphicon glyphicon-refresh"></i> Reset Form
                </button>
              </div>
            </form>
          </div>

        <?php
        if(isset($_POST['calculate'])) {
          // Debug: Validate form data
          $errors = [];
          
          if(empty($_POST['product_id'])) {
            $errors[] = 'Product selection is required';
          }
          if(empty($_POST['fixed_costs']) || !is_numeric($_POST['fixed_costs'])) {
            $errors[] = 'Fixed costs must be a valid number';
          }
          if(empty($_POST['variable_cost']) || !is_numeric($_POST['variable_cost'])) {
            $errors[] = 'Variable cost must be a valid number';
          }
          if(empty($_POST['selling_price']) || !is_numeric($_POST['selling_price'])) {
            $errors[] = 'Selling price must be a valid number';
          }
          
          if(!empty($errors)) {
            echo '<div class="results-section">';
            echo '<div class="alert alert-danger alert-enhanced">';
            echo '<h4><i class="glyphicon glyphicon-exclamation-sign"></i> Form Validation Errors:</h4>';
            echo '<ul>';
            foreach($errors as $error) {
              echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
            echo '</div>';
          } else {
            $product_id = (int)$_POST['product_id'];
            $fixed_costs = floatval($_POST['fixed_costs']);
            $variable_cost = floatval($_POST['variable_cost']);
            $selling_price = floatval($_POST['selling_price']);
            
            // Get product details
            $product = find_by_id('products', $product_id);
            
            if(!$product) {
              echo '<div class="results-section">';
              echo '<div class="alert alert-danger alert-enhanced">';
              echo '<i class="glyphicon glyphicon-exclamation-sign"></i> ';
              echo '<strong>Error:</strong> Selected product not found in database.';
              echo '</div>';
              echo '</div>';
            } elseif($selling_price <= $variable_cost) {
              echo '<div class="results-section">';
              echo '<div class="alert alert-danger alert-enhanced">';
              echo '<i class="glyphicon glyphicon-exclamation-sign"></i> ';
              echo '<strong>Error:</strong> Selling price must be greater than variable cost per unit for a valid break-even point.';
              echo '</div>';
              echo '</div>';
            } else {
            $contribution_margin = $selling_price - $variable_cost;
            $break_even_units = ceil($fixed_costs / $contribution_margin);
            $break_even_revenue = $break_even_units * $selling_price;
            
              echo '<div class="results-section">';
              echo '<h3 style="color: #2c3e50; margin-bottom: 25px; text-align: center;">';
              echo '<i class="glyphicon glyphicon-stats"></i> ';
              echo 'Analysis Results for ' . remove_junk($product['name']);
              echo '</h3>';
              
              // Key Metrics Cards
            echo '<div class="row">';
              echo '<div class="col-md-3">';
              echo '<div class="metric-card">';
              echo '<div class="metric-value">LKR ' . number_format($fixed_costs, 2) . '</div>';
              echo '<div class="metric-label">Fixed Costs</div>';
              echo '</div>';
              echo '</div>';
              
              echo '<div class="col-md-3">';
              echo '<div class="metric-card">';
              echo '<div class="metric-value">LKR ' . number_format($variable_cost, 2) . '</div>';
              echo '<div class="metric-label">Variable Cost per Unit</div>';
              echo '</div>';
              echo '</div>';
              
              echo '<div class="col-md-3">';
              echo '<div class="metric-card">';
              echo '<div class="metric-value">LKR ' . number_format($selling_price, 2) . '</div>';
              echo '<div class="metric-label">Selling Price per Unit</div>';
              echo '</div>';
              echo '</div>';
              
              echo '<div class="col-md-3">';
              echo '<div class="metric-card">';
              echo '<div class="metric-value">LKR ' . number_format($contribution_margin, 2) . '</div>';
              echo '<div class="metric-label">Contribution Margin</div>';
              echo '</div>';
              echo '</div>';
              echo '</div>';
              
              // Break-Even Results
              echo '<div class="row" style="margin-top: 20px;">';
            echo '<div class="col-md-6">';
              echo '<div class="metric-card" style="background: linear-gradient(135deg, #28a745, #20c997); color: white;">';
              echo '<div class="metric-value" style="color: white;">' . number_format($break_even_units) . ' units</div>';
              echo '<div class="metric-label" style="color: rgba(255,255,255,0.8);">Units to Break Even</div>';
              echo '</div>';
            echo '</div>';
            
            echo '<div class="col-md-6">';
              echo '<div class="metric-card" style="background: linear-gradient(135deg, #007bff, #6610f2); color: white;">';
              echo '<div class="metric-value" style="color: white;">LKR ' . number_format($break_even_revenue, 2) . '</div>';
              echo '<div class="metric-label" style="color: rgba(255,255,255,0.8);">Break-Even Revenue</div>';
              echo '</div>';
              echo '</div>';
              echo '</div>';
              
              // Charts Section
              echo '<div class="chart-container">';
              echo '<div class="chart-title">Break-Even Analysis Charts</div>';
              echo '<div class="row">';
              echo '<div class="col-md-6">';
              echo '<div style="position: relative; height: 300px;">';
              echo '<canvas id="costChart"></canvas>';
              echo '</div>';
              echo '</div>';
              echo '<div class="col-md-6">';
              echo '<div style="position: relative; height: 300px;">';
              echo '<canvas id="breakEvenChart"></canvas>';
              echo '</div>';
              echo '</div>';
            echo '</div>';
            echo '</div>';
            
              // Interpretation
              echo '<div class="alert alert-info alert-enhanced">';
              echo '<i class="glyphicon glyphicon-lightbulb"></i> ';
              echo '<strong>Key Insights:</strong> You need to sell ' . number_format($break_even_units) . ' units of ' . 
                 remove_junk($product['name']) . ' to cover all costs. Each unit sold beyond this point contributes LKR ' . 
                 number_format($contribution_margin, 2) . ' to your profit.';
            echo '</div>';
            
              echo '</div>';
              
              // Chart.js Script with enhanced error handling
              ?>
              <script>
              function initializeCharts() {
                console.log('Initializing charts...');
                try {
                  if (typeof Chart === "undefined") {
                    console.error("Chart.js is not loaded");
                    // Show fallback content
                    const costContainer = document.getElementById("costChart");
                    const breakEvenContainer = document.getElementById("breakEvenChart");
                    
                    if (costContainer && breakEvenContainer) {
                      costContainer.innerHTML = '<div style="text-align: center; padding: 50px; background: #f8f9fa; border-radius: 8px;"><h4>Cost Structure</h4><p>Fixed Costs: LKR <?php echo number_format($fixed_costs, 2); ?></p><p>Variable Cost: LKR <?php echo number_format($variable_cost, 2); ?></p></div>';
                      breakEvenContainer.innerHTML = '<div style="text-align: center; padding: 50px; background: #f8f9fa; border-radius: 8px;"><h4>Break-Even Analysis</h4><p>Break-Even Units: <?php echo number_format($break_even_units); ?></p><p>Break-Even Revenue: LKR <?php echo number_format($break_even_revenue, 2); ?></p></div>';
                    } else {
                      console.error('Chart containers not found');
                    }
                    return;
                  }
                  
                  console.log('Chart.js loaded successfully, version:', Chart.version);

                  // Cost Structure Chart
                  const costCanvas = document.getElementById("costChart");
                  if (costCanvas) {
                    console.log('Creating cost chart...');
                    const costCtx = costCanvas.getContext("2d");
                    new Chart(costCtx, {
                      type: "doughnut",
                      data: {
                        labels: ["Fixed Costs", "Variable Cost per Unit"],
                        datasets: [{
                          data: [<?php echo $fixed_costs; ?>, <?php echo $variable_cost; ?>],
                          backgroundColor: ["#2563eb", "#059669"],
                          borderWidth: 2,
                          borderColor: "#fff"
                        }]
                      },
                      options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                          title: {
                            display: true,
                            text: "Cost Structure Analysis",
                            font: { size: 16, weight: "bold" }
                          },
                          legend: {
                            position: "bottom"
                          }
                        }
                      }
                    });
                    console.log('Cost chart created successfully');
                  } else {
                    console.error('Cost chart canvas not found');
                  }

                  // Break-Even Chart
                  const breakEvenCanvas = document.getElementById("breakEvenChart");
                  if (breakEvenCanvas) {
                    console.log('Creating break-even chart...');
                    const breakEvenCtx = breakEvenCanvas.getContext("2d");
                    const units = [0, <?php echo $break_even_units * 0.5; ?>, <?php echo $break_even_units; ?>, <?php echo $break_even_units * 1.5; ?>, <?php echo $break_even_units * 2; ?>];
                    const totalCosts = units.map(u => <?php echo $fixed_costs; ?> + (u * <?php echo $variable_cost; ?>));
                    const revenues = units.map(u => u * <?php echo $selling_price; ?>);

                    new Chart(breakEvenCtx, {
                      type: "line",
                      data: {
                        labels: units.map(u => Math.round(u)),
                        datasets: [{
                          label: "Total Costs",
                          data: totalCosts,
                          borderColor: "#ef4444",
                          backgroundColor: "rgba(239, 68, 68, 0.1)",
                          tension: 0.1
                        }, {
                          label: "Revenue",
                          data: revenues,
                          borderColor: "#10b981",
                          backgroundColor: "rgba(16, 185, 129, 0.1)",
                          tension: 0.1
                        }]
                      },
                      options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                          y: {
                            beginAtZero: true,
                            title: {
                              display: true,
                              text: "Amount (LKR)"
                            }
                          },
                          x: {
                            title: {
                              display: true,
                              text: "Units Sold"
                            }
                          }
                        },
                        plugins: {
                          title: {
                            display: true,
                            text: "Break-Even Point Analysis",
                            font: { size: 16, weight: "bold" }
                          },
                          legend: {
                            position: "top"
                          }
                        }
                      }
                    });
                    console.log('Break-even chart created successfully');
                  } else {
                    console.error('Break-even chart canvas not found');
                  }
                } catch (error) {
                  console.error("Error initializing charts:", error);
                  const costCanvas = document.getElementById("costChart");
                  const breakEvenCanvas = document.getElementById("breakEvenChart");
                  if (costCanvas) costCanvas.innerHTML = '<p style="text-align: center; color: #dc3545;">Error loading chart. Please try again.</p>';
                  if (breakEvenCanvas) breakEvenCanvas.innerHTML = '<p style="text-align: center; color: #dc3545;">Error loading chart. Please try again.</p>';
                }
              }

              // Initialize charts when DOM is ready
              if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", initializeCharts);
              } else {
                initializeCharts();
              }
              </script>
              <?php
            }
          }
        }
        ?>

          <div class="help-section">
            <h4 class="help-title">
              <i class="glyphicon glyphicon-question-sign"></i>
              How to Use This Tool
            </h4>
            <ol class="help-list">
              <li><strong>Select a Product:</strong> Choose the product you want to analyze from the dropdown menu. The selling price will be automatically loaded from the product database.</li>
              <li><strong>Enter Fixed Costs:</strong> Input all costs that don't change with production volume (rent, utilities, salaries, insurance).</li>
              <li><strong>Enter Variable Cost:</strong> Input the cost per unit including materials, direct labor, packaging, shipping, commissions, raw materials, manufacturing supplies, and other expenses that increase with each unit produced or sold.</li>
              <li><strong>Review Selling Price:</strong> The selling price is automatically populated from the product database. You can modify it if needed for your analysis.</li>
              <li><strong>Calculate:</strong> Click the calculate button to generate comprehensive break-even analysis with visual charts.</li>
            </ol>
            <div style="margin-top: 15px; padding: 15px; background: rgba(255,255,255,0.7); border-radius: 8px;">
              <strong>💡 Pro Tip:</strong> The break-even point shows you exactly how many units you need to sell to cover all costs. 
              Every unit sold beyond this point contributes to your profit!<br><br>
              <strong>📋 Variable Cost Examples:</strong> Raw materials, direct labor wages, packaging materials, 
              shipping costs, sales commissions, credit card processing fees, inventory storage per unit, 
              and any other costs that increase with production or sales volume.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once('layouts/footer.php'); ?> 