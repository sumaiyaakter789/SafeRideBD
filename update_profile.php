<?php
session_start();
include_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'প্রোফাইল আপডেট করতে লগইন করুন';
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = 'ভুল পদ্ধতি';
    header("Location: profile.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$full_name = trim($_POST['full_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$date_of_birth = $_POST['date_of_birth'] ?? null;
$gender = $_POST['gender'] ?? null;
$address = trim($_POST['address'] ?? '');
$bio = trim($_POST['bio'] ?? '');

$errors = [];

if (empty($full_name) || strlen($full_name) < 2) {
    $errors[] = 'নাম কমপক্ষে ২ অক্ষরের হতে হবে';
}

if (!empty($phone) && !preg_match('/^01[0-9]{9}$/', $phone)) {
    $errors[] = 'সঠিক বাংলাদেশি ফোন নম্বর দিন (০১XXXXXXXXX)';
}

if (!empty($phone)) {
    $check_sql = "SELECT id FROM users WHERE phone = ? AND id != ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("si", $phone, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $errors[] = 'এই ফোন নম্বরটি অন্য একটি অ্যাকাউন্টের সাথে নিবন্ধিত';
    }
    $check_stmt->close();
}

if (!empty($errors)) {
    $_SESSION['error_message'] = implode('<br>', $errors);
    header("Location: profile.php");
    exit();
}

$phone = empty($phone) ? null : $phone;

$sql = "UPDATE users SET 
        full_name = ?, 
        phone = ?, 
        date_of_birth = ?, 
        gender = ?, 
        address = ?, 
        bio = ?, 
        updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssi", 
    $full_name, 
    $phone, 
    $date_of_birth, 
    $gender, 
    $address, 
    $bio, 
    $user_id
);

if ($stmt->execute()) {
    $_SESSION['user_name'] = $full_name;
    $_SESSION['user_phone'] = $phone;
    
    $_SESSION['success_message'] = 'প্রোফাইল সফলভাবে আপডেট হয়েছে!';
} else {
    $_SESSION['error_message'] = 'প্রোফাইল আপডেট করতে ব্যর্থ হয়েছে। আবার চেষ্টা করুন।';
}

$stmt->close();
$conn->close();

header("Location: profile.php");
exit();
?>