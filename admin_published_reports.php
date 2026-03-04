<?php
require_once 'admin_auth.php';
include_once 'db_config.php';

// Get all published reports
$sql = "SELECT p.*, i.title as incident_title, i.id as incident_id, a.full_name as publisher_name
        FROM published_reports p
        LEFT JOIN incident_reports i ON p.incident_report_id = i.id
        LEFT JOIN admin_users a ON p.published_by = a.id
        ORDER BY p.publish_date DESC, p.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>প্রকাশিত প্রতিবেদন - SafeRideBD অ্যাডমিন</title>
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

        /* Sidebar (same as dashboard) */
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
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

        .back-btn {
            padding: 12px 24px;
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-secondary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s;
        }

        .back-btn:hover {
            border-color: var(--accent-primary);
            color: var(--text-primary);
        }

        /* Alert Messages */
        .message-alert {
            margin: 20px 0;
            padding: 16px 20px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease;
            border-left: 4px solid;
        }

        .success-alert {
            background-color: rgba(74, 222, 128, 0.1);
            border-left-color: var(--accent-secondary);
            color: var(--accent-secondary);
        }

        .error-alert {
            background-color: rgba(239, 68, 68, 0.1);
            border-left-color: var(--accent-danger);
            color: var(--accent-danger);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Reports Grid */
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .report-card {
            background-color: var(--bg-card);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            transition: all 0.3s;
        }

        .report-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent-primary);
            box-shadow: var(--shadow);
        }

        .report-cover {
            height: 200px;
            background-color: var(--bg-secondary);
            position: relative;
            overflow: hidden;
        }

        .report-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .report-cover-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            color: white;
            font-size: 48px;
        }

        .report-content {
            padding: 20px;
        }

        .report-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .report-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 13px;
            color: var(--text-muted);
            flex-wrap: wrap;
        }

        .report-meta i {
            margin-right: 5px;
            color: var(--accent-primary);
        }

        .report-excerpt {
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .report-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
        }

        .report-stats {
            display: flex;
            gap: 15px;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--text-muted);
            font-size: 13px;
        }

        .stat-item i {
            color: var(--accent-primary);
        }

        .report-actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s;
            text-decoration: none;
        }

        .view-btn {
            background-color: rgba(59, 130, 246, 0.1);
            color: var(--accent-info);
        }

        .view-btn:hover {
            background-color: var(--accent-info);
            color: white;
        }

        .edit-btn {
            background-color: rgba(255, 107, 74, 0.1);
            color: var(--accent-primary);
        }

        .edit-btn:hover {
            background-color: var(--accent-primary);
            color: white;
        }

        .delete-btn {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--accent-danger);
        }

        .delete-btn:hover {
            background-color: var(--accent-danger);
            color: white;
        }

        .no-reports {
            text-align: center;
            padding: 60px;
            color: var(--text-muted);
            grid-column: 1 / -1;
        }

        .no-reports i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .reports-grid {
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
                <a href="admin_published_reports.php" class="nav-link active">
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
            <div>
                <h1 class="page-title">প্রকাশিত প্রতিবেদন</h1>
                <p class="page-subtitle">সকল প্রকাশিত প্রতিবেদনের তালিকা</p>
            </div>
            <a href="admin_reports.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                রিপোর্টে ফিরে যান
            </a>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="message-alert success-alert">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="message-alert error-alert">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="reports-grid">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($report = $result->fetch_assoc()): ?>
                    <div class="report-card">
                        <div class="report-cover">
                            <?php if (!empty($report['cover_image']) && file_exists($report['cover_image'])): ?>
                                <img src="<?php echo $report['cover_image']; ?>" alt="Cover">
                            <?php else: ?>
                                <div class="report-cover-placeholder">
                                    <i class="fas fa-newspaper"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="report-content">
                            <h3 class="report-title"><?php echo htmlspecialchars($report['title']); ?></h3>
                            <div class="report-meta">
                                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($report['author']); ?></span>
                                <span><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($report['publish_date'])); ?></span>
                                <span><i class="fas fa-tag"></i> <?php 
                                    $categories = [
                                        'incident' => 'ঘটনা',
                                        'safety' => 'নিরাপত্তা',
                                        'update' => 'আপডেট',
                                        'other' => 'অন্যান্য'
                                    ];
                                    echo $categories[$report['category']] ?? $report['category']; 
                                ?></span>
                            </div>
                            <div class="report-excerpt">
                                <?php echo htmlspecialchars(substr(strip_tags($report['content']), 0, 150)) . '...'; ?>
                            </div>
                            <div class="report-footer">
                                <div class="report-stats">
                                    <div class="stat-item">
                                        <i class="fas fa-eye"></i> <?php echo $report['views']; ?>
                                    </div>
                                    <?php if ($report['incident_report_id']): ?>
                                        <div class="stat-item">
                                            <i class="fas fa-link"></i> #<?php echo $report['incident_report_id']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="report-actions">
                                    <a href="view_published_report.php?id=<?php echo $report['id']; ?>" class="action-btn view-btn" title="দেখুন">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_published_report.php?id=<?php echo $report['id']; ?>" class="action-btn edit-btn" title="সম্পাদনা">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="action-btn delete-btn" onclick="confirmDelete(<?php echo $report['id']; ?>)" title="মুছে ফেলুন">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-reports">
                    <i class="fas fa-newspaper"></i>
                    <p>কোন প্রকাশিত প্রতিবেদন পাওয়া যায়নি</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function confirmDelete(id) {
            if (confirm('আপনি কি নিশ্চিতভাবে এই প্রতিবেদনটি মুছে ফেলতে চান?')) {
                window.location.href = 'delete_published_report.php?id=' + id;
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>