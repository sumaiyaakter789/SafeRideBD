<?php
session_start();
include_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to save routes']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['from']) || !isset($data['to']) || !isset($data['distance']) || 
    !isset($data['regular_fare']) || !isset($data['student_fare'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

$user_id = $_SESSION['user_id'];
$from_location = $conn->real_escape_string($data['from']);
$to_location = $conn->real_escape_string($data['to']);
$distance_km = floatval($data['distance']);
$regular_fare = floatval($data['regular_fare']);
$student_fare = floatval($data['student_fare']);
$operating_bus = isset($data['operating_bus']) ? $conn->real_escape_string($data['operating_bus']) : '';

$check_sql = "SELECT id FROM saved_routes WHERE user_id = ? AND from_location = ? AND to_location = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("iss", $user_id, $from_location, $to_location);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'This route is already saved']);
    exit();
}

$sql = "INSERT INTO saved_routes (user_id, from_location, to_location, distance_km, regular_fare, student_fare, operating_bus) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("issddds", $user_id, $from_location, $to_location, $distance_km, $regular_fare, $student_fare, $operating_bus);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Route saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save route']);
}

$stmt->close();
$conn->close();
?>