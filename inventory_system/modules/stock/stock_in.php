<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get categories for filtering
$categories = $db->query("SELECT id, name FROM categories WHERE status = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get suppliers (brands)
$suppliers = $db->query("SELECT id, company_name FROM suppliers WHERE status = 1 ORDER BY company_name")->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = $_POST['product_id'] ?? 0;
    $quantity = $_POST['quantity'] ?? 0;
    $batch_number = sanitize($_POST['batch_number'] ?? '');
    $location = sanitize($_POST['location'] ?? '');
    $expiry_date = $_POST['expiry_date'] ?? '';
    $notes = sanitize($_POST['notes'] ?? '');
    $unit_price = $_POST['unit_price'] ?? 0;
    $cost_price = $_POST['cost_price'] ?? 0;
    
    if (empty($product_id) || empty($quantity)) {
        $error = 'Please select a product and enter quantity';
    } else {
        // Start transaction
        $db->beginTransaction();
        
        try {
            // Check if stock entry exists with same batch
            $check = $db->prepare("SELECT id FROM stock WHERE product_id = ? AND batch_number = ?");
            $check->execute([$product_id, $batch_number]);
            $existing = $check->fetch();
            
            if ($existing) {
                // Update existing stock
                $update = $db->prepare("UPDATE stock SET quantity = quantity + ? WHERE id = ?");
                $update->execute([$quantity, $existing['id']]);
            } else {
                // Insert new stock
                $insert = $db->prepare("INSERT INTO stock (product_id, quantity, batch_number, location, expiry_date) 
                                        VALUES (?, ?, ?, ?, ?)");
                $insert->execute([$product_id, $quantity, $batch_number, $location, $expiry_date]);
            }
            
            // Update product prices if provided
            if ($unit_price > 0 || $cost_price > 0) {
                $price_update = "UPDATE products SET ";
                $params = [];
                if ($unit_price > 0) {
                    $price_update .= "unit_price = ?, ";
                    $params[] = $unit_price;
                }
                if ($cost_price > 0) {
                    $price_update .= "cost_price = ?, ";
                    $params[] = $cost_price;
                }
                $price_update = rtrim($price_update, ", ");
                $price_update .= " WHERE id = ?";
                $params[] = $product_id;
                
                $update_price = $db->prepare($price_update);
                $update_price->execute($params);
            }
            
            // Record movement
            $movement = $db->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, notes, created_by) 
                                      VALUES (?, 'in', ?, ?, ?)");
            $movement->execute([$product_id, $quantity, $notes, $_SESSION['user_id']]);
            
            $db->commit();
            
            logActivity($db, $_SESSION['user_id'], 'Stock In', "Added $quantity units to product ID: $product_id");
            $_SESSION['success'] = 'Stock added successfully';
            header('Location: index.php');
            exit();
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Error adding stock: ' . $e->getMessage();
        }
    }
}

