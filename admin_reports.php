<?php
require_once 'admin_auth.php';
include_once 'db_config.php';

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$sql = "SELECT r.*, u.full_name as user_full_name, u.email as user_email, u.phone as user_phone 
        FROM incident_reports r 
        LEFT JOIN users u ON r.user_id = u.id 
        WHERE 1=1";

$params = [];
$types = "";

if ($status_filter != 'all') {
    $sql .= " AND r.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search_query)) {
    $sql .= " AND (r.title LIKE ? OR r.description LIKE ? OR r.location LIKE ? OR r.bus_number LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ssss";
}

if (!empty($date_from)) {
    $sql .= " AND DATE(r.incident_date) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $sql .= " AND DATE(r.incident_date) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$sql .= " ORDER BY r.created_at DESC";

// Prepare and execute
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$reports = $stmt->get_result();

// Get statistics
$stats = [];
$statuses = ['pending', 'reviewing', 'published', 'rejected'];
foreach ($statuses as $status) {
    $result = $conn->query("SELECT COUNT(*) as count FROM incident_reports WHERE status = '$status'");
    $stats[$status] = $result->fetch_assoc()['count'];
}
$stats['total'] = array_sum($stats);

// Handle report status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $report_id = intval($_POST['report_id']);
    
    if ($_POST['action'] == 'update_status') {
        $new_status = $_POST['status'];
        $admin_notes = isset($_POST['admin_notes']) ? $_POST['admin_notes'] : null;
        
        $update_sql = "UPDATE incident_reports SET status = ?, reviewed_by = ?, reviewed_at = NOW()";
        $update_params = [$new_status, $_SESSION['admin_id']];
        $update_types = "si";
        
        if ($admin_notes) {
            $update_sql .= ", admin_notes = ?";
            $update_params[] = $admin_notes;
            $update_types .= "s";
        }
        
        $update_sql .= " WHERE id = ?";
        $update_params[] = $report_id;
        $update_types .= "i";
        
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param($update_types, ...$update_params);
        
        if ($update_stmt->execute()) {
            // Log the action
            $log_sql = "INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, 'update', ?, ?)";
            $log_stmt = $conn->prepare($log_sql);
            $log_details = "রিপোর্ট #$report_id এর অবস্থা '$new_status' এ পরিবর্তন করা হয়েছে";
            $ip = $_SERVER['REMOTE_ADDR'];
            $log_stmt->bind_param("iss", $_SESSION['admin_id'], $log_details, $ip);
            $log_stmt->execute();
            
            $_SESSION['success_message'] = "রিপোর্টের অবস্থা সফলভাবে আপডেট করা হয়েছে।";
        } else {
            $_SESSION['error_message'] = "রিপোর্ট আপডেট করতে সমস্যা হয়েছে।";
        }
        
        header("Location: admin_reports.php");
        exit();
    }
    
    if ($_POST['action'] == 'write_report') {
        // Store report ID in session for the writing form
        $_SESSION['writing_report_id'] = $report_id;
        header("Location: admin_reports.php?write=true&id=$report_id");
        exit();
    }
}

// Get single report for viewing/writing
$single_report = null;
if (isset($_GET['view'])) {
    $report_id = intval($_GET['view']);
} elseif (isset($_GET['write'])) {
    $report_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
} else {
    $report_id = 0;
}

