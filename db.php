<?php
// =============================================
// Rain Detection and Control System
// Database Configuration
// =============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');         // Change to your MySQL username
define('DB_PASS', '');             // Change to your MySQL password
define('DB_NAME', 'rain_system');

function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
    }
    $conn->set_charset("utf8");
    return $conn;
}

session_start();

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
?>
