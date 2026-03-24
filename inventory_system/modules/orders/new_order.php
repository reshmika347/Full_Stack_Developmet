<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get suppliers
$suppliers = $db->query("SELECT id, company_name FROM suppliers WHERE status = 1 ORDER BY company_name")->fetchAll(PDO::FETCH_ASSOC);

// Get products
$products = $db->query("SELECT p.*, COALESCE(SUM(s.quantity), 0) as stock 
                        FROM products p 
                        LEFT JOIN stock s ON p.id = s.product_id 
                        WHERE p.status = 1 
                        GROUP BY p.id 
                        ORDER BY p.name")->fetchAll(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $order_type = $_POST['order_type'];
    $supplier_id = $_POST['supplier_id'] ?? null;
    $customer_name = trim($_POST['customer_name'] ?? '');
    $order_date = $_POST['order_date'];
    $notes = trim($_POST['notes'] ?? '');
    
    // Get products from form
    $product_ids = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $prices = $_POST['price'] ?? [];
    
    // Validate
    if (empty($product_ids)) {
        $error = 'Please add at least one product';
    } elseif ($order_type == 'purchase' && empty($supplier_id)) {
        $error = 'Please select a supplier for purchase order';
    } elseif ($order_type == 'sales' && empty($customer_name)) {
        $error = 'Please enter customer name for sales order';
    } else {
        // Generate order number
        $prefix = $order_type == 'purchase' ? 'PO' : 'SO';
        $order_number = $prefix . date('Ymd') . rand(1000, 9999);
        
        // Calculate total
        $total_amount = 0;
        $items = [];
        
        foreach ($product_ids as $index => $product_id) {
            if (!empty($product_id) && $quantities[$index] > 0) {
                $total = $quantities[$index] * $prices[$index];
                $total_amount += $total;
                $items[] = [
                    'product_id' => $product_id,
                    'quantity' => $quantities[$index],
                    'price' => $prices[$index],
                    'total' => $total
                ];
            }
        }
        
        // Start transaction
        $db->beginTransaction();
        
        try {
            // Insert order
            $query = "INSERT INTO orders (order_number, order_type, supplier_id, customer_name, order_date, total_amount, created_by) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$order_number, $order_type, $supplier_id, $customer_name, $order_date, $total_amount, $_SESSION['user_id']]);
            
            $order_id = $db->lastInsertId();
            
            // Insert order items
            $item_query = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) 
                          VALUES (?, ?, ?, ?, ?)";
            $item_stmt = $db->prepare($item_query);
            
            foreach ($items as $item) {
                $item_stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price'], $item['total']]);
            }
            
            $db->commit();
            
            $_SESSION['success'] = 'Order created successfully';
            header('Location: index.php');
            exit();
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Error creating order: ' . $e->getMessage();
        }
    }
}

$page_title = 'New Order';
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">New Order</h1>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="" id="orderForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Order Type *</label>
                            <select name="order_type" id="order_type" class="form-control" required onchange="toggleOrderType()">
                                <option value="">Select Type</option>
                                <option value="purchase">Purchase Order</option>
                                <option value="sales">Sales Order</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="supplier_group" style="display: none;">
                            <label>Supplier *</label>
                            <select name="supplier_id" class="form-control">
                                <option value="">Select Supplier</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['id']; ?>">
                                        <?php echo htmlspecialchars($supplier['company_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" id="customer_group" style="display: none;">
                            <label>Customer Name *</label>
                            <input type="text" name="customer_name" class="form-control">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Order Date *</label>
                            <input type="date" name="order_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <h5>Order Items</h5>
                
                <div class="table-responsive mb-3">
                    <table class="table table-bordered" id="itemsTable">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr id="row1">
                                <td>
                                    <select name="product_id[]" class="form-control product-select" required>
                                        <option value="">Select Product</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?php echo $product['id']; ?>" 
                                                    data-price="<?php echo $product['unit_price']; ?>">
                                                <?php echo htmlspecialchars($product['name'] . ' (' . $product['sku'] . ')'); ?>
                                                (Stock: <?php echo $product['stock']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="quantity[]" class="form-control quantity" 
                                           min="1" required onchange="calculateRowTotal(this)">
                                </td>
                                <td>
                                    <input type="number" name="price[]" class="form-control price" 
                                           step="0.01" min="0" required onchange="calculateRowTotal(this)">
                                </td>
                                <td>
                                    <input type="text" class="form-control row-total" readonly>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-right"><strong>Grand Total:</strong></td>
                                <td>
                                    <input type="text" id="grand_total" class="form-control" readonly>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <button type="button" class="btn btn-info mb-3" onclick="addRow()">
                    <i class="fas fa-plus"></i> Add Item
                </button>
                
                <hr>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Order
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let rowCount = 1;

function toggleOrderType() {
    var type = document.getElementById('order_type').value;
    var supplierGroup = document.getElementById('supplier_group');
    var customerGroup = document.getElementById('customer_group');
    
    if (type == 'purchase') {
        supplierGroup.style.display = 'block';
        customerGroup.style.display = 'none';
    } else if (type == 'sales') {
        supplierGroup.style.display = 'none';
        customerGroup.style.display = 'block';
    }
}

function addRow() {
    rowCount++;
    var newRow = document.getElementById('row1').cloneNode(true);
    newRow.id = 'row' + rowCount;
    
    // Clear values
    var selects = newRow.getElementsByTagName('select');
    for (var i = 0; i < selects.length; i++) {
        selects[i].value = '';
    }
    
    var inputs = newRow.getElementsByTagName('input');
    for (var i = 0; i < inputs.length; i++) {
        if (inputs[i].type != 'button') {
            inputs[i].value = '';
        }
    }
    
    document.getElementById('itemsTable').getElementsByTagName('tbody')[0].appendChild(newRow);
}

function removeRow(btn) {
    if (document.getElementById('itemsTable').getElementsByTagName('tbody')[0].children.length > 1) {
        btn.closest('tr').remove();
        calculateGrandTotal();
    }
}

function calculateRowTotal(element) {
    var row = element.closest('tr');
    var quantity = row.querySelector('.quantity').value || 0;
    var price = row.querySelector('.price').value || 0;
    var total = quantity * price;
    row.querySelector('.row-total').value = '₹' + total.toFixed(2);
    calculateGrandTotal();
}

function calculateGrandTotal() {
    var totals = document.querySelectorAll('.row-total');
    var grandTotal = 0;
    totals.forEach(function(total) {
        var value = total.value.replace('₹', '');
        grandTotal += parseFloat(value) || 0;
    });
    document.getElementById('grand_total').value = '₹' + grandTotal.toFixed(2);
}
</script>

<?php include '../../includes/footer.php'; ?>