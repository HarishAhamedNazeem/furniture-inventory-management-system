<?php
  $page_title = 'Supplier Report';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
  page_require_level(2);

  // Get all suppliers
  $suppliers = find_all('suppliers');
  
  // Get products for each supplier
  $supplier_products = array();
  foreach($suppliers as $supplier) {
    $supplier_products[$supplier['id']] = find_products_by_supplier($supplier['id']);
  }
  
  // Calculate metrics
  $total_suppliers = count($suppliers);
  $active_suppliers = 0;
  $total_buying_value = 0;
  $suppliers_with_products = 0;
  
  foreach($suppliers as $supplier) {
    if($supplier['status'] == 1) {
      $active_suppliers++;
    }
    
    // Calculate total buying value for this supplier
    if(isset($supplier_products[$supplier['id']]) && count($supplier_products[$supplier['id']]) > 0) {
      $suppliers_with_products++;
      foreach($supplier_products[$supplier['id']] as $product) {
        $total_buying_value += $product['quantity'] * $product['buy_price'];
      }
    }
  }
?>
<?php include_once('layouts/header.php'); ?>
<div class="row">
  <div class="col-md-12">
    <?php echo display_msg($msg); ?>
  </div>
</div>
<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading clearfix">
        <strong>
          <span class="glyphicon glyphicon-th"></span>
          <span>Supplier Report</span>
        </strong>
      </div>
      <div class="panel-body">
<!-- Enhanced KPI Cards -->
<style>
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 15px;
  margin-bottom: 25px;
}

.kpi-card {
  background: rgba(255, 255, 255, 0.95);
  border-radius: 12px;
  padding: 18px;
  min-height: 120px;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-left: 6px solid #667eea;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.kpi-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
}

.kpi-value {
  font-size: 1.8rem;
  font-weight: 700;
  color: #2d3748;
  margin-bottom: 6px;
  line-height: 1.2;
  order: 0;
}

.kpi-label {
  font-size: 1.4rem;
  color: #2d3748;
  font-weight: 1000;
  margin-bottom: 10px;
  line-height: 1.3;
  order: -1;
}

.kpi-change {
  font-size: 1.2rem;
  font-weight: 500;
  margin-top: auto;
  order: 1;
  color: #000000;
}

@media (max-width: 768px) {
  .kpi-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .kpi-card {
    padding: 15px;
    min-height: 100px;
  }
  
  .kpi-value {
    font-size: 1.6rem;
  }
  
  .kpi-label {
    font-size: 1rem;
  }
  
  .kpi-change {
    font-size: 0.9rem;
  }
}

/* Info Box Styling */
.info-box {
  display: flex;
  align-items: center;
  padding: 15px;
  margin-bottom: 15px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  border-left: 4px solid #007bff;
}

.info-box-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  margin-right: 15px;
  color: white;
  font-size: 20px;
}

.info-box-icon.bg-blue {
  background: linear-gradient(45deg, #007bff, #0056b3);
}

.info-box-icon.bg-green {
  background: linear-gradient(45deg, #28a745, #1e7e34);
}

.info-box-content {
  flex: 1;
}

.info-box-text {
  display: block;
  font-size: 14px;
  color: #6c757d;
  margin-bottom: 5px;
}

.info-box-number {
  display: block;
  font-size: 24px;
  font-weight: bold;
  color: #2d3748;
}
</style>

<div class="kpi-grid">
    <div class="kpi-card" style="border-left-color: #667eea;">
        <div class="kpi-label">Total Suppliers</div>
        <div class="kpi-value"><?php echo $total_suppliers; ?> suppliers</div>
        <div class="kpi-change">All suppliers</div>
    </div>
    
    <div class="kpi-card" style="border-left-color: #43e97b;">
        <div class="kpi-label">Active Suppliers</div>
        <div class="kpi-value"><?php echo $active_suppliers; ?> suppliers</div>
        <div class="kpi-change">Currently active</div>
    </div>
    
    <div class="kpi-card" style="border-left-color: #f093fb;">
        <div class="kpi-label">Suppliers with Products</div>
        <div class="kpi-value"><?php echo $suppliers_with_products; ?> suppliers</div>
        <div class="kpi-change">Supplying products</div>
    </div>
    
    <div class="kpi-card" style="border-left-color: #ffecd2;">
        <div class="kpi-label">Total Buying Value</div>
        <div class="kpi-value">LKR <?php echo number_format($total_buying_value, 2); ?></div>
        <div class="kpi-change">Inventory investment</div>
    </div>
</div>
        <?php foreach($suppliers as $supplier): ?>
        <div class="panel panel-default">
          <div class="panel-heading">
            <h3 class="panel-title">
              <?php echo remove_junk($supplier['name']); ?>
              <span class="label <?php echo $supplier['status'] == 1 ? 'label-success' : 'label-danger'; ?>">
                <?php echo $supplier['status'] == 1 ? 'Active' : 'Inactive'; ?>
              </span>
            </h3>
          </div>
          <div class="panel-body">
            <div class="row">
              <div class="col-md-6">
                <p><strong>Contact Person:</strong> <?php echo remove_junk($supplier['contact_person']); ?></p>
                <p><strong>Phone:</strong> <?php echo remove_junk($supplier['phone']); ?></p>
                <p><strong>Email:</strong> <?php echo remove_junk($supplier['email']); ?></p>
                <p><strong>Address:</strong> <?php echo remove_junk($supplier['address']); ?></p>
              </div>
              <div class="col-md-6">
                <h4>Financial Summary</h4>
                <?php 
                $supplier_total_buying_value = 0;
                $product_count = 0;
                if(isset($supplier_products[$supplier['id']]) && count($supplier_products[$supplier['id']]) > 0): 
                  foreach($supplier_products[$supplier['id']] as $product): 
                    $supplier_total_buying_value += $product['quantity'] * $product['buy_price'];
                    $product_count++;
                  endforeach;
                ?>
                  <div class="row">
                    <div class="col-md-6">
                      <div class="info-box">
                        <span class="info-box-icon bg-blue"><i class="fa fa-cubes"></i></span>
                        <div class="info-box-content">
                          <span class="info-box-text">Products Supplied</span>
                          <span class="info-box-number"><?php echo $product_count; ?></span>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="info-box">
                        <span class="info-box-icon bg-green"><i class="fa fa-dollar"></i></span>
                        <div class="info-box-content">
                          <span class="info-box-text">Total Buying Value</span>
                          <span class="info-box-number">LKR <?php echo number_format($supplier_total_buying_value, 2); ?></span>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php else: ?>
                  <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>No Products:</strong> This supplier has no products in inventory.
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<?php include_once('layouts/footer.php'); ?> 