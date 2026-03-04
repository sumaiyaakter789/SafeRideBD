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

// Handle status toggle
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $admin_id = $_GET['toggle'];
    
    // Cannot toggle own status
    if ($admin_id == $_SESSION['admin_id']) {
        $error = "আপনি নিজের স্ট্যাটাস পরিবর্তন করতে পারবেন না!";
    } else {
        $toggle_sql = "UPDATE admin_users SET is_active = NOT is_active WHERE id = ?";
        $toggle_stmt = $conn->prepare($toggle_sql);
        $toggle_stmt->bind_param("i", $admin_id);
        
        if ($toggle_stmt->execute()) {
            // Log activity
            $log_sql = "INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, 'admin_toggle', ?, ?)";
            $log_stmt = $conn->prepare($log_sql);
            $details = "অ্যাডমিনের স্ট্যাটাস পরিবর্তন করা হয়েছে (ID: {$admin_id})";
            $ip = $_SERVER['REMOTE_ADDR'];
            $log_stmt->bind_param("iss", $_SESSION['admin_id'], $details, $ip);
            $log_stmt->execute();
            $log_stmt->close();
            
            $success = "অ্যাডমিনের স্ট্যাটাস পরিবর্তন করা হয়েছে!";
        } else {
            $error = "স্ট্যাটাস পরিবর্তন করতে ব্যর্থ হয়েছে!";
        }
        $toggle_stmt->close();
    }
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $admin_id = $_GET['delete'];
    
    // Cannot delete own account
    if ($admin_id == $_SESSION['admin_id']) {
        $error = "আপনি নিজের অ্যাকাউন্ট মুছে ফেলতে পারবেন না!";
    } else {
        // Get admin details for log
        $select_sql = "SELECT username FROM admin_users WHERE id = ?";
        $select_stmt = $conn->prepare($select_sql);
        $select_stmt->bind_param("i", $admin_id);
        $select_stmt->execute();
        $result = $select_stmt->get_result();
        $admin = $result->fetch_assoc();
        
        // Delete
        $delete_sql = "DELETE FROM admin_users WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $admin_id);
        
        if ($delete_stmt->execute()) {
            // Log activity
            $log_sql = "INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, 'admin_delete', ?, ?)";
            $log_stmt = $conn->prepare($log_sql);
            $details = "অ্যাডমিন মুছে ফেলা হয়েছে: {$admin['username']}";
            $ip = $_SERVER['REMOTE_ADDR'];
            $log_stmt->bind_param("iss", $_SESSION['admin_id'], $details, $ip);
            $log_stmt->execute();
            $log_stmt->close();
            
            $success = "অ্যাডমিন মুছে ফেলা হয়েছে!";
        } else {
            $error = "অ্যাডমিন মুছে ফেলতে ব্যর্থ হয়েছে!";
        }
        $delete_stmt->close();
    }
}

