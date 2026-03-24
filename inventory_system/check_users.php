<?php
require_once 'config/database.php';

echo "<h1>🔍 User Database Check</h1>";

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("<p style='color:red'>❌ Database connection failed</p>");
}

echo "<p style='color:green'>✅ Database connected</p>";

// Check if users table exists
$tables = $db->query("SHOW TABLES LIKE 'users'");
if ($tables->rowCount() == 0) {
    echo "<p style='color:red'>❌ Users table does not exist!</p>";
    
    // Create users table
    echo "<p>Creating users table...</p>";
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100),
        role ENUM('admin', 'manager', 'staff') DEFAULT 'staff',
        last_login DATETIME,
        status TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($db->exec($sql)) {
        echo "<p style='color:green'>✅ Users table created</p>";
    } else {
        echo "<p style='color:red'>❌ Failed to create users table</p>";
    }
}

// Check for users
$result = $db->query("SELECT id, username, email, full_name, role, status FROM users");
$users = $result->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Users in database: " . count($users) . "</h2>";

if (empty($users)) {
    echo "<p style='color:orange'>⚠️ No users found in database!</p>";
    echo "<p><a href='create_admin.php'>Click here to create admin user</a></p>";
} else {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Role</th><th>Status</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "<td>" . ($user['status'] ? 'Active' : 'Inactive') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<h3>Quick Actions:</h3>";
echo "<ul>";
echo "<li><a href='create_admin.php'>Create Admin User</a></li>";
echo "<li><a href='login.php'>Go to Login</a></li>";
echo "<li><a href='register.php'>Register New User</a></li>";
echo "</ul>";
?>