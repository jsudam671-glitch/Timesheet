<?php
// Database Configuration Development
// define('DB_HOST', 'localhost');
// define('DB_USER', 'root');
// define('DB_PASS', '');
// define('DB_NAME', 'timesheet_pro');

// Database Configuration Staging
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'timesheet_pro');

// Initialize connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Check connection
if ($conn->connect_error) {
    // Don't expose connection errors to user
    error_log("Database Connection Failed: " . $conn->connect_error);
    die("Database connection failed. Please try again later.");
}

// Set error mode to exception for proper error handling
mysqli_report(MYSQLI_REPORT_ALL);

// Define security constants
define('MAX_HOURS_PER_DAY', 8);
define('MAX_HOURS_PER_WEEK', 40);
define('FILE_UPLOAD_DIR', __DIR__ . '/temp/');
?>
