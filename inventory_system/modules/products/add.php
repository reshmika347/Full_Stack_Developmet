<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get categories for dropdown
$categories = $db->query("SELECT * FROM categories WHERE status = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get suppliers for dropdown
$suppliers = $db->query("SELECT * FROM suppliers WHERE status = 1 ORDER BY company_name")->fetchAll(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sku = trim($_POST['sku']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    $supplier_id = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;
    $unit_price = $_POST['unit_price'] ?: 0;
    $cost_price = $_POST['cost_price'] ?: 0;
    $reorder_level = $_POST['reorder_level'] ?: 5;
    
    if (empty($sku) || empty($name)) {
        $error = 'SKU and Name are required';
    } else {
        // Check if SKU exists
        $check = $db->prepare("SELECT id FROM products WHERE sku = ?");
        $check->execute([$sku]);
        if ($check->fetch()) {
            $error = 'SKU already exists';
        } else {
            $query = "INSERT INTO products (sku, name, description, category_id, supplier_id, unit_price, cost_price, reorder_level) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($query);
            if ($stmt->execute([$sku, $name, $description, $category_id, $supplier_id, $unit_price, $cost_price, $reorder_level])) {
                $_SESSION['success'] = 'Product added successfully';
                header('Location: index.php');
                exit();
            } else {
                $error = 'Error adding product';
            }
        }
    }
}

$page_title = 'Add Product';
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Add Product</h1>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>SKU <span class="text-danger">*</span></label>
                            <input type="text" name="sku" class="form-control" required>
                            <small class="text-muted">Unique product identifier</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category_id" class="form-control">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Supplier</label>
                            <select name="supplier_id" class="form-control">
                                <option value="">Select Supplier</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['id']; ?>">
                                        <?php echo htmlspecialchars($supplier['company_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Unit Price (₹)</label>
                            <input type="number" step="0.01" name="unit_price" class="form-control" value="0.00">
                        </div>
                        
                        <div class="form-group">
                            <label>Cost Price (₹)</label>
                            <input type="number" step="0.01" name="cost_price" class="form-control" value="0.00">
                        </div>
                        
                        <div class="form-group">
                            <label>Reorder Level</label>
                            <input type="number" name="reorder_level" class="form-control" value="5">
                            <small class="text-muted">Minimum stock before alert</small>
                        </div>
                    </div>
                </div>
                
                <div class="form-group mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>