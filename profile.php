<?php
session_start();
include_once 'navbar.php';
include_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=profile");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = null;

// Get user details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
}
$stmt->close();

// Get saved routes count
$count_sql = "SELECT COUNT(*) as total_routes FROM saved_routes WHERE user_id = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$route_count = $count_result->fetch_assoc()['total_routes'];
$count_stmt->close();

// Get recent saved routes
$routes_sql = "SELECT * FROM saved_routes WHERE user_id = ? ORDER BY saved_at DESC LIMIT 5";
$routes_stmt = $conn->prepare($routes_sql);
$routes_stmt->bind_param("i", $user_id);
$routes_stmt->execute();
$routes_result = $routes_stmt->get_result();
$recent_routes = [];
while ($route = $routes_result->fetch_assoc()) {
    $recent_routes[] = $route;
}
$routes_stmt->close();

// Success/Error messages
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Get all locations for typeahead
$locations = [];
$location_query = "SELECT DISTINCT `from` as location FROM fare_chart 
                   UNION 
                   SELECT DISTINCT `to` as location FROM fare_chart 
                   ORDER BY location";
$location_result = $conn->query($location_query);
if ($location_result && $location_result->num_rows > 0) {
    while ($row = $location_result->fetch_assoc()) {
        $locations[] = $row['location'];
    }
}
?>

