<?php
require_once 'admin_auth.php';
include_once 'db_config.php';

// Pagination settings
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Filter parameters
$action_filter = isset($_GET['action']) ? $_GET['action'] : '';
$admin_filter = isset($_GET['admin_id']) ? intval($_GET['admin_id']) : 0;
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Build WHERE clause for filters
$where_conditions = [];
$params = [];
$types = "";

if (!empty($action_filter)) {
    $where_conditions[] = "l.action = ?";
    $params[] = $action_filter;
    $types .= "s";
}

if ($admin_filter > 0) {
    $where_conditions[] = "l.admin_id = ?";
    $params[] = $admin_filter;
    $types .= "i";
}

if (!empty($date_filter)) {
    $where_conditions[] = "DATE(l.created_at) = ?";
    $params[] = $date_filter;
    $types .= "s";
}

$where_sql = "";
if (!empty($where_conditions)) {
    $where_sql = "WHERE " . implode(" AND ", $where_conditions);
}

// Get total count with filters
$count_query = "SELECT COUNT(*) as total FROM admin_logs l $where_sql";
$count_stmt = $conn->prepare($count_query);

if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);
$count_stmt->close();

// Get logs with admin info and filters
$logs = [];
$query = "SELECT l.*, a.username, a.full_name, a.role 
          FROM admin_logs l 
          JOIN admin_users a ON l.admin_id = a.id 
          $where_sql 
          ORDER BY l.created_at DESC 
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);

// Add pagination parameters
$all_params = array_merge($params, [$per_page, $offset]);
$all_types = $types . "ii";

if (!empty($all_params)) {
    $stmt->bind_param($all_types, ...$all_params);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}
$stmt->close();

// Get action statistics for last 30 days
$stats_query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN action = 'login' THEN 1 ELSE 0 END) as logins,
                    SUM(CASE WHEN action = 'logout' THEN 1 ELSE 0 END) as logouts,
                    SUM(CASE WHEN action = 'insert' THEN 1 ELSE 0 END) as inserts,
                    SUM(CASE WHEN action = 'update' THEN 1 ELSE 0 END) as updates,
                    SUM(CASE WHEN action = 'delete' THEN 1 ELSE 0 END) as deletes,
                    SUM(CASE WHEN action = 'bulk_upload' THEN 1 ELSE 0 END) as bulk_uploads,
                    SUM(CASE WHEN action = 'admin_create' THEN 1 ELSE 0 END) as admin_creates,
                    SUM(CASE WHEN action = 'admin_toggle' THEN 1 ELSE 0 END) as admin_toggles,
                    SUM(CASE WHEN action = 'admin_delete' THEN 1 ELSE 0 END) as admin_deletes
                FROM admin_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get all admins for filter dropdown
