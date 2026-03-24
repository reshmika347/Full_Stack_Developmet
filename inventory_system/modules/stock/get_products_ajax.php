<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$search = $_POST['search'] ?? '';
$category = $_POST['category'] ?? '';
$supplier = $_POST['supplier'] ?? '';
$sort = $_POST['sort'] ?? 'name';

// Build query
$query = "SELECT p.*, c.name as category_name, s.company_name as brand_name,
          COALESCE(SUM(st.quantity), 0) as current_stock
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          LEFT JOIN suppliers s ON p.supplier_id = s.id
          LEFT JOIN stock st ON p.id = st.product_id
          WHERE p.status = 1";

$params = [];

if ($search) {
    $query .= " AND (p.name LIKE :search OR p.sku LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($category) {
    $query .= " AND p.category_id = :category";
    $params[':category'] = $category;
}

if ($supplier) {
    $query .= " AND p.supplier_id = :supplier";
    $params[':supplier'] = $supplier;
}

$query .= " GROUP BY p.id";

// Add sorting
switch ($sort) {
    case 'name_desc':
        $query .= " ORDER BY p.name DESC";
        break;
    case 'price_low':
        $query .= " ORDER BY p.unit_price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY p.unit_price DESC";
        break;
    case 'stock':
        $query .= " ORDER BY current_stock ASC";
        break;
    default:
        $query .= " ORDER BY p.name ASC";
}

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($products)) {
    echo '<div class="text-center py-4 text-muted">';
    echo '<i class="fas fa-box-open fa-3x mb-3"></i>';
    echo '<p>No products found</p>';
    echo '<a href="../products/add.php" class="btn btn-sm btn-primary">Add New Product</a>';
    echo '</div>';
} else {
    foreach ($products as $product) {
        $stock = $product['current_stock'] ?? 0;
        $stock_class = $stock <= 5 ? 'text-danger' : 'text-success';
        ?>
        <div class="product-card" id="product_<?php echo $product['id']; ?>" 
             onclick="selectProduct(
                 <?php echo $product['id']; ?>,
                 '<?php echo addslashes($product['name']); ?>',
                 '<?php echo addslashes($product['sku']); ?>',
                 '<?php echo addslashes($product['category_name']); ?>',
                 '<?php echo addslashes($product['brand_name']); ?>',
                 <?php echo $product['unit_price'] ?? 0; ?>,
                 <?php echo $product['cost_price'] ?? 0; ?>,
                 <?php echo $stock; ?>
             )">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="product-image">
                        <i class="fas fa-box text-primary"></i>
                    </div>
                </div>
                <div class="col">
                    <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                    <small class="text-muted">
                        SKU: <?php echo htmlspecialchars($product['sku']); ?>
                        <?php if ($product['category_name']): ?>
                            | <?php echo htmlspecialchars($product['category_name']); ?>
                        <?php endif; ?>
                        <?php if ($product['brand_name']): ?>
                            | <?php echo htmlspecialchars($product['brand_name']); ?>
                        <?php endif; ?>
                    </small>
                </div>
                <div class="col-auto text-right">
                    <div class="font-weight-bold text-primary">₹<?php echo number_format($product['unit_price'] ?? 0, 2); ?></div>
                    <small class="<?php echo $stock_class; ?>">
                        <i class="fas fa-cubes"></i> <?php echo $stock; ?> units
                    </small>
                </div>
            </div>
        </div>
        <?php
    }
}
?>