<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>আমার প্রোফাইল - SafeRideBD</title>
    <style>
        /* Import the same CSS variables and base styles from index.php */
        :root {
            --bg-primary: #0a0a0a;
            --bg-secondary: #111111;
            --bg-card: #1a1a1a;
            --bg-hover: #2a2a2a;
            --text-primary: #ffffff;
            --text-secondary: #b0b0b0;
            --text-muted: #808080;
            --border-color: #2a2a2a;
            --border-light: #3a3a3a;
            --accent-primary: #ff6b4a;
            --accent-secondary: #4ade80;
            --accent-warning: #fbbf24;
            --accent-danger: #ef4444;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.5);
            --glow: 0 0 0 2px rgba(255, 107, 74, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Bornomala', 'Hind Siliguri', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 24px;
            flex: 1;
        }

        /* Message Alerts - Enhanced */
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

        .info-alert {
            background-color: rgba(59, 130, 246, 0.1);
            border-left-color: #3b82f6;
            color: #3b82f6;
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

        /* Profile Header - Enhanced */
        .profile-header {
            background: linear-gradient(135deg, var(--bg-card), var(--bg-secondary));
            border-radius: 16px;
            padding: 40px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 40px;
            flex-wrap: wrap;
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255, 107, 74, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .profile-avatar {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 56px;
            font-weight: 700;
            position: relative;
            cursor: pointer;
            transition: all 0.3s;
            border: 4px solid var(--border-light);
            box-shadow: var(--shadow);
            z-index: 2;
        }

        .profile-avatar:hover {
            transform: scale(1.05) rotate(5deg);
            border-color: var(--accent-primary);
        }

        .avatar-edit-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 24px;
            backdrop-filter: blur(2px);
        }

        .profile-avatar:hover .avatar-edit-overlay {
            opacity: 1;
        }

        .profile-info {
            flex: 1;
            z-index: 2;
        }

        .profile-info h1 {
            color: var(--text-primary);
            margin-bottom: 12px;
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--text-primary), var(--accent-primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .profile-info p {
            color: var(--text-muted);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 16px;
        }

        .profile-info i {
            color: var(--accent-primary);
            width: 20px;
            font-size: 16px;
        }

        .profile-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: var(--bg-hover);
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            font-size: 14px;
        }

        /* Profile Stats - Enhanced */
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--bg-card);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            border: 1px solid var(--border-color);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent-primary);
            box-shadow: 0 10px 20px -5px rgba(255, 107, 74, 0.2);
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-primary), var(--accent-secondary));
            transform: scaleX(0);
            transition: transform 0.3s;
        }

        .stat-card:hover::after {
            transform: scaleX(1);
        }

        .stat-icon {
            font-size: 36px;
            color: var(--accent-primary);
            margin-bottom: 15px;
        }

        .stat-number {
            font-size: 42px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 10px 0;
            line-height: 1;
        }

        .stat-label {
            font-size: 15px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Profile Details - Enhanced */
        .profile-details {
            background-color: var(--bg-card);
            border-radius: 16px;
            padding: 32px;
            border: 1px solid var(--border-color);
            margin-bottom: 30px;
        }

        .details-title {
            color: var(--text-primary);
            margin-bottom: 25px;
            font-size: 22px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }

        .details-title i {
            color: var(--accent-primary);
            font-size: 24px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px 35px;
        }

        .detail-item {
            margin-bottom: 0;
            padding: 10px;
            border-radius: 8px;
            transition: background-color 0.2s;
        }

        .detail-item:hover {
            background-color: var(--bg-hover);
        }

        .detail-label {
            color: var(--text-muted);
            font-size: 13px;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            color: var(--text-primary);
            font-size: 18px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .verified-badge,
        .not-verified-badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .verified-badge {
            background-color: rgba(74, 222, 128, 0.1);
            color: var(--accent-secondary);
            border: 1px solid rgba(74, 222, 128, 0.2);
        }

        .not-verified-badge {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--accent-danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        /* Recent Routes Section - New */
        .recent-routes {
            background-color: var(--bg-card);
            border-radius: 16px;
            padding: 32px;
            border: 1px solid var(--border-color);
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }

        .section-header i {
            color: var(--accent-primary);
            font-size: 24px;
        }

        .section-header h2 {
            color: var(--text-primary);
            font-size: 22px;
            font-weight: 600;
            flex: 1;
        }

        .view-all-link {
            color: var(--accent-primary);
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 6px;
            background-color: var(--bg-hover);
            transition: all 0.2s;
        }

        .view-all-link:hover {
            background-color: var(--accent-primary);
            color: white;
        }

        .routes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .route-card {
            background-color: var(--bg-secondary);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--border-color);
            transition: all 0.3s;
            position: relative;
        }

        .route-card:hover {
            transform: translateY(-3px);
            border-color: var(--accent-primary);
            box-shadow: 0 8px 16px rgba(255, 107, 74, 0.1);
        }

        .route-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }

        .route-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, rgba(255, 107, 74, 0.1), rgba(74, 222, 128, 0.1));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-primary);
            font-size: 18px;
        }

        .route-locations {
            flex: 1;
        }

        .route-from {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 16px;
        }

        .route-to {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .route-arrow {
            color: var(--text-muted);
            margin: 0 4px;
        }

        .route-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px dashed var(--border-color);
        }

        .route-info {
            text-align: center;
        }

        .route-info-label {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        .route-info-value {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .route-info-value.regular {
            color: var(--accent-primary);
        }

        .route-info-value.student {
            color: var(--accent-secondary);
        }

        .route-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .route-date {
            font-size: 12px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .route-actions {
            display: flex;
            gap: 8px;
        }

        .route-btn {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            background-color: var(--bg-hover);
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .route-btn:hover {
            background-color: var(--accent-primary);
            color: white;
            border-color: var(--accent-primary);
        }

        .route-btn.delete:hover {
            background-color: var(--accent-danger);
            border-color: var(--accent-danger);
        }

        .empty-routes {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
            background-color: var(--bg-secondary);
            border-radius: 12px;
            border: 2px dashed var(--border-color);
        }

        .empty-routes i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        .empty-routes p {
            margin-bottom: 20px;
            font-size: 16px;
        }

        .explore-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background-color: var(--accent-primary);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .explore-btn:hover {
            background-color: #ff5a3a;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(255, 107, 74, 0.3);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 16px 32px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: 'Bornomala', serif;
            font-size: 16px;
            flex: 1;
            justify-content: center;
        }

        .edit-profile-btn {
            background: linear-gradient(135deg, var(--accent-primary), #ff8a6a);
            color: white;
            box-shadow: 0 4px 12px rgba(255, 107, 74, 0.3);
        }

        .edit-profile-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 107, 74, 0.4);
        }

        .change-password-btn {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            border: 2px solid var(--border-color);
        }

        .change-password-btn:hover {
            border-color: var(--accent-primary);
            background-color: var(--bg-hover);
            transform: translateY(-2px);
        }

        /* Modal Styles - Enhanced */
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
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: linear-gradient(135deg, var(--bg-card), var(--bg-secondary));
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            border: 2px solid var(--accent-primary);
            position: relative;
            animation: slideUp 0.3s;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
            text-align: center;
        }

        .modal-title {
            color: var(--accent-primary);
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .modal-subtitle {
            color: var(--text-muted);
            font-size: 15px;
        }

        .close-modal {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: var(--bg-hover);
            color: var(--text-secondary);
            border: 2px solid var(--border-color);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .close-modal:hover {
            background-color: var(--accent-danger);
            color: white;
            border-color: var(--accent-danger);
            transform: rotate(90deg);
        }

        /* Form Styles - Enhanced from index.php */
        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: var(--text-secondary);
            font-size: 15px;
            font-weight: 500;
        }

        .form-group label i {
            color: var(--accent-primary);
            margin-right: 8px;
        }

        .form-control {
            width: 100%;
            padding: 16px 20px;
            background-color: var(--bg-secondary);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-family: 'Bornomala', serif;
            font-size: 16px;
            color: var(--text-primary);
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: var(--glow);
        }

        .form-control.error {
            border-color: var(--accent-danger);
            animation: shake 0.3s;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            75% {
                transform: translateX(5px);
            }
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        select.form-control {
            cursor: pointer;
        }

        .error-message {
            color: var(--accent-danger);
            font-size: 13px;
            margin-top: 8px;
            display: none;
            align-items: center;
            gap: 6px;
        }

        .error-message.active {
            display: flex;
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .modal-btn {
            padding: 16px 28px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            flex: 1;
            font-family: 'Bornomala', serif;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .save-btn {
            background: linear-gradient(135deg, var(--accent-primary), #ff8a6a);
            color: white;
            box-shadow: 0 4px 12px rgba(255, 107, 74, 0.3);
        }

        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 107, 74, 0.4);
        }

        .cancel-btn {
            background-color: var(--bg-secondary);
            color: var(--text-secondary);
            border: 2px solid var(--border-color);
        }

        .cancel-btn:hover {
            border-color: var(--accent-primary);
            background-color: var(--bg-hover);
            color: var(--text-primary);
        }

        /* Password strength indicator - From index.php */
        .password-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 18px;
            transition: color 0.2s;
        }

        .password-toggle:hover {
            color: var(--accent-primary);
        }

        .password-strength {
            margin-top: 10px;
        }

        .strength-bar {
            height: 6px;
            background-color: var(--border-color);
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 6px;
        }

        .strength-fill {
            height: 100%;
            width: 0;
            transition: all 0.3s;
            border-radius: 3px;
        }

        .strength-text {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        .password-requirements {
            background-color: var(--bg-secondary);
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border: 1px solid var(--border-color);
        }

        .password-requirements strong {
            color: var(--text-primary);
            display: block;
            margin-bottom: 10px;
        }

        .password-requirements ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .password-requirements li {
            color: var(--text-muted);
            margin-bottom: 8px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .password-requirements li::before {
            content: '○';
            color: var(--text-muted);
        }

        .password-requirements li.valid {
            color: var(--accent-secondary);
        }

        .password-requirements li.valid::before {
            content: '✓';
            color: var(--accent-secondary);
        }

        .password-match {
            font-size: 13px;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .match-success {
            color: var(--accent-secondary);
        }

        .match-error {
            color: var(--accent-danger);
        }

        /* Delete Confirmation Modal */
        .delete-modal .modal-content {
            max-width: 400px;
            text-align: center;
        }

        .delete-icon {
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

        .delete-title {
            color: var(--text-primary);
            font-size: 24px;
            margin-bottom: 10px;
        }

        .delete-text {
            color: var(--text-muted);
            margin-bottom: 30px;
        }

        .delete-actions {
            display: flex;
            gap: 15px;
        }

        .delete-btn {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Bornomala', serif;
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 24px 16px;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
                padding: 30px 20px;
            }

            .profile-info p {
                justify-content: center;
            }

            .details-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .routes-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .modal-actions {
                flex-direction: column;
            }

            .modal-content {
                padding: 30px 20px;
            }
        }

        @media (max-width: 480px) {
            .profile-stats {
                grid-template-columns: 1fr;
            }

            .route-details {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }

            .route-info {
                width: 100%;
                display: flex;
                justify-content: space-between;
            }

            .route-info-label {
                margin-bottom: 0;
            }
        }

        /* Loading Spinner - From index.php */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar" id="avatarContainer">
                <?php
                $initial = isset($user['full_name']) ? strtoupper(mb_substr($user['full_name'], 0, 1)) : 'ই';
                echo $initial;
                ?>
                <div class="avatar-edit-overlay">
                    <i class="fas fa-camera"></i>
                </div>
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($user['full_name'] ?? 'ব্যবহারকারী'); ?></h1>
                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                <?php if ($user['phone']): ?>
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone']); ?></p>
                <?php endif; ?>
                <div class="profile-badge">
                    <i class="fas fa-calendar-alt"></i>
                    সদস্য হয়েছেন: <?php echo date('F Y', strtotime($user['created_at'])); ?>
                </div>
            </div>
        </div>

        <!-- Profile Stats -->
        <div class="profile-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-route"></i>
                </div>
                <div class="stat-number"><?php echo $route_count; ?></div>
                <div class="stat-label">সংরক্ষিত রুট</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number">২৪/৭</div>
                <div class="stat-label">নিরাপত্তা সুরক্ষা</div>
            </div>
        </div>

        <!-- Account Details -->
        <div class="profile-details">
            <h2 class="details-title">
                <i class="fas fa-user-circle"></i> অ্যাকাউন্ট তথ্য
            </h2>

            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-label">পুরো নাম</div>
                    <div class="detail-value">
                        <i class="fas fa-user"></i>
                        <?php echo htmlspecialchars($user['full_name']); ?>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">ইমেইল</div>
                    <div class="detail-value">
                        <i class="fas fa-envelope"></i>
                        <?php echo htmlspecialchars($user['email']); ?>
                        <?php if ($user['email_verified']): ?>
                            <span class="verified-badge"><i class="fas fa-check-circle"></i> ভেরিফাইড</span>
                        <?php else: ?>
                            <span class="not-verified-badge"><i class="fas fa-exclamation-circle"></i> ভেরিফাইড নয়</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">ফোন নম্বর</div>
                    <div class="detail-value">
                        <i class="fas fa-phone"></i>
                        <?php echo $user['phone'] ? htmlspecialchars($user['phone']) : 'দেওয়া হয়নি'; ?>
                        <?php if ($user['phone_verified']): ?>
                            <span class="verified-badge"><i class="fas fa-check-circle"></i> ভেরিফাইড</span>
                        <?php elseif ($user['phone']): ?>
                            <span class="not-verified-badge"><i class="fas fa-exclamation-circle"></i> ভেরিফাইড নয়</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($user['date_of_birth']): ?>
                    <div class="detail-item">
                        <div class="detail-label">জন্ম তারিখ</div>
                        <div class="detail-value">
                            <i class="fas fa-calendar"></i>
                            <?php echo date('d F, Y', strtotime($user['date_of_birth'])); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($user['gender']): ?>
                    <div class="detail-item">
                        <div class="detail-label">লিঙ্গ</div>
                        <div class="detail-value">
                            <i class="fas fa-venus-mars"></i>
                            <?php
                            $genders = [
                                'male' => 'পুরুষ',
                                'female' => 'মহিলা',
                                'other' => 'অন্যান্য',
                                'prefer_not_to_say' => 'বলতে চাই না'
                            ];
                            echo $genders[$user['gender']] ?? $user['gender'];
                            ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($user['address']): ?>
                    <div class="detail-item" style="grid-column: span 2;">
                        <div class="detail-label">ঠিকানা</div>
                        <div class="detail-value">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($user['address']); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($user['bio']): ?>
                    <div class="detail-item" style="grid-column: span 2;">
                        <div class="detail-label">সম্পর্কে</div>
                        <div class="detail-value">
                            <i class="fas fa-info-circle"></i>
                            <?php echo nl2br(htmlspecialchars($user['bio'])); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="detail-item">
                    <div class="detail-label">অ্যাকাউন্ট টাইপ</div>
                    <div class="detail-value">
                        <?php echo $user['google_id'] ? 'গুগল অ্যাকাউন্ট' : 'সাধারণ অ্যাকাউন্ট'; ?>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="action-btn edit-profile-btn" id="editProfileBtn">
                    <i class="fas fa-edit"></i> প্রোফাইল সম্পাদনা
                </button>
                <button class="action-btn change-password-btn" id="changePasswordBtn">
                    <i class="fas fa-key"></i> পাসওয়ার্ড পরিবর্তন
                </button>
            </div>
        </div>

        <?php
        // Get user's incident reports
        $reports_sql = "SELECT id, title, incident_type, severity, status, created_at 
                FROM incident_reports 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 5";
        $reports_stmt = $conn->prepare($reports_sql);
        $reports_stmt->bind_param("i", $user_id);
        $reports_stmt->execute();
        $reports_result = $reports_stmt->get_result();
        $user_reports = [];
        while ($report = $reports_result->fetch_assoc()) {
            $user_reports[] = $report;
        }
        $reports_stmt->close();

        // Get counts by status
        $status_counts = [
            'pending' => 0,
            'reviewing' => 0,
            'published' => 0,
            'rejected' => 0
        ];

        $count_sql = "SELECT status, COUNT(*) as count FROM incident_reports WHERE user_id = ? GROUP BY status";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param("i", $user_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        while ($count = $count_result->fetch_assoc()) {
            $status_counts[$count['status']] = $count['count'];
        }
        $count_stmt->close();
        ?>

        <!-- My Reports Section -->
        <div class="recent-routes">
            <div class="section-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h2>আমার রিপোর্টসমূহ</h2>
                <?php if (count($user_reports) > 0): ?>
                    <a href="my_reports.php" class="view-all-link">
                        <i class="fas fa-arrow-right"></i> সব দেখুন
                    </a>
                <?php endif; ?>
            </div>

            <!-- Report Status Summary -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 25px;">
                <div style="background-color: var(--bg-secondary); padding: 15px; border-radius: 8px; text-align: center; border: 1px solid var(--border-color);">
                    <div style="font-size: 24px; font-weight: 700; color: var(--accent-warning);"><?php echo $status_counts['pending']; ?></div>
                    <div style="font-size: 13px; color: var(--text-muted);">অপেক্ষমান</div>
                </div>
                <div style="background-color: var(--bg-secondary); padding: 15px; border-radius: 8px; text-align: center; border: 1px solid var(--border-color);">
                    <div style="font-size: 24px; font-weight: 700; color: var(--accent-info);"><?php echo $status_counts['reviewing']; ?></div>
                    <div style="font-size: 13px; color: var(--text-muted);">পর্যালোচনাধীন</div>
                </div>
                <div style="background-color: var(--bg-secondary); padding: 15px; border-radius: 8px; text-align: center; border: 1px solid var(--border-color);">
                    <div style="font-size: 24px; font-weight: 700; color: var(--accent-secondary);"><?php echo $status_counts['published']; ?></div>
                    <div style="font-size: 13px; color: var(--text-muted);">প্রকাশিত</div>
                </div>
                <div style="background-color: var(--bg-secondary); padding: 15px; border-radius: 8px; text-align: center; border: 1px solid var(--border-color);">
                    <div style="font-size: 24px; font-weight: 700; color: var(--accent-danger);"><?php echo $status_counts['rejected']; ?></div>
                    <div style="font-size: 13px; color: var(--text-muted);">বাতিল</div>
                </div>
            </div>

            <?php if (count($user_reports) > 0): ?>
                <div style="display: grid; grid-template-columns: 1fr; gap: 15px;">
                    <?php foreach ($user_reports as $report): ?>
                        <div style="background-color: var(--bg-secondary); border-radius: 10px; padding: 20px; border: 1px solid var(--border-color);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
                                <div>
                                    <h4 style="color: var(--text-primary); font-size: 18px; margin-bottom: 5px;">
                                        <?php echo htmlspecialchars($report['title']); ?>
                                    </h4>
                                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                        <span style="font-size: 13px; color: var(--text-muted);">
                                            <i class="fas fa-calendar"></i>
                                            <?php echo date('d M Y', strtotime($report['created_at'])); ?>
                                        </span>
                                        <span style="font-size: 13px; color: var(--text-muted);">
                                            <i class="fas fa-tag"></i>
                                            <?php
                                            $types = [
                                                'harassment' => 'হয়রানি',
                                                'assault' => 'সহিংসতা',
                                                'theft' => 'চুরি',
                                                'overcharging' => 'অতিরিক্ত ভাড়া',
                                                'misbehavior' => 'অসদাচরণ',
                                                'other' => 'অন্যান্য'
                                            ];
                                            echo $types[$report['incident_type']] ?? $report['incident_type'];
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <?php
                                    $status_colors = [
                                        'pending' => 'var(--accent-warning)',
                                        'reviewing' => 'var(--accent-info)',
                                        'published' => 'var(--accent-secondary)',
                                        'rejected' => 'var(--accent-danger)'
                                    ];
                                    $status_texts = [
                                        'pending' => 'অপেক্ষমান',
                                        'reviewing' => 'পর্যালোচনাধীন',
                                        'published' => 'প্রকাশিত',
                                        'rejected' => 'বাতিল'
                                    ];
                                    ?>
                                    <span style="background-color: <?php echo $status_colors[$report['status']]; ?>20; color: <?php echo $status_colors[$report['status']]; ?>; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                        <?php echo $status_texts[$report['status']]; ?>
                                    </span>
                                    <a href="view_my_report.php?id=<?php echo $report['id']; ?>" style="color: var(--accent-primary); text-decoration: none; padding: 6px 12px; border-radius: 6px; background-color: var(--bg-hover);">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>

                            <?php if ($report['status'] == 'published'): ?>
                                <?php
                                // Check if this report has been published
                                $pub_sql = "SELECT id FROM published_reports WHERE incident_report_id = ? LIMIT 1";
                                $pub_stmt = $conn->prepare($pub_sql);
                                $pub_stmt->bind_param("i", $report['id']);
                                $pub_stmt->execute();
                                $pub_result = $pub_stmt->get_result();
                                if ($pub_result->num_rows > 0):
                                    $pub_report = $pub_result->fetch_assoc();
                                ?>
                                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed var(--border-color);">
                                        <a href="view_published_report.php?id=<?php echo $pub_report['id']; ?>" style="display: inline-flex; align-items: center; gap: 8px; color: var(--accent-secondary); text-decoration: none;">
                                            <i class="fas fa-newspaper"></i>
                                            <span>প্রকাশিত প্রতিবেদন দেখুন</span>
                                            <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($report['status'] == 'rejected'): ?>
                                <div style="margin-top: 15px; padding: 12px; background-color: rgba(239, 68, 68, 0.05); border-radius: 6px; font-size: 13px; color: var(--text-muted);">
                                    <i class="fas fa-info-circle" style="color: var(--accent-danger); margin-right: 8px;"></i>
                                    এই রিপোর্টটি প্রশাসন কর্তৃক বাতিল করা হয়েছে।
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-routes">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>আপনি এখনো কোনো রিপোর্ট জমা দেননি</p>
                    <a href="report_incident.php" class="explore-btn">
                        <i class="fas fa-pen"></i>
                        রিপোর্ট জমা দিন
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal-overlay" id="editProfileModal">
        <div class="modal-content">
            <button class="close-modal" id="closeEditProfileModal">&times;</button>
            <div class="modal-header">
                <h2 class="modal-title">প্রোফাইল সম্পাদনা</h2>
                <p class="modal-subtitle">আপনার ব্যক্তিগত তথ্য আপডেট করুন</p>
            </div>

            <form id="editProfileForm" method="POST" action="update_profile.php">
                <div class="form-group">
                    <label for="editFullName">
                        <i class="fas fa-user"></i>
                        পুরো নাম *
                    </label>
                    <input type="text" id="editFullName" name="full_name" class="form-control"
                        value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    <div class="error-message" id="fullNameError"></div>
                </div>

                <div class="form-group">
                    <label for="editPhone">
                        <i class="fas fa-phone"></i>
                        ফোন নম্বর
                    </label>
                    <input type="tel" id="editPhone" name="phone" class="form-control"
                        value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                        placeholder="০১XXXXXXXXX">
                    <div class="error-message" id="phoneError"></div>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 5px;">
                        উদাহরণ: ০১৭১২৩৪৫৬৭৮
                    </div>
                </div>

                <div class="form-group">
                    <label for="editDateOfBirth">
                        <i class="fas fa-calendar"></i>
                        জন্ম তারিখ
                    </label>
                    <input type="date" id="editDateOfBirth" name="date_of_birth" class="form-control"
                        value="<?php echo $user['date_of_birth'] ?? ''; ?>"
                        max="<?php echo date('Y-m-d', strtotime('-13 years')); ?>">
                </div>

                <div class="form-group">
                    <label for="editGender">
                        <i class="fas fa-venus-mars"></i>
                        লিঙ্গ
                    </label>
                    <select id="editGender" name="gender" class="form-control">
                        <option value="">লিঙ্গ নির্বাচন করুন</option>
                        <option value="male" <?php echo ($user['gender'] ?? '') == 'male' ? 'selected' : ''; ?>>পুরুষ</option>
                        <option value="female" <?php echo ($user['gender'] ?? '') == 'female' ? 'selected' : ''; ?>>মহিলা</option>
                        <option value="other" <?php echo ($user['gender'] ?? '') == 'other' ? 'selected' : ''; ?>>অন্যান্য</option>
                        <option value="prefer_not_to_say" <?php echo ($user['gender'] ?? '') == 'prefer_not_to_say' ? 'selected' : ''; ?>>বলতে চাই না</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="editAddress">
                        <i class="fas fa-map-marker-alt"></i>
                        ঠিকানা
                    </label>
                    <textarea id="editAddress" name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="editBio">
                        <i class="fas fa-info-circle"></i>
                        বায়ো (আপনার সম্পর্কে)
                    </label>
                    <textarea id="editBio" name="bio" class="form-control" rows="4"
                        placeholder="আপনার সম্পর্কে কিছু বলুন..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="modal-btn cancel-btn" id="cancelEditProfile">
                        <i class="fas fa-times"></i> বাতিল
                    </button>
                    <button type="submit" class="modal-btn save-btn">
                        <i class="fas fa-save"></i> সংরক্ষণ করুন
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal-overlay" id="changePasswordModal">
        <div class="modal-content">
            <button class="close-modal" id="closeChangePasswordModal">&times;</button>
            <div class="modal-header">
                <h2 class="modal-title">পাসওয়ার্ড পরিবর্তন</h2>
                <p class="modal-subtitle">নিরাপত্তার জন্য আপনার পাসওয়ার্ড আপডেট করুন</p>
            </div>

            <form id="changePasswordForm" method="POST" action="change_password.php">
                <?php if (empty($user['google_id'])): ?>
                    <div class="form-group">
                        <label for="currentPassword">
                            <i class="fas fa-lock"></i>
                            বর্তমান পাসওয়ার্ড *
                        </label>
                        <div class="password-container">
                            <input type="password" id="currentPassword" name="current_password" class="form-control" required>
                            <button type="button" class="password-toggle" data-target="currentPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="error-message" id="currentPasswordError"></div>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="is_google_account" value="1">
                    <div class="info-alert message-alert" style="margin-bottom: 20px;">
                        <i class="fas fa-info-circle"></i>
                        আপনি গুগল অ্যাকাউন্ট দিয়ে লগইন করেছেন। পাসওয়ার্ড পরিবর্তনের প্রয়োজন নেই।
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="newPassword">
                        <i class="fas fa-lock"></i>
                        নতুন পাসওয়ার্ড *
                    </label>
                    <div class="password-container">
                        <input type="password" id="newPassword" name="new_password" class="form-control" required>
                        <button type="button" class="password-toggle" data-target="newPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="strength-bar">
                            <div id="passwordStrengthBar" class="strength-fill"></div>
                        </div>
                        <div id="passwordStrengthText" class="strength-text"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirmPassword">
                        <i class="fas fa-lock"></i>
                        নতুন পাসওয়ার্ড নিশ্চিত করুন *
                    </label>
                    <div class="password-container">
                        <input type="password" id="confirmPassword" name="confirm_password" class="form-control" required>
                        <button type="button" class="password-toggle" data-target="confirmPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div id="passwordMatch" class="password-match"></div>
                </div>

                <div class="password-requirements">
                    <strong>পাসওয়ার্ড শর্তসমূহ:</strong>
                    <ul>
                        <li id="reqLength">কমপক্ষে ৮ অক্ষর</li>
                        <li id="reqUppercase">কমপক্ষে ১টি বড় হাতের অক্ষর</li>
                        <li id="reqLowercase">কমপক্ষে ১টি ছোট হাতের অক্ষর</li>
                        <li id="reqNumber">কমপক্ষে ১টি সংখ্যা</li>
                        <li id="reqSpecial">কমপক্ষে ১টি বিশেষ অক্ষর (!@#$%^&*)</li>
                    </ul>
                </div>

                <div class="modal-actions">
                    <button type="button" class="modal-btn cancel-btn" id="cancelChangePassword">
                        <i class="fas fa-times"></i> বাতিল
                    </button>
                    <button type="submit" class="modal-btn save-btn">
                        <i class="fas fa-key"></i> পাসওয়ার্ড আপডেট করুন
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const editProfileBtn = document.getElementById('editProfileBtn');
        const changePasswordBtn = document.getElementById('changePasswordBtn');
        const editProfileModal = document.getElementById('editProfileModal');
        const changePasswordModal = document.getElementById('changePasswordModal');
        const deleteRouteModal = document.getElementById('deleteRouteModal');

        // Open Edit Profile Modal
        editProfileBtn.addEventListener('click', () => {
            editProfileModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        // Open Change Password Modal
        changePasswordBtn.addEventListener('click', () => {
            changePasswordModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        // Close all modals function
        function closeAllModals() {
            editProfileModal.classList.remove('active');
            changePasswordModal.classList.remove('active');
            deleteRouteModal.classList.remove('active');
            document.body.style.overflow = 'auto';
            currentRouteToDelete = null;
        }

        // Modal close buttons
        document.getElementById('closeEditProfileModal').addEventListener('click', closeAllModals);
        document.getElementById('closeChangePasswordModal').addEventListener('click', closeAllModals);
        document.getElementById('cancelEditProfile').addEventListener('click', closeAllModals);
        document.getElementById('cancelChangePassword').addEventListener('click', closeAllModals);
        document.getElementById('cancelDelete').addEventListener('click', closeAllModals);

        // Close modal when clicking overlay
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeAllModals();
                }
            });
        });

        // Close modal with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeAllModals();
            }
        });

        document.querySelectorAll('.password-toggle').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });

        const newPasswordInput = document.getElementById('newPassword');
        const passwordStrengthBar = document.getElementById('passwordStrengthBar');
        const passwordStrengthText = document.getElementById('passwordStrengthText');
        const passwordRequirements = {
            length: document.getElementById('reqLength'),
            uppercase: document.getElementById('reqUppercase'),
            lowercase: document.getElementById('reqLowercase'),
            number: document.getElementById('reqNumber'),
            special: document.getElementById('reqSpecial')
        };

        if (newPasswordInput) {
            newPasswordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                const hasLength = password.length >= 8;
                const hasUppercase = /[A-Z]/.test(password);
                const hasLowercase = /[a-z]/.test(password);
                const hasNumber = /[0-9]/.test(password);
                const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);

                // Update requirement indicators
                if (passwordRequirements.length) {
                    passwordRequirements.length.classList.toggle('valid', hasLength);
                }
                if (passwordRequirements.uppercase) {
                    passwordRequirements.uppercase.classList.toggle('valid', hasUppercase);
                }
                if (passwordRequirements.lowercase) {
                    passwordRequirements.lowercase.classList.toggle('valid', hasLowercase);
                }
                if (passwordRequirements.number) {
                    passwordRequirements.number.classList.toggle('valid', hasNumber);
                }
                if (passwordRequirements.special) {
                    passwordRequirements.special.classList.toggle('valid', hasSpecial);
                }

                // Calculate strength
                if (hasLength) strength += 20;
                if (hasUppercase) strength += 20;
                if (hasLowercase) strength += 20;
                if (hasNumber) strength += 20;
                if (hasSpecial) strength += 20;

                if (passwordStrengthBar) {
                    passwordStrengthBar.style.width = strength + '%';

                    if (strength < 40) {
                        passwordStrengthBar.style.backgroundColor = '#ef4444';
                        if (passwordStrengthText) passwordStrengthText.textContent = 'দুর্বল';
                    } else if (strength < 60) {
                        passwordStrengthBar.style.backgroundColor = '#f97316';
                        if (passwordStrengthText) passwordStrengthText.textContent = 'মাঝারি';
                    } else if (strength < 80) {
                        passwordStrengthBar.style.backgroundColor = '#4ade80';
                        if (passwordStrengthText) passwordStrengthText.textContent = 'ভালো';
                    } else {
                        passwordStrengthBar.style.backgroundColor = '#22c55e';
                        if (passwordStrengthText) passwordStrengthText.textContent = 'শক্তিশালী';
                    }
                }
            });
        }

        const confirmPasswordInput = document.getElementById('confirmPassword');
        if (newPasswordInput && confirmPasswordInput) {
            function checkPasswordMatch() {
                const password = newPasswordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                const matchDiv = document.getElementById('passwordMatch');

                if (matchDiv) {
                    if (confirmPassword) {
                        if (password === confirmPassword) {
                            matchDiv.innerHTML = '<i class="fas fa-check-circle"></i> পাসওয়ার্ড মিলেছে';
                            matchDiv.className = 'password-match match-success';
                        } else {
                            matchDiv.innerHTML = '<i class="fas fa-times-circle"></i> পাসওয়ার্ড মেলেনি';
                            matchDiv.className = 'password-match match-error';
                        }
                    } else {
                        matchDiv.innerHTML = '';
                    }
                }
            }

            newPasswordInput.addEventListener('input', checkPasswordMatch);
            confirmPasswordInput.addEventListener('input', checkPasswordMatch);
        }

        const editProfileForm = document.getElementById('editProfileForm');
        if (editProfileForm) {
            editProfileForm.addEventListener('submit', function(e) {
                e.preventDefault();

                let isValid = true;

                // Clear previous errors
                document.querySelectorAll('.error-message').forEach(el => {
                    el.classList.remove('active');
                });
                document.querySelectorAll('.form-control').forEach(el => {
                    el.classList.remove('error');
                });

                // Validate full name
                const fullName = document.getElementById('editFullName').value.trim();
                if (!fullName || fullName.length < 2) {
                    document.getElementById('fullNameError').textContent = 'নাম কমপক্ষে ২ অক্ষরের হতে হবে';
                    document.getElementById('fullNameError').classList.add('active');
                    document.getElementById('editFullName').classList.add('error');
                    isValid = false;
                }

                // Validate phone (if provided)
                const phone = document.getElementById('editPhone').value.trim();
                if (phone && !/^01[0-9]{9}$/.test(phone)) {
                    document.getElementById('phoneError').textContent = 'সঠিক বাংলাদেশি ফোন নম্বর দিন (০১XXXXXXXXX)';
                    document.getElementById('phoneError').classList.add('active');
                    document.getElementById('editPhone').classList.add('error');
                    isValid = false;
                }

                if (isValid) {
                    this.submit();
                }
            });
        }

        const changePasswordForm = document.getElementById('changePasswordForm');
        if (changePasswordForm) {
            changePasswordForm.addEventListener('submit', function(e) {
                e.preventDefault();

                let isValid = true;

                // Clear previous errors
                document.querySelectorAll('.error-message').forEach(el => {
                    el.classList.remove('active');
                });
                document.querySelectorAll('.form-control').forEach(el => {
                    el.classList.remove('error');
                });

                const newPassword = document.getElementById('newPassword').value;
                const confirmPassword = document.getElementById('confirmPassword').value;

                // Check password strength
                const hasLength = newPassword.length >= 8;
                const hasUppercase = /[A-Z]/.test(newPassword);
                const hasLowercase = /[a-z]/.test(newPassword);
                const hasNumber = /[0-9]/.test(newPassword);
                const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(newPassword);

                if (!hasLength || !hasUppercase || !hasLowercase || !hasNumber || !hasSpecial) {
                    document.getElementById('newPassword').classList.add('error');
                    isValid = false;
                }

                // Check password match
                if (newPassword !== confirmPassword) {
                    document.getElementById('confirmPassword').classList.add('error');
                    isValid = false;
                }

                <?php if (empty($user['google_id'])): ?>
                    const currentPassword = document.getElementById('currentPassword');
                    if (currentPassword && !currentPassword.value) {
                        currentPassword.classList.add('error');
                        isValid = false;
                    }
                <?php endif; ?>

                if (isValid) {
                    this.submit();
                }
            });
        }

        setTimeout(() => {
            document.querySelectorAll('.message-alert').forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>

</html>
<?php
$conn->close();
include_once 'footer.php';
?>