$admins = [];
$admin_query = "SELECT id, username, full_name FROM admin_users ORDER BY full_name";
$admin_result = $conn->query($admin_query);
while ($admin = $admin_result->fetch_assoc()) {
    $admins[] = $admin;
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>কার্যকলাপ লগ - SafeRideBD অ্যাডমিন</title>
    <link rel="icon" type="image/png" href="saferidebd_removebg_main.png">
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
            --accent-info: #3b82f6;
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
            background-color: <?php echo isSuperAdmin() ? 'rgba(255, 107, 74, 0.2)' : 'rgba(74, 222, 128, 0.2)'; ?>;
            color: <?php echo isSuperAdmin() ? 'var(--accent-primary)' : 'var(--accent-secondary)'; ?>;
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
            margin-right: 120px;
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--border-color);
            transition: all 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent-primary);
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .stat-icon.total {
            background-color: rgba(255, 107, 74, 0.1);
            color: var(--accent-primary);
        }

        .stat-icon.login {
            background-color: rgba(74, 222, 128, 0.1);
            color: var(--accent-secondary);
        }

        .stat-icon.insert {
            background-color: rgba(59, 130, 246, 0.1);
            color: var(--accent-info);
        }

        .stat-icon.update {
            background-color: rgba(251, 191, 36, 0.1);
            color: var(--accent-warning);
        }

        .stat-icon.delete {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--accent-danger);
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 13px;
        }

        /* Filter Bar */
        .filter-bar {
            background-color: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-secondary);
            font-size: 13px;
        }

        .filter-group label i {
            color: var(--accent-primary);
            margin-right: 5px;
        }

        .filter-select, .filter-input {
            width: 100%;
            padding: 12px 16px;
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-family: 'Bornomala', serif;
            font-size: 14px;
        }

        .filter-select:focus, .filter-input:focus {
            outline: none;
            border-color: var(--accent-primary);
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: center;
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
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background-color: var(--accent-primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: #ff5a3a;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: var(--bg-hover);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background-color: var(--border-color);
            color: var(--text-primary);
        }

        .btn-danger {
            background-color: var(--accent-danger);
            color: white;
        }

        .btn-danger:hover {
            background-color: #dc2626;
            transform: translateY(-2px);
        }

        /* Logs Card */
        .logs-card {
            background-color: var(--bg-card);
            border-radius: 12px;
            padding: 24px;
            border: 1px solid var(--border-color);
            overflow-x: auto;
        }

        .logs-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }

        .logs-table th {
            text-align: left;
            padding: 15px 10px;
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 14px;
            border-bottom: 2px solid var(--border-color);
            white-space: nowrap;
        }

        .logs-table td {
            padding: 15px 10px;
            color: var(--text-primary);
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
        }

        .logs-table tr:hover td {
            background-color: var(--bg-hover);
        }

        .action-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-login {
            background-color: rgba(74, 222, 128, 0.15);
            color: var(--accent-secondary);
            border: 1px solid rgba(74, 222, 128, 0.3);
        }

        .badge-logout {
            background-color: rgba(239, 68, 68, 0.15);
            color: var(--accent-danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .badge-insert {
            background-color: rgba(59, 130, 246, 0.15);
            color: var(--accent-info);
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .badge-update {
            background-color: rgba(251, 191, 36, 0.15);
            color: var(--accent-warning);
            border: 1px solid rgba(251, 191, 36, 0.3);
        }

        .badge-delete {
            background-color: rgba(239, 68, 68, 0.15);
            color: var(--accent-danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .badge-bulk_upload {
            background-color: rgba(168, 85, 247, 0.15);
            color: #a855f7;
            border: 1px solid rgba(168, 85, 247, 0.3);
        }

        .badge-admin_create {
            background-color: rgba(236, 72, 153, 0.15);
            color: #ec4899;
            border: 1px solid rgba(236, 72, 153, 0.3);
        }

        .badge-admin_toggle {
            background-color: rgba(249, 115, 22, 0.15);
            color: #f97316;
            border: 1px solid rgba(249, 115, 22, 0.3);
        }

        .badge-admin_delete {
            background-color: rgba(220, 38, 38, 0.15);
            color: #dc2626;
            border: 1px solid rgba(220, 38, 38, 0.3);
        }

        .badge-other {
            background-color: rgba(107, 114, 128, 0.15);
            color: #6b7280;
            border: 1px solid rgba(107, 114, 128, 0.3);
        }

        .admin-cell {
            display: flex;
            flex-direction: column;
        }

        .admin-name {
            color: var(--text-primary);
            font-weight: 500;
            font-size: 14px;
        }

        .admin-username {
            color: var(--text-muted);
            font-size: 11px;
        }

        .admin-role-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            margin-left: 5px;
        }

        .role-super {
            background-color: rgba(255, 107, 74, 0.2);
            color: var(--accent-primary);
        }

        .role-admin {
            background-color: rgba(74, 222, 128, 0.2);
            color: var(--accent-secondary);
        }

        .ip-address {
            font-family: monospace;
            color: var(--text-muted);
            font-size: 12px;
        }

        .details-cell {
            max-width: 350px;
            white-space: normal;
            word-wrap: break-word;
            color: var(--text-secondary);
            font-size: 13px;
            line-height: 1.5;
        }

        .time-cell {
            color: var(--text-muted);
            font-size: 13px;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .page-link {
            padding: 10px 16px;
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s;
            font-size: 14px;
            min-width: 40px;
            text-align: center;
        }

        .page-link:hover {
            border-color: var(--accent-primary);
            color: var(--text-primary);
            background-color: var(--bg-hover);
        }

        .page-link.active {
            background-color: var(--accent-primary);
            border-color: var(--accent-primary);
            color: white;
        }

        .page-link.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        .page-info {
            text-align: center;
            margin-top: 15px;
            color: var(--text-muted);
            font-size: 13px;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.3;
        }

        .no-data h3 {
            color: var(--text-primary);
            margin-bottom: 10px;
        }

        .export-btn {
            background-color: var(--accent-secondary);
            color: var(--bg-primary);
        }

        .export-btn:hover {
            background-color: #3bcc6c;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                min-width: 100%;
            }
            
            .filter-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .page-link {
                padding: 8px 12px;
                font-size: 12px;
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
            <div class="admin-role">
                <?php echo isSuperAdmin() ? 'সুপার অ্যাডমিন' : 'অ্যাডমিন'; ?>
            </div>
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
            <?php if (isSuperAdmin()): ?>
            <li class="nav-item">
                <a href="admin_bulk_upload.php" class="nav-link">
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
            <?php endif; ?>
            <li class="nav-item">
                <a href="admin_logs.php" class="nav-link active">
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
            <h1 class="page-title">কার্যকলাপ লগ</h1>
            <p class="page-subtitle">সকল অ্যাডমিনের কার্যকলাপের ইতিহাস</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-history"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
                <div class="stat-label">গত ৩০ দিনে মোট</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon login">
                    <i class="fas fa-sign-in-alt"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['logins']); ?></div>
                <div class="stat-label">লগইন</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon login">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['logouts']); ?></div>
                <div class="stat-label">লগআউট</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon insert">
                    <i class="fas fa-plus"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['inserts']); ?></div>
                <div class="stat-label">নতুন যোগ</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon update">
                    <i class="fas fa-edit"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['updates']); ?></div>
                <div class="stat-label">আপডেট</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon delete">
                    <i class="fas fa-trash"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['deletes']); ?></div>
                <div class="stat-label">মুছে ফেলা</div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <form method="GET" action="admin_logs.php" style="display: contents;" id="filterForm">
                <div class="filter-group">
                    <label>
                        <i class="fas fa-filter"></i>
                        কার্যকলাপের ধরণ
                    </label>
                    <select name="action" class="filter-select" onchange="this.form.submit()">
                        <option value="">সকল কার্যকলাপ</option>
                        <option value="login" <?php echo $action_filter == 'login' ? 'selected' : ''; ?>>লগইন</option>
                        <option value="logout" <?php echo $action_filter == 'logout' ? 'selected' : ''; ?>>লগআউট</option>
                        <option value="insert" <?php echo $action_filter == 'insert' ? 'selected' : ''; ?>>নতুন যোগ</option>
                        <option value="update" <?php echo $action_filter == 'update' ? 'selected' : ''; ?>>আপডেট</option>
                        <option value="delete" <?php echo $action_filter == 'delete' ? 'selected' : ''; ?>>মুছে ফেলা</option>
                        <option value="bulk_upload" <?php echo $action_filter == 'bulk_upload' ? 'selected' : ''; ?>>বাল্ক আপলোড</option>
                        <option value="admin_create" <?php echo $action_filter == 'admin_create' ? 'selected' : ''; ?>>অ্যাডমিন তৈরি</option>
                        <option value="admin_toggle" <?php echo $action_filter == 'admin_toggle' ? 'selected' : ''; ?>>অ্যাডমিন স্ট্যাটাস</option>
                        <option value="admin_delete" <?php echo $action_filter == 'admin_delete' ? 'selected' : ''; ?>>অ্যাডমিন মুছে ফেলা</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>
                        <i class="fas fa-user"></i>
                        অ্যাডমিন
                    </label>
                    <select name="admin_id" class="filter-select" onchange="this.form.submit()">
                        <option value="">সকল অ্যাডমিন</option>
                        <?php foreach ($admins as $admin): ?>
                            <option value="<?php echo $admin['id']; ?>" <?php echo $admin_filter == $admin['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($admin['full_name']); ?> (@<?php echo htmlspecialchars($admin['username']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>
                        <i class="fas fa-calendar"></i>
                        তারিখ
                    </label>
                    <input type="date" name="date" class="filter-input" value="<?php echo $date_filter; ?>" onchange="this.form.submit()">
                </div>

                <div class="filter-actions">
                    <?php if (!empty($action_filter) || $admin_filter > 0 || !empty($date_filter)): ?>
                        <a href="admin_logs.php" class="btn btn-danger">
                            <i class="fas fa-times"></i>
                            ফিল্টার মুছুন
                        </a>
                    <?php endif; ?>
                    
                    <button type="button" class="btn btn-secondary" onclick="exportLogs()">
                        <i class="fas fa-download"></i>
                        এক্সপোর্ট
                    </button>
                </div>
            </form>
        </div>

        <!-- Logs Table -->
        <div class="logs-card">
            <?php if (empty($logs)): ?>
                <div class="no-data">
                    <i class="fas fa-history"></i>
                    <h3>কোন কার্যকলাপ পাওয়া যায়নি</h3>
                    <p>নির্বাচিত ফিল্টারে কোনো কার্যকলাপ নেই বা এখনও কোনো অ্যাডমিন কার্যকলাপ রেকর্ড হয়নি</p>
                </div>
            <?php else: ?>
                <table class="logs-table" id="logsTable">
                    <thead>
                        <tr>
                            <th>সময়</th>
                            <th>অ্যাডমিন</th>
                            <th>কার্যকলাপ</th>
                            <th>বিবরণ</th>
                            <th>আইপি ঠিকানা</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): 
                            $action_class = 'badge-other';
                            $action_text = $log['action'];
                            
                            // Set action class and text
                            switch($log['action']) {
                                case 'login':
                                    $action_class = 'badge-login';
                                    $action_text = 'লগইন';
                                    break;
                                case 'logout':
                                    $action_class = 'badge-logout';
                                    $action_text = 'লগআউট';
                                    break;
                                case 'insert':
                                    $action_class = 'badge-insert';
                                    $action_text = 'নতুন যোগ';
                                    break;
                                case 'update':
                                    $action_class = 'badge-update';
                                    $action_text = 'আপডেট';
                                    break;
                                case 'delete':
                                    $action_class = 'badge-delete';
                                    $action_text = 'মুছে ফেলা';
                                    break;
                                case 'bulk_upload':
                                    $action_class = 'badge-bulk_upload';
                                    $action_text = 'বাল্ক আপলোড';
                                    break;
                                case 'admin_create':
                                    $action_class = 'badge-admin_create';
                                    $action_text = 'অ্যাডমিন তৈরি';
                                    break;
                                case 'admin_toggle':
                                    $action_class = 'badge-admin_toggle';
                                    $action_text = 'স্ট্যাটাস পরিবর্তন';
                                    break;
                                case 'admin_delete':
                                    $action_class = 'badge-admin_delete';
                                    $action_text = 'অ্যাডমিন মুছে ফেলা';
                                    break;
                            }
                        ?>
                            <tr>
                                <td class="time-cell">
                                    <i class="far fa-clock" style="color: var(--text-muted); margin-right: 5px;"></i>
                                    <?php echo date('d M, Y', strtotime($log['created_at'])); ?><br>
                                    <span style="color: var(--text-muted); font-size: 11px;">
                                        <?php echo date('h:i:s A', strtotime($log['created_at'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="admin-cell">
                                        <span class="admin-name">
                                            <i class="fas fa-user-circle" style="color: <?php echo $log['role'] == 'super_admin' ? 'var(--accent-primary)' : 'var(--accent-secondary)'; ?>; margin-right: 5px;"></i>
                                            <?php echo htmlspecialchars($log['full_name']); ?>
                                            <span class="admin-role-badge <?php echo $log['role'] == 'super_admin' ? 'role-super' : 'role-admin'; ?>">
                                                <?php echo $log['role'] == 'super_admin' ? 'সুপার' : 'অ্যাডমিন'; ?>
                                            </span>
                                        </span>
                                        <span class="admin-username">@<?php echo htmlspecialchars($log['username']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="action-badge <?php echo $action_class; ?>">
                                        <?php echo $action_text; ?>
                                    </span>
                                </td>
                                <td class="details-cell">
                                    <?php echo htmlspecialchars($log['details']); ?>
                                </td>
                                <td>
                                    <span class="ip-address">
                                        <i class="fas fa-network-wired" style="color: var(--text-muted); margin-right: 5px;"></i>
                                        <?php echo htmlspecialchars($log['ip_address']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php
                        // Build query string for pagination links
                        $query_params = [];
                        if (!empty($action_filter)) $query_params['action'] = $action_filter;
                        if ($admin_filter > 0) $query_params['admin_id'] = $admin_filter;
                        if (!empty($date_filter)) $query_params['date'] = $date_filter;
                        
                        $query_string = http_build_query($query_params);
                        $base_url = "admin_logs.php" . (!empty($query_string) ? "?" . $query_string . "&" : "?");
                        ?>
                        
                        <a href="<?php echo $base_url; ?>page=1" class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        <a href="<?php echo $base_url; ?>page=<?php echo $page - 1; ?>" class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <i class="fas fa-angle-left"></i>
                        </a>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <a href="<?php echo $base_url; ?>page=<?php echo $i; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <a href="<?php echo $base_url; ?>page=<?php echo $page + 1; ?>" class="page-link <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <i class="fas fa-angle-right"></i>
                        </a>
                        <a href="<?php echo $base_url; ?>page=<?php echo $total_pages; ?>" class="page-link <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    </div>
                    
                                        <div class="page-info">
                        পৃষ্ঠা <?php echo $page; ?> of <?php echo $total_pages; ?> (মোট <?php echo number_format($total_rows); ?>টি এন্ট্রি)
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Export Logs JavaScript -->
    <script>
    function exportLogs() {
        // Get current filter values
        const action = document.querySelector('select[name="action"]').value;
        const adminId = document.querySelector('select[name="admin_id"]').value;
        const date = document.querySelector('input[name="date"]').value;
        
        // Build export URL with filters
        let exportUrl = 'admin_export_logs.php?';
        const params = [];
        if (action) params.push('action=' + encodeURIComponent(action));
        if (adminId) params.push('admin_id=' + encodeURIComponent(adminId));
        if (date) params.push('date=' + encodeURIComponent(date));
        
        exportUrl += params.join('&');
        
        // Open export in new tab
        window.open(exportUrl, '_blank');
    }
    
    // Optional: Add keyboard shortcut for export (Ctrl+E)
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'e') {
            e.preventDefault();
            exportLogs();
        }
    });
    
    setTimeout(function() {
        location.reload();
    }, 300000);
    
    </script>
    
    <script>
    // Add tooltips for action badges
    document.addEventListener('DOMContentLoaded', function() {
        const actionBadges = document.querySelectorAll('.action-badge');
        actionBadges.forEach(badge => {
            const action = badge.textContent.trim();
            let tooltip = '';
            
            switch(action) {
                case 'লগইন':
                    tooltip = 'অ্যাডমিন লগইন করেছেন';
                    break;
                case 'লগআউট':
                    tooltip = 'অ্যাডমিন লগআউট করেছেন';
                    break;
                case 'নতুন যোগ':
                    tooltip = 'নতুন ভাড়া যোগ করা হয়েছে';
                    break;
                case 'আপডেট':
                    tooltip = 'ভাড়া তথ্য আপডেট করা হয়েছে';
                    break;
                case 'মুছে ফেলা':
                    tooltip = 'ভাড়া তথ্য মুছে ফেলা হয়েছে';
                    break;
                case 'বাল্ক আপলোড':
                    tooltip = 'এক্সেল ফাইল থেকে ডেটা আপলোড করা হয়েছে';
                    break;
                case 'অ্যাডমিন তৈরি':
                    tooltip = 'নতুন অ্যাডমিন তৈরি করা হয়েছে';
                    break;
                case 'স্ট্যাটাস পরিবর্তন':
                    tooltip = 'অ্যাডমিনের স্ট্যাটাস পরিবর্তন করা হয়েছে';
                    break;
                case 'অ্যাডমিন মুছে ফেলা':
                    tooltip = 'অ্যাডমিন মুছে ফেলা হয়েছে';
                    break;
            }
            
            badge.setAttribute('title', tooltip);
        });
        
        // Add hover effect for IP addresses
        const ipAddresses = document.querySelectorAll('.ip-address');
        ipAddresses.forEach(ip => {
            ip.addEventListener('mouseenter', function() {
                this.style.color = 'var(--accent-primary)';
                this.style.cursor = 'pointer';
            });
            
            ip.addEventListener('mouseleave', function() {
                this.style.color = 'var(--text-muted)';
            });
            
            ip.addEventListener('click', function() {
                // Copy IP to clipboard
                const ipText = this.textContent.replace(/[^0-9.]/g, '').trim();
                navigator.clipboard.writeText(ipText).then(() => {
                    // Show temporary tooltip
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-check" style="color: var(--accent-secondary);"></i> কপি হয়েছে!';
                    setTimeout(() => {
                        this.innerHTML = originalText;
                    }, 2000);
                });
            });
        });
    });
    </script>
    
    <style>
        /* Additional styles for better UX */
        .ip-address {
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .action-badge {
            cursor: help;
        }
        
        .logs-table td {
            vertical-align: middle;
        }
        
        .logs-table tr {
            transition: background-color 0.2s;
        }
        
        /* Add zebra striping for better readability */
        .logs-table tbody tr:nth-child(even) {
            background-color: rgba(255, 255, 255, 0.02);
        }
        
        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .logs-table {
                font-size: 13px;
            }
            
            .details-cell {
                max-width: 250px;
            }
        }
        
        @media (max-width: 992px) {
            .logs-card {
                padding: 15px;
            }
            
            .logs-table th,
            .logs-table td {
                padding: 12px 8px;
            }
        }
        
        /* Loading state for export */
        .btn-success.exporting {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }
        
        .btn-success.exporting::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            border: 2px solid var(--bg-primary);
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: translateY(-50%) rotate(360deg); }
        }
        
        /* Highlight new logs */
        @keyframes highlight {
            0% {
                background-color: rgba(255, 107, 74, 0.3);
            }
            100% {
                background-color: transparent;
            }
        }
        
        .logs-table tr.new-log {
            animation: highlight 2s ease-out;
        }
        
        /* Print styles */
        @media print {
            .sidebar,
            .filter-bar,
            .stats-grid,
            .pagination,
            .page-info,
            .btn,
            .logout-btn,
            .admin-info {
                display: none !important;
            }
            
            .main-content {
                margin: 0;
                padding: 20px;
            }
            
            .logs-card {
                border: none;
                padding: 0;
            }
            
            .logs-table {
                border-collapse: collapse;
                width: 100%;
            }
            
            .logs-table th {
                background-color: #f0f0f0;
                color: #000;
            }
            
            .logs-table td {
                color: #000;
            }
            
            .action-badge {
                border: 1px solid #ccc;
            }
        }
    </style>
</body>
</html>