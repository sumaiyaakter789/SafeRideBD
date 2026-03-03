<?php
session_start();
include_once 'navbar.php';
include_once 'db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=report_incident");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = null;

// Get user details for auto-fill
$sql = "SELECT full_name, email, phone, address FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
}
$stmt->close();

// Get locations for incident location selection
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
    <title>ঘটনা রিপোর্ট - SafeRideBD</title>
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
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 24px;
            flex: 1;
        }

        /* Page Header */
        .page-header {
            text-align: center;
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
            max-width: 700px;
            margin: 0 auto 20px;
            line-height: 1.8;
        }

        .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: var(--bg-card);
            padding: 8px 16px;
            border-radius: 8px;
            color: var(--accent-secondary);
            font-size: 14px;
            border: 1px solid var(--border-color);
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

        /* Main Card */
        .report-card {
            background: linear-gradient(135deg, var(--bg-card), var(--bg-secondary));
            border-radius: 20px;
            padding: 40px;
            border: 2px solid var(--border-color);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .card-title {
            color: var(--text-primary);
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }

        .card-title i {
            color: var(--accent-danger);
            font-size: 28px;
        }

        /* Form Sections */
        .form-section {
            background-color: var(--bg-secondary);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .section-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(251, 191, 36, 0.1));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-danger);
            font-size: 20px;
        }

        .section-title {
            color: var(--text-primary);
            font-size: 22px;
            font-weight: 600;
            flex: 1;
        }

        .section-badge {
            background-color: var(--accent-danger);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
        }

        /* Form Grid */
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
            margin-bottom: 10px;
            color: var(--text-secondary);
            font-size: 15px;
            font-weight: 500;
        }

        .form-group label i {
            color: var(--accent-danger);
            margin-right: 8px;
        }

        .form-control {
            width: 100%;
            padding: 16px 20px;
            background-color: var(--bg-primary);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-family: 'Bornomala', serif;
            font-size: 16px;
            color: var(--text-primary);
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-danger);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
        }

        .form-control.error {
            border-color: var(--accent-danger);
            animation: shake 0.3s;
        }

        .form-control:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            background-color: var(--bg-secondary);
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
            min-height: 120px;
        }

        select.form-control {
            cursor: pointer;
        }

        /* Typeahead (from index.php) */
        .typeahead-container {
            position: relative;
        }

        .select-container {
            position: relative;
        }

        .select-container i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            z-index: 2;
            font-size: 18px;
        }

        .typeahead-input {
            width: 100%;
            padding: 16px 20px 16px 48px;
            background-color: var(--bg-primary);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-family: 'Bornomala', serif;
            font-size: 16px;
            color: var(--text-primary);
            transition: all 0.3s;
        }

        .typeahead-input:focus {
            outline: none;
            border-color: var(--accent-danger);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
        }

        .typeahead-input::placeholder {
            color: var(--text-muted);
        }

        .suggestions-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background-color: var(--bg-card);
            border: 2px solid var(--border-color);
            border-radius: 0 0 10px 10px;
            max-height: 250px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: var(--shadow);
            margin-top: 2px;
        }

        .suggestions-dropdown.active {
            display: block;
        }

        .suggestion-item {
            padding: 14px 20px;
            cursor: pointer;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border-color);
            transition: all 0.2s;
        }

        .suggestion-item:last-child {
            border-bottom: none;
        }

        .suggestion-item:hover {
            background-color: var(--bg-hover);
            color: var(--text-primary);
        }

        /* Toggle Switch for Anonymous */
        .toggle-container {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            background-color: var(--bg-primary);
            padding: 20px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }

        .toggle-info {
            flex: 1;
        }

        .toggle-title {
            color: var(--text-primary);
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .toggle-title i {
            color: var(--accent-danger);
        }

        .toggle-desc {
            color: var(--text-muted);
            font-size: 13px;
        }

        .toggle-switch {
            position: relative;
            width: 70px;
            height: 34px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
            position: absolute;
            margin: 0;
            padding: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--bg-hover);
            transition: .3s;
            border-radius: 34px;
            border: 2px solid var(--border-color);
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 2px;
            bottom: 2px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
        }

        input:checked+.toggle-slider {
            background-color: var(--accent-danger);
            border-color: var(--accent-danger);
        }

        input:checked+.toggle-slider:before {
            transform: translateX(36px);
        }

        .toggle-status {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
            min-width: 60px;
        }

        /* File Upload */
        .file-upload-container {
            margin-top: 10px;
        }

        .file-upload-area {
            border: 2px dashed var(--border-color);
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            background-color: var(--bg-primary);
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }

        .file-upload-area:hover {
            border-color: var(--accent-danger);
            background-color: var(--bg-hover);
        }

        .file-upload-area.dragover {
            border-color: var(--accent-danger);
            background-color: rgba(239, 68, 68, 0.1);
        }

        .file-upload-icon {
            font-size: 48px;
            color: var(--accent-danger);
            margin-bottom: 15px;
        }

        .file-upload-text {
            color: var(--text-secondary);
            font-size: 16px;
            margin-bottom: 8px;
        }

        .file-upload-hint {
            color: var(--text-muted);
            font-size: 13px;
        }

        .file-upload-input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .file-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .file-preview-item {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 12px;
            position: relative;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .file-preview-image {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 8px;
        }

        .file-preview-icon {
            width: 100%;
            height: 120px;
            background-color: var(--bg-hover);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .file-preview-name {
            font-size: 12px;
            color: var(--text-secondary);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            margin-bottom: 4px;
        }

        .file-preview-size {
            font-size: 10px;
            color: var(--text-muted);
        }

        .file-remove-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background-color: var(--accent-danger);
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            transition: all 0.2s;
        }

        .file-remove-btn:hover {
            transform: scale(1.1);
            background-color: #dc2626;
        }

        /* Incident Type Cards */
        .incident-types {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .incident-type-card {
            background-color: var(--bg-primary);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .incident-type-card:hover {
            border-color: var(--accent-danger);
            transform: translateY(-3px);
        }

        .incident-type-card.selected {
            border-color: var(--accent-danger);
            background-color: rgba(239, 68, 68, 0.1);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
        }

        .incident-type-icon {
            font-size: 32px;
            color: var(--accent-danger);
            margin-bottom: 12px;
        }

        .incident-type-title {
            color: var(--text-primary);
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .incident-type-desc {
            color: var(--text-muted);
            font-size: 12px;
        }

        /* Severity Indicator */
        .severity-container {
            margin-top: 10px;
        }

        .severity-options {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .severity-option {
            flex: 1;
            min-width: 120px;
        }

        .severity-option input[type="radio"] {
            display: none;
        }

        .severity-option label {
            display: block;
            padding: 16px;
            text-align: center;
            background-color: var(--bg-primary);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            margin: 0;
        }

        .severity-option.low input:checked+label {
            border-color: #4ade80;
            background-color: rgba(74, 222, 128, 0.1);
            box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.2);
        }

        .severity-option.medium input:checked+label {
            border-color: #fbbf24;
            background-color: rgba(251, 191, 36, 0.1);
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.2);
        }

        .severity-option.high input:checked+label {
            border-color: #ef4444;
            background-color: rgba(239, 68, 68, 0.1);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
        }

        .severity-icon {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .severity-title {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .severity-desc {
            font-size: 11px;
            color: var(--text-muted);
        }

        .severity-option.low .severity-icon {
            color: #4ade80;
        }

        .severity-option.medium .severity-icon {
            color: #fbbf24;
        }

        .severity-option.high .severity-icon {
            color: #ef4444;
        }

        /* DateTime Input */
        .datetime-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .datetime-input {
            padding: 16px 20px;
            background-color: var(--bg-primary);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-family: 'Bornomala', serif;
            font-size: 16px;
            color: var(--text-primary);
            width: 100%;
        }

        .datetime-input:focus {
            outline: none;
            border-color: var(--accent-danger);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
        }

        /* Submit Button */
        .submit-btn {
            width: 100%;
            padding: 20px;
            background: linear-gradient(135deg, var(--accent-danger), #dc2626);
            color: white;
            border: none;
            border-radius: 12px;
            font-family: 'Bornomala', serif;
            font-size: 20px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
        }

        .submit-btn:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(239, 68, 68, 0.4);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .submit-btn i {
            font-size: 20px;
        }

        /* Error Messages */
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

        /* Guidelines */
        .guidelines-card {
            background-color: var(--bg-secondary);
            border-radius: 16px;
            padding: 30px;
            border: 1px solid var(--border-color);
        }

        .guidelines-title {
            color: var(--text-primary);
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .guidelines-title i {
            color: var(--accent-warning);
        }

        .guidelines-list {
            list-style: none;
        }

        .guidelines-list li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 15px;
            color: var(--text-secondary);
            font-size: 15px;
        }

        .guidelines-list li i {
            color: var(--accent-secondary);
            margin-top: 3px;
            font-size: 14px;
        }

        .guidelines-list li.warning i {
            color: var(--accent-warning);
        }

        .guidelines-list li.danger i {
            color: var(--accent-danger);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 24px 16px;
            }

            .page-title {
                font-size: 32px;
            }

            .report-card {
                padding: 30px 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full-width {
                grid-column: auto;
            }

            .incident-types {
                grid-template-columns: repeat(2, 1fr);
            }

            .datetime-grid {
                grid-template-columns: 1fr;
            }

            .severity-options {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .incident-types {
                grid-template-columns: 1fr;
            }

            .toggle-container {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        /* Loading Spinner */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
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
            <h1 class="page-title">ঘটনা রিপোর্ট করুন</h1>
            <p class="page-subtitle">পাবলিক পরিবহনে হয়রানি, সহিংসতা বা যেকোনো অস্বাভাবিক ঘটনা রিপোর্ট করুন। আপনার রিপোর্ট প্রশাসনিক পর্যায়ে পর্যালোচনা করা হবে।</p>
            <div class="security-badge">
                <i class="fas fa-shield-alt"></i> আপনার তথ্য সুরক্ষিত থাকবে
            </div>
        </div>

        <!-- Main Report Form -->
        <div class="report-card">
            <h2 class="card-title">
                <i class="fas fa-exclamation-triangle"></i>
                রিপোর্ট ফর্ম
            </h2>

            <form id="reportForm" action="submit_report.php" method="POST" enctype="multipart/form-data">
                <!-- Reporter Information Section -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <h3 class="section-title">রিপোর্টার তথ্য</h3>
                        <span class="section-badge">স্বয়ংক্রিয়</span>
                    </div>

                    <div class="toggle-container">
                        <div class="toggle-info">
                            <div class="toggle-title">
                                <i class="fas fa-user-secret"></i>
                                বেনামে রিপোর্ট করুন
                            </div>
                            <div class="toggle-desc">
                                সক্রিয় করলে আপনার নাম এবং ব্যক্তিগত তথ্য রিপোর্টে গোপন থাকবে। শুধুমাত্র প্রশাসক আপনার তথ্য দেখতে পাবেন।
                            </div>
                        </div>
                        <div class="toggle-switch">
                            <input type="checkbox" id="anonymousToggle" name="anonymous">
                            <span class="toggle-slider"></span>
                        </div>
                        <div class="toggle-status" id="toggleStatus">বেনামে নয়</div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>
                                <i class="fas fa-user"></i>
                                পুরো নাম
                            </label>
                            <input type="text" id="fullName" name="full_name" class="form-control"
                                value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>"
                                placeholder="আপনার নাম">
                            <div class="error-message" id="nameError"></div>
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-envelope"></i>
                                ইমেইল
                            </label>
                            <input type="email" id="email" name="email" class="form-control"
                                value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                placeholder="your@email.com">
                            <div class="error-message" id="emailError"></div>
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-phone"></i>
                                ফোন নম্বর
                            </label>
                            <input type="tel" id="phone" name="phone" class="form-control"
                                value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                placeholder="০১XXXXXXXXX">
                            <div class="error-message" id="phoneError"></div>
                        </div>

                        <div class="form-group full-width">
                            <label>
                                <i class="fas fa-map-marker-alt"></i>
                                ঠিকানা
                            </label>
                            <input type="text" id="address" name="address" class="form-control"
                                value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>"
                                placeholder="আপনার ঠিকানা">
                        </div>
                    </div>
                </div>

                <!-- Incident Details Section -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <h3 class="section-title">ঘটনার বিবরণ</h3>
                        <span class="section-badge">অত্যাবশ্যক</span>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-exclamation"></i>
                            ঘটনার ধরণ *
                        </label>
                        <div class="incident-types" id="incidentTypes">
                            <div class="incident-type-card" data-type="harassment">
                                <div class="incident-type-icon">
                                    <i class="fas fa-ban"></i>
                                </div>
                                <div class="incident-type-title">হয়রানি</div>
                                <div class="incident-type-desc">মৌখিক/শারীরিক হয়রানি</div>
                            </div>
                            <div class="incident-type-card" data-type="assault">
                                <div class="incident-type-icon">
                                    <i class="fas fa-fist-raised"></i>
                                </div>
                                <div class="incident-type-title">সহিংসতা</div>
                                <div class="incident-type-desc">শারীরিক আক্রমণ, মারামারি</div>
                            </div>
                            <div class="incident-type-card" data-type="theft">
                                <div class="incident-type-icon">
                                    <i class="fas fa-mask"></i>
                                </div>
                                <div class="incident-type-title">চুরি/ছিনতাই</div>
                                <div class="incident-type-desc">মূল্যবান জিনিসপত্র চুরি</div>
                            </div>
                            <div class="incident-type-card" data-type="overcharging">
                                <div class="incident-type-icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div class="incident-type-title">অতিরিক্ত ভাড়া</div>
                                <div class="incident-type-desc">নির্ধারিত ভাড়ার বেশি নেওয়া</div>
                            </div>
                            <div class="incident-type-card" data-type="misbehavior">
                                <div class="incident-type-icon">
                                    <i class="fas fa-user-ninja"></i>
                                </div>
                                <div class="incident-type-title">অসদাচরণ</div>
                                <div class="incident-type-desc">কর্মচারী/চালকের অসদাচরণ</div>
                            </div>
                            <div class="incident-type-card" data-type="other">
                                <div class="incident-type-icon">
                                    <i class="fas fa-ellipsis-h"></i>
                                </div>
                                <div class="incident-type-title">অন্যান্য</div>
                                <div class="incident-type-desc">অন্যান্য ঘটনা</div>
                            </div>
                        </div>
                        <input type="hidden" id="incidentType" name="incident_type">
                        <div class="error-message" id="incidentTypeError"></div>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-exclamation-circle"></i>
                            ঘটনার শিরোনাম *
                        </label>
                        <input type="text" id="incidentTitle" name="title" class="form-control"
                            placeholder="সংক্ষেপে ঘটনার শিরোনাম" required>
                        <div class="error-message" id="titleError"></div>
                    </div>

                    <div class="form-group full-width">
                        <label>
                            <i class="fas fa-align-left"></i>
                            ঘটনার বিবরণ *
                        </label>
                        <textarea id="incidentDescription" name="description" class="form-control"
                            placeholder="বিস্তারিত বর্ণনা করুন... কী ঘটেছে, কখন, কোথায়, কারা জড়িত ছিল?" required></textarea>
                        <div class="error-message" id="descriptionError"></div>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-tachometer-alt"></i>
                            ঘটনার তীব্রতা *
                        </label>
                        <div class="severity-container">
                            <div class="severity-options">
                                <div class="severity-option low">
                                    <input type="radio" name="severity" id="severityLow" value="low">
                                    <label for="severityLow">
                                        <div class="severity-icon"><i class="fas fa-smile"></i></div>
                                        <div class="severity-title">নিম্ন</div>
                                        <div class="severity-desc">সামান্য অসুবিধা</div>
                                    </label>
                                </div>
                                <div class="severity-option medium">
                                    <input type="radio" name="severity" id="severityMedium" value="medium">
                                    <label for="severityMedium">
                                        <div class="severity-icon"><i class="fas fa-meh"></i></div>
                                        <div class="severity-title">মধ্যম</div>
                                        <div class="severity-desc">মাঝারি গুরুত্ব</div>
                                    </label>
                                </div>
                                <div class="severity-option high">
                                    <input type="radio" name="severity" id="severityHigh" value="high">
                                    <label for="severityHigh">
                                        <div class="severity-icon"><i class="fas fa-frown"></i></div>
                                        <div class="severity-title">উচ্চ</div>
                                        <div class="severity-desc">গুরুতর ঘটনা</div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="error-message" id="severityError"></div>
                    </div>
                </div>

                <!-- Incident Location Section -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-map-marked-alt"></i>
                        </div>
                        <h3 class="section-title">ঘটনার অবস্থান</h3>
                        <span class="section-badge">অত্যাবশ্যক</span>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>
                                <i class="fas fa-bus"></i>
                                বাস নম্বর/রুট
                            </label>
                            <input type="text" id="busNumber" name="bus_number" class="form-control"
                                placeholder="যেমন: ৪, ৬, ১০ (যদি জানেন)">
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-map-marker-alt"></i>
                                এলাকা/স্থান *
                            </label>
                            <div class="typeahead-container">
                                <div class="select-container">
                                    <i class="fas fa-search-location"></i>
                                    <input type="text" id="location" class="typeahead-input"
                                        placeholder="ঘটনার অবস্থান" required autocomplete="off">
                                </div>
                                <div class="suggestions-dropdown" id="locationSuggestions"></div>
                            </div>
                            <div class="error-message" id="locationError"></div>
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-road"></i>
                                নির্দিষ্ট স্টপেজ/এলাকা
                            </label>
                            <input type="text" id="specificLocation" name="specific_location" class="form-control"
                                placeholder="যেমন: ফার্মগেট, মিরপুর-১০">
                        </div>
                    </div>

                    <div class="datetime-grid">
                        <div class="form-group">
                            <label>
                                <i class="fas fa-calendar"></i>
                                ঘটনার তারিখ *
                            </label>
                            <input type="date" id="incidentDate" name="incident_date" class="datetime-input"
                                max="<?php echo date('Y-m-d'); ?>" required>
                            <div class="error-message" id="dateError"></div>
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-clock"></i>
                                ঘটনার সময় (প্রায়)
                            </label>
                            <input type="time" id="incidentTime" name="incident_time" class="datetime-input">
                        </div>
                    </div>
                </div>

                <!-- Involved Parties Section -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="section-title">জড়িত পক্ষ</h3>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-user-tie"></i>
                            বাস চালকের নাম/পরিচয়
                        </label>
                        <input type="text" id="driverName" name="driver_name" class="form-control"
                            placeholder="যদি জানেন">
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-user-tag"></i>
                            হেলপারের নাম/পরিচয়
                        </label>
                        <input type="text" id="helperName" name="helper_name" class="form-control"
                            placeholder="যদি জানেন">
                    </div>

                    <div class="form-group full-width">
                        <label>
                            <i class="fas fa-id-card"></i>
                            বাসের বিবরণ
                        </label>
                        <input type="text" id="busDetails" name="bus_details" class="form-control"
                            placeholder="বাসের রং, কোম্পানির নাম, রেজিস্ট্রেশন নম্বর (যদি জানেন)">
                    </div>

                    <div class="form-group full-width">
                        <label>
                            <i class="fas fa-users"></i>
                            অন্যান্য সাক্ষী
                        </label>
                        <textarea id="witnesses" name="witnesses" class="form-control"
                            placeholder="অন্যান্য যাত্রী/সাক্ষীদের সম্পর্কে তথ্য"></textarea>
                    </div>
                </div>

                <!-- Evidence Upload Section -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-camera"></i>
                        </div>
                        <h3 class="section-title">প্রমাণ সংযুক্তি</h3>
                        <span class="section-badge">ঐচ্ছিক</span>
                    </div>

                    <div class="file-upload-container">
                        <div class="file-upload-area" id="fileUploadArea">
                            <div class="file-upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <div class="file-upload-text">
                                ফাইল আপলোড করতে ক্লিক করুন বা ড্র্যাগ করুন
                            </div>
                            <div class="file-upload-hint">
                                সমর্থিত ফাইল: JPG, PNG, MP4, MP3, PDF (সর্বোচ্চ ৫টি ফাইল, প্রতিটি ১০MB)
                            </div>
                            <input type="file" id="fileInput" name="evidence_files[]" multiple class="file-upload-input"
                                accept=".jpg,.jpeg,.png,.gif,.mp4,.mp3,.pdf">
                        </div>
                        <div id="filePreview" class="file-preview"></div>
                        <div class="error-message" id="fileError"></div>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <h3 class="section-title">অতিরিক্ত তথ্য</h3>
                    </div>

                    <div class="form-group full-width">
                        <label>
                            <i class="fas fa-comment"></i>
                            অতিরিক্ত মন্তব্য
                        </label>
                        <textarea id="additionalInfo" name="additional_info" class="form-control"
                            placeholder="আপনার মনে হয় আরও কোনো তথ্য প্রয়োজন?"></textarea>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-headset"></i>
                            ফলো-আপ এর মাধ্যম নির্বাচন করুন
                        </label>
                        <select id="followUp" name="follow_up" class="form-control">
                            <option value="email">ইমেইলের মাধ্যমে</option>
                            <option value="phone">ফোনের মাধ্যমে</option>
                            <option value="none">কোনো যোগাযোগ চাই না</option>
                        </select>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="fas fa-paper-plane"></i>
                    রিপোর্ট জমা দিন
                </button>
            </form>
        </div>

        <!-- Guidelines Card -->
        <div class="guidelines-card">
            <h3 class="guidelines-title">
                <i class="fas fa-clipboard-check"></i>
                রিপোর্ট জমা দেওয়ার নির্দেশিকা
            </h3>
            <ul class="guidelines-list">
                <li>
                    <i class="fas fa-check-circle"></i>
                    ঘটনার সঠিক তারিখ এবং সময় উল্লেখ করুন।
                </li>
                <li>
                    <i class="fas fa-check-circle"></i>
                    বিস্তারিত বর্ণনা দিন - কী ঘটেছে, কীভাবে ঘটেছে, কারা জড়িত।
                </li>
                <li>
                    <i class="fas fa-check-circle"></i>
                    সম্ভব হলে ছবি বা ভিডিও সংযুক্ত করুন যা ঘটনার সত্যতা প্রমাণ করে।
                </li>
                <li class="warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    ব্যক্তিগত তথ্য গোপন রাখতে "বেনামে রিপোর্ট" অপশন ব্যবহার করুন।
                </li>
                <li class="danger">
                    <i class="fas fa-times-circle"></i>
                    মিথ্যা বা ভিত্তিহীন রিপোর্ট দেওয়া থেকে বিরত থাকুন।
                </li>
            </ul>
        </div>
    </div>

    <script>
        // Dhaka locations for typeahead
        const dhakaLocations = <?php echo json_encode($locations); ?>;
        let selectedFiles = [];

        // ==================== TYPEAHEAD FUNCTIONALITY ====================
        function setupTypeahead(inputId, suggestionsId) {
            const input = document.getElementById(inputId);
            const suggestionsDropdown = document.getElementById(suggestionsId);

            if (!input || !suggestionsDropdown) return;

            input.addEventListener('input', function() {
                const query = input.value.toLowerCase().trim();
                suggestionsDropdown.innerHTML = '';

                if (query.length > 0) {
                    const filteredLocations = dhakaLocations.filter(location =>
                        location.toLowerCase().includes(query)
                    );

                    if (filteredLocations.length > 0) {
                        filteredLocations.forEach(location => {
                            const suggestionItem = document.createElement('div');
                            suggestionItem.className = 'suggestion-item';
                            suggestionItem.textContent = location;
                            suggestionItem.addEventListener('click', function() {
                                input.value = location;
                                suggestionsDropdown.classList.remove('active');
                            });
                            suggestionsDropdown.appendChild(suggestionItem);
                        });
                        suggestionsDropdown.classList.add('active');
                    } else {
                        suggestionsDropdown.classList.remove('active');
                    }
                } else {
                    suggestionsDropdown.classList.remove('active');
                }
            });

            document.addEventListener('click', function(event) {
                if (!input.contains(event.target) && !suggestionsDropdown.contains(event.target)) {
                    suggestionsDropdown.classList.remove('active');
                }
            });

            input.addEventListener('focus', function() {
                if (input.value === '') {
                    suggestionsDropdown.innerHTML = '';
                    dhakaLocations.forEach(location => {
                        const suggestionItem = document.createElement('div');
                        suggestionItem.className = 'suggestion-item';
                        suggestionItem.textContent = location;
                        suggestionItem.addEventListener('click', function() {
                            input.value = location;
                            suggestionsDropdown.classList.remove('active');
                        });
                        suggestionsDropdown.appendChild(suggestionItem);
                    });
                    suggestionsDropdown.classList.add('active');
                }
            });
        }

        // Setup typeahead for location
        setupTypeahead('location', 'locationSuggestions');

        // ==================== ANONYMOUS TOGGLE ====================
        const anonymousToggle = document.getElementById('anonymousToggle');
        const toggleStatus = document.getElementById('toggleStatus');
        const nameField = document.getElementById('fullName');
        const emailField = document.getElementById('email');
        const phoneField = document.getElementById('phone');
        const addressField = document.getElementById('address');

        // Store original values
        if (nameField) nameField.setAttribute('data-original', nameField.value);
        if (emailField) emailField.setAttribute('data-original', emailField.value);
        if (phoneField) phoneField.setAttribute('data-original', phoneField.value);
        if (addressField) addressField.setAttribute('data-original', addressField.value);

        if (anonymousToggle) {
            // Initial check
            if (anonymousToggle.checked) {
                toggleStatus.textContent = 'বেনামে';
                toggleStatus.style.color = 'var(--accent-danger)';

                if (nameField) {
                    nameField.disabled = true;
                    nameField.value = '';
                }
                if (emailField) {
                    emailField.disabled = true;
                    emailField.value = '';
                }
                if (phoneField) {
                    phoneField.disabled = true;
                    phoneField.value = '';
                }
                if (addressField) {
                    addressField.disabled = true;
                    addressField.value = '';
                }
            } else {
                toggleStatus.textContent = 'বেনামে নয়';
                toggleStatus.style.color = 'var(--text-muted)';
            }

            anonymousToggle.addEventListener('change', function() {
                if (this.checked) {
                    toggleStatus.textContent = 'বেনামে';
                    toggleStatus.style.color = 'var(--accent-danger)';

                    // Disable and clear personal info fields
                    if (nameField) {
                        nameField.disabled = true;
                        nameField.value = '';
                    }
                    if (emailField) {
                        emailField.disabled = true;
                        emailField.value = '';
                    }
                    if (phoneField) {
                        phoneField.disabled = true;
                        phoneField.value = '';
                    }
                    if (addressField) {
                        addressField.disabled = true;
                        addressField.value = '';
                    }
                } else {
                    toggleStatus.textContent = 'বেনামে নয়';
                    toggleStatus.style.color = 'var(--text-muted)';

                    // Re-enable fields and restore original values
                    if (nameField) {
                        nameField.disabled = false;
                        nameField.value = nameField.getAttribute('data-original') || '';
                    }
                    if (emailField) {
                        emailField.disabled = false;
                        emailField.value = emailField.getAttribute('data-original') || '';
                    }
                    if (phoneField) {
                        phoneField.disabled = false;
                        phoneField.value = phoneField.getAttribute('data-original') || '';
                    }
                    if (addressField) {
                        addressField.disabled = false;
                        addressField.value = addressField.getAttribute('data-original') || '';
                    }
                }
            });
        }

        // ==================== INCIDENT TYPE SELECTION ====================
        const incidentCards = document.querySelectorAll('.incident-type-card');
        const incidentTypeInput = document.getElementById('incidentType');

        incidentCards.forEach(card => {
            card.addEventListener('click', function() {
                // Remove selected class from all cards
                incidentCards.forEach(c => c.classList.remove('selected'));

                // Add selected class to clicked card
                this.classList.add('selected');

                // Update hidden input
                const type = this.getAttribute('data-type');
                incidentTypeInput.value = type;

                // Clear error
                document.getElementById('incidentTypeError').classList.remove('active');
            });
        });

        // ==================== FILE UPLOAD HANDLING ====================
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileInput = document.getElementById('fileInput');
        const filePreview = document.getElementById('filePreview');
        const fileError = document.getElementById('fileError');

        // Drag and drop handlers
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, unhighlight, false);
        });

        function highlight() {
            fileUploadArea.classList.add('dragover');
        }

        function unhighlight() {
            fileUploadArea.classList.remove('dragover');
        }

        fileUploadArea.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        }

        fileInput.addEventListener('change', function() {
            handleFiles(this.files);
        });

        function handleFiles(files) {
            fileError.classList.remove('active');

            // Check file count
            if (selectedFiles.length + files.length > 5) {
                fileError.textContent = 'সর্বোচ্চ ৫টি ফাইল আপলোড করা যাবে।';
                fileError.classList.add('active');
                return;
            }

            // Process each file
            for (let i = 0; i < files.length; i++) {
                const file = files[i];

                // Check file size (10MB max)
                if (file.size > 10 * 1024 * 1024) {
                    fileError.textContent = `${file.name} - ফাইলের সাইজ ১০MB এর বেশি।`;
                    fileError.classList.add('active');
                    continue;
                }

                // Check file type
                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'audio/mp3', 'application/pdf'];
                if (!validTypes.includes(file.type)) {
                    fileError.textContent = `${file.name} - অনুমোদিত ফাইল টাইপ নয়।`;
                    fileError.classList.add('active');
                    continue;
                }

                selectedFiles.push(file);
                displayFilePreview(file);
            }

            // Update file input
            updateFileInput();
        }

        function displayFilePreview(file) {
            const reader = new FileReader();
            const previewItem = document.createElement('div');
            previewItem.className = 'file-preview-item';

            reader.onload = function(e) {
                let previewContent = '';

                if (file.type.startsWith('image/')) {
                    previewContent = `<img src="${e.target.result}" class="file-preview-image" alt="Preview">`;
                } else {
                    const icon = getFileIcon(file.type);
                    previewContent = `<div class="file-preview-icon"><i class="fas ${icon}"></i></div>`;
                }

                previewItem.innerHTML = `
                    ${previewContent}
                    <div class="file-preview-name">${file.name}</div>
                    <div class="file-preview-size">${formatFileSize(file.size)}</div>
                    <button type="button" class="file-remove-btn" onclick="removeFile('${file.name}')">
                        <i class="fas fa-times"></i>
                    </button>
                `;

                filePreview.appendChild(previewItem);
            };

            if (file.type.startsWith('image/')) {
                reader.readAsDataURL(file);
            } else {
                reader.readAsArrayBuffer(file);
            }
        }

        function getFileIcon(fileType) {
            if (fileType.startsWith('video/')) return 'fa-video';
            if (fileType.startsWith('audio/')) return 'fa-music';
            if (fileType === 'application/pdf') return 'fa-file-pdf';
            return 'fa-file';
        }

        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }

        window.removeFile = function(fileName) {
            selectedFiles = selectedFiles.filter(f => f.name !== fileName);

            // Remove preview element
            const previewItems = document.querySelectorAll('.file-preview-item');
            previewItems.forEach(item => {
                if (item.querySelector('.file-preview-name').textContent === fileName) {
                    item.remove();
                }
            });

            // Update file input
            updateFileInput();
        };

        function updateFileInput() {
            // Create new FileList-like structure
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => dataTransfer.items.add(file));
            fileInput.files = dataTransfer.files;
        }

        // ==================== FORM VALIDATION ====================
        const reportForm = document.getElementById('reportForm');
        const submitBtn = document.getElementById('submitBtn');

        reportForm.addEventListener('submit', function(e) {
            e.preventDefault();

            let isValid = true;

            // Clear all previous errors
            document.querySelectorAll('.error-message').forEach(el => {
                el.classList.remove('active');
            });
            document.querySelectorAll('.form-control').forEach(el => {
                el.classList.remove('error');
            });

            // Validate incident type
            if (!incidentTypeInput.value) {
                document.getElementById('incidentTypeError').textContent = 'ঘটনার ধরণ নির্বাচন করুন।';
                document.getElementById('incidentTypeError').classList.add('active');
                isValid = false;
            }

            // Validate title
            const title = document.getElementById('incidentTitle').value.trim();
            if (!title) {
                document.getElementById('titleError').textContent = 'ঘটনার শিরোনাম দিন।';
                document.getElementById('titleError').classList.add('active');
                document.getElementById('incidentTitle').classList.add('error');
                isValid = false;
            } else if (title.length < 5) {
                document.getElementById('titleError').textContent = 'শিরোনাম কমপক্ষে ৫ অক্ষরের হতে হবে।';
                document.getElementById('titleError').classList.add('active');
                document.getElementById('incidentTitle').classList.add('error');
                isValid = false;
            }

            // Validate description
            const description = document.getElementById('incidentDescription').value.trim();
            if (!description) {
                document.getElementById('descriptionError').textContent = 'ঘটনার বিবরণ দিন।';
                document.getElementById('descriptionError').classList.add('active');
                document.getElementById('incidentDescription').classList.add('error');
                isValid = false;
            } else if (description.length < 20) {
                document.getElementById('descriptionError').textContent = 'বিবরণ কমপক্ষে ২০ অক্ষরের হতে হবে।';
                document.getElementById('descriptionError').classList.add('active');
                document.getElementById('incidentDescription').classList.add('error');
                isValid = false;
            }

            // Validate severity
            const severity = document.querySelector('input[name="severity"]:checked');
            if (!severity) {
                document.getElementById('severityError').textContent = 'ঘটনার তীব্রতা নির্বাচন করুন।';
                document.getElementById('severityError').classList.add('active');
                isValid = false;
            }

            // Validate location
            const location = document.getElementById('location').value.trim();
            if (!location) {
                document.getElementById('locationError').textContent = 'ঘটনার অবস্থান নির্বাচন করুন।';
                document.getElementById('locationError').classList.add('active');
                document.getElementById('location').classList.add('error');
                isValid = false;
            } else if (!dhakaLocations.includes(location)) {
                document.getElementById('locationError').textContent = 'তালিকা থেকে সঠিক অবস্থান নির্বাচন করুন।';
                document.getElementById('locationError').classList.add('active');
                document.getElementById('location').classList.add('error');
                isValid = false;
            }

            // Validate date
            const date = document.getElementById('incidentDate').value;
            if (!date) {
                document.getElementById('dateError').textContent = 'ঘটনার তারিখ নির্বাচন করুন।';
                document.getElementById('dateError').classList.add('active');
                document.getElementById('incidentDate').classList.add('error');
                isValid = false;
            }

            // Validate personal info if not anonymous
            if (!anonymousToggle.checked) {
                const name = document.getElementById('fullName').value.trim();
                if (!name) {
                    document.getElementById('nameError').textContent = 'নাম প্রদান করুন বা বেনামে রিপোর্ট করুন।';
                    document.getElementById('nameError').classList.add('active');
                    document.getElementById('fullName').classList.add('error');
                    isValid = false;
                }

                const email = document.getElementById('email').value.trim();
                if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    document.getElementById('emailError').textContent = 'সঠিক ইমেইল ঠিকানা দিন।';
                    document.getElementById('emailError').classList.add('active');
                    document.getElementById('email').classList.add('error');
                    isValid = false;
                }

                const phone = document.getElementById('phone').value.trim();
                if (phone && !/^01[0-9]{9}$/.test(phone)) {
                    document.getElementById('phoneError').textContent = 'সঠিক বাংলাদেশি ফোন নম্বর দিন (০১XXXXXXXXX)';
                    document.getElementById('phoneError').classList.add('active');
                    document.getElementById('phone').classList.add('error');
                    isValid = false;
                }
            }

            if (isValid) {
                // Show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> জমা দেওয়া হচ্ছে...';

                // Submit the form
                this.submit();
            } else {
                // Scroll to first error
                const firstError = document.querySelector('.error-message.active');
                if (firstError) {
                    firstError.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest'
                    });
                }
            }
        });

        // ==================== AUTO-DISMISS ALERTS ====================
        setTimeout(() => {
            document.querySelectorAll('.message-alert').forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);

        // ==================== SET DEFAULT DATE ====================
        document.getElementById('incidentDate').value = new Date().toISOString().split('T')[0];

        // ==================== FIX TOGGLE CLICK ISSUE ====================
        document.addEventListener('DOMContentLoaded', function() {
            const toggleSlider = document.querySelector('.toggle-slider');
            const anonymousToggle = document.getElementById('anonymousToggle');

            if (toggleSlider && anonymousToggle) {
                // Make the slider clickable
                toggleSlider.addEventListener('click', function(e) {
                    e.preventDefault();
                    anonymousToggle.checked = !anonymousToggle.checked;

                    // Trigger change event
                    const event = new Event('change', {
                        bubbles: true
                    });
                    anonymousToggle.dispatchEvent(event);
                });
            }
        });
    </script>
</body>

</html>
<?php
$conn->close();
include_once 'footer.php';
?>