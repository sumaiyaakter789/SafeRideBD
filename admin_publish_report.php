<?php
require_once 'admin_auth.php';
include_once 'db_config.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['action']) || $_POST['action'] != 'publish') {
    header("Location: admin_reports.php");
    exit();
}

$report_id = intval($_POST['report_id']);
$title = trim($_POST['title']);
$content = trim($_POST['content']);
$publish_date = $_POST['publish_date'] ?? date('Y-m-d');
$author = trim($_POST['author'] ?? getCurrentAdminName());
$category = $_POST['category'] ?? 'incident';
$source = trim($_POST['source'] ?? '');

// Validate required fields
if (empty($title) || empty($content)) {
    $_SESSION['error_message'] = "শিরোনাম এবং বিবরণ অবশ্যই প্রদান করতে হবে।";
    header("Location: admin_reports.php?write=true&id=$report_id");
    exit();
}

// Handle cover image upload
$cover_image = null;
if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/reports/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array($file_ext, $allowed_exts)) {
        $filename = 'report_' . time() . '_' . $report_id . '.' . $file_ext;
        $upload_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_path)) {
            $cover_image = $upload_path;
        }
    }
}

// Insert into published_reports
$insert_sql = "INSERT INTO published_reports 
               (incident_report_id, title, content, cover_image, author, category, source, publish_date, published_by) 
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param(
    "isssssssi",
    $report_id,
    $title,
    $content,
    $cover_image,
    $author,
    $category,
    $source,
    $publish_date,
    $_SESSION['admin_id']
);

if ($insert_stmt->execute()) {
    $published_id = $insert_stmt->insert_id;
    
    // Update the original incident report status to 'published'
    $update_sql = "UPDATE incident_reports SET status = 'published', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $_SESSION['admin_id'], $report_id);
    $update_stmt->execute();
    
    // Log the action
    $log_sql = "INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, 'publish', ?, ?)";
    $log_stmt = $conn->prepare($log_sql);
    $log_details = "রিপোর্ট #$report_id এর ভিত্তিতে প্রতিবেদন #$published_id প্রকাশিত হয়েছে";
    $ip = $_SERVER['REMOTE_ADDR'];
    $log_stmt->bind_param("iss", $_SESSION['admin_id'], $log_details, $ip);
    $log_stmt->execute();
    
    $_SESSION['success_message'] = "প্রতিবেদন সফলভাবে প্রকাশিত হয়েছে।";
} else {
    $_SESSION['error_message'] = "প্রতিবেদন প্রকাশ করতে সমস্যা হয়েছে।";
}

// Redirect back to reports page
header("Location: admin_reports.php");
exit();
?>