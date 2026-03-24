<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get products with stock
$products = $db->query("SELECT p.id, p.name, p.sku, COALESCE(SUM(s.quantity), 0) as total_stock 
                        FROM products p 
                        LEFT JOIN stock s ON p.id = s.product_id 
                        WHERE p.status = 1 
                        GROUP BY p.id 
                        HAVING total_stock > 0 
                        ORDER BY p.name")->fetchAll(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $reason = $_POST['reason'];
    $notes = trim($_POST['notes']);
    
    if (empty($product_id) || empty($quantity)) {
        $error = 'Product and quantity are required';
    } else {
        // Check available stock
        $stock_check = $db->prepare("SELECT SUM(quantity) as total FROM stock WHERE product_id = ?");
        $stock_check->execute([$product_id]);
        $available = $stock_check->fetchColumn();
        
        if ($available < $quantity) {
            $error = 'Insufficient stock. Available: ' . $available;
        } else {
            // Start transaction
            $db->beginTransaction();
            
            try {
                // Reduce stock (FIFO method)
                $batches = $db->prepare("SELECT id, quantity FROM stock WHERE product_id = ? AND quantity > 0 ORDER BY created_at");
                $batches->execute([$product_id]);
                
                $remaining = $quantity;
                
                foreach ($batches as $batch) {
                    if ($remaining <= 0) break;
                    
                    $reduce = min($batch['quantity'], $remaining);
                    
                    $update = $db->prepare("UPDATE stock SET quantity = quantity - ? WHERE id = ?");
                    $update->execute([$reduce, $batch['id']]);
                    
                    $remaining -= $reduce;
                }
                
                // Record movement
                $movement = $db->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, notes, created_by) 
                                          VALUES (?, 'out', ?, ?, ?)");
                $movement->execute([$product_id, $quantity, $notes, $_SESSION['user_id']]);
                
                $db->commit();
                
                $_SESSION['success'] = 'Stock removed successfully';
                header('Location: index.php');
                exit();
                
            } catch (Exception $e) {
                $db->rollBack();
                $error = 'Error removing stock: ' . $e->getMessage();
            }
        }
    }
}

$page_title = 'Stock Out';
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Stock Out - Remove Stock</h1>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="" id="stockOutForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Product <span class="text-danger">*</span></label>
                            <select name="product_id" id="product_id" class="form-control" required>
                                <option value="">Select Product</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>" data-stock="<?php echo $product['total_stock']; ?>">
                                        <?php echo htmlspecialchars($product['name'] . ' (' . $product['sku'] . ') - Available: ' . $product['total_stock']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="quantity" class="form-control" min="1" required>
                            <small class="text-muted" id="stockInfo"></small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Reason</label>
                            <select name="reason" class="form-control">
                                <option value="sale">Sale</option>
                                <option value="damage">Damage</option>
                                <option value="expired">Expired</option>
                                <option value="return">Return to Supplier</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="form-group mt-3">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-save"></i> Remove Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('product_id').addEventListener('change', function() {
    var selected = this.options[this.selectedIndex];
    var stock = selected.getAttribute('data-stock');
    document.getElementById('stockInfo').innerText = 'Available stock: ' + stock;
    
    var quantityInput = document.getElementById('quantity');
    quantityInput.max = stock;
});
</script>

<?php include '../../includes/footer.php'; ?>