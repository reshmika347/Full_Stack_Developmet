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

// Check if product has stock
$check_stock = $db->prepare("SELECT SUM(quantity) as total FROM stock WHERE product_id = ?");
$check_stock->execute([$product_id]);
$stock = $check_stock->fetch(PDO::FETCH_ASSOC);

if ($stock['total'] > 0) {
    $_SESSION['error'] = 'Cannot delete product with existing stock. Please remove stock first.';
    header('Location: index.php');
    exit();
}

// Get product name for log
$product = $db->prepare("SELECT name FROM products WHERE id = ?");
$product->execute([$product_id]);
$product_name = $product->fetch(PDO::FETCH_ASSOC)['name'];

// Delete product
$stmt = $db->prepare("DELETE FROM products WHERE id = ?");
if ($stmt->execute([$product_id])) {
    logActivity($db, $_SESSION['user_id'], 'Delete Product', "Deleted product: $product_name");
    $_SESSION['success'] = 'Product deleted successfully';
} else {
    $_SESSION['error'] = 'Error deleting product';
}

header('Location: index.php');
exit();
?>