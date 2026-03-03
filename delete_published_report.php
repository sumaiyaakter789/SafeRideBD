<?php
require_once 'admin_auth.php';
include_once 'db_config.php';

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "অবৈধ অনুরোধ।";
    header("Location: admin_published_reports.php");
    exit();
}

$report_id = intval($_GET['id']);

// Get report details to delete cover image
$select_sql = "SELECT cover_image FROM published_reports WHERE id = ?";
$select_stmt = $conn->prepare($select_sql);
$select_stmt->bind_param("i", $report_id);
$select_stmt->execute();
$result = $select_stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error_message'] = "প্রতিবেদনটি পাওয়া যায়নি।";
    header("Location: admin_published_reports.php");
    exit();
}

$report = $result->fetch_assoc();

// Delete the report
$delete_sql = "DELETE FROM published_reports WHERE id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param("i", $report_id);

if ($delete_stmt->execute()) {
    // Delete cover image if exists
    if (!empty($report['cover_image']) && file_exists($report['cover_image'])) {
        unlink($report['cover_image']);
    }
    
    // Log the action
    $log_sql = "INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, 'delete', ?, ?)";
    $log_stmt = $conn->prepare($log_sql);
    $log_details = "প্রকাশিত প্রতিবেদন #$report_id মুছে ফেলা হয়েছে";
    $ip = $_SERVER['REMOTE_ADDR'];
    $log_stmt->bind_param("iss", $_SESSION['admin_id'], $log_details, $ip);
    $log_stmt->execute();
    
    $_SESSION['success_message'] = "প্রতিবেদন সফলভাবে মুছে ফেলা হয়েছে।";
} else {
    $_SESSION['error_message'] = "প্রতিবেদন মুছতে সমস্যা হয়েছে।";
}

header("Location: admin_published_reports.php");
exit();
?>