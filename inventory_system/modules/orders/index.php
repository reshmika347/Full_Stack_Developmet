<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get orders
$query = "SELECT o.*, u.username as created_by_name 
          FROM orders o 
          JOIN users u ON o.created_by = u.id 
          ORDER BY o.created_at DESC 
          LIMIT 20";
$stmt = $db->prepare($query);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Orders';
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Orders</h1>
        <a href="new_order.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Order
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-striped" id="ordersTable">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Type</th>
                        <th>Customer/Supplier</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No orders found. <a href="new_order.php">Create your first order</a></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $order['order_type'] == 'purchase' ? 'info' : 'success'; ?>">
                                    <?php echo ucfirst($order['order_type']); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                if ($order['order_type'] == 'purchase') {
                                    echo 'Supplier ID: ' . $order['supplier_id'];
                                } else {
                                    echo htmlspecialchars($order['customer_name'] ?: 'N/A');
                                }
                                ?>
                            </td>
                            <td><?php echo date('d-m-Y', strtotime($order['order_date'])); ?></td>
                            <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $order['status'] == 'completed' ? 'success' : 
                                        ($order['status'] == 'pending' ? 'warning' : 'secondary'); 
                                ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($order['created_by_name']); ?></td>
                            <td>
                                <a href="view.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
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
    $('#ordersTable').DataTable({
        pageLength: 25,
        order: [[0, 'desc']]
    });
});
</script>

<?php include '../../includes/footer.php'; ?>