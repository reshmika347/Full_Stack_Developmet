<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

define('APP_NAME', 'Inventory System');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/INVENTORY_SYSTEM/');  // Updated to caps
define('TIMEZONE', 'Asia/Kolkata');
date_default_timezone_set(TIMEZONE);

define('ITEMS_PER_PAGE', 10);
define('LOW_STOCK_THRESHOLD', 10);

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>