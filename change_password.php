<?php
session_start();
include_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Please login to change password';
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = 'Invalid request method';
    header("Location: profile.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$is_google_account = isset($_POST['is_google_account']);
$errors = [];

$user_sql = "SELECT password, google_id FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_stmt->close();

if (!$is_google_account) {
    $current_password = $_POST['current_password'] ?? '';
    
    if (empty($current_password)) {
        $errors[] = 'Current password is required';
    } elseif (!password_verify($current_password, $user_data['password'])) {
        $errors[] = 'Current password is incorrect';
    }
}

$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($new_password)) {
    $errors[] = 'New password is required';
} elseif (strlen($new_password) < 8) {
    $errors[] = 'Password must be at least 8 characters long';
} elseif (!preg_match('/[A-Z]/', $new_password)) {
    $errors[] = 'Password must contain at least one uppercase letter';
} elseif (!preg_match('/[a-z]/', $new_password)) {
    $errors[] = 'Password must contain at least one lowercase letter';
} elseif (!preg_match('/[0-9]/', $new_password)) {
    $errors[] = 'Password must contain at least one number';
} elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password)) {
    $errors[] = 'Password must contain at least one special character';
}

if ($new_password !== $confirm_password) {
    $errors[] = 'Passwords do not match';
}

if (!$is_google_account && password_verify($new_password, $user_data['password'])) {
    $errors[] = 'New password cannot be the same as current password';
}

if (!empty($errors)) {
    $_SESSION['error_message'] = implode('<br>', $errors);
    header("Location: profile.php");
    exit();
}

$password_hash = password_hash($new_password, PASSWORD_DEFAULT);

$update_sql = "UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("si", $password_hash, $user_id);

if ($update_stmt->execute()) {
    if ($is_google_account && $user_data['google_id']) {
        $convert_sql = "UPDATE users SET password = ? WHERE id = ?";
        $convert_stmt = $conn->prepare($convert_sql);
        $convert_stmt->bind_param("si", $password_hash, $user_id);
        $convert_stmt->execute();
        $convert_stmt->close();
    }
    
    $_SESSION['success_message'] = 'পাসওয়ার্ড সফলভাবে পরিবর্তন হয়েছে!';
} else {
    $_SESSION['error_message'] = 'পাসওয়ার্ড আপডেট করতে ব্যর্থ। দয়া করে আবার চেষ্টা করুন।';
}

$update_stmt->close();
$conn->close();

header("Location: profile.php");
exit();
?>