if ($report_id > 0) {
    $view_sql = "SELECT r.*, u.full_name as user_full_name, u.email as user_email, u.phone as user_phone 
                 FROM incident_reports r 
                 LEFT JOIN users u ON r.user_id = u.id 
                 WHERE r.id = ?";
    $view_stmt = $conn->prepare($view_sql);
    $view_stmt->bind_param("i", $report_id);
    $view_stmt->execute();
    $single_report = $view_stmt->get_result()->fetch_assoc();
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
    <title>রিপোর্ট ব্যবস্থাপনা - SafeRideBD অ্যাডমিন</title>
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

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--border-color);
            transition: all 0.2s;
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

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .stat-icon.pending { background-color: rgba(251, 191, 36, 0.1); color: var(--accent-warning); }
        .stat-icon.reviewing { background-color: rgba(59, 130, 246, 0.1); color: var(--accent-info); }
        .stat-icon.published { background-color: rgba(74, 222, 128, 0.1); color: var(--accent-secondary); }
        .stat-icon.rejected { background-color: rgba(239, 68, 68, 0.1); color: var(--accent-danger); }
        .stat-icon.total { background-color: rgba(255, 107, 74, 0.1); color: var(--accent-primary); }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 13px;
        }

        .stat-badge {
            font-size: 12px;
            padding: 3px 8px;
            border-radius: 4px;
            background-color: var(--bg-hover);
            color: var(--text-secondary);
        }

        /* Filters */
        .filters-section {
            background-color: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }

        .filters-form {
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
            font-size: 14px;
        }

        .filter-group label i {
            color: var(--accent-primary);
            margin-right: 6px;
        }

        .filter-input {
            width: 100%;
            padding: 12px 16px;
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-family: 'Bornomala', serif;
        }

        .filter-input:focus {
            outline: none;
            border-color: var(--accent-primary);
        }

        .filter-actions {
            display: flex;
            gap: 10px;
        }

        .filter-btn {
            padding: 12px 24px;
            background-color: var(--accent-primary);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Bornomala', serif;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .filter-btn:hover {
            background-color: #ff5a3a;
            transform: translateY(-2px);
        }

        .reset-btn {
            padding: 12px 24px;
            background-color: var(--bg-hover);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .reset-btn:hover {
            border-color: var(--accent-primary);
            color: var(--text-primary);
        }

        /* Reports Table */
        .reports-section {
            background-color: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--border-color);
        }

        .table-responsive {
            overflow-x: auto;
        }

        .reports-table {
            width: 100%;
            border-collapse: collapse;
        }

        .reports-table th {
            text-align: left;
            padding: 16px 12px;
            color: var(--text-muted);
            font-weight: 500;
            font-size: 14px;
            border-bottom: 1px solid var(--border-color);
        }

        .reports-table td {
            padding: 16px 12px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-secondary);
        }

        .reports-table tr:hover td {
            background-color: var(--bg-hover);
        }

        .report-title {
            color: var(--text-primary);
            font-weight: 500;
            margin-bottom: 4px;
        }

        .report-meta {
            font-size: 12px;
            color: var(--text-muted);
        }

        .report-meta i {
            margin-right: 4px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pending {
            background-color: rgba(251, 191, 36, 0.1);
            color: var(--accent-warning);
        }

        .status-reviewing {
            background-color: rgba(59, 130, 246, 0.1);
            color: var(--accent-info);
        }

        .status-published {
            background-color: rgba(74, 222, 128, 0.1);
            color: var(--accent-secondary);
        }

        .status-rejected {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--accent-danger);
        }

        .severity-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
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

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
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

        .review-btn {
            background-color: rgba(251, 191, 36, 0.1);
            color: var(--accent-warning);
        }

        .review-btn:hover {
            background-color: var(--accent-warning);
            color: white;
        }

        .write-btn {
            background-color: rgba(74, 222, 128, 0.1);
            color: var(--accent-secondary);
        }

        .write-btn:hover {
            background-color: var(--accent-secondary);
            color: white;
        }

        .reject-btn {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--accent-danger);
        }

        .reject-btn:hover {
            background-color: var(--accent-danger);
            color: white;
        }

        .no-reports {
            text-align: center;
            padding: 60px;
            color: var(--text-muted);
        }

        .no-reports i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.3;
        }

        /* Report Detail View */
        .report-detail {
            background-color: var(--bg-card);
            border-radius: 12px;
            padding: 30px;
            border: 1px solid var(--border-color);
        }

        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            flex-wrap: wrap;
            gap: 20px;
        }

        .detail-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 10px;
        }

        .detail-id {
            color: var(--text-muted);
            font-size: 14px;
        }

        .detail-badges {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }

        .detail-section {
            background-color: var(--bg-secondary);
            border-radius: 8px;
            padding: 20px;
            border: 1px solid var(--border-color);
        }

        .detail-section.full-width {
            grid-column: span 2;
        }

        .section-heading {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
            font-size: 18px;
        }

        .section-heading i {
            color: var(--accent-primary);
        }

        .info-row {
            display: flex;
            margin-bottom: 15px;
        }

        .info-label {
            width: 140px;
            color: var(--text-muted);
            font-size: 14px;
        }

        .info-value {
            flex: 1;
            color: var(--text-primary);
        }

        .evidence-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .evidence-item {
            background-color: var(--bg-card);
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

        .admin-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .admin-action-btn {
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-family: 'Bornomala', serif;
            text-decoration: none;
        }

        .btn-review {
            background-color: var(--accent-warning);
            color: white;
        }

        .btn-review:hover {
            background-color: #f59e0b;
            transform: translateY(-2px);
        }

        .btn-write {
            background-color: var(--accent-secondary);
            color: white;
        }

        .btn-write:hover {
            background-color: #22c55e;
            transform: translateY(-2px);
        }

        .btn-reject {
            background-color: var(--accent-danger);
            color: white;
        }

        .btn-reject:hover {
            background-color: #dc2626;
            transform: translateY(-2px);
        }

        .btn-cancel {
            background-color: var(--bg-hover);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }

        .btn-cancel:hover {
            border-color: var(--accent-primary);
            color: var(--text-primary);
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            backdrop-filter: blur(8px);
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background-color: var(--bg-card);
            border-radius: 16px;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            border: 2px solid var(--accent-warning);
        }

        .modal-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .modal-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: rgba(251, 191, 36, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 24px;
            color: var(--accent-warning);
        }

        .modal-title {
            font-size: 22px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 10px;
        }

        .modal-text {
            color: var(--text-muted);
            margin-bottom: 20px;
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

        .modal-btn-confirm {
            background-color: var(--accent-danger);
            color: white;
        }

        .modal-btn-confirm:hover {
            background-color: #dc2626;
        }

        .modal-btn-cancel {
            background-color: var(--bg-hover);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }

        .modal-btn-cancel:hover {
            background-color: var(--border-color);
            color: var(--text-primary);
        }

        /* Write Report Form */
        .write-report-form {
            margin-top: 30px;
            padding: 30px;
            background-color: var(--bg-secondary);
            border-radius: 12px;
            border: 2px solid var(--accent-secondary);
        }

        .form-group {
            margin-bottom: 20px;
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
            background-color: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-family: 'Bornomala', serif;
            font-size: 15px;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-secondary);
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .detail-section.full-width {
                grid-column: auto;
            }

            .info-row {
                flex-direction: column;
            }

            .info-label {
                width: 100%;
                margin-bottom: 5px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .filters-form {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .filter-actions {
                width: 100%;
            }
            
            .filter-btn, .reset-btn {
                flex: 1;
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
                <a href="admin_reports.php" class="nav-link active">
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

        <?php if (isset($_GET['write']) && $single_report): ?>
            <!-- Write Report View -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">প্রতিবেদন লিখুন</h1>
                    <p class="page-subtitle">রিপোর্ট #<?php echo $single_report['id']; ?> - <?php echo htmlspecialchars($single_report['title']); ?></p>
                </div>
                <a href="admin_reports.php?view=<?php echo $single_report['id']; ?>" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    রিপোর্টে ফিরে যান
                </a>
            </div>

            <div class="write-report-form">
                <form method="POST" action="admin_publish_report.php" enctype="multipart/form-data">
                    <input type="hidden" name="report_id" value="<?php echo $single_report['id']; ?>">
                    
                    <div class="form-group">
                        <label>
                            <i class="fas fa-heading"></i>
                            প্রতিবেদনের শিরোনাম *
                        </label>
                        <input type="text" name="title" class="form-control" 
                               value="রিপোর্ট: <?php echo htmlspecialchars($single_report['title']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-align-left"></i>
                            প্রতিবেদনের বিবরণ *
                        </label>
                        <textarea name="content" class="form-control" 
                                  placeholder="ঘটনার বিস্তারিত বিবরণ লিখুন..." required></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>
                                <i class="fas fa-calendar"></i>
                                প্রকাশের তারিখ
                            </label>
                            <input type="date" name="publish_date" class="form-control" 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-user"></i>
                                লেখকের নাম
                            </label>
                            <input type="text" name="author" class="form-control" 
                                   value="<?php echo htmlspecialchars(getCurrentAdminName()); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-image"></i>
                            কভার ছবি
                        </label>
                        <input type="file" name="cover_image" class="form-control" accept="image/*">
                        <small style="color: var(--text-muted);">প্রতিবেদনের জন্য একটি কভার ছবি নির্বাচন করুন (ঐচ্ছিক)</small>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-tags"></i>
                            ট্যাগ/ক্যাটাগরি
                        </label>
                        <select name="category" class="form-control">
                            <option value="incident">ঘটনা রিপোর্ট</option>
                            <option value="safety">নিরাপত্তা সতর্কতা</option>
                            <option value="update">আপডেট</option>
                            <option value="other">অন্যান্য</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-link"></i>
                            সোর্স/রেফারেন্স
                        </label>
                        <input type="text" name="source" class="form-control" 
                               placeholder="প্রাথমিক রিপোর্ট #<?php echo $single_report['id']; ?>">
                    </div>

                    <div class="admin-actions">
                        <button type="submit" name="action" value="publish" class="admin-action-btn btn-write">
                            <i class="fas fa-paper-plane"></i>
                            প্রতিবেদন প্রকাশ করুন
                        </button>
                        <a href="admin_reports.php?view=<?php echo $single_report['id']; ?>" class="admin-action-btn btn-cancel">
                            <i class="fas fa-times"></i>
                            বাতিল
                        </a>
                    </div>
                </form>
            </div>

            <!-- Evidence Reference -->
            <div style="margin-top: 30px; padding: 20px; background-color: var(--bg-card); border-radius: 12px;">
                <h3 style="margin-bottom: 15px; color: var(--text-primary);">সংযুক্ত প্রমাণসমূহ</h3>
                <?php if (!empty($single_report['evidence_files'])): ?>
                    <?php $evidence = json_decode($single_report['evidence_files'], true); ?>
                    <div class="evidence-gallery">
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
                <?php else: ?>
                    <p class="text-muted">কোন প্রমাণ সংযুক্ত নেই</p>
                <?php endif; ?>
            </div>

        <?php elseif (isset($_GET['view']) && $single_report): ?>
            <!-- Single Report View -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">রিপোর্ট বিস্তারিত</h1>
                    <p class="page-subtitle">রিপোর্ট #<?php echo $single_report['id']; ?> - সম্পূর্ণ তথ্য দেখুন</p>
                </div>
                <a href="admin_reports.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    সব রিপোর্ট দেখুন
                </a>
            </div>

            <div class="report-detail">
                <div class="detail-header">
                    <div>
                        <h2 class="detail-title"><?php echo htmlspecialchars($single_report['title']); ?></h2>
                        <div class="detail-id">রিপোর্ট আইডি: #<?php echo $single_report['id']; ?></div>
                    </div>
                    <div class="detail-badges">
                        <span class="status-badge status-<?php echo $single_report['status']; ?>">
                            <?php 
                                $status_labels = [
                                    'pending' => 'অপেক্ষমান',
                                    'reviewing' => 'পর্যালোচনাধীন',
                                    'published' => 'প্রকাশিত',
                                    'rejected' => 'বাতিল'
                                ];
                                echo $status_labels[$single_report['status']];
                            ?>
                        </span>
                        <span class="severity-badge severity-<?php echo $single_report['severity']; ?>">
                            <?php 
                                $severity_labels = [
                                    'low' => 'নিম্ন',
                                    'medium' => 'মধ্যম',
                                    'high' => 'উচ্চ'
                                ];
                                echo $severity_labels[$single_report['severity']];
                            ?>
                        </span>
                    </div>
                </div>

                <div class="detail-grid">
                    <!-- Reporter Information -->
                    <div class="detail-section">
                        <div class="section-heading">
                            <i class="fas fa-user"></i>
                            <span>রিপোর্টারের তথ্য</span>
                        </div>
                        <?php if ($single_report['is_anonymous']): ?>
                            <div style="text-align: center; padding: 20px; color: var(--text-muted);">
                                <i class="fas fa-user-secret" style="font-size: 32px; margin-bottom: 10px;"></i>
                                <p>বেনামে রিপোর্ট করা হয়েছে</p>
                                <p style="font-size: 12px;">শুধুমাত্র প্রশাসক তথ্য দেখতে পারেন</p>
                            </div>
                            <?php if ($single_report['full_name'] || $single_report['email'] || $single_report['phone']): ?>
                                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed var(--border-color);">
                                    <p style="color: var(--text-muted); font-size: 12px; margin-bottom: 10px;">গোপন তথ্য (শুধুমাত্র প্রশাসকের জন্য)</p>
                                    <?php if ($single_report['full_name']): ?>
                                        <div class="info-row">
                                            <div class="info-label">নাম:</div>
                                            <div class="info-value"><?php echo htmlspecialchars($single_report['full_name']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($single_report['email']): ?>
                                        <div class="info-row">
                                            <div class="info-label">ইমেইল:</div>
                                            <div class="info-value"><?php echo htmlspecialchars($single_report['email']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($single_report['phone']): ?>
                                        <div class="info-row">
                                            <div class="info-label">ফোন:</div>
                                            <div class="info-value"><?php echo htmlspecialchars($single_report['phone']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="info-row">
                                <div class="info-label">নাম:</div>
                                <div class="info-value"><?php echo htmlspecialchars($single_report['full_name'] ?: $single_report['user_full_name']); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">ইমেইল:</div>
                                <div class="info-value"><?php echo htmlspecialchars($single_report['email'] ?: $single_report['user_email']); ?></div>
                            </div>
                            <?php if ($single_report['phone']): ?>
                                <div class="info-row">
                                    <div class="info-label">ফোন:</div>
                                    <div class="info-value"><?php echo htmlspecialchars($single_report['phone']); ?></div>
                                </div>
                            <?php endif; ?>
                            <?php if ($single_report['address']): ?>
                                <div class="info-row">
                                    <div class="info-label">ঠিকানা:</div>
                                    <div class="info-value"><?php echo nl2br(htmlspecialchars($single_report['address'])); ?></div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Incident Information -->
                    <div class="detail-section">
                        <div class="section-heading">
                            <i class="fas fa-info-circle"></i>
                            <span>ঘটনার তথ্য</span>
                        </div>
                        <div class="info-row">
                            <div class="info-label">ধরণ:</div>
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
                                    echo $incident_types[$single_report['incident_type']] ?? $single_report['incident_type'];
                                ?>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">তারিখ:</div>
                            <div class="info-value">
                                <?php echo date('d F, Y', strtotime($single_report['incident_date'])); ?>
                                <?php if ($single_report['incident_time']): ?>
                                    <?php echo date('h:i A', strtotime($single_report['incident_time'])); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">অবস্থান:</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($single_report['location']); ?>
                                <?php if ($single_report['specific_location']): ?>
                                    <br><small style="color: var(--text-muted);"><?php echo htmlspecialchars($single_report['specific_location']); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($single_report['bus_number']): ?>
                            <div class="info-row">
                                <div class="info-label">বাস নম্বর:</div>
                                <div class="info-value"><?php echo htmlspecialchars($single_report['bus_number']); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Incident Description -->
                    <div class="detail-section full-width">
                        <div class="section-heading">
                            <i class="fas fa-align-left"></i>
                            <span>ঘটনার বিবরণ</span>
                        </div>
                        <div style="background-color: var(--bg-primary); padding: 20px; border-radius: 8px;">
                            <?php echo nl2br(htmlspecialchars($single_report['description'])); ?>
                        </div>
                    </div>

                    <!-- Involved Parties -->
                    <?php if ($single_report['driver_name'] || $single_report['helper_name'] || $single_report['bus_details'] || $single_report['witnesses']): ?>
                    <div class="detail-section full-width">
                        <div class="section-heading">
                            <i class="fas fa-users"></i>
                            <span>জড়িত পক্ষ</span>
                        </div>
                        <?php if ($single_report['driver_name']): ?>
                            <div class="info-row">
                                <div class="info-label">চালক:</div>
                                <div class="info-value"><?php echo htmlspecialchars($single_report['driver_name']); ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if ($single_report['helper_name']): ?>
                            <div class="info-row">
                                <div class="info-label">হেলপার:</div>
                                <div class="info-value"><?php echo htmlspecialchars($single_report['helper_name']); ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if ($single_report['bus_details']): ?>
                            <div class="info-row">
                                <div class="info-label">বাসের বিবরণ:</div>
                                <div class="info-value"><?php echo htmlspecialchars($single_report['bus_details']); ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if ($single_report['witnesses']): ?>
                            <div class="info-row">
                                <div class="info-label">সাক্ষী:</div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($single_report['witnesses'])); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Evidence Files -->
                    <?php if (!empty($single_report['evidence_files'])): ?>
                    <div class="detail-section full-width">
                        <div class="section-heading">
                            <i class="fas fa-paperclip"></i>
                            <span>প্রমাণ সংযুক্তি</span>
                        </div>
                        <?php 
                        $evidence = json_decode($single_report['evidence_files'], true);
                        if (!empty($evidence)):
                        ?>
                            <div class="evidence-gallery">
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
                        <?php else: ?>
                            <p class="text-muted">প্রমাণ ফাইল পাওয়া যায়নি</p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Admin Notes -->
                    <?php if ($single_report['admin_notes']): ?>
                    <div class="detail-section full-width">
                        <div class="section-heading">
                            <i class="fas fa-sticky-note"></i>
                            <span>প্রশাসকের নোট</span>
                        </div>
                        <div style="background-color: var(--bg-primary); padding: 20px; border-radius: 8px; border-left: 4px solid var(--accent-warning);">
                            <?php echo nl2br(htmlspecialchars($single_report['admin_notes'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Admin Actions -->
                <div class="admin-actions">
                    <?php if ($single_report['status'] == 'pending'): ?>
                        <form method="POST" style="display: inline-block;">
                            <input type="hidden" name="report_id" value="<?php echo $single_report['id']; ?>">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="status" value="reviewing">
                            <button type="submit" class="admin-action-btn btn-review">
                                <i class="fas fa-eye"></i>
                                পর্যালোচনা শুরু করুন
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($single_report['status'] == 'reviewing'): ?>
                        <form method="POST" style="display: inline-block;">
                            <input type="hidden" name="report_id" value="<?php echo $single_report['id']; ?>">
                            <input type="hidden" name="action" value="write_report">
                            <button type="submit" class="admin-action-btn btn-write">
                                <i class="fas fa-pen"></i>
                                প্রতিবেদন লিখুন
                            </button>
                        </form>

                        <button class="admin-action-btn btn-reject" onclick="showRejectModal(<?php echo $single_report['id']; ?>)">
                            <i class="fas fa-times"></i>
                            বাতিল করুন
                        </button>
                    <?php endif; ?>

                    <?php if ($single_report['status'] == 'published'): ?>
                        <span style="color: var(--accent-secondary); padding: 14px 28px;">
                            <i class="fas fa-check-circle"></i>
                            প্রতিবেদন প্রকাশিত হয়েছে
                        </span>
                    <?php endif; ?>

                    <?php if ($single_report['status'] == 'rejected'): ?>
                        <span style="color: var(--accent-danger); padding: 14px 28px;">
                            <i class="fas fa-times-circle"></i>
                            প্রতিবেদন বাতিল করা হয়েছে
                        </span>
                    <?php endif; ?>

                    <a href="admin_reports.php" class="admin-action-btn btn-cancel">
                        <i class="fas fa-arrow-left"></i>
                        ফিরে যান
                    </a>
                </div>
            </div>

            <!-- Reject Modal -->
            <div class="modal-overlay" id="rejectModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="modal-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h3 class="modal-title">রিপোর্ট বাতিল করুন</h3>
                        <p class="modal-text">আপনি কি নিশ্চিতভাবে এই রিপোর্টটি বাতিল করতে চান?</p>
                    </div>
                    <form method="POST" id="rejectForm">
                        <input type="hidden" name="report_id" id="rejectReportId" value="">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="status" value="rejected">
                        
                        <div class="form-group">
                            <label for="admin_notes">বাতিলের কারণ (ঐচ্ছিক)</label>
                            <textarea name="admin_notes" id="admin_notes" class="form-control" rows="3" placeholder="বাতিলের কারণ লিখুন..."></textarea>
                        </div>
                        
                        <div class="modal-actions">
                            <button type="button" class="modal-btn modal-btn-cancel" onclick="hideRejectModal()">
                                <i class="fas fa-times"></i> বাতিল
                            </button>
                            <button type="submit" class="modal-btn modal-btn-confirm">
                                <i class="fas fa-check"></i> নিশ্চিত করুন
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                function showRejectModal(reportId) {
                    document.getElementById('rejectReportId').value = reportId;
                    document.getElementById('rejectModal').classList.add('active');
                }
                
                function hideRejectModal() {
                    document.getElementById('rejectModal').classList.remove('active');
                }
            </script>

        <?php else: ?>
            <!-- Reports List View -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">রিপোর্ট ব্যবস্থাপনা</h1>
                    <p class="page-subtitle">সকল রিপোর্ট দেখুন এবং ব্যবস্থাপনা করুন</p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <a href="admin_reports.php?status=all" class="stat-card <?php echo $status_filter == 'all' ? 'active' : ''; ?>">
                    <div class="stat-header">
                        <div class="stat-icon total">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <span class="stat-badge">সবগুলো</span>
                    </div>
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">মোট রিপোর্ট</div>
                </a>

                <a href="admin_reports.php?status=pending" class="stat-card <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">
                    <div class="stat-header">
                        <div class="stat-icon pending">
                            <i class="fas fa-clock"></i>
                        </div>
                        <span class="stat-badge">অপেক্ষমান</span>
                    </div>
                    <div class="stat-value"><?php echo $stats['pending']; ?></div>
                    <div class="stat-label">পর্যালোচনার অপেক্ষায়</div>
                </a>

                <a href="admin_reports.php?status=reviewing" class="stat-card <?php echo $status_filter == 'reviewing' ? 'active' : ''; ?>">
                    <div class="stat-header">
                        <div class="stat-icon reviewing">
                            <i class="fas fa-spinner"></i>
                        </div>
                        <span class="stat-badge">পর্যালোচনাধীন</span>
                    </div>
                    <div class="stat-value"><?php echo $stats['reviewing']; ?></div>
                    <div class="stat-label">পর্যালোচনা চলছে</div>
                </a>

                <a href="admin_reports.php?status=published" class="stat-card <?php echo $status_filter == 'published' ? 'active' : ''; ?>">
                    <div class="stat-header">
                        <div class="stat-icon published">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <span class="stat-badge">প্রকাশিত</span>
                    </div>
                    <div class="stat-value"><?php echo $stats['published']; ?></div>
                    <div class="stat-label">প্রকাশিত রিপোর্ট</div>
                </a>

                <a href="admin_reports.php?status=rejected" class="stat-card <?php echo $status_filter == 'rejected' ? 'active' : ''; ?>">
                    <div class="stat-header">
                        <div class="stat-icon rejected">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <span class="stat-badge">বাতিল</span>
                    </div>
                    <div class="stat-value"><?php echo $stats['rejected']; ?></div>
                    <div class="stat-label">বাতিলকৃত রিপোর্ট</div>
                </a>
            </div>

            <!-- Filters -->
            <div class="filters-section">
                <form method="GET" class="filters-form">
                    <div class="filter-group">
                        <label><i class="fas fa-search"></i> অনুসন্ধান</label>
                        <input type="text" name="search" class="filter-input" placeholder="শিরোনাম, বিবরণ, অবস্থান..." value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-calendar"></i> তারিখ থেকে</label>
                        <input type="date" name="date_from" class="filter-input" value="<?php echo $date_from; ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-calendar"></i> তারিখ পর্যন্ত</label>
                        <input type="date" name="date_to" class="filter-input" value="<?php echo $date_to; ?>">
                    </div>

                    <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                    
                    <div class="filter-actions">
                        <button type="submit" class="filter-btn">
                            <i class="fas fa-filter"></i> ফিল্টার
                        </button>
                        <a href="admin_reports.php" class="reset-btn">
                            <i class="fas fa-redo"></i> রিসেট
                        </a>
                    </div>
                </form>
            </div>

            <!-- Reports Table -->
            <div class="reports-section">
                <div class="table-responsive">
                    <table class="reports-table">
                        <thead>
                            <tr>
                                <th>আইডি</th>
                                <th>শিরোনাম</th>
                                <th>অবস্থান</th>
                                <th>ধরণ</th>
                                <th>তারিখ</th>
                                <th>তীব্রতা</th>
                                <th>অবস্থা</th>
                                <th>অ্যাকশন</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($reports->num_rows > 0): ?>
                                <?php while ($report = $reports->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $report['id']; ?></td>
                                        <td>
                                            <div class="report-title"><?php echo htmlspecialchars(substr($report['title'], 0, 50)) . (strlen($report['title']) > 50 ? '...' : ''); ?></div>
                                            <div class="report-meta">
                                                <i class="fas fa-user"></i> 
                                                <?php echo $report['is_anonymous'] ? 'বেনামে' : htmlspecialchars($report['full_name'] ?: $report['user_full_name']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($report['location']); ?>
                                            <?php if ($report['specific_location']): ?>
                                                <br><small style="color: var(--text-muted);"><?php echo htmlspecialchars($report['specific_location']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
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
                                        </td>
                                        <td>
                                            <?php echo date('d M Y', strtotime($report['incident_date'])); ?>
                                            <?php if ($report['incident_time']): ?>
                                                <br><small style="color: var(--text-muted);"><?php echo date('h:i A', strtotime($report['incident_time'])); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="severity-badge severity-<?php echo $report['severity']; ?>">
                                                <?php 
                                                    $severity_labels = [
                                                        'low' => 'নিম্ন',
                                                        'medium' => 'মধ্যম',
                                                        'high' => 'উচ্চ'
                                                    ];
                                                    echo $severity_labels[$report['severity']];
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $report['status']; ?>">
                                                <?php 
                                                    $status_labels = [
                                                        'pending' => 'অপেক্ষমান',
                                                        'reviewing' => 'পর্যালোচনাধীন',
                                                        'published' => 'প্রকাশিত',
                                                        'rejected' => 'বাতিল'
                                                    ];
                                                    echo $status_labels[$report['status']];
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="admin_reports.php?view=<?php echo $report['id']; ?>" class="action-btn view-btn" title="বিস্তারিত দেখুন">
                                                    <i class="fas fa-eye"></i> দেখুন
                                                </a>
                                                <?php if ($report['status'] == 'pending'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="status" value="reviewing">
                                                        <button type="submit" class="action-btn review-btn" title="পর্যালোচনা শুরু করুন">
                                                            <i class="fas fa-spinner"></i> পর্যালোচনা
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if ($report['status'] == 'reviewing'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                                        <input type="hidden" name="action" value="write_report">
                                                        <button type="submit" class="action-btn write-btn" title="প্রতিবেদন লিখুন">
                                                            <i class="fas fa-pen"></i> লিখুন
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="no-reports">
                                            <i class="fas fa-inbox"></i>
                                            <p>কোন রিপোর্ট পাওয়া যায়নি</p>
                                            <?php if ($status_filter != 'all' || $search_query || $date_from || $date_to): ?>
                                                <p style="margin-top: 10px;">
                                                    <a href="admin_reports.php" style="color: var(--accent-primary);">ফিল্টার রিসেট করুন</a>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>