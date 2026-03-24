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
$query = "SELECT p.*, c.name as category_name, s.company_name as supplier_name,
          (SELECT SUM(quantity) FROM stock WHERE product_id = p.id) as current_stock
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          LEFT JOIN suppliers s ON p.supplier_id = s.id 
          WHERE p.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $product_id);
$stmt->execute();
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: index.php');
    exit();
}

// Get stock movements for this product
$movements = $db->prepare("SELECT sm.*, u.username 
                           FROM stock_movements sm 
                           LEFT JOIN users u ON sm.created_by = u.id 
                           WHERE sm.product_id = ? 
                           ORDER BY sm.created_at DESC LIMIT 20");
$movements->execute([$product_id]);
$stock_movements = $movements->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'View Product';
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Product Details</h1>
        <div>
            <a href="edit.php?id=<?php echo $product['id']; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Product Information Card -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Product Information</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-box fa-4x text-primary"></i>
                    </div>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <th width="40%">SKU:</th>
                            <td><?php echo htmlspecialchars($product['sku']); ?></td>
                        </tr>
                        <tr>
                            <th>Name:</th>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                        </tr>
                        <tr>
                            <th>Category:</th>
                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                        </tr>
                        <tr>
                            <th>Supplier:</th>
                            <td><?php echo htmlspecialchars($product['supplier_name'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Unit Price:</th>
                            <td class="text-primary font-weight-bold"><?php echo formatCurrency($product['unit_price']); ?></td>
                        </tr>
                        <tr>
                            <th>Cost Price:</th>
                            <td><?php echo formatCurrency($product['cost_price']); ?></td>
                        </tr>
                        <tr>
                            <th>Current Stock:</th>
                            <td>
                                <span class="badge badge-<?php 
                                    $stock = $product['current_stock'] ?? 0;
                                    echo $stock == 0 ? 'danger' : ($stock <= $product['reorder_level'] ? 'warning' : 'success');
                                ?> badge-lg p-2">
                                    <?php echo $stock; ?> units
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Reorder Level:</th>
                            <td><?php echo $product['reorder_level']; ?></td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span class="badge badge-<?php echo $product['status'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $product['status'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Created:</th>
                            <td><?php echo date('d-m-Y', strtotime($product['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>Last Updated:</th>
                            <td><?php echo date('d-m-Y H:i', strtotime($product['updated_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Description Card -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Description</h5>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($product['description'] ?: 'No description provided.')); ?></p>
                </div>
            </div>

            <!-- Stock Movements -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Stock Movement History</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($stock_movements)): ?>
                        <p class="text-muted text-center">No stock movements recorded yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Quantity</th>
                                        <th>Notes</th>
                                        <th>By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stock_movements as $movement): ?>
                                    <tr>
                                        <td><?php echo date('d-m-Y H:i', strtotime($movement['created_at'])); ?></td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo $movement['movement_type'] == 'in' ? 'success' : 
                                                    ($movement['movement_type'] == 'out' ? 'danger' : 'warning'); 
                                            ?>">
                                                <?php echo strtoupper($movement['movement_type']); ?>
                                            </span>
                                        </td>
                                        <td class="<?php echo $movement['movement_type'] == 'in' ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo $movement['movement_type'] == 'in' ? '+' : '-'; ?><?php echo $movement['quantity']; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($movement['notes'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($movement['username'] ?? 'System'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <a href="../stock/stock_in.php?product_id=<?php echo $product['id']; ?>" class="btn btn-success btn-block">
                                <i class="fas fa-arrow-down"></i> Add Stock
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="../stock/stock_out.php?product_id=<?php echo $product['id']; ?>" class="btn btn-danger btn-block">
                                <i class="fas fa-arrow-up"></i> Remove Stock
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>