// Get all admins
$admins = [];
$query = "SELECT * FROM admin_users ORDER BY role, created_at DESC";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $admins[] = $row;
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>অ্যাডমিন ব্যবস্থাপনা - SafeRideBD</title>
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

        /* Sidebar (same as before) */
        .sidebar {
            width: 280px;
            background-color: var(--bg-card);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
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

        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
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

        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .admin-card {
            background-color: var(--bg-card);
            border-radius: 12px;
            padding: 24px;
            border: 1px solid var(--border-color);
            transition: all 0.2s;
        }

        .admin-card:hover {
            border-color: var(--accent-primary);
            transform: translateY(-5px);
        }

        .admin-card.super-admin {
            border-left: 4px solid var(--accent-primary);
        }

        .admin-card.admin {
            border-left: 4px solid var(--accent-secondary);
        }

        .admin-card.inactive {
            opacity: 0.6;
        }

        .admin-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .admin-avatar-large {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--accent-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 600;
        }

        .admin-info h3 {
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .admin-info p {
            color: var(--text-muted);
            font-size: 13px;
        }

        .admin-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 5px;
        }

        .badge-super {
            background-color: rgba(255, 107, 74, 0.2);
            color: var(--accent-primary);
        }

        .badge-admin {
            background-color: rgba(74, 222, 128, 0.2);
            color: var(--accent-secondary);
        }

        .badge-inactive {
            background-color: rgba(239, 68, 68, 0.2);
            color: var(--accent-danger);
        }

        .admin-details {
            margin: 15px 0;
            padding: 15px 0;
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .detail-label {
            color: var(--text-muted);
        }

        .detail-value {
            color: var(--text-primary);
        }

        .admin-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .action-btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 6px;
            font-family: 'Bornomala', serif;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            text-decoration: none;
        }

        .edit-btn {
            background-color: var(--accent-warning);
            color: var(--bg-primary);
        }

        .edit-btn:hover {
            background-color: #f59e0b;
            transform: translateY(-2px);
        }

        .toggle-btn {
            background-color: var(--bg-hover);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }

        .toggle-btn:hover {
            border-color: var(--accent-primary);
            color: var(--text-primary);
        }

        .delete-btn {
            background-color: var(--accent-danger);
            color: white;
        }

        .delete-btn:hover {
            background-color: #dc2626;
            transform: translateY(-2px);
        }

        .action-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .admin-grid {
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
                <h1 class="page-title">অ্যাডমিন ব্যবস্থাপনা</h1>
                <p class="page-subtitle">অ্যাডমিন ব্যবহারকারীদের তথ্য দেখুন এবং পরিচালনা করুন</p>
            </div>
            <a href="admin_recruit_admin.php" class="btn btn-primary">
                <i class="fas fa-user-plus"></i>
                নতুন অ্যাডমিন যোগ
            </a>
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

        <div class="admin-grid">
            <?php foreach ($admins as $admin): 
                $isSuper = $admin['role'] === 'super_admin';
                $isActive = $admin['is_active'];
                $isCurrentUser = $admin['id'] == $_SESSION['admin_id'];
            ?>
                <div class="admin-card <?php echo $isSuper ? 'super-admin' : 'admin'; ?> <?php echo !$isActive ? 'inactive' : ''; ?>">
                    <div class="admin-header">
                        <div class="admin-avatar-large">
                            <?php echo strtoupper(substr($admin['full_name'], 0, 1)); ?>
                        </div>
                        <div class="admin-info">
                            <h3><?php echo htmlspecialchars($admin['full_name']); ?></h3>
                            <p>@<?php echo htmlspecialchars($admin['username']); ?></p>
                            <?php if ($isCurrentUser): ?>
                                <span class="admin-badge badge-super">আপনি</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="admin-details">
                        <div class="detail-row">
                            <span class="detail-label">ইমেইল:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($admin['email']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">রোল:</span>
                            <span class="detail-value">
                                <span class="admin-badge <?php echo $isSuper ? 'badge-super' : 'badge-admin'; ?>">
                                    <?php echo $isSuper ? 'সুপার অ্যাডমিন' : 'অ্যাডমিন'; ?>
                                </span>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">স্ট্যাটাস:</span>
                            <span class="detail-value">
                                <?php if ($isActive): ?>
                                    <span class="admin-badge badge-admin">সক্রিয়</span>
                                <?php else: ?>
                                    <span class="admin-badge badge-inactive">নিষ্ক্রিয়</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">যোগদান:</span>
                            <span class="detail-value"><?php echo date('d M, Y', strtotime($admin['created_at'])); ?></span>
                        </div>
                        <?php if ($admin['last_login']): ?>
                        <div class="detail-row">
                            <span class="detail-label">সর্বশেষ লগইন:</span>
                            <span class="detail-value"><?php echo date('d M, Y h:i A', strtotime($admin['last_login'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="admin-actions">
                        <a href="admin_edit_admin.php?id=<?php echo $admin['id']; ?>" class="action-btn edit-btn <?php echo $isCurrentUser ? 'disabled' : ''; ?>">
                            <i class="fas fa-edit"></i>
                            সম্পাদনা
                        </a>
                        
                        <?php if (!$isCurrentUser): ?>
                            <a href="admin_manage_admins.php?toggle=<?php echo $admin['id']; ?>" class="action-btn toggle-btn" onclick="return confirm('স্ট্যাটাস পরিবর্তন করবেন?')">
                                <i class="fas <?php echo $isActive ? 'fa-ban' : 'fa-check'; ?>"></i>
                                <?php echo $isActive ? 'নিষ্ক্রিয়' : 'সক্রিয়'; ?>
                            </a>
                            
                            <a href="admin_manage_admins.php?delete=<?php echo $admin['id']; ?>" class="action-btn delete-btn" onclick="return confirm('অ্যাডমিন মুছে ফেলবেন?')">
                                <i class="fas fa-trash"></i>
                                মুছুন
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
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