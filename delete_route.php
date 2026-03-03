<?php
session_start();
include_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to delete routes']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['route_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

$user_id = $_SESSION['user_id'];
$route_id = intval($data['route_id']);

$check_sql = "SELECT id FROM saved_routes WHERE id = ? AND user_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $route_id, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Route not found or access denied']);
    exit();
}

$delete_sql = "DELETE FROM saved_routes WHERE id = ? AND user_id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param("ii", $route_id, $user_id);

if ($delete_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Route deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete route']);
}

$delete_stmt->close();
$conn->close();
?>