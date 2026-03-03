<?php
session_start();
include_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Create upload directory if it doesn't exist
$upload_dir = 'uploads/incidents/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Create table if not exists
$create_table = "CREATE TABLE IF NOT EXISTS incident_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    is_anonymous BOOLEAN DEFAULT FALSE,
    full_name VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(15),
    address TEXT,
    incident_type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    severity ENUM('low', 'medium', 'high') NOT NULL,
    location VARCHAR(100) NOT NULL,
    specific_location VARCHAR(255),
    incident_date DATE NOT NULL,
    incident_time TIME,
    bus_number VARCHAR(50),
    bus_details TEXT,
    driver_name VARCHAR(100),
    helper_name VARCHAR(100),
    witnesses TEXT,
    evidence_files TEXT,
    additional_info TEXT,
    follow_up VARCHAR(20) DEFAULT 'email',
    status ENUM('pending', 'reviewing', 'resolved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT,
    reviewed_by INT,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_incident_date (incident_date),
    INDEX idx_location (location)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$conn->query($create_table);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Handle file uploads
    $uploaded_files = [];
    if (isset($_FILES['evidence_files']) && !empty($_FILES['evidence_files']['name'][0])) {
        $files = $_FILES['evidence_files'];
        $file_count = count($files['name']);
        
        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $tmp_name = $files['tmp_name'][$i];
                $original_name = $files['name'][$i];
                $file_ext = pathinfo($original_name, PATHINFO_EXTENSION);
                $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($tmp_name, $upload_path)) {
                    $uploaded_files[] = [
                        'original' => $original_name,
                        'filename' => $new_filename,
                        'type' => $files['type'][$i],
                        'size' => $files['size'][$i]
                    ];
                }
            }
        }
    }
    
    // Prepare data for insertion
    $is_anonymous = isset($_POST['anonymous']) ? 1 : 0;
    
    // If anonymous, don't save personal info
    if ($is_anonymous) {
        $full_name = null;
        $email = null;
        $phone = null;
        $address = null;
    } else {
        $full_name = $_POST['full_name'] ?? null;
        $email = $_POST['email'] ?? null;
        $phone = $_POST['phone'] ?? null;
        $address = $_POST['address'] ?? null;
    }
    
    $incident_type = $_POST['incident_type'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $severity = $_POST['severity'] ?? 'medium';
    $location = $_POST['location'] ?? '';
    $specific_location = $_POST['specific_location'] ?? null;
    $incident_date = $_POST['incident_date'] ?? '';
    $incident_time = $_POST['incident_time'] ?? null;
    $bus_number = $_POST['bus_number'] ?? null;
    $bus_details = $_POST['bus_details'] ?? null;
    $driver_name = $_POST['driver_name'] ?? null;
    $helper_name = $_POST['helper_name'] ?? null;
    $witnesses = $_POST['witnesses'] ?? null;
    $additional_info = $_POST['additional_info'] ?? null;
    $follow_up = $_POST['follow_up'] ?? 'email';
    
    // Convert uploaded files to JSON
    $evidence_json = !empty($uploaded_files) ? json_encode($uploaded_files) : null;
    
    // Insert into database
    $sql = "INSERT INTO incident_reports (
        user_id, is_anonymous, full_name, email, phone, address,
        incident_type, title, description, severity, location, specific_location,
        incident_date, incident_time, bus_number, bus_details, driver_name,
        helper_name, witnesses, evidence_files, additional_info, follow_up, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "iissssssssssssssssssss",
        $user_id,
        $is_anonymous,
        $full_name,
        $email,
        $phone,
        $address,
        $incident_type,
        $title,
        $description,
        $severity,
        $location,
        $specific_location,
        $incident_date,
        $incident_time,
        $bus_number,
        $bus_details,
        $driver_name,
        $helper_name,
        $witnesses,
        $evidence_json,
        $additional_info,
        $follow_up
    );
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "আপনার রিপোর্ট সফলভাবে জমা দেওয়া হয়েছে। প্রশাসনিক পর্যালোচনার পর প্রয়োজনীয় ব্যবস্থা নেওয়া হবে। রিপোর্ট আইডি: #" . $stmt->insert_id;
    } else {
        $_SESSION['error_message'] = "রিপোর্ট জমা দিতে সমস্যা হয়েছে। আবার চেষ্টা করুন।";
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: report_incident.php");
    exit();
} else {
    header("Location: report_incident.php");
    exit();
}
?>