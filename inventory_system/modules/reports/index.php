<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get date range
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'summary';

$page_title = 'Reports';
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Reports</h1>
    </div>

    <!-- Report Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="form-inline">
                <div class="form-group mr-3">
                    <label class="mr-2">Report Type:</label>
                    <select name="report_type" class="form-control" onchange="this.form.submit()">
                        <option value="summary" <?php echo $report_type == 'summary' ? 'selected' : ''; ?>>Summary</option>
                        <option value="stock" <?php echo $report_type == 'stock' ? 'selected' : ''; ?>>Stock Report</option>
                        <option value="sales" <?php echo $report_type == 'sales' ? 'selected' : ''; ?>>Sales Report</option>
                        <option value="purchase" <?php echo $report_type == 'purchase' ? 'selected' : ''; ?>>Purchase Report</option>
                    </select>
                </div>
                
                <div class="form-group mr-3">
                    <label class="mr-2">From:</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                
                <div class="form-group mr-3">
                    <label class="mr-2">To:</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">Generate</button>
            </form>
        </div>
    </div>

    <!-- Report Content -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <?php 
                switch($report_type) {
                    case 'summary': 
                        echo 'Summary Report'; 
                        break;
                    case 'stock': 
                        echo 'Stock Report'; 
                        break;
                    case 'sales': 
                        echo 'Sales Report'; 
                        break;
                    case 'purchase': 
                        echo 'Purchase Report'; 
                        break;
                    default:
                        echo 'Summary Report';
                }
                ?>
            </h5>
        </div>
        <div class="card-body">
            <?php if ($report_type == 'summary'): ?>
                <?php
                // Get summary statistics
                $total_products = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
                $total_categories = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
                $total_suppliers = $db->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
                
                $stock_value = $db->query("SELECT SUM(p.unit_price * s.quantity) 
                                          FROM products p 
                                          JOIN stock s ON p.id = s.product_id")->fetchColumn();
                if (!$stock_value) $stock_value = 0;
                
                $total_orders = $db->query("SELECT COUNT(*) FROM orders 
                                           WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'")->fetchColumn();
                
                $total_sales = $db->query("SELECT SUM(total_amount) FROM orders 
                                          WHERE order_type='sales' 
                                          AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'")->fetchColumn();
                if (!$total_sales) $total_sales = 0;
                
                $total_purchases = $db->query("SELECT SUM(total_amount) FROM orders 
                                              WHERE order_type='purchase' 
                                              AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'")->fetchColumn();
                if (!$total_purchases) $total_purchases = 0;
                ?>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white mb-3">
                            <div class="card-body">
                                <h5>Total Products</h5>
                                <h2><?php echo $total_products; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white mb-3">
                            <div class="card-body">
                                <h5>Total Categories</h5>
                                <h2><?php echo $total_categories; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white mb-3">
                            <div class="card-body">
                                <h5>Total Suppliers</h5>
                                <h2><?php echo $total_suppliers; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card bg-warning text-white mb-3">
                            <div class="card-body">
                                <h5>Stock Value</h5>
                                <h2>₹<?php echo number_format($stock_value, 2); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-danger text-white mb-3">
                            <div class="card-body">
                                <h5>Total Orders</h5>
                                <h2><?php echo $total_orders; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Sales (<?php echo date('d-m-Y', strtotime($start_date)); ?> to <?php echo date('d-m-Y', strtotime($end_date)); ?>)</h5>
                            </div>
                            <div class="card-body">
                                <h3 class="text-success">₹<?php echo number_format($total_sales, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">Purchases (<?php echo date('d-m-Y', strtotime($start_date)); ?> to <?php echo date('d-m-Y', strtotime($end_date)); ?>)</h5>
                            </div>
                            <div class="card-body">
                                <h3 class="text-info">₹<?php echo number_format($total_purchases, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($report_type == 'stock'): ?>
                <?php
                $stock_query = "SELECT p.id, p.name, p.sku, p.unit_price, 
                               COALESCE(SUM(s.quantity), 0) as quantity,
                               COALESCE(SUM(s.quantity * p.unit_price), 0) as value
                               FROM products p
                               LEFT JOIN stock s ON p.id = s.product_id
                               GROUP BY p.id
                               ORDER BY p.name";
                $stock_items = $db->query($stock_query)->fetchAll(PDO::FETCH_ASSOC);
                ?>
                
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_value = 0;
                        foreach ($stock_items as $item): 
                            $total_value += $item['value'];
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars($item['sku']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>₹<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td>₹<?php echo number_format($item['value'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="font-weight-bold">
                            <td colspan="4" class="text-right">Total:</td>
                            <td>₹<?php echo number_format($total_value, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
                
            <?php elseif ($report_type == 'sales'): ?>
                <?php
                $sales_query = "SELECT o.order_number, o.order_date, o.total_amount,
                               o.customer_name, u.username as created_by
                               FROM orders o
                               JOIN users u ON o.created_by = u.id
                               WHERE o.order_type = 'sales'
                               AND DATE(o.created_at) BETWEEN '$start_date' AND '$end_date'
                               ORDER BY o.created_at DESC";
                $sales = $db->query($sales_query)->fetchAll(PDO::FETCH_ASSOC);
                
                $total_sales = 0;
                foreach ($sales as $sale) {
                    $total_sales += $sale['total_amount'];
                }
                ?>
                
                <h4 class="mb-3">Total Sales: ₹<?php echo number_format($total_sales, 2); ?></h4>
                
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales as $sale): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sale['order_number']); ?></td>
                            <td><?php echo date('d-m-Y', strtotime($sale['order_date'])); ?></td>
                            <td><?php echo htmlspecialchars($sale['customer_name'] ?: 'N/A'); ?></td>
                            <td>₹<?php echo number_format($sale['total_amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($sale['created_by']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
            <?php elseif ($report_type == 'purchase'): ?>
                <?php
                $purchase_query = "SELECT o.order_number, o.order_date, o.total_amount,
                                  s.company_name as supplier, u.username as created_by
                                  FROM orders o
                                  LEFT JOIN suppliers s ON o.supplier_id = s.id
                                  JOIN users u ON o.created_by = u.id
                                  WHERE o.order_type = 'purchase'
                                  AND DATE(o.created_at) BETWEEN '$start_date' AND '$end_date'
                                  ORDER BY o.created_at DESC";
                $purchases = $db->query($purchase_query)->fetchAll(PDO::FETCH_ASSOC);
                
                $total_purchases = 0;
                foreach ($purchases as $purchase) {
                    $total_purchases += $purchase['total_amount'];
                }
                ?>
                
                <h4 class="mb-3">Total Purchases: ₹<?php echo number_format($total_purchases, 2); ?></h4>
                
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Supplier</th>
                            <th>Amount</th>
                            <th>Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($purchases as $purchase): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($purchase['order_number']); ?></td>
                            <td><?php echo date('d-m-Y', strtotime($purchase['order_date'])); ?></td>
                            <td><?php echo htmlspecialchars($purchase['supplier'] ?: 'N/A'); ?></td>
                            <td>₹<?php echo number_format($purchase['total_amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($purchase['created_by']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>