<?php
session_start();
include_once 'navbar.php';
include_once 'db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=my_reports");
    exit();
}

$user_id = $_SESSION['user_id'];

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query
$count_sql = "SELECT COUNT(*) as total FROM incident_reports WHERE user_id = ?";
$sql = "SELECT * FROM incident_reports WHERE user_id = ?";

if ($status_filter != 'all') {
    $count_sql .= " AND status = '$status_filter'";
    $sql .= " AND status = '$status_filter'";
}

$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_reports = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_reports / $limit);

$sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $limit, $offset);
$stmt->execute();
$reports = $stmt->get_result();

// Get statistics
$stats = [
    'total' => $total_reports,
    'pending' => 0,
    'reviewing' => 0,
    'published' => 0,
    'rejected' => 0
];

$stats_sql = "SELECT status, COUNT(*) as count FROM incident_reports WHERE user_id = ? GROUP BY status";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
while ($stat = $stats_result->fetch_assoc()) {
    $stats[$stat['status']] = $stat['count'];
}

// Get published reports mapping
$published_map = [];
$pub_sql = "SELECT incident_report_id, id as published_id FROM published_reports WHERE incident_report_id IN (SELECT id FROM incident_reports WHERE user_id = ?)";
$pub_stmt = $conn->prepare($pub_sql);
$pub_stmt->bind_param("i", $user_id);
$pub_stmt->execute();
$pub_result = $pub_stmt->get_result();
while ($pub = $pub_result->fetch_assoc()) {
    $published_map[$pub['incident_report_id']] = $pub['published_id'];
}

