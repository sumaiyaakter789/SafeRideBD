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
$report_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($report_id == 0) {
    header("Location: my_reports.php");
    exit();
}

// Get report details
$sql = "SELECT * FROM incident_reports WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $report_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: my_reports.php");
    exit();
}

$report = $result->fetch_assoc();

// Check if published
$published_id = null;
$pub_sql = "SELECT id FROM published_reports WHERE incident_report_id = ?";
$pub_stmt = $conn->prepare($pub_sql);
$pub_stmt->bind_param("i", $report_id);
$pub_stmt->execute();
$pub_result = $pub_stmt->get_result();
if ($pub_result->num_rows > 0) {
    $published = $pub_result->fetch_assoc();
    $published_id = $published['id'];
}
?>

<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>রিপোর্ট বিস্তারিত - SafeRideBD</title>
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
            --shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
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
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 24px;
            flex: 1;
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
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--accent-danger), var(--accent-warning));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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

        .report-detail {
            background-color: var(--bg-card);
            border-radius: 16px;
            padding: 30px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
        }

        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            flex-wrap: wrap;
            gap: 15px;
        }

        .detail-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .detail-id {
            color: var(--text-muted);
            font-size: 14px;
        }

        .badge-container {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .status-badge {
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
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
            padding: 6px 16px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
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

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-item {
            background-color: var(--bg-secondary);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .info-label {
            color: var(--text-muted);
            font-size: 13px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .info-label i {
            color: var(--accent-primary);
        }

        .info-value {
            color: var(--text-primary);
            font-size: 16px;
            font-weight: 500;
        }

        .description-section {
            background-color: var(--bg-secondary);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: var(--text-primary);
            font-size: 18px;
        }

        .section-title i {
            color: var(--accent-primary);
        }

        .description-text {
            color: var(--text-secondary);
            line-height: 1.8;
            white-space: pre-line;
        }

        .evidence-section {
            margin-bottom: 30px;
        }

        .evidence-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .evidence-item {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
        }

        .evidence-preview {
            height: 120px;
            background-color: var(--bg-hover);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-size: 32px;
        }

        .evidence-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .evidence-info {
            padding: 10px;
            border-top: 1px solid var(--border-color);
        }

        .evidence-name {
            font-size: 12px;
            color: var(--text-secondary);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .evidence-size {
            font-size: 10px;
            color: var(--text-muted);
        }

        .evidence-download {
            display: block;
            text-align: center;
            padding: 8px;
            background-color: var(--bg-hover);
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 12px;
            transition: all 0.2s;
        }

        .evidence-download:hover {
            background-color: var(--accent-primary);
            color: white;
        }

        .admin-notes {
            background-color: rgba(239, 68, 68, 0.05);
            border-left: 4px solid var(--accent-danger);
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }

        .admin-notes-title {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--accent-danger);
            margin-bottom: 10px;
        }

        .admin-notes-text {
            color: var(--text-muted);
            margin-left: 26px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: none;
            font-family: 'Bornomala', serif;
        }

        .delete-btn {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--accent-danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .delete-btn:hover {
            background-color: var(--accent-danger);
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

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-btn {
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">রিপোর্ট বিস্তারিত</h1>
            <a href="my_reports.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                আমার রিপোর্টে ফিরে যান
            </a>
        </div>

        <div class="report-detail">
            <div class="detail-header">
                <div>
                    <h2 class="detail-title"><?php echo htmlspecialchars($report['title']); ?></h2>
                    <div class="detail-id">রিপোর্ট আইডি: #<?php echo $report['id']; ?></div>
                </div>
                <div class="badge-container">
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
                    $severity_icons = [
                        'low' => 'fa-smile',
                        'medium' => 'fa-meh',
                        'high' => 'fa-frown'
                    ];
                    ?>
                    <span class="severity-badge <?php echo $severity_classes[$report['severity']]; ?>">
                        <i class="fas <?php echo $severity_icons[$report['severity']]; ?>"></i>
                        <?php echo $severity_texts[$report['severity']]; ?>
                    </span>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-calendar"></i>
                        ঘটনার তারিখ
                    </div>
                    <div class="info-value">
                        <?php echo date('d F Y', strtotime($report['incident_date'])); ?>
                        <?php if ($report['incident_time']): ?>
                            <span style="color: var(--text-muted); margin-left: 10px;">
                                <?php echo date('h:i A', strtotime($report['incident_time'])); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-tag"></i>
                        ঘটনার ধরণ
                    </div>
                    <div class="info-value">
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
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-map-marker-alt"></i>
                        অবস্থান
                    </div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($report['location']); ?>
                        <?php if ($report['specific_location']): ?>
                            <br>
                            <small style="color: var(--text-muted);"><?php echo htmlspecialchars($report['specific_location']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-bus"></i>
                        বাসের তথ্য
                    </div>
                    <div class="info-value">
                        <?php if ($report['bus_number']): ?>
                            নম্বর: <?php echo htmlspecialchars($report['bus_number']); ?><br>
                        <?php endif; ?>
                        <?php if ($report['bus_details']): ?>
                            <small><?php echo htmlspecialchars($report['bus_details']); ?></small>
                        <?php endif; ?>
                        <?php if (!$report['bus_number'] && !$report['bus_details']): ?>
                            দেওয়া হয়নি
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($report['driver_name']): ?>
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-user-tie"></i>
                        চালকের নাম
                    </div>
                    <div class="info-value"><?php echo htmlspecialchars($report['driver_name']); ?></div>
                </div>
                <?php endif; ?>

                <?php if ($report['helper_name']): ?>
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-user-tag"></i>
                        হেলপারের নাম
                    </div>
                    <div class="info-value"><?php echo htmlspecialchars($report['helper_name']); ?></div>
                </div>
                <?php endif; ?>

                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-clock"></i>
                        জমা দেওয়ার তারিখ
                    </div>
                    <div class="info-value">
                        <?php echo date('d F Y, h:i A', strtotime($report['created_at'])); ?>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-sync"></i>
                        সর্বশেষ আপডেট
                    </div>
                    <div class="info-value">
                        <?php echo date('d F Y, h:i A', strtotime($report['updated_at'])); ?>
                    </div>
                </div>
            </div>

            <div class="description-section">
                <div class="section-title">
                    <i class="fas fa-align-left"></i>
                    ঘটনার বিবরণ
                </div>
                <div class="description-text">
                    <?php echo nl2br(htmlspecialchars($report['description'])); ?>
                </div>
            </div>

            <?php if (!empty($report['witnesses'])): ?>
            <div class="description-section">
                <div class="section-title">
                    <i class="fas fa-users"></i>
                    সাক্ষীদের তথ্য
                </div>
                <div class="description-text">
                    <?php echo nl2br(htmlspecialchars($report['witnesses'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($report['additional_info'])): ?>
            <div class="description-section">
                <div class="section-title">
                    <i class="fas fa-plus-circle"></i>
                    অতিরিক্ত তথ্য
                </div>
                <div class="description-text">
                    <?php echo nl2br(htmlspecialchars($report['additional_info'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($report['evidence_files'])): ?>
            <div class="evidence-section">
                <div class="section-title">
                    <i class="fas fa-paperclip"></i>
                    সংযুক্ত প্রমাণ
                </div>
                <?php
                $evidence = json_decode($report['evidence_files'], true);
                if (!empty($evidence)):
                ?>
                    <div class="evidence-grid">
                        <?php foreach ($evidence as $file): ?>
                            <div class="evidence-item">
                                <?php if (strpos($file['type'], 'image/') === 0): ?>
                                    <div class="evidence-preview">
                                        <img src="uploads/incidents/<?php echo $file['filename']; ?>" alt="Evidence">
                                    </div>
                                <?php else: ?>
                                    <div class="evidence-preview">
                                        <i class="fas <?php 
                                            echo strpos($file['type'], 'video/') === 0 ? 'fa-video' : 
                                                (strpos($file['type'], 'audio/') === 0 ? 'fa-music' : 
                                                ($file['type'] == 'application/pdf' ? 'fa-file-pdf' : 'fa-file')); 
                                        ?>"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="evidence-info">
                                    <div class="evidence-name"><?php echo htmlspecialchars($file['original']); ?></div>
                                    <div class="evidence-size"><?php echo round($file['size'] / 1024, 1); ?> KB</div>
                                </div>
                                <a href="uploads/incidents/<?php echo $file['filename']; ?>" class="evidence-download" download>
                                    <i class="fas fa-download"></i> ডাউনলোড
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($report['status'] == 'rejected' && !empty($report['admin_notes'])): ?>
            <div class="admin-notes">
                <div class="admin-notes-title">
                    <i class="fas fa-info-circle"></i>
                    <strong>বাতিলের কারণ</strong>
                </div>
                <div class="admin-notes-text">
                    <?php echo nl2br(htmlspecialchars($report['admin_notes'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="action-buttons">
                <a href="my_reports.php" class="action-btn back-btn" style="flex: 1;">
                    <i class="fas fa-arrow-left"></i>
                    তালিকায় ফিরে যান
                </a>

                <?php if ($published_id): ?>
                    <a href="view_published_report.php?id=<?php echo $published_id; ?>" class="action-btn published-btn" style="flex: 1;">
                        <i class="fas fa-newspaper"></i>
                        প্রকাশিত প্রতিবেদন দেখুন
                    </a>
                <?php endif; ?>

                <?php if ($report['status'] == 'pending' || $report['status'] == 'rejected'): ?>
                    <button onclick="confirmDelete(<?php echo $report['id']; ?>)" class="action-btn delete-btn" style="flex: 1;">
                        <i class="fas fa-trash"></i>
                        রিপোর্ট মুছে ফেলুন
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(reportId) {
            if (confirm('আপনি কি নিশ্চিতভাবে এই রিপোর্টটি মুছে ফেলতে চান?')) {
                window.location.href = 'delete_user_report.php?id=' + reportId;
            }
        }
    </script>
</body>

</html>
<?php
$conn->close();
include_once 'footer.php';
?>