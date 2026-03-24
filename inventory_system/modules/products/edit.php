<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

$product_id = isset($_GET['id']) ? $_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: index.php');
    exit();
}

// Get product details
$query = "SELECT * FROM products WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $product_id);
$stmt->execute();
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: index.php');
    exit();
}

// Get categories and suppliers for dropdowns
$categories = $db->query("SELECT * FROM categories WHERE status = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$suppliers = $db->query("SELECT * FROM suppliers WHERE status = 1 ORDER BY company_name")->fetchAll(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sku = sanitize($_POST['sku']);
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    $supplier_id = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;
    $unit_price = $_POST['unit_price'] ?: 0;
    $cost_price = $_POST['cost_price'] ?: 0;
    $reorder_level = $_POST['reorder_level'] ?: 5;
    $status = $_POST['status'] ?? 1;
    
    if (empty($sku) || empty($name)) {
        $error = 'SKU and Name are required';
    } else {
        // Check if SKU exists for other products
        $check = $db->prepare("SELECT id FROM products WHERE sku = ? AND id != ?");
        $check->execute([$sku, $product_id]);
        
        if ($check->fetch()) {
            $error = 'SKU already exists';
        } else {
            $query = "UPDATE products SET 
                      sku = :sku, 
                      name = :name, 
                      description = :description, 
                      category_id = :category_id, 
                      supplier_id = :supplier_id, 
                      unit_price = :unit_price, 
                      cost_price = :cost_price, 
                      reorder_level = :reorder_level,
                      status = :status
                      WHERE id = :id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':sku', $sku);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':category_id', $category_id);
            $stmt->bindParam(':supplier_id', $supplier_id);
            $stmt->bindParam(':unit_price', $unit_price);
            $stmt->bindParam(':cost_price', $cost_price);
            $stmt->bindParam(':reorder_level', $reorder_level);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $product_id);
            
            if ($stmt->execute()) {
                logActivity($db, $_SESSION['user_id'], 'Edit Product', "Edited product: $name");
                $_SESSION['success'] = 'Product updated successfully';
                header('Location: index.php');
                exit();
            } else {
                $error = 'Error updating product';
            }
        }
    }
}

$page_title = 'Edit Product';
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Edit Product</h1>
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
                            <input type="text" name="sku" class="form-control" value="<?php echo htmlspecialchars($product['sku']); ?>" required>
                            <small class="text-muted">Unique product identifier</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($product['description']); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category_id" class="form-control">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                        <?php echo $product['category_id'] == $category['id'] ? 'selected' : ''; ?>>
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
                                    <option value="<?php echo $supplier['id']; ?>" 
                                        <?php echo $product['supplier_id'] == $supplier['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($supplier['company_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Unit Price (₹)</label>
                            <input type="number" step="0.01" name="unit_price" class="form-control" value="<?php echo $product['unit_price']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Cost Price (₹)</label>
                            <input type="number" step="0.01" name="cost_price" class="form-control" value="<?php echo $product['cost_price']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Reorder Level</label>
                            <input type="number" name="reorder_level" class="form-control" value="<?php echo $product['reorder_level']; ?>">
                            <small class="text-muted">Minimum stock before alert</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="1" <?php echo $product['status'] == 1 ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo $product['status'] == 0 ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>