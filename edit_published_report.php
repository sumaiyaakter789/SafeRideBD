<?php
require_once 'admin_auth.php';
include_once 'db_config.php';

$report_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($report_id == 0) {
    header("Location: admin_published_reports.php");
    exit();
}

// Get report details
$sql = "SELECT * FROM published_reports WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: admin_published_reports.php");
    exit();
}

$report = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category = $_POST['category'];
    $author = trim($_POST['author']);
    $source = trim($_POST['source']);
    $publish_date = $_POST['publish_date'];

    // Validate required fields
    if (empty($title) || empty($content)) {
        $error = "শিরোনাম এবং বিবরণ অবশ্যই প্রদান করতে হবে।";
    } else {
        // Handle cover image upload
        $cover_image = $report['cover_image'];
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/reports/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_ext, $allowed_exts)) {
                // Delete old image if exists
                if (!empty($cover_image) && file_exists($cover_image)) {
                    unlink($cover_image);
                }
                
                $filename = 'report_' . time() . '_' . $report_id . '.' . $file_ext;
                $upload_path = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_path)) {
                    $cover_image = $upload_path;
                }
            }
        }

        // Update report
        $update_sql = "UPDATE published_reports 
                       SET title = ?, content = ?, category = ?, author = ?, source = ?, cover_image = ?, publish_date = ? 
                       WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sssssssi", $title, $content, $category, $author, $source, $cover_image, $publish_date, $report_id);
        
        if ($update_stmt->execute()) {
            // Log the action
            $log_sql = "INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, 'update', ?, ?)";
            $log_stmt = $conn->prepare($log_sql);
            $log_details = "প্রকাশিত প্রতিবেদন #$report_id সম্পাদনা করা হয়েছে";
            $ip = $_SERVER['REMOTE_ADDR'];
            $log_stmt->bind_param("iss", $_SESSION['admin_id'], $log_details, $ip);
            $log_stmt->execute();
            
            $_SESSION['success_message'] = "প্রতিবেদন সফলভাবে আপডেট করা হয়েছে।";
            header("Location: admin_published_reports.php");
            exit();
        } else {
            $error = "প্রতিবেদন আপডেট করতে সমস্যা হয়েছে।";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>প্রতিবেদন সম্পাদনা - SafeRideBD অ্যাডমিন</title>
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
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-warning));
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

        /* Form */
        .edit-form {
            background-color: var(--bg-card);
            border-radius: 16px;
            padding: 30px;
            border: 2px solid var(--border-color);
            box-shadow: var(--shadow);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .form-group label i {
            color: var(--accent-primary);
            margin-right: 8px;
        }

        .form-control {
            width: 100%;
            padding: 14px 18px;
            background-color: var(--bg-secondary);
            border: 2px solid var(--border-color);
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

        textarea.form-control {
            min-height: 200px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .current-image {
            margin-top: 10px;
            padding: 15px;
            background-color: var(--bg-secondary);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .current-image img {
            max-width: 200px;
            max-height: 150px;
            border-radius: 4px;
            margin-top: 10px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .submit-btn {
            padding: 16px 32px;
            background: linear-gradient(135deg, var(--accent-primary), #ff8a6a);
            color: white;
            border: none;
            border-radius: 8px;
            font-family: 'Bornomala', serif;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 107, 74, 0.3);
        }

        .cancel-btn {
            padding: 16px 32px;
            background-color: var(--bg-hover);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-family: 'Bornomala', serif;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .cancel-btn:hover {
            border-color: var(--accent-primary);
            color: var(--text-primary);
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .submit-btn, .cancel-btn {
                width: 100%;
                justify-content: center;
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
                <h1 class="page-title">প্রতিবেদন সম্পাদনা</h1>
                <p class="page-subtitle">প্রকাশিত প্রতিবেদন #<?php echo $report_id; ?> সম্পাদনা করুন</p>
            </div>
            <a href="admin_published_reports.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                প্রতিবেদন তালিকায় ফিরে যান
            </a>
        </div>

        <?php if (isset($error)): ?>
            <div class="message-alert error-alert">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="edit-form">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>
                        <i class="fas fa-heading"></i>
                        শিরোনাম *
                    </label>
                    <input type="text" name="title" class="form-control" 
                           value="<?php echo htmlspecialchars($report['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-align-left"></i>
                        বিবরণ *
                    </label>
                    <textarea name="content" class="form-control" required><?php echo htmlspecialchars($report['content']); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <i class="fas fa-tag"></i>
                            ক্যাটাগরি
                        </label>
                        <select name="category" class="form-control">
                            <option value="incident" <?php echo $report['category'] == 'incident' ? 'selected' : ''; ?>>ঘটনা রিপোর্ট</option>
                            <option value="safety" <?php echo $report['category'] == 'safety' ? 'selected' : ''; ?>>নিরাপত্তা সতর্কতা</option>
                            <option value="update" <?php echo $report['category'] == 'update' ? 'selected' : ''; ?>>আপডেট</option>
                            <option value="other" <?php echo $report['category'] == 'other' ? 'selected' : ''; ?>>অন্যান্য</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-user"></i>
                            লেখকের নাম
                        </label>
                        <input type="text" name="author" class="form-control" 
                               value="<?php echo htmlspecialchars($report['author']); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <i class="fas fa-link"></i>
                            সোর্স/রেফারেন্স
                        </label>
                        <input type="text" name="source" class="form-control" 
                               value="<?php echo htmlspecialchars($report['source'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-calendar"></i>
                            প্রকাশের তারিখ
                        </label>
                        <input type="date" name="publish_date" class="form-control" 
                               value="<?php echo $report['publish_date']; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-image"></i>
                        কভার ছবি
                    </label>
                    <input type="file" name="cover_image" class="form-control" accept="image/*">
                    
                    <?php if (!empty($report['cover_image']) && file_exists($report['cover_image'])): ?>
                        <div class="current-image">
                            <p style="color: var(--text-muted); margin-bottom: 10px;">বর্তমান ছবি:</p>
                            <img src="<?php echo $report['cover_image']; ?>" alt="Current Cover">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i>
                        আপডেট করুন
                    </button>
                    <a href="admin_published_reports.php" class="cancel-btn">
                        <i class="fas fa-times"></i>
                        বাতিল
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>