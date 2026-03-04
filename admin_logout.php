<?php
session_start();

// Log logout if admin was logged in
if (isset($_SESSION['admin_id'])) {
    include_once 'db_config.php';
    
    $log_sql = "INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, 'logout', 'Admin logged out', ?)";
    $log_stmt = $conn->prepare($log_sql);
    $ip = $_SERVER['REMOTE_ADDR'];
    $log_stmt->bind_param("is", $_SESSION['admin_id'], $ip);
    $log_stmt->execute();
    $log_stmt->close();
    $conn->close();
}

// Destroy session
session_destroy();

// Redirect to login
header("Location: admin_login.php");
exit();
?>