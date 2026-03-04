<?php
require_once 'admin_auth.php';

// Only super admin can access this page
if (!isSuperAdmin()) {
    header("Location: admin_dashboard.php");
    exit();
}

include_once 'db_config.php';

$error = '';
$success = '';
$preview_data = [];
$has_file = false;

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];
    
    // Check if file is uploaded
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "ফাইল আপলোড করতে সমস্যা হয়েছে!";
    } else {
        // Get file extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, ['csv', 'xlsx', 'xls', 'txt'])) {
            $error = "শুধুমাত্র CSV বা Excel ফাইল আপলোড করা যাবে!";
        } else {
            // Try to detect delimiter for CSV files
            if ($ext == 'csv' || $ext == 'txt') {
                // Read first line to detect delimiter
                $handle = fopen($file['tmp_name'], "r");
                $first_line = fgets($handle);
                fclose($handle);
                
                // Check for common delimiters
                $delimiters = [',', ';', "\t", '|'];
                $delimiter = ','; // default
                $max_count = 0;
                
                foreach ($delimiters as $d) {
                    $count = count(str_getcsv($first_line, $d));
                    if ($count > $max_count) {
                        $max_count = $count;
                        $delimiter = $d;
                    }
                }
                
                // Read CSV file with detected delimiter
                if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
                    $row = 0;
                    $headers = [];
                    $data = [];
                    
                    while (($row_data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                        // Remove empty values and trim
                        $row_data = array_map('trim', $row_data);
                        
                        if ($row == 0) {
                            // First row - headers
                            $headers = $row_data;
                            // Validate headers (case-insensitive)
                            $required = ['From', 'To', 'Fare', 'Distance', 'Bus_Name'];
                            $header_upper = array_map('strtolower', $headers);
                            $required_upper = array_map('strtolower', $required);
                            
                            $missing = [];
                            foreach ($required_upper as $req) {
                                if (!in_array($req, $header_upper)) {
                                    $missing[] = $req;
                                }
                            }
                            
                            if (!empty($missing)) {
                                $error = "ফাইলে প্রয়োজনীয় কলাম নেই: " . implode(', ', $missing);
                                break;
                            }
                        } else {
                            // Data rows
                            if (count($row_data) >= 3) { // At least have From, To, and Fare
                                $row_assoc = [];
                                foreach ($headers as $index => $header) {
                                    $value = isset($row_data[$index]) ? trim($row_data[$index]) : '';
                                    $row_assoc[$header] = $value;
                                }
                                
                                // Validate data - allow empty Bus_Name
                                if (!empty($row_assoc['From']) && !empty($row_assoc['To'])) {
                                    // Check if Fare is numeric
                                    $fare = str_replace(['৳', ',', ' ', 'Tk', 'tk', 'BDT'], '', $row_assoc['Fare']);
                                    if (is_numeric($fare)) {
                                        $row_assoc['Fare'] = $fare;
                                        
                                        // Check if Distance is numeric (if provided)
                                        if (!empty($row_assoc['Distance'])) {
                                            $distance = str_replace(['কিমি', 'km', ' ', 'কি.মি.', 'কিলোমিটার'], '', $row_assoc['Distance']);
                                            $distance = str_replace(',', '', $distance);
                                            if (is_numeric($distance)) {
                                                $row_assoc['Distance'] = $distance;
                                            } else {
                                                $row_assoc['Distance'] = 0;
                                            }
                                        } else {
                                            $row_assoc['Distance'] = 0;
                                        }
                                        
                                        // Handle empty Bus_Name
                                        if (empty($row_assoc['Bus_Name'])) {
                                            $row_assoc['Bus_Name'] = 'N/A';
                                        }
                                        
                                        $data[] = $row_assoc;
                                    }
                                }
                            }
                        }
                        $row++;
                    }
                    fclose($handle);
                    
                    if (empty($error) && !empty($data)) {
                        $preview_data = array_slice($data, 0, 10); // Show first 10 rows for preview
                        $_SESSION['bulk_data'] = $data;
                        $has_file = true;
                    } elseif (empty($data)) {
                        $error = "ফাইলে কোনো বৈধ ডেটা পাওয়া যায়নি! ফাইলটি সঠিক ফরম্যাটে আছে কিনা যাচাই করুন।";
                    }
                } else {
                    $error = "ফাইল পড়তে সমস্যা হয়েছে!";
                }
            } else {
                // Handle Excel files - you'll need to use a library like PhpSpreadsheet
                $error = "Excel ফাইল সাপোর্ট করতে PHPExcel/PhpSpreadsheet লাইব্রেরি প্রয়োজন। বর্তমানে শুধুমাত্র CSV ফাইল সাপোর্ট করে।";
            }
        }
    }
}

