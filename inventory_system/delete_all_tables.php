<?php
// Force error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Delete All Tables</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f0f2f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #e7f3ff; color: #004085; padding: 15px; border-radius: 5px; margin: 10px 0; }
        button { padding: 12px 24px; margin: 10px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-primary { background: #007bff; color: white; }
        hr { margin: 20px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🗑️ Delete All Tables - Emergency Tool</h1>
        <p>Current time: <?php echo date('Y-m-d H:i:s'); ?></p>
        <hr>";

// Database configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'inventory_system';

try {
    echo "<h3>🔍 Step 1: Testing MySQL Connection...</h3>";
    
    // Connect to MySQL server (without selecting database)
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='success'>✅ Connected to MySQL server successfully!</div>";
    
    echo "<h3>🔍 Step 2: Checking Database...</h3>";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
    
    if ($stmt->rowCount() == 0) {
        echo "<div class='warning'>⚠️ Database '$dbname' does not exist. Creating it now...</div>";
        $pdo->exec("CREATE DATABASE $dbname");
        echo "<div class='success'>✅ Database '$dbname' created successfully!</div>";
    } else {
        echo "<div class='success'>✅ Database '$dbname' exists</div>";
    }
    
    // Connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    
    echo "<h3>🔍 Step 3: Checking Tables...</h3>";
    
    // Get all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<div class='info'>ℹ️ No tables found in database. Database is empty.</div>";
    } else {
        echo "<div class='warning'>⚠️ Found " . count($tables) . " tables:</div>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li><strong>$table</strong></li>";
        }
        echo "</ul>";
    }
    
    // Handle form submissions
    if (isset($_POST['action'])) {
        echo "<h3>🔍 Step 4: Executing Action...</h3>";
        
        if ($_POST['action'] == 'delete_tables' && !empty($tables)) {
            // Disable foreign key checks
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // Delete all tables
            $deleted = 0;
            foreach ($tables as $table) {
                try {
                    $pdo->exec("DROP TABLE IF EXISTS $table");
                    echo "<div style='color:green; margin:5px;'>✅ Deleted table: $table</div>";
                    $deleted++;
                } catch(Exception $e) {
                    echo "<div style='color:red; margin:5px;'>❌ Error deleting $table: " . $e->getMessage() . "</div>";
                }
            }
            
            // Re-enable foreign key checks
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            echo "<div class='success'>✅ Successfully deleted $deleted tables!</div>";
            
            // Refresh table list
            $stmt = $pdo->query("SHOW TABLES");
            $remaining = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($remaining)) {
                echo "<div class='success'>✅ Database is now completely empty!</div>";
            }
        }
        
        if ($_POST['action'] == 'create_tables') {
            echo "<div class='info'>Redirecting to setup...</div>";
            echo "<script>window.location.href = 'setup_fixed.php';</script>";
        }
        
        if ($_POST['action'] == 'drop_database') {
            // Switch to different database
            $pdo = new PDO("mysql:host=$host", $user, $pass);
            $pdo->exec("DROP DATABASE IF EXISTS $dbname");
            echo "<div class='success'>✅ Database '$dbname' dropped successfully!</div>";
            
            // Recreate empty database
            $pdo->exec("CREATE DATABASE $dbname");
            echo "<div class='success'>✅ Database '$dbname' recreated empty!</div>";
        }
    }
    
} catch(PDOException $e) {
    echo "<div class='error'>❌ ERROR: " . $e->getMessage() . "</div>";
    
    echo "<h3>🔧 Troubleshooting Tips:</h3>";
    echo "<ul>";
    echo "<li>Make sure MySQL is running in XAMPP (check control panel)</li>";
    echo "<li>Try clicking 'Start' next to MySQL in XAMPP</li>";
    echo "<li>Check if password is correct (default is blank)</li>";
    echo "<li>Try restarting XAMPP completely</li>";
    echo "</ul>";
}

?>

<hr>

<form method="POST" style="margin-top: 20px;">
    <h3>Available Actions:</h3>
    
    <button type="submit" name="action" value="delete_tables" class="btn-danger" 
            onclick="return confirm('⚠️ WARNING: This will delete ALL tables! Are you sure?')">
        🗑️ Delete ALL Tables
    </button>
    
    <button type="submit" name="action" value="create_tables" class="btn-success">
        🔧 Create Tables (Run Setup)
    </button>
    
    <button type="submit" name="action" value="drop_database" class="btn-primary" 
            onclick="return confirm('⚠️ WARNING: This will DROP and RECREATE the entire database! Continue?')">
        🔄 Reset Complete Database
    </button>
</form>

<hr>

<h3>📋 Quick Links:</h3>
<p>
    <a href="test.php">Run PHP Test</a> | 
    <a href="login.php">Go to Login Page</a> | 
    <a href="setup_fixed.php">Run Setup Script</a> | 
    <a href="../phpmyadmin">Open phpMyAdmin</a>
</p>

<p><strong>File Location:</strong> <?php echo __FILE__; ?></p>
<p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>

</div>
</body>
</html>