// Get recent stock movements for display
$recent_movements = $db->query("SELECT sm.*, p.name as product_name, p.sku 
                                 FROM stock_movements sm 
                                 JOIN products p ON sm.product_id = p.id 
                                 WHERE sm.movement_type = 'in'
                                 ORDER BY sm.created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Stock In';
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
    .product-card {
        border: 1px solid #e3e6f0;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 15px;
        cursor: pointer;
        transition: all 0.3s;
    }
    .product-card:hover {
        border-color: #4e73df;
        box-shadow: 0 0 15px rgba(78, 115, 223, 0.1);
        transform: translateY(-2px);
    }
    .product-card.selected {
        border-color: #4e73df;
        background-color: #f8f9fc;
        border-left: 4px solid #4e73df;
    }
    .product-image {
        width: 60px;
        height: 60px;
        background: #f8f9fc;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
    }
    .filter-group {
        background: white;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .selected-product-info {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    .stock-badge {
        font-size: 24px;
        font-weight: bold;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Stock In</h1>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Stock
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Left Column - Product Selection -->
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Select Product</h5>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="filter-group">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Search Product</label>
                                    <input type="text" id="search_product" class="form-control" placeholder="Search by name or SKU...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Filter by Category</label>
                                    <select id="category_filter" class="form-control">
                                        <option value="">All Categories</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Filter by Brand/Supplier</label>
                                    <select id="supplier_filter" class="form-control">
                                        <option value="">All Brands</option>
                                        <?php foreach ($suppliers as $supplier): ?>
                                            <option value="<?php echo $supplier['id']; ?>"><?php echo htmlspecialchars($supplier['company_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Sort By</label>
                                    <select id="sort_by" class="form-control">
                                        <option value="name">Name (A-Z)</option>
                                        <option value="name_desc">Name (Z-A)</option>
                                        <option value="price_low">Price (Low to High)</option>
                                        <option value="price_high">Price (High to Low)</option>
                                        <option value="stock">Stock (Low to High)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Products List -->
                    <div id="products_list" style="max-height: 500px; overflow-y: auto;">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <p class="mt-2">Loading products...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Stock Entry Form -->
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Add Stock Entry</h5>
                </div>
                <div class="card-body">
                    <div id="selected_product_display" style="display: none;">
                        <div class="selected-product-info">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <div class="product-image bg-white rounded">
                                        <i class="fas fa-box fa-3x text-primary"></i>
                                    </div>
                                </div>
                                <div class="col">
                                    <h4 id="selected_product_name" class="mb-1"></h4>
                                    <p class="mb-0">SKU: <span id="selected_product_sku"></span></p>
                                    <p class="mb-0">Category: <span id="selected_product_category"></span></p>
                                    <p class="mb-0">Brand: <span id="selected_product_brand"></span></p>
                                </div>
                                <div class="col-auto text-right">
                                    <div class="stock-badge" id="selected_product_stock"></div>
                                    <small>Current Stock</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="" id="stock_in_form">
                        <input type="hidden" name="product_id" id="product_id" required>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Quantity *</label>
                                    <input type="number" name="quantity" id="quantity" class="form-control" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Unit Price (Selling Price)</label>
                                    <input type="number" name="unit_price" id="unit_price" class="form-control" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Cost Price (Purchase Price)</label>
                                    <input type="number" name="cost_price" id="cost_price" class="form-control" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Batch Number</label>
                                    <input type="text" name="batch_number" id="batch_number" class="form-control" placeholder="e.g., BATCH-2024-001">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Storage Location</label>
                                    <input type="text" name="location" class="form-control" placeholder="e.g., Warehouse A, Shelf 2">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Expiry Date</label>
                                    <input type="date" name="expiry_date" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Notes</label>
                                    <textarea name="notes" class="form-control" rows="2" placeholder="Additional notes about this stock entry..."></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mt-3">
                            <button type="submit" class="btn btn-success btn-lg btn-block">
                                <i class="fas fa-arrow-down"></i> Add Stock
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Recent Stock In Movements -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Recent Stock In Movements</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Batch</th>
                                    <th>By</th>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_movements as $movement): ?>
                                    <tr>
                                        <td><?php echo date('d-m-Y H:i', strtotime($movement['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($movement['product_name']); ?></td>
                                        <td class="text-success font-weight-bold">+<?php echo $movement['quantity']; ?></td>
                                        <td><?php echo htmlspecialchars($movement['batch_number'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($movement['created_by']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadProducts();
    
    // Filter events
    $('#search_product, #category_filter, #supplier_filter, #sort_by').on('change keyup', function() {
        loadProducts();
    });
});

function loadProducts() {
    let search = $('#search_product').val();
    let category = $('#category_filter').val();
    let supplier = $('#supplier_filter').val();
    let sort = $('#sort_by').val();
    
    $.ajax({
        url: 'get_products_ajax.php',
        method: 'POST',
        data: {
            search: search,
            category: category,
            supplier: supplier,
            sort: sort
        },
        success: function(response) {
            $('#products_list').html(response);
        }
    });
}

function selectProduct(id, name, sku, category, brand, price, cost, stock) {
    // Remove selected class from all products
    $('.product-card').removeClass('selected');
    // Add selected class to clicked product
    $('#product_' + id).addClass('selected');
    
    // Update form
    $('#product_id').val(id);
    $('#selected_product_name').text(name);
    $('#selected_product_sku').text(sku);
    $('#selected_product_category').text(category || 'Uncategorized');
    $('#selected_product_brand').text(brand || 'N/A');
    $('#selected_product_stock').text(stock + ' units');
    $('#unit_price').val(price);
    $('#cost_price').val(cost);
    
    // Generate batch number suggestion
    let date = new Date();
    let batchSuggestion = sku + '-' + date.getFullYear() + '-' + (date.getMonth() + 1).toString().padStart(2, '0');
    $('#batch_number').val(batchSuggestion);
    
    // Show selected product display
    $('#selected_product_display').show();
    
    // Scroll to form
    $('html, body').animate({
        scrollTop: $('#stock_in_form').offset().top - 100
    }, 500);
}

// Auto-generate batch number based on SKU
$('#batch_number').on('focus', function() {
    let sku = $('#selected_product_sku').text();
    if (sku && !$(this).val()) {
        let date = new Date();
        let batch = sku + '-' + date.getFullYear() + '-' + (date.getMonth() + 1).toString().padStart(2, '0');
        $(this).val(batch);
    }
});
</script>

<?php include '../../includes/footer.php'; ?>