<?php
require_once 'config/database.php';

echo "<h1>👤 Create Admin User</h1>";

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("<p style='color:red'>❌ Database connection failed</p>");
}

// Check if admin already exists
$check = $db->query("SELECT id FROM users WHERE username = 'admin'");
if ($check->fetch()) {
    echo "<p style='color:orange'>⚠️ Admin user already exists!</p>";
} else {
    // Create admin user with password 'admin123'
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (username, email, password, full_name, role) 
            VALUES ('admin', 'admin@example.com', ?, 'Administrator', 'admin')";
    
    $stmt = $db->prepare($sql);
    if ($stmt->execute([$password])) {
        echo "<p style='color:green'>✅ Admin user created successfully!</p>";
        echo "<p>Username: <strong>admin</strong></p>";
        echo "<p>Password: <strong>admin123</strong></p>";
    } else {
        echo "<p style='color:red'>❌ Failed to create admin user</p>";
    }
}

echo "<hr>";
echo "<p><a href='login.php'>Go to Login Page</a></p>";
echo "<p><a href='check_users.php'>Check Users</a></p>";
?>