// Success/Error messages
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>আমার রিপোর্ট - SafeRideBD</title>
    <style>
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
            --border-light: #3a4754;
            --shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
            --glow: 0 0 0 2px rgba(255, 107, 74, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Bornomala', serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 24px;
            flex: 1;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 40px;
        }

        .page-title {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 16px;
            background: linear-gradient(135deg, var(--accent-danger), var(--accent-warning));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 18px;
            line-height: 1.8;
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

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 1px solid var(--border-color);
            transition: all 0.2s;
            cursor: pointer;
            text-decoration: none;
            display: block;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            border-color: var(--accent-primary);
        }

        .stat-card.active {
            border-color: var(--accent-primary);
            background-color: rgba(255, 107, 74, 0.1);
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: var(--text-muted);
        }

        .stat-card.pending .stat-number {
            color: var(--accent-warning);
        }

        .stat-card.reviewing .stat-number {
            color: var(--accent-info);
        }

        .stat-card.published .stat-number {
            color: var(--accent-secondary);
        }

        .stat-card.rejected .stat-number {
            color: var(--accent-danger);
        }

        .stat-card.total .stat-number {
            color: var(--text-primary);
        }

        /* Filters */
        .filters-section {
            background-color: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }

        .filter-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .filter-btn {
            padding: 10px 20px;
            border-radius: 30px;
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
        }

        .filter-btn:hover {
            border-color: var(--accent-primary);
            color: var(--text-primary);
        }

        .filter-btn.active {
            background-color: var(--accent-primary);
            color: white;
            border-color: var(--accent-primary);
        }

        /* Reports List */
        .reports-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 30px;
        }

        .report-card {
            background-color: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            border: 1px solid var(--border-color);
            transition: all 0.2s;
        }

        .report-card:hover {
            border-color: var(--accent-primary);
            box-shadow: var(--shadow);
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .report-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .report-id {
            font-size: 13px;
            color: var(--text-muted);
        }

        .report-badges {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .status-badge {
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-pending {
            background-color: rgba(251, 191, 36, 0.1);
            color: var(--accent-warning);
            border: 1px solid rgba(251, 191, 36, 0.2);
        }

        .status-reviewing {
            background-color: rgba(59, 130, 246, 0.1);
            color: var(--accent-info);
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .status-published {
            background-color: rgba(74, 222, 128, 0.1);
            color: var(--accent-secondary);
            border: 1px solid rgba(74, 222, 128, 0.2);
        }

        .status-rejected {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--accent-danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .severity-badge {
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .severity-low {
            background-color: rgba(74, 222, 128, 0.1);
            color: var(--accent-secondary);
        }

        .severity-medium {
            background-color: rgba(251, 191, 36, 0.1);
            color: var(--accent-warning);
        }

        .severity-high {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--accent-danger);
        }

        .report-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            flex-wrap: wrap;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
            font-size: 14px;
        }

        .meta-item i {
            color: var(--accent-primary);
            width: 16px;
        }

        .report-description {
            color: var(--text-secondary);
            margin-bottom: 15px;
            line-height: 1.8;
        }

        .report-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .report-date {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
            font-size: 13px;
        }

        .report-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .view-btn {
            background-color: rgba(59, 130, 246, 0.1);
            color: var(--accent-info);
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .view-btn:hover {
            background-color: var(--accent-info);
            color: white;
        }

        .published-btn {
            background-color: rgba(74, 222, 128, 0.1);
            color: var(--accent-secondary);
            border: 1px solid rgba(74, 222, 128, 0.2);
        }

        .published-btn:hover {
            background-color: var(--accent-secondary);
            color: white;
        }

        .delete-btn {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--accent-danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
            cursor: pointer;
            border: none;
            font-family: 'Bornomala', serif;
        }

        .delete-btn:hover {
            background-color: var(--accent-danger);
            color: white;
        }

        .new-report-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 28px;
            background: linear-gradient(135deg, var(--accent-danger), #dc2626);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s;
            margin-bottom: 20px;
        }

        .new-report-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background-color: var(--bg-card);
            border-radius: 12px;
            border: 2px dashed var(--border-color);
        }

        .empty-state i {
            font-size: 64px;
            color: var(--text-muted);
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-state h3 {
            color: var(--text-primary);
            font-size: 24px;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: var(--text-muted);
            margin-bottom: 30px;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 30px;
        }

        .page-link {
            padding: 10px 16px;
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s;
            min-width: 45px;
            text-align: center;
        }

        .page-link:hover {
            border-color: var(--accent-primary);
            color: var(--text-primary);
        }

        .page-link.active {
            background-color: var(--accent-primary);
            color: white;
            border-color: var(--accent-primary);
        }

        .page-link.disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        /* Delete Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            padding: 20px;
            backdrop-filter: blur(10px);
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: linear-gradient(135deg, var(--bg-card), var(--bg-secondary));
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            border: 2px solid var(--accent-danger);
            text-align: center;
        }

        .modal-icon {
            width: 80px;
            height: 80px;
            background-color: rgba(239, 68, 68, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            color: var(--accent-danger);
            border: 3px solid rgba(239, 68, 68, 0.2);
        }

        .modal-title {
            color: var(--text-primary);
            font-size: 24px;
            margin-bottom: 10px;
        }

        .modal-text {
            color: var(--text-muted);
            margin-bottom: 30px;
        }

        .modal-actions {
            display: flex;
            gap: 15px;
        }

        .modal-btn {
            flex: 1;
            padding: 14px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-family: 'Bornomala', serif;
            transition: all 0.2s;
        }

        .delete-confirm {
            background-color: var(--accent-danger);
            color: white;
        }

        .delete-confirm:hover {
            background-color: #dc2626;
            transform: translateY(-2px);
        }

        .delete-cancel {
            background-color: var(--bg-secondary);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }

        .delete-cancel:hover {
            background-color: var(--bg-hover);
            color: var(--text-primary);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 24px 16px;
            }

            .page-title {
                font-size: 32px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .report-header {
                flex-direction: column;
            }

            .report-footer {
                flex-direction: column;
                align-items: flex-start;
            }

            .report-actions {
                width: 100%;
            }

            .action-btn {
                flex: 1;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .modal-actions {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Alert Messages -->
        <?php if ($success_message): ?>
            <div class="message-alert success-alert">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="message-alert error-alert">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">আমার রিপোর্ট</h1>
            <p class="page-subtitle">আপনার জমা দেওয়া সকল রিপোর্টের তালিকা এবং বর্তমান অবস্থা</p>
        </div>

        <!-- New Report Button -->
        <a href="report_incident.php" class="new-report-btn">
            <i class="fas fa-plus-circle"></i>
            নতুন রিপোর্ট জমা দিন
        </a>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <a href="my_reports.php" class="stat-card total <?php echo $status_filter == 'all' ? 'active' : ''; ?>">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">মোট রিপোর্ট</div>
            </a>
            <a href="my_reports.php?status=pending" class="stat-card pending <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">
                <div class="stat-number"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">অপেক্ষমান</div>
            </a>
            <a href="my_reports.php?status=reviewing" class="stat-card reviewing <?php echo $status_filter == 'reviewing' ? 'active' : ''; ?>">
                <div class="stat-number"><?php echo $stats['reviewing']; ?></div>
                <div class="stat-label">পর্যালোচনাধীন</div>
            </a>
            <a href="my_reports.php?status=published" class="stat-card published <?php echo $status_filter == 'published' ? 'active' : ''; ?>">
                <div class="stat-number"><?php echo $stats['published']; ?></div>
                <div class="stat-label">প্রকাশিত</div>
            </a>
            <a href="my_reports.php?status=rejected" class="stat-card rejected <?php echo $status_filter == 'rejected' ? 'active' : ''; ?>">
                <div class="stat-number"><?php echo $stats['rejected']; ?></div>
                <div class="stat-label">বাতিল</div>
            </a>
        </div>

        <!-- Filter Buttons -->
        <div class="filters-section">
            <div class="filter-buttons">
                <a href="my_reports.php" class="filter-btn <?php echo $status_filter == 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-layer-group"></i> সবগুলো
                </a>
                <a href="my_reports.php?status=pending" class="filter-btn <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i> অপেক্ষমান
                </a>
                <a href="my_reports.php?status=reviewing" class="filter-btn <?php echo $status_filter == 'reviewing' ? 'active' : ''; ?>">
                    <i class="fas fa-spinner"></i> পর্যালোচনাধীন
                </a>
                <a href="my_reports.php?status=published" class="filter-btn <?php echo $status_filter == 'published' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i> প্রকাশিত
                </a>
                <a href="my_reports.php?status=rejected" class="filter-btn <?php echo $status_filter == 'rejected' ? 'active' : ''; ?>">
                    <i class="fas fa-times-circle"></i> বাতিল
                </a>
            </div>
        </div>

        <!-- Reports List -->
        <?php if ($reports->num_rows > 0): ?>
            <div class="reports-list">
                <?php while ($report = $reports->fetch_assoc()): ?>
                    <div class="report-card" id="report-<?php echo $report['id']; ?>">
                        <div class="report-header">
                            <div>
                                <h3 class="report-title"><?php echo htmlspecialchars($report['title']); ?></h3>
                                <div class="report-id">রিপোর্ট আইডি: #<?php echo $report['id']; ?></div>
                            </div>
                            <div class="report-badges">
                                <?php
                                $status_classes = [
                                    'pending' => 'status-pending',
                                    'reviewing' => 'status-reviewing',
                                    'published' => 'status-published',
                                    'rejected' => 'status-rejected'
                                ];
                                $status_icons = [
                                    'pending' => 'fa-clock',
                                    'reviewing' => 'fa-spinner',
                                    'published' => 'fa-check-circle',
                                    'rejected' => 'fa-times-circle'
                                ];
                                $status_texts = [
                                    'pending' => 'অপেক্ষমান',
                                    'reviewing' => 'পর্যালোচনাধীন',
                                    'published' => 'প্রকাশিত',
                                    'rejected' => 'বাতিল'
                                ];
                                ?>
                                <span class="status-badge <?php echo $status_classes[$report['status']]; ?>">
                                    <i class="fas <?php echo $status_icons[$report['status']]; ?>"></i>
                                    <?php echo $status_texts[$report['status']]; ?>
                                </span>
                                
                                <?php
                                $severity_classes = [
                                    'low' => 'severity-low',
                                    'medium' => 'severity-medium',
                                    'high' => 'severity-high'
                                ];
                                $severity_texts = [
                                    'low' => 'নিম্ন',
                                    'medium' => 'মধ্যম',
                                    'high' => 'উচ্চ'
                                ];
                                ?>
                                <span class="severity-badge <?php echo $severity_classes[$report['severity']]; ?>">
                                    <?php echo $severity_texts[$report['severity']]; ?>
                                </span>
                            </div>
                        </div>

                        <div class="report-meta">
                            <div class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <span>ঘটনার তারিখ: <?php echo date('d F Y', strtotime($report['incident_date'])); ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>অবস্থান: <?php echo htmlspecialchars($report['location']); ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-tag"></i>
                                <span>
                                    <?php
                                    $incident_types = [
                                        'harassment' => 'হয়রানি',
                                        'assault' => 'সহিংসতা',
                                        'theft' => 'চুরি/ছিনতাই',
                                        'overcharging' => 'অতিরিক্ত ভাড়া',
                                        'misbehavior' => 'অসদাচরণ',
                                        'other' => 'অন্যান্য'
                                    ];
                                    echo $incident_types[$report['incident_type']] ?? $report['incident_type'];
                                    ?>
                                </span>
                            </div>
                        </div>

                        <div class="report-description">
                            <?php echo htmlspecialchars(substr($report['description'], 0, 200)) . (strlen($report['description']) > 200 ? '...' : ''); ?>
                        </div>

                        <div class="report-footer">
                            <div class="report-date">
                                <i class="far fa-clock"></i>
                                <span>জমা দেওয়ার তারিখ: <?php echo date('d F Y, h:i A', strtotime($report['created_at'])); ?></span>
                            </div>
                            
                            <div class="report-actions">
                                <a href="view_my_report.php?id=<?php echo $report['id']; ?>" class="action-btn view-btn">
                                    <i class="fas fa-eye"></i>
                                    বিস্তারিত
                                </a>
                                
                                <?php if (isset($published_map[$report['id']])): ?>
                                    <a href="view_published_report.php?id=<?php echo $published_map[$report['id']]; ?>" class="action-btn published-btn">
                                        <i class="fas fa-newspaper"></i>
                                        প্রকাশিত প্রতিবেদন
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($report['status'] == 'pending' || $report['status'] == 'rejected'): ?>
                                    <button onclick="showDeleteModal(<?php echo $report['id']; ?>)" class="action-btn delete-btn">
                                        <i class="fas fa-trash"></i>
                                        মুছে ফেলুন
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($report['status'] == 'rejected' && !empty($report['admin_notes'])): ?>
                            <div style="margin-top: 15px; padding: 15px; background-color: rgba(239, 68, 68, 0.05); border-radius: 8px; border-left: 4px solid var(--accent-danger);">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                    <i class="fas fa-info-circle" style="color: var(--accent-danger);"></i>
                                    <span style="color: var(--text-primary); font-weight: 600;">বাতিলের কারণ:</span>
                                </div>
                                <p style="color: var(--text-muted); margin-left: 26px;"><?php echo nl2br(htmlspecialchars($report['admin_notes'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <a href="?page=<?php echo max(1, $page - 1); ?>&status=<?php echo $status_filter; ?>" 
                       class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>" 
                           class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <a href="?page=<?php echo min($total_pages, $page + 1); ?>&status=<?php echo $status_filter; ?>" 
                       class="page-link <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>কোন রিপোর্ট পাওয়া যায়নি</h3>
                <p>
                    <?php if ($status_filter != 'all'): ?>
                        এই অবস্থায় কোনো রিপোর্ট নেই। 
                        <a href="my_reports.php" style="color: var(--accent-primary);">সব রিপোর্ট দেখুন</a>
                    <?php else: ?>
                        আপনি এখনো কোনো রিপোর্ট জমা দেননি। আপনার প্রথম রিপোর্ট জমা দিন।
                    <?php endif; ?>
                </p>
                <a href="report_incident.php" class="new-report-btn" style="display: inline-flex;">
                    <i class="fas fa-pen"></i>
                    রিপোর্ট জমা দিন
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-content">
            <div class="modal-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="modal-title">রিপোর্ট মুছে ফেলবেন?</h3>
            <p class="modal-text">আপনি কি নিশ্চিতভাবে এই রিপোর্টটি মুছে ফেলতে চান? এই কাজটি পূর্বাবস্থায় ফিরিয়ে আনা যাবে না।</p>
            <div class="modal-actions">
                <button class="modal-btn delete-cancel" onclick="hideDeleteModal()">বাতিল</button>
                <button class="modal-btn delete-confirm" id="confirmDeleteBtn">মুছে ফেলুন</button>
            </div>
        </div>
    </div>

    <script>
        let currentDeleteId = null;

        function showDeleteModal(reportId) {
            currentDeleteId = reportId;
            document.getElementById('deleteModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function hideDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            document.body.style.overflow = 'auto';
            currentDeleteId = null;
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (currentDeleteId) {
                // Show loading state
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> মুছছে...';
                this.disabled = true;

                // Send delete request
                fetch('delete_user_report.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'report_id=' + currentDeleteId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the report card
                        const reportCard = document.getElementById('report-' + currentDeleteId);
                        if (reportCard) {
                            reportCard.style.opacity = '0';
                            reportCard.style.transform = 'translateX(20px)';
                            setTimeout(() => {
                                reportCard.remove();
                                
                                // Check if no reports left
                                const remainingReports = document.querySelectorAll('.report-card').length;
                                if (remainingReports === 0) {
                                    location.reload(); // Reload to show empty state
                                }
                            }, 300);
                        }

                        // Show success message
                        showNotification('রিপোর্ট সফলভাবে মুছে ফেলা হয়েছে।', 'success');
                    } else {
                        showNotification(data.message || 'রিপোর্ট মুছতে সমস্যা হয়েছে।', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('রিপোর্ট মুছতে সমস্যা হয়েছে। আবার চেষ্টা করুন।', 'error');
                })
                .finally(() => {
                    hideDeleteModal();
                    this.innerHTML = 'মুছে ফেলুন';
                    this.disabled = false;
                });
            }
        });

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `message-alert ${type}-alert`;
            notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
            
            const container = document.querySelector('.container');
            container.insertBefore(notification, container.firstChild);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateY(-10px)';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideDeleteModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('deleteModal').classList.contains('active')) {
                hideDeleteModal();
            }
        });
    </script>
</body>

</html>
<?php
$conn->close();
include_once 'footer.php';
?>