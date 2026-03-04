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
$admin_data = null;

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_manage_admins.php");
    exit();
}

$admin_id = $_GET['id'];

// Cannot edit own account? (Optional - you can allow editing own account)
if ($admin_id == $_SESSION['admin_id']) {
    $error = "আপনি নিজের অ্যাকাউন্ট সম্পাদনা করতে পারবেন না! অন্য পৃষ্ঠা থেকে প্রোফাইল আপডেট করুন।";
    // You can redirect or show error
}

// Get admin data
$select_sql = "SELECT * FROM admin_users WHERE id = ?";
$select_stmt = $conn->prepare($select_sql);
$select_stmt->bind_param("i", $admin_id);
$select_stmt->execute();
$result = $select_stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: admin_manage_admins.php");
    exit();
}

$admin_data = $result->fetch_assoc();
$select_stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "ইউজারনেম প্রয়োজন!";
    } elseif (strlen($username) < 3) {
        $errors[] = "ইউজারনেম কমপক্ষে ৩ অক্ষর হতে হবে!";
    }
    
    if (empty($full_name)) {
        $errors[] = "পূর্ণ নাম প্রয়োজন!";
    }
    
    if (empty($email)) {
        $errors[] = "ইমেইল প্রয়োজন!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "বৈধ ইমেইল ঠিকানা দিন!";
    }
    
    // Check if username already exists (excluding current admin)
    $check_sql = "SELECT id FROM admin_users WHERE username = ? AND id != ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("si", $username, $admin_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $errors[] = "এই ইউজারনেম already exists!";
    }
    $check_stmt->close();
    
    // Check if email already exists (excluding current admin)
    $check_sql = "SELECT id FROM admin_users WHERE email = ? AND id != ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("si", $email, $admin_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $errors[] = "এই ইমেইল already exists!";
    }
    $check_stmt->close();
    
    // Password validation (if provided)
    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            $errors[] = "পাসওয়ার্ড কমপক্ষে ৬ অক্ষর হতে হবে!";
        }
        if ($new_password !== $confirm_password) {
            $errors[] = "পাসওয়ার্ড মিলছে না!";
        }
    }
    
    if (empty($errors)) {
        // Build update query
        if (!empty($new_password)) {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE admin_users SET username = ?, full_name = ?, email = ?, role = ?, is_active = ?, password = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssssisi", $username, $full_name, $email, $role, $is_active, $hashed_password, $admin_id);
        } else {
            $update_sql = "UPDATE admin_users SET username = ?, full_name = ?, email = ?, role = ?, is_active = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sssii", $username, $full_name, $email, $role, $is_active, $admin_id);
        }
        
        if ($update_stmt->execute()) {
            // Log activity
            $log_sql = "INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, 'admin_update', ?, ?)";
            $log_stmt = $conn->prepare($log_sql);
            $details = "অ্যাডমিন তথ্য আপডেট করা হয়েছে: {$username} (ID: {$admin_id})";
            $ip = $_SERVER['REMOTE_ADDR'];
            $log_stmt->bind_param("iss", $_SESSION['admin_id'], $details, $ip);
            $log_stmt->execute();
            $log_stmt->close();
            
            $success = "অ্যাডমিনের তথ্য সফলভাবে আপডেট করা হয়েছে!";
            
            // Refresh admin data
            $select_sql = "SELECT * FROM admin_users WHERE id = ?";
            $select_stmt = $conn->prepare($select_sql);
            $select_stmt->bind_param("i", $admin_id);
            $select_stmt->execute();
            $result = $select_stmt->get_result();
            $admin_data = $result->fetch_assoc();
            $select_stmt->close();
        } else {
            $error = "আপডেট করতে ব্যর্থ হয়েছে!";
        }
        $update_stmt->close();
    } else {
        $error = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>অ্যাডমিন সম্পাদনা - SafeRideBD</title>
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

        /* Sidebar (same as admin_manage_admins.php) */
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-subtitle {
            color: var(--text-muted);
            font-size: 15px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-family: 'Bornomala', serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background-color: var(--accent-primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: #ff5a3a;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(255, 107, 74, 0.3);
        }

        .btn-secondary {
            background-color: var(--bg-hover);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background-color: var(--border-color);
            color: var(--text-primary);
            transform: translateY(-2px);
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

        .alert-warning {
            background-color: rgba(251, 191, 36, 0.1);
            border-left: 4px solid var(--accent-warning);
            color: var(--accent-warning);
        }

        .form-card {
            background-color: var(--bg-card);
            border-radius: 12px;
            padding: 30px;
            border: 1px solid var(--border-color);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .form-group label i {
            color: var(--accent-primary);
            margin-right: 5px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-family: 'Bornomala', serif;
            font-size: 15px;
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(255, 107, 74, 0.2);
        }

        .form-control[readonly] {
            background-color: var(--bg-hover);
            cursor: not-allowed;
            opacity: 0.7;
        }

        select.form-control {
            cursor: pointer;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--accent-primary);
        }

        .checkbox-group label {
            margin-bottom: 0;
            cursor: pointer;
        }

        .info-box {
            background-color: var(--bg-secondary);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
        }

        .info-box h4 {
            color: var(--accent-primary);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .info-item {
            padding: 10px;
            background-color: var(--bg-card);
            border-radius: 6px;
        }

        .info-label {
            color: var(--text-muted);
            font-size: 12px;
            margin-bottom: 5px;
        }

        .info-value {
            color: var(--text-primary);
            font-size: 14px;
            font-weight: 500;
        }

        .password-hint {
            margin-top: 8px;
            color: var(--text-muted);
            font-size: 12px;
        }

        .password-hint i {
            color: var(--accent-warning);
            margin-right: 5px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: flex-end;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
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
                <a href="admin_bulk_upload.php" class="nav-link">
                    <i class="fas fa-file-upload"></i>
                    বাল্ক আপলোড
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_manage_admins.php" class="nav-link active">
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
            <div>
                <h1 class="page-title">অ্যাডমিন সম্পাদনা</h1>
                <p class="page-subtitle">অ্যাডমিনের তথ্য আপডেট করুন</p>
            </div>
            <a href="admin_manage_admins.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                ফিরে যান
            </a>
        </div>

        <?php if ($admin_id == $_SESSION['admin_id']): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                আপনি নিজের অ্যাকাউন্ট সম্পাদনা করছেন। সতর্কতার সাথে পরিবর্তন করুন।
            </div>
        <?php endif; ?>

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

        <div class="form-card">
            <!-- Current Info Box -->
            <div class="info-box">
                <h4><i class="fas fa-info-circle"></i> বর্তমান তথ্য</h4>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">ইউজারনেম</div>
                        <div class="info-value"><?php echo htmlspecialchars($admin_data['username']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">পূর্ণ নাম</div>
                        <div class="info-value"><?php echo htmlspecialchars($admin_data['full_name']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">ইমেইল</div>
                        <div class="info-value"><?php echo htmlspecialchars($admin_data['email']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">রোল</div>
                        <div class="info-value">
                            <span class="admin-badge" style="background-color: rgba(255, 107, 74, 0.2); color: var(--accent-primary); padding: 4px 12px; border-radius: 20px; font-size: 12px;">
                                <?php echo $admin_data['role'] == 'super_admin' ? 'সুপার অ্যাডমিন' : 'অ্যাডমিন'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">স্ট্যাটাস</div>
                        <div class="info-value">
                            <span class="admin-badge" style="background-color: <?php echo $admin_data['is_active'] ? 'rgba(74, 222, 128, 0.2)' : 'rgba(239, 68, 68, 0.2)'; ?>; color: <?php echo $admin_data['is_active'] ? 'var(--accent-secondary)' : 'var(--accent-danger)'; ?>; padding: 4px 12px; border-radius: 20px; font-size: 12px;">
                                <?php echo $admin_data['is_active'] ? 'সক্রিয়' : 'নিষ্ক্রিয়'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">যোগদানের তারিখ</div>
                        <div class="info-value"><?php echo date('d M, Y', strtotime($admin_data['created_at'])); ?></div>
                    </div>
                </div>
            </div>

            <!-- Edit Form -->
            <form method="POST" action="admin_edit_admin.php?id=<?php echo $admin_id; ?>" onsubmit="return validateForm()">
                <div class="form-grid">
                    <div class="form-group">
                        <label>
                            <i class="fas fa-user"></i>
                            ইউজারনেম <span style="color: var(--accent-danger);">*</span>
                        </label>
                        <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($admin_data['username']); ?>" required minlength="3">
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-user-tag"></i>
                            পূর্ণ নাম <span style="color: var(--accent-danger);">*</span>
                        </label>
                        <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($admin_data['full_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-envelope"></i>
                            ইমেইল <span style="color: var(--accent-danger);">*</span>
                        </label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin_data['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-user-shield"></i>
                            রোল <span style="color: var(--accent-danger);">*</span>
                        </label>
                        <select name="role" class="form-control" required>
                            <option value="admin" <?php echo $admin_data['role'] == 'admin' ? 'selected' : ''; ?>>অ্যাডমিন</option>
                            <option value="super_admin" <?php echo $admin_data['role'] == 'super_admin' ? 'selected' : ''; ?>>সুপার অ্যাডমিন</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <div class="checkbox-group">
                            <input type="checkbox" name="is_active" id="is_active" <?php echo $admin_data['is_active'] ? 'checked' : ''; ?>>
                            <label for="is_active">
                                <i class="fas fa-check-circle" style="color: var(--accent-secondary);"></i>
                                অ্যাকাউন্ট সক্রিয়
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-lock"></i>
                            নতুন পাসওয়ার্ড (ঐচ্ছিক)
                        </label>
                        <input type="password" name="new_password" id="new_password" class="form-control" minlength="6">
                        <div class="password-hint">
                            <i class="fas fa-info-circle"></i>
                            নতুন পাসওয়ার্ড দিতে চাইলে কমপক্ষে ৬ অক্ষর দিন। ফাঁকা রাখলে পুরানো পাসওয়ার্ড থাকবে।
                        </div>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-lock"></i>
                            পাসওয়ার্ড নিশ্চিত করুন
                        </label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control">
                    </div>
                </div>

                <div class="action-buttons">
                    <a href="admin_manage_admins.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        বাতিল
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        আপডেট করুন
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Form validation
        function validateForm() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                alert('পাসওয়ার্ড মিলছে না!');
                return false;
            }
            
            if (newPassword.length > 0 && newPassword.length < 6) {
                alert('পাসওয়ার্ড কমপক্ষে ৬ অক্ষর হতে হবে!');
                return false;
            }
            
            return true;
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Password match indicator
        document.getElementById('confirm_password').addEventListener('keyup', function() {
            const newPass = document.getElementById('new_password').value;
            const confirmPass = this.value;
            
            if (confirmPass.length > 0) {
                if (newPass === confirmPass) {
                    this.style.borderColor = 'var(--accent-secondary)';
                } else {
                    this.style.borderColor = 'var(--accent-danger)';
                }
            } else {
                this.style.borderColor = '';
            }
        });

        // Prevent accidental navigation
        let formChanged = false;
        document.querySelectorAll('form input, form select').forEach(element => {
            element.addEventListener('change', () => {
                formChanged = true;
            });
        });

        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        document.querySelector('form').addEventListener('submit', () => {
            formChanged = false;
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>