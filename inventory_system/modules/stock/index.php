<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get stock with product details
$query = "SELECT s.*, p.name as product_name, p.sku, p.unit_price 
          FROM stock s 
          JOIN products p ON s.product_id = p.id 
          ORDER BY s.updated_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$stock_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get low stock products
$low_stock_query = "SELECT p.*, COALESCE(SUM(s.quantity), 0) as current_stock
                   FROM products p
                   LEFT JOIN stock s ON p.id = s.product_id
                   GROUP BY p.id
                   HAVING current_stock <= p.reorder_level
                   ORDER BY current_stock ASC";
$low_stmt = $db->prepare($low_stock_query);
$low_stmt->execute();
$low_stock = $low_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Stock Management';
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Stock Management</h1>
        <div>
            <a href="stock_in.php" class="btn btn-success">
                <i class="fas fa-arrow-down"></i> Stock In
            </a>
            <a href="stock_out.php" class="btn btn-danger">
                <i class="fas fa-arrow-up"></i> Stock Out
            </a>
        </div>
    </div>

    <?php if (!empty($low_stock)): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>Low Stock Alert!</strong> There are <?php echo count($low_stock); ?> products with low stock.
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php endif; ?>

    <!-- Low Stock Table -->
    <?php if (!empty($low_stock)): ?>
    <div class="card mb-4 border-warning">
        <div class="card-header bg-warning text-white">
            <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Low Stock Products</h5>
        </div>
        <div class="card-body">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Current Stock</th>
                        <th>Reorder Level</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($low_stock as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['sku']); ?></td>
                        <td class="text-danger font-weight-bold"><?php echo $product['current_stock']; ?></td>
                        <td><?php echo $product['reorder_level']; ?></td>
                        <td>
                            <a href="stock_in.php?product_id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Restock
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Stock Items Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Current Stock</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped" id="stockTable">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Batch Number</th>
                        <th>Quantity</th>
                        <th>Location</th>
                        <th>Expiry Date</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stock_items)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No stock items found. <a href="stock_in.php">Add stock</a></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($stock_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['sku']); ?></td>
                            <td><?php echo htmlspecialchars($item['batch_number'] ?: 'N/A'); ?></td>
                            <td class="<?php echo $item['quantity'] <= 10 ? 'text-danger font-weight-bold' : ''; ?>">
                                <?php echo $item['quantity']; ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['location'] ?: 'N/A'); ?></td>
                            <td>
                                <?php 
                                if ($item['expiry_date']) {
                                    echo date('d-m-Y', strtotime($item['expiry_date']));
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                            <td>₹<?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#stockTable').DataTable({
        pageLength: 25,
        order: [[3, 'desc']]
    });
});
</script>

<?php include '../../includes/footer.php'; ?>