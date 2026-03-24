<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Inventory System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f0f2f5;
        }
        .navbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .container {
            padding: 30px;
        }
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .module-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        .module-card:hover {
            transform: translateY(-5px);
        }
        .module-card a {
            text-decoration: none;
            color: #333;
        }
        .module-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 5px;
        }
        .logout-btn:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h2>Inventory System</h2>
        <div>
            Welcome, <?php echo $_SESSION['full_name'] ?? $_SESSION['username']; ?> |
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome-card">
            <h1>Dashboard</h1>
            <p><?php echo date('l, F j, Y'); ?></p>
        </div>
        
        <div class="modules-grid">
            <div class="module-card">
                <a href="modules/products/index.php">
                    <div class="module-icon">📦</div>
                    <h3>Products</h3>
                </a>
            </div>
            <div class="module-card">
                <a href="modules/categories/index.php">
                    <div class="module-icon">🏷️</div>
                    <h3>Categories</h3>
                </a>
            </div>
            <div class="module-card">
                <a href="modules/suppliers/index.php">
                    <div class="module-icon">🚚</div>
                    <h3>Suppliers</h3>
                </a>
            </div>
            <div class="module-card">
                <a href="modules/stock/index.php">
                    <div class="module-icon">📊</div>
                    <h3>Stock</h3>
                </a>
            </div>
            <div class="module-card">
                <a href="modules/orders/index.php">
                    <div class="module-icon">🛒</div>
                    <h3>Orders</h3>
                </a>
            </div>
            <div class="module-card">
                <a href="modules/reports/index.php">
                    <div class="module-icon">📈</div>
                    <h3>Reports</h3>
                </a>
            </div>
            <div class="module-card">
                <a href="modules/users/index.php">
                    <div class="module-icon">👥</div>
                    <h3>Users</h3>
                </a>
            </div>
            <div class="module-card">
                <a href="modules/settings/index.php">
                    <div class="module-icon">⚙️</div>
                    <h3>Settings</h3>
                </a>
            </div>
        </div>
    </div>
</body>
</html>