// Handle import confirmation
if (isset($_POST['confirm_import']) && isset($_SESSION['bulk_data'])) {
    $data = $_SESSION['bulk_data'];
    $inserted = 0;
    $updated = 0;
    $skipped = 0;
    
    foreach ($data as $row) {
        $from = $conn->real_escape_string(trim($row['From']));
        $to = $conn->real_escape_string(trim($row['To']));
        $fare = floatval($row['Fare']);
        $distance = floatval($row['Distance'] ?? 0);
        $bus_name = $conn->real_escape_string(trim($row['Bus_Name'] ?? 'N/A'));
        
        // Check if route exists
        $check_sql = "SELECT id FROM fare_chart WHERE `from` = '$from' AND `to` = '$to'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            // Update existing
            $update_sql = "UPDATE fare_chart SET fare = $fare, distance_km = $distance, operating_bus = '$bus_name' WHERE `from` = '$from' AND `to` = '$to'";
            if ($conn->query($update_sql)) {
                $updated++;
            } else {
                $skipped++;
            }
        } else {
            // Insert new
            $insert_sql = "INSERT INTO fare_chart (`from`, `to`, fare, distance_km, operating_bus) VALUES ('$from', '$to', $fare, $distance, '$bus_name')";
            if ($conn->query($insert_sql)) {
                $inserted++;
            } else {
                $skipped++;
            }
        }
    }
    
    // Log activity
    $log_sql = "INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, 'bulk_upload', ?, ?)";
    $log_stmt = $conn->prepare($log_sql);
    $details = "বাল্ক আপলোড সম্পন্ন হয়েছে: {$inserted}টি নতুন, {$updated}টি আপডেট, {$skipped}টি স্কিপ";
    $ip = $_SERVER['REMOTE_ADDR'];
    $log_stmt->bind_param("iss", $_SESSION['admin_id'], $details, $ip);
    $log_stmt->execute();
    $log_stmt->close();
    
    // Record in update history
    $history_sql = "INSERT INTO fare_update_history (admin_id, update_type, records_affected, file_name) VALUES (?, 'bulk', ?, ?)";
    $history_stmt = $conn->prepare($history_sql);
    $total = $inserted + $updated;
    $file_name = $_FILES['excel_file']['name'] ?? 'unknown.csv';
    $history_stmt->bind_param("iis", $_SESSION['admin_id'], $total, $file_name);
    $history_stmt->execute();
    $history_stmt->close();
    
    unset($_SESSION['bulk_data']);
    
    $success = "বাল্ক আপলোড সম্পন্ন হয়েছে!<br>
                নতুন যোগ: {$inserted}টি<br>
                আপডেট: {$updated}টি<br>
                স্কিপ: {$skipped}টি";
}

