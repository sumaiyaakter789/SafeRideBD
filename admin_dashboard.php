<?php
require_once 'admin_auth.php';
include_once 'db_config.php';

// Get statistics
$stats = [];

// Total fare entries
$result = $conn->query("SELECT COUNT(*) as total FROM fare_chart");
$stats['total_fares'] = $result->fetch_assoc()['total'];

// Total admins
$result = $conn->query("SELECT COUNT(*) as total FROM admin_users");
$stats['total_admins'] = $result->fetch_assoc()['total'];

// Recent updates
$result = $conn->query("SELECT COUNT(*) as total FROM fare_update_history WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats['recent_updates'] = $result->fetch_assoc()['total'];

// Get recent activities
$activities = [];
$activity_query = "SELECT l.*, a.username, a.full_name 
                   FROM admin_logs l 
                   JOIN admin_users a ON l.admin_id = a.id 
                   ORDER BY l.created_at DESC 
                   LIMIT 10";
$result = $conn->query($activity_query);
while ($row = $result->fetch_assoc()) {
    $activities[] = $row;
}
?>

<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ড্যাশবোর্ড - SafeRideBD অ্যাডমিন</title>
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--bg-card);
            border-radius: 12px;
            padding: 24px;
            border: 1px solid var(--border-color);
            transition: all 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent-primary);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            background-color: rgba(255, 107, 74, 0.1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            color: var(--accent-primary);
            font-size: 20px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 14px;
        }

        /* Recent Activity */
        .recent-activity {
            background-color: var(--bg-card);
            border-radius: 12px;
            padding: 24px;
            border: 1px solid var(--border-color);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--accent-primary);
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background-color: var(--bg-secondary);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: rgba(255, 107, 74, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-primary);
        }

        .activity-content {
            flex: 1;
        }

        .activity-text {
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .activity-time {
            color: var(--text-muted);
            font-size: 12px;
        }

        .activity-admin {
            color: var(--accent-secondary);
            font-weight: 500;
        }

        .no-activity {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                display: none;
            }

            .main-content {
                margin-left: 0;
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
                <a href="admin_dashboard.php" class="nav-link active">
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
            <h1 class="page-title">ড্যাশবোর্ড</h1>
            <p class="page-subtitle">স্বাগতম, <?php echo htmlspecialchars(getCurrentAdminName()); ?>! আজকের সারসংক্ষেপ দেখুন।</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-route"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_fares']; ?></div>
                <div class="stat-label">মোট ভাড়া এন্ট্রি</div>
            </div>

            <?php if (isSuperAdmin()): ?>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['total_admins']; ?></div>
                    <div class="stat-label">মোট অ্যাডমিন</div>
                </div>
            <?php endif; ?>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?php echo $stats['recent_updates']; ?></div>
                <div class="stat-label">গত ৭ দিনে আপডেট</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="stat-value"><?php echo date('d M, Y'); ?></div>
                <div class="stat-label">আজকের তারিখ</div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="recent-activity">
            <div class="section-header">
                <div class="section-title">
                    <i class="fas fa-history"></i>
                    সাম্প্রতিক কার্যকলাপ
                </div>
            </div>

            <?php if (empty($activities)): ?>
                <div class="no-activity">
                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                    <p>কোন কার্যকলাপ পাওয়া যায়নি</p>
                </div>
            <?php else: ?>
                <div class="activity-list">
                    <?php foreach ($activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas <?php
                                                echo $activity['action'] == 'login' ? 'fa-sign-in-alt' : ($activity['action'] == 'insert' ? 'fa-plus' : ($activity['action'] == 'update' ? 'fa-edit' : ($activity['action'] == 'delete' ? 'fa-trash' : 'fa-circle')));
                                                ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-text">
                                    <span class="activity-admin"><?php echo htmlspecialchars($activity['full_name']); ?></span>
                                    <?php echo htmlspecialchars($activity['details']); ?>
                                </div>
                                <div class="activity-time">
                                    <i class="far fa-clock"></i>
                                    <?php echo date('d M, Y h:i A', strtotime($activity['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
<?php $conn->close(); ?>