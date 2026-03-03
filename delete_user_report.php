<?php
session_start();
header('Content-Type: application/json');

include_once 'db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'লগইন প্রয়োজন।']);
    exit();
}

$user_id = $_SESSION['user_id'];
$report_id = isset($_POST['report_id']) ? intval($_POST['report_id']) : 0;

if ($report_id == 0) {
    echo json_encode(['success' => false, 'message' => 'অবৈধ অনুরোধ।']);
    exit();
}

// Check if report exists and belongs to user
$check_sql = "SELECT id, status, evidence_files FROM incident_reports WHERE id = ? AND user_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $report_id, $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'রিপোর্ট পাওয়া যায়নি।']);
    exit();
}

$report = $result->fetch_assoc();

// Only allow deletion of pending or rejected reports
if ($report['status'] != 'pending' && $report['status'] != 'rejected') {
    echo json_encode(['success' => false, 'message' => 'শুধুমাত্র অপেক্ষমান বা বাতিল রিপোর্ট মুছে ফেলা যাবে।']);
    exit();
}

// Delete evidence files
if (!empty($report['evidence_files'])) {
    $evidence = json_decode($report['evidence_files'], true);
    if (is_array($evidence)) {
        foreach ($evidence as $file) {
            $filepath = 'uploads/incidents/' . $file['filename'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
    }
}

// Delete the report
$delete_sql = "DELETE FROM incident_reports WHERE id = ? AND user_id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param("ii", $report_id, $user_id);

if ($delete_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'রিপোর্ট সফলভাবে মুছে ফেলা হয়েছে।']);
} else {
    echo json_encode(['success' => false, 'message' => 'রিপোর্ট মুছতে সমস্যা হয়েছে।']);
}

$conn->close();
?>