// Clear session data
if (isset($_POST['cancel_import'])) {
    unset($_SESSION['bulk_data']);
    header("Location: admin_bulk_upload.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>বাল্ক আপলোড - SafeRideBD অ্যাডমিন</title>
    <link href="https://banglawebfonts.pages.dev/css/bornomala.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-primary: #0a0c0f;
            --bg-secondary: #14181c;
            --bg-card: #1e2429;
            --bg-hover: #2a323a;
            --text-primary: #ffffff;
            --text-secondary: #b0b8c2;
            --text-muted: #8a929c;
            --accent-primary: #ff6b4a;
            --accent-secondary: #4ade80;
            --accent-warning: #fbbf24;
            --accent-danger: #ef4444;
            --border-color: #2e3a44;
            --shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
        }

        body {
            font-family: 'Bornomala', serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background-color: var(--bg-card);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }

        .sidebar-header {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
        }

        .sidebar-logo {
            height: 60px;
            width: auto;
            filter: brightness(0) invert(1);
            margin-bottom: 10px;
        }

        .sidebar-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .sidebar-subtitle {
            font-size: 12px;
            color: var(--accent-primary);
            margin-top: 5px;
        }

        .admin-info {
            padding: 20px;
            background-color: var(--bg-secondary);
            margin: 20px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid var(--border-color);
        }

        .admin-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--accent-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 20px;
            font-weight: 600;
        }

        .admin-name {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 5px;
        }

        .admin-role {
            display: inline-block;
            padding: 4px 12px;
            background-color: rgba(255, 107, 74, 0.2);
            color: var(--accent-primary);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .nav-menu {
            flex: 1;
            padding: 0 20px;
        }

        .nav-item {
            list-style: none;
            margin-bottom: 5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .nav-link:hover {
            background-color: var(--bg-hover);
            color: var(--text-primary);
        }

        .nav-link.active {
            background-color: var(--accent-primary);
            color: white;
        }

        .nav-link i {
            width: 20px;
            font-size: 16px;
        }

        .logout-btn {
            margin: 20px;
            padding: 12px;
            background-color: var(--bg-hover);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-secondary);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.2s;
        }

        .logout-btn:hover {
            background-color: var(--accent-danger);
            color: white;
            border-color: var(--accent-danger);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-subtitle {
            color: var(--text-muted);
            font-size: 15px;
        }

        .upload-card {
            background-color: var(--bg-card);
            border-radius: 12px;
            padding: 30px;
            border: 1px solid var(--border-color);
        }

        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .alert-success {
            background-color: rgba(74, 222, 128, 0.1);
            border-left: 4px solid var(--accent-secondary);
            color: var(--accent-secondary);
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            border-left: 4px solid var(--accent-danger);
            color: var(--accent-danger);
        }

        .upload-area {
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            padding: 50px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 20px;
        }

        .upload-area:hover {
            border-color: var(--accent-primary);
            background-color: var(--bg-hover);
        }

        .upload-area i {
            font-size: 48px;
            color: var(--accent-primary);
            margin-bottom: 15px;
        }

        .upload-area h3 {
            color: var(--text-primary);
            margin-bottom: 10px;
        }

        .upload-area p {
            color: var(--text-muted);
        }

        .file-info {
            display: none;
            margin-top: 15px;
            padding: 10px;
            background-color: var(--bg-secondary);
            border-radius: 6px;
            color: var(--accent-secondary);
        }

        .sample-format {
            background-color: var(--bg-secondary);
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }

        .sample-format h4 {
            color: var(--text-primary);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sample-table {
            width: 100%;
            border-collapse: collapse;
        }

        .sample-table th {
            text-align: left;
            padding: 10px;
            background-color: var(--bg-hover);
            color: var(--text-secondary);
            font-size: 13px;
        }

        .sample-table td {
            padding: 8px 10px;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border-color);
            font-size: 13px;
        }

        .preview-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .preview-table th {
            text-align: left;
            padding: 12px;
            background-color: var(--bg-hover);
            color: var(--text-secondary);
            font-size: 13px;
        }

        .preview-table td {
            padding: 10px 12px;
            color: var(--text-primary);
            border-bottom: 1px solid var(--border-color);
            font-size: 13px;
        }

        .preview-table tr:hover td {
            background-color: var(--bg-hover);
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .btn {
            padding: 14px 28px;
            border-radius: 8px;
            font-family: 'Bornomala', serif;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background-color: var(--accent-primary);
            color: white;
            flex: 1;
        }

        .btn-primary:hover {
            background-color: #ff5a3a;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(255, 107, 74, 0.3);
        }

        .btn-success {
            background-color: var(--accent-secondary);
            color: var(--bg-primary);
            flex: 1;
        }

        .btn-success:hover {
            background-color: #3bcc6c;
            transform: translateY(-2px);
        }

        .btn-warning {
            background-color: var(--accent-warning);
            color: var(--bg-primary);
            flex: 1;
        }

        .btn-danger {
            background-color: var(--accent-danger);
            color: white;
            flex: 1;
        }

        .btn-danger:hover {
            background-color: #dc2626;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: var(--bg-hover);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            flex: 1;
        }

        .btn-secondary:hover {
            background-color: var(--border-color);
            color: var(--text-primary);
        }

        .stats-box {
            background-color: var(--bg-secondary);
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid var(--border-color);
        }

        .stats-box h4 {
            color: var(--accent-secondary);
            margin-bottom: 15px;
        }

        .empty-value {
            color: var(--text-muted);
            font-style: italic;
        }

        .debug-hint {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 10px 15px;
            font-size: 12px;
            color: var(--text-muted);
            z-index: 1000;
        }

        .debug-hint i {
            color: var(--accent-warning);
            margin-right: 5px;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .preview-table {
                font-size: 12px;
            }
            
            .debug-hint {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Debug Hint -->
    <div class="debug-hint">
        <i class="fas fa-keyboard"></i>
        Ctrl+Shift+D - ফাইলের কন্টেন্ট দেখুন (Console)
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="saferidebd_removebg_main.png" alt="SafeRideBD" class="sidebar-logo">
            <div class="sidebar-title">SafeRideBD</div>
            <div class="sidebar-subtitle">অ্যাডমিন প্যানেল</div>
        </div>

        <div class="admin-info">
            <div class="admin-avatar">
                <?php echo strtoupper(substr(getCurrentAdminName(), 0, 1)); ?>
            </div>
            <div class="admin-name"><?php echo htmlspecialchars(getCurrentAdminName()); ?></div>
            <div class="admin-role">সুপার অ্যাডমিন</div>
        </div>

        <ul class="nav-menu">
            <li class="nav-item">
                <a href="admin_dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    ড্যাশবোর্ড
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_reports.php" class="nav-link">
                    <i class="fas fa-exclamation-triangle"></i>
                    রিপোর্টসমূহ
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_published_reports.php" class="nav-link">
                    <i class="fas fa-newspaper"></i>
                    প্রকাশিত প্রতিবেদন
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_fare_list.php" class="nav-link">
                    <i class="fas fa-list"></i>
                    ভাড়া তালিকা
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_add_fare.php" class="nav-link">
                    <i class="fas fa-plus-circle"></i>
                    নতুন ভাড়া যোগ
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_bulk_upload.php" class="nav-link active">
                    <i class="fas fa-file-upload"></i>
                    বাল্ক আপলোড
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_manage_admins.php" class="nav-link">
                    <i class="fas fa-users-cog"></i>
                    অ্যাডমিন ব্যবস্থাপনা
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_logs.php" class="nav-link">
                    <i class="fas fa-history"></i>
                    কার্যকলাপ লগ
                </a>
            </li>
        </ul>

        <a href="admin_logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            লগআউট
        </a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">বাল্ক আপলোড</h1>
            <p class="page-subtitle">একসাথে অনেক ভাড়ার তথ্য আপলোড করুন</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['bulk_data']) && !empty($preview_data)): ?>
            <!-- Preview Data -->
            <div class="upload-card">
                <h3 style="color: var(--text-primary); margin-bottom: 20px;">আপলোডের পূর্বে দেখুন</h3>
                
                <div class="stats-box">
                    <h4><i class="fas fa-info-circle"></i> ডেটা পরিসংখ্যান</h4>
                    <p style="color: var(--text-secondary);">মোট এন্ট্রি: <?php echo count($_SESSION['bulk_data']); ?>টি</p>
                    <p style="color: var(--text-secondary);">প্রথম ১০টি দেখানো হয়েছে</p>
                </div>

                <table class="preview-table">
                    <thead>
                        <tr>
                            <th>থেকে</th>
                            <th>যাওয়ার গন্তব্য</th>
                            <th>ভাড়া (৳)</th>
                            <th>দূরত্ব (কিমি)</th>
                            <th>বাসের নাম</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preview_data as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['From']); ?></td>
                            <td><?php echo htmlspecialchars($row['To']); ?></td>
                            <td><span style="color: var(--accent-primary);">৳<?php echo number_format($row['Fare'], 0); ?></span></td>
                            <td>
                                <?php if ($row['Distance'] > 0): ?>
                                    <span style="color: var(--accent-secondary);"><?php echo $row['Distance']; ?> কিমি</span>
                                <?php else: ?>
                                    <span class="empty-value">ফাঁকা</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['Bus_Name'] != 'N/A'): ?>
                                    <?php echo htmlspecialchars($row['Bus_Name']); ?>
                                <?php else: ?>
                                    <span class="empty-value">ফাঁকা</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <form method="POST" action="admin_bulk_upload.php">
                    <div class="action-buttons">
                        <button type="submit" name="confirm_import" class="btn btn-success">
                            <i class="fas fa-check"></i>
                            নিশ্চিত করে আপলোড করুন
                        </button>
                        <button type="submit" name="cancel_import" class="btn btn-danger">
                            <i class="fas fa-times"></i>
                            বাতিল করুন
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Upload Form -->
            <div class="upload-card">
                <form method="POST" action="admin_bulk_upload.php" enctype="multipart/form-data" id="uploadForm">
                    <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                        <input type="file" name="excel_file" id="fileInput" accept=".csv,.xlsx,.xls,.txt" style="display: none;" required>
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h3>ফাইল নির্বাচন করুন</h3>
                        <p>CSV বা Excel ফাইল আপলোড করুন</p>
                        <div id="fileName" class="file-info"></div>
                    </div>

                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i>
                            আপলোড করুন
                        </button>
                        <a href="admin_fare_list.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            বাতিল
                        </a>
                    </div>
                </form>

                <!-- Sample Format -->
                <div class="sample-format">
                    <h4><i class="fas fa-info-circle" style="color: var(--accent-primary);"></i> নমুনা ফরম্যাট</h4>
                    <table class="sample-table">
                        <thead>
                            <tr>
                                <th>From</th>
                                <th>To</th>
                                <th>Fare</th>
                                <th>Distance</th>
                                <th>Bus_Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Savar</td>
                                <td>Gabtoli</td>
                                <td>34</td>
                                <td>13.8</td>
                                <td>boishakhi</td>
                            </tr>
                            <tr>
                                <td>Savar</td>
                                <td>Mirpur-1</td>
                                <td>41</td>
                                <td>16.9</td>
                                <td><span style="color: var(--text-muted);">(ফাঁকা রাখা যাবে)</span></td>
                            </tr>
                            <tr>
                                <td>Gabtoli</td>
                                <td>Mohakhali</td>
                                <td>31</td>
                                <td>12.5</td>
                                <td>N/A</td>
                            </tr>
                        </tbody>
                    </table>
                    <p style="color: var(--text-muted); margin-top: 15px; font-size: 13px;">
                        <i class="fas fa-lightbulb" style="color: var(--accent-warning);"></i>
                        CSV ফাইল তৈরি করার সময় প্রথম সারিতে কলামের নাম ঠিক মতো দিন। Bus_Name ফাঁকা রাখা যাবে।
                    </p>
                    <p style="color: var(--accent-info); margin-top: 10px; font-size: 12px;">
                        <i class="fas fa-info-circle"></i>
                        টিপ: ফাইল সেভ করার সময় UTF-8 এনকোডিং ব্যবহার করুন এবং কমা (,) দ্বারা আলাদা করুন।
                    </p>
                    <p style="color: var(--accent-secondary); margin-top: 10px; font-size: 12px;">
                        <i class="fas fa-download"></i>
                        <a href="sample_bulk_upload.csv" style="color: var(--accent-secondary); text-decoration: underline;" download>নমুনা CSV ফাইল ডাউনলোড করুন</a>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('fileInput').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : '';
            const fileInfo = document.getElementById('fileName');
            
            if (fileName) {
                fileInfo.innerHTML = '<i class="fas fa-check-circle" style="color: var(--accent-secondary);"></i> নির্বাচিত ফাইল: ' + fileName;
                
                // Show file size
                const fileSize = (e.target.files[0].size / 1024).toFixed(2);
                fileInfo.innerHTML += `<br><small>ফাইলের আকার: ${fileSize} KB</small>`;
                
                // Show first few lines for debugging
                const reader = new FileReader();
                reader.onload = function(e) {
                    const content = e.target.result;
                    const lines = content.split('\n').slice(0, 3);
                    console.log('First 3 lines of file:');
                    lines.forEach((line, i) => console.log(`Line ${i+1}:`, line));
                    
                    // Detect delimiter
                    const firstLine = lines[0];
                    const delimiters = [',', ';', '\t', '|'];
                    let detectedDelimiter = 'unknown';
                    
                    for (let d of delimiters) {
                        if (firstLine.includes(d)) {
                            detectedDelimiter = d === '\t' ? 'tab' : d;
                            break;
                        }
                    }
                    
                    fileInfo.innerHTML += `<br><small>Detected delimiter: ${detectedDelimiter}</small>`;
                };
                reader.readAsText(e.target.files[0]);
                
                fileInfo.style.display = 'block';
            } else {
                fileInfo.style.display = 'none';
            }
        });

        // Add drag and drop support
        const uploadArea = document.querySelector('.upload-area');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, unhighlight, false);
        });

        function highlight() {
            uploadArea.style.borderColor = 'var(--accent-primary)';
            uploadArea.style.backgroundColor = 'var(--bg-hover)';
        }

        function unhighlight() {
            uploadArea.style.borderColor = 'var(--border-color)';
            uploadArea.style.backgroundColor = 'transparent';
        }

        uploadArea.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            document.getElementById('fileInput').files = files;
            
            // Trigger change event
            const event = new Event('change', { bubbles: true });
            document.getElementById('fileInput').dispatchEvent(event);
        }

        // Debug mode - press Ctrl+Shift+D to see file content
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && e.key === 'D') {
                const fileInput = document.getElementById('fileInput');
                if (fileInput.files.length > 0) {
                    const file = fileInput.files[0];
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        console.log('=== FILE CONTENT ===');
                        console.log(e.target.result);
                        console.log('=== END FILE CONTENT ===');
                        alert('ফাইলের কন্টেন্ট কনসোলে দেখা যাচ্ছে (F12 চাপুন)');
                    };
                    reader.readAsText(file);
                } else {
                    alert('কোন ফাইল নির্বাচন করা হয়নি');
                }
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
<?php $conn->close(); ?>