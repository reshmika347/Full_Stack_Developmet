<?php
echo "<h1>🚨 Emergency Diagnostics</h1>";

echo "<h2>File Check:</h2>";
echo "<ul>";
$files = [
    'test.php',
    'login.php',
    'dashboard.php',
    'setup_fixed.php',
    'config/database.php',
    'includes/auth.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<li style='color:green'>✅ $file - OK</li>";
    } else {
        echo "<li style='color:red'>❌ $file - MISSING</li>";
    }
}
echo "</ul>";

echo "<h2>Directory Contents:</h2>";
echo "<pre>";
print_r(scandir(__DIR__));
echo "</pre>";

echo "<h2>Quick Links:</h2>";
echo "<ul>";
echo "<li><a href='test.php'>Test Page</a></li>";
echo "<li><a href='setup_fixed.php'>Run Setup</a></li>";
echo "<li><a href='login.php'>Login</a></li>";
echo "</ul>";
?>