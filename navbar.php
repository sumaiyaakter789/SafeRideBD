<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
$profileInitial = isset($_SESSION['user_name']) ? strtoupper(substr($_SESSION['user_name'], 0, 1)) : '?';

// Bangla translations only
$translations = [
    'nav_home' => 'ভাড়া ক্যালকুলেটর',
    'nav_emergency' => 'জরুরি সেবা',
    'nav_login' => 'লগইন',
    'nav_profile' => 'আমার প্রোফাইল',
    'nav_saved_routes' => 'সংরক্ষিত রুট',
    'nav_logout' => 'লগআউট',
    'nav_safe_place' => 'নিরাপদ স্থান'
];

function trans($key)
{
    global $translations;
    return isset($translations[$key]) ? $translations[$key] : $key;
}

?>
<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeRideBD - নিরাপদ যাত্রা</title>
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
            --border-color: #2e3a44;
            --border-light: #3a4754;
            --shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
            --glow: 0 0 0 2px rgba(255, 107, 74, 0.2);
        }

        body {
            font-family: 'Bornomala', serif;
            font-weight: 400;
            font-style: normal;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            display: flex;
            flex-direction: column;
        }

        /* Container */
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 40px 24px;
            flex: 1;
            width: 50%;
        }

        /* Page Header */
        .page-header {
            text-align: center;
            margin-bottom: 48px;
        }

        .page-title {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 16px;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 18px;
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.8;
        }

        /* Cards */
        .card {
            background-color: var(--bg-card);
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 32px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
        }

        .card-title {
            color: var(--text-primary);
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-title i {
            color: var(--accent-primary);
            font-size: 24px;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-secondary);
            font-size: 16px;
            font-weight: 500;
        }

        .form-group label i {
            color: var(--accent-primary);
            margin-right: 8px;
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
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-family: 'Bornomala', serif;
            font-size: 16px;
            color: var(--text-primary);
            transition: all 0.2s;
        }

        .typeahead-input:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: var(--glow);
        }

        .typeahead-input::placeholder {
            color: var(--text-muted);
        }

        /* Buttons */
        .search-btn {
            width: 100%;
            padding: 16px 24px;
            background-color: var(--accent-primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-family: 'Bornomala', serif;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .search-btn:hover {
            background-color: #ff5a3a;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255, 107, 74, 0.3);
        }

        .search-btn i {
            font-size: 18px;
        }

        /* Error Message */
        .error-message {
            background-color: rgba(239, 68, 68, 0.1);
            border-left: 4px solid var(--accent-danger);
            color: var(--accent-danger);
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: none;
        }

        .error-message.active {
            display: block;
            animation: slideDown 0.3s ease;
        }

        .error-message i {
            margin-right: 8px;
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

        /* Navbar Styles */
        .navbar {
            background-color: var(--bg-secondary);
            border-bottom: 2px solid var(--border-color);
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow);
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            transition: opacity 0.2s;
        }

        .logo-container:hover {
            opacity: 0.9;
        }

        .logo-img {
            height: 40px;
            width: auto;
            filter: brightness(0) invert(1);
        }

        .logo-text {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: 0.5px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-link {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s;
            display: none;
            font-size: 16px;
        }

        .nav-link i {
            margin-right: 8px;
            font-size: 16px;
        }

        .nav-link:hover {
            color: var(--text-primary);
            background-color: var(--bg-hover);
        }

        .nav-link.active {
            color: var(--accent-primary);
            background-color: rgba(255, 107, 74, 0.1);
        }

        .emergency-btn {
            background-color: var(--accent-danger);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Bornomala', serif;
            font-size: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .emergency-btn:hover {
            background-color: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(220, 38, 38, 0.3);
        }

        .login-btn {
            background-color: var(--bg-hover);
            color: var(--text-primary);
            border: 1px solid var(--border-light);
            padding: 10px 22px;
            border-radius: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            font-size: 16px;
        }

        .login-btn:hover {
            background-color: var(--border-color);
            border-color: var(--accent-primary);
        }

        .profile-container {
            position: relative;
        }

        .profile-pic {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background-color: var(--accent-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            cursor: pointer;
            border: 2px solid var(--border-light);
            transition: all 0.2s;
            font-size: 18px;
        }

        .profile-pic:hover {
            border-color: var(--accent-primary);
            transform: scale(1.05);
        }

        .profile-pic img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .profile-dropdown {
            position: absolute;
            top: 52px;
            right: 0;
            background-color: var(--bg-card);
            border-radius: 8px;
            box-shadow: var(--shadow);
            width: 220px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s;
            border: 1px solid var(--border-color);
            z-index: 100;
        }

        .profile-container:hover .profile-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-item {
            padding: 14px 20px;
            color: var(--text-secondary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s;
            border-bottom: 1px solid var(--border-color);
            font-size: 15px;
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background-color: var(--bg-hover);
            color: var(--text-primary);
        }

        .dropdown-item i {
            width: 20px;
            color: var(--accent-primary);
            font-size: 16px;
        }

        /* Emergency Popup */
        .emergency-popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.95);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            backdrop-filter: blur(8px);
            padding: 20px;
        }

        .emergency-popup.active {
            display: flex;
        }

        .popup-content {
            background-color: var(--bg-card);
            border-radius: 16px;
            padding: 32px;
            width: 95%;
            max-width: 1000px;
            max-height: 85vh;
            overflow-y: auto;
            border: 2px solid var(--accent-danger);
            box-shadow: var(--shadow);
            position: relative;
        }

        .popup-header {
            text-align: center;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .popup-icon {
            font-size: 48px;
            color: var(--accent-danger);
            margin-bottom: 16px;
            width: 80px;
            height: 80px;
            line-height: 80px;
            border-radius: 50%;
            background-color: rgba(239, 68, 68, 0.1);
            display: inline-block;
        }

        .popup-title {
            color: var(--accent-danger);
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .popup-message {
            color: var(--text-secondary);
            font-size: 16px;
        }

        .login-required-section {
            text-align: center;
            padding: 32px;
            background-color: var(--bg-secondary);
            border-radius: 12px;
            margin: 24px 0;
        }

        .login-required-icon {
            font-size: 64px;
            color: var(--accent-danger);
            margin-bottom: 16px;
        }

        .login-required-text {
            color: var(--text-primary);
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .login-required-subtext {
            color: var(--text-muted);
            font-size: 15px;
            margin-bottom: 24px;
        }

        .emergency-features {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin: 24px 0;
        }

        .feature-box {
            background-color: var(--bg-secondary);
            border-radius: 12px;
            padding: 24px 20px;
            text-align: center;
            border: 1px solid var(--border-color);
            transition: all 0.2s;
            cursor: pointer;
        }

        .feature-box:hover {
            border-color: var(--accent-danger);
            transform: translateY(-4px);
            box-shadow: var(--shadow);
        }

        .feature-icon {
            font-size: 32px;
            color: var(--accent-danger);
            margin-bottom: 16px;
        }

        .feature-title {
            color: var(--text-primary);
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .feature-desc {
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.5;
        }

        .feature-badge {
            display: inline-block;
            background-color: var(--accent-danger);
            color: white;
            font-size: 10px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 4px;
            margin-bottom: 12px;
        }

        .popup-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 24px;
        }

        .popup-btn {
            padding: 12px 28px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            font-size: 15px;
            font-family: 'Bornomala', serif;
            min-width: 140px;
        }

        .popup-login-btn {
            background-color: var(--accent-primary);
            color: white;
        }

        .popup-login-btn:hover {
            background-color: #ff5a3a;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(255, 107, 74, 0.3);
        }

        .popup-close-btn {
            background-color: var(--bg-hover);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }

        .popup-close-btn:hover {
            background-color: var(--border-color);
            color: var(--text-primary);
        }

        .close-popup {
            position: absolute;
            top: 16px;
            right: 16px;
            background-color: var(--bg-hover);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-popup:hover {
            background-color: var(--accent-danger);
            color: white;
            border-color: var(--accent-danger);
        }

        /* Footer Styles */
        .footer {
            background-color: var(--bg-secondary);
            border-top: 2px solid var(--border-color);
            padding: 48px 24px 32px;
            margin-top: 60px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 32px;
        }

        .footer-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            text-align: center;
        }

        .footer-logo img {
            height: 80px;
            width: auto;
            filter: brightness(0) invert(1);
            opacity: 0.9;
        }

        .footer-logo-text {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .footer-tagline {
            color: var(--text-muted);
            font-size: 15px;
            max-width: 500px;
            line-height: 1.8;
        }

        .footer-links {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 16px 24px;
        }

        .footer-link {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 15px;
            transition: all 0.2s;
            padding: 6px 12px;
            border-radius: 6px;
        }

        .footer-link:hover {
            color: var(--text-primary);
            background-color: var(--bg-hover);
        }

        .footer-link i {
            margin-right: 6px;
            color: var(--accent-primary);
        }

        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            padding-top: 24px;
            border-top: 1px solid var(--border-color);
            flex-wrap: wrap;
            gap: 20px;
        }

        .copyright {
            color: var(--text-muted);
            font-size: 14px;
        }

        .social-links {
            display: flex;
            gap: 12px;
        }

        .social-link {
            width: 38px;
            height: 38px;
            border-radius: 6px;
            background-color: var(--bg-hover);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s;
            border: 1px solid var(--border-color);
        }

        .social-link:hover {
            background-color: var(--accent-primary);
            color: white;
            border-color: var(--accent-primary);
            transform: translateY(-3px);
        }

        .emergency-note {
            margin-top: 24px;
            font-size: 13px;
            color: var(--text-muted);
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .emergency-note i {
            color: var(--accent-danger);
        }

        /* Responsive Design */
        @media (min-width: 768px) {
            .nav-link {
                display: inline-block;
            }

            .emergency-features {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 24px 16px;
            }

            .page-title {
                font-size: 32px;
            }

            .page-subtitle {
                font-size: 16px;
            }

            .card {
                padding: 24px;
            }

            .emergency-features {
                grid-template-columns: repeat(2, 1fr);
            }

            .popup-content {
                padding: 24px;
            }

            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .navbar {
                padding: 12px 16px;
            }

            .logo-text {
                font-size: 20px;
            }

            .login-btn span,
            .emergency-btn span {
                display: none;
            }

            .login-btn i,
            .emergency-btn i {
                margin: 0;
            }

            .login-btn,
            .emergency-btn {
                padding: 10px;
            }

            .emergency-features {
                grid-template-columns: 1fr;
            }

            .popup-buttons {
                flex-direction: column;
            }

            .popup-btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div id="emergencyPopup" class="emergency-popup">
        <div class="popup-content">
            <button class="close-popup" id="closePopup">&times;</button>
            <div class="popup-header">
                <div class="popup-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h2 class="popup-title">জরুরি সেবা</h2>
                <div id="popupMessage" class="popup-message">
                    <!-- Message will be inserted here -->
                </div>
            </div>

            <div id="loginRequiredSection" class="login-required-section" style="display: none;">
                <div class="login-required-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <h3 class="login-required-text">লগইন অত্যাবশ্যক</h3>
                <p class="login-required-subtext">
                    জরুরি সেবা ব্যবহার করতে অনুগ্রহ করে লগইন করুন। এই টুলগুলি আপনাকে জটিল পরিস্থিতিতে সাহায্য করার জন্য তৈরি করা হয়েছে।
                </p>
            </div>

            <div id="emergencyFeatures" class="emergency-features" style="display: none;">
                <div class="feature-box" onclick="window.location.href='location_share.php'">
                    <span class="feature-badge">লাইভ</span>
                    <div class="feature-icon">
                        <i class="fa-solid fa-location-crosshairs"></i>
                    </div>
                    <h3 class="feature-title">লোকেশন শেয়ার</h3>
                    <p class="feature-desc">আপনার অবস্থান পরিবার ও পরিচিতদের সাথে শেয়ার করুন</p>
                </div>

                <div class="feature-box" onclick="window.location.href='safeplace.php'">
                    <span class="feature-badge">কাছাকাছি</span>
                    <div class="feature-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3 class="feature-title">নিরাপদ স্থান</h3>
                    <p class="feature-desc">পুলিশ স্টেশন, হাসপাতাল, শিক্ষা প্রতিষ্ঠান ও নিরাপদ আশ্রয় খুঁজুন</p>
                </div>

                <div class="feature-box" onclick="window.location.href='report_incident.php'">
                    <span class="feature-badge">দ্রুত</span>
                    <div class="feature-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <h3 class="feature-title">ঘটনা রিপোর্ট</h3>
                    <p class="feature-desc">ছবি ও অবস্থানসহ যেকোনো সহিংস এবং হয়রানির ঘটনা রিপোর্ট করুন</p>
                </div>

                <div class="feature-box" onclick="window.location.href='track_risk.php'">
                    <span class="feature-badge">ঝুঁকিপূর্ণ এলাকা</span>
                    <div class="feature-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <h3 class="feature-title">ট্র্যাক করুন</h3>
                    <p class="feature-desc">রিপোর্ট করা ঘটনার ভিত্তিতে ঝুঁকিপূর্ণ এলাকার বর্তমান অবস্থা দেখুন</p>
                </div>
            </div>

            <div id="popupButtons" class="popup-buttons">
                <!-- Buttons will be inserted here -->
            </div>
        </div>
    </div>

    <nav class="navbar">
        <a href="index.php" class="logo-container">
            <img src="saferidebd.png" alt="SafeRideBD Logo" class="logo-img">
        </a>

        <div class="nav-links">
            <a href="index.php" class="nav-link active">
                <i class="fas fa-calculator"></i> <?php echo trans('nav_home'); ?>
            </a>

            <button class="emergency-btn" id="emergencyBtn">
                <i class="fas fa-shield-alt"></i> <span><?php echo trans('nav_emergency'); ?></span>
            </button>

            <a href="all_reports.php" class="nav-link">
                <i class="fas fa-newspaper"></i> প্রতিবেদন
            </a>

            <?php if ($isLoggedIn): ?>
                <div class="profile-container">
                    <div class="profile-pic" id="profilePic">
                        <?php if (isset($_SESSION['google_profile_image'])): ?>
                            <img src="<?php echo $_SESSION['google_profile_image']; ?>" alt="Profile">
                        <?php elseif (isset($_SESSION['profile_image']) && !empty($_SESSION['profile_image'])): ?>
                            <img src="<?php echo $_SESSION['profile_image']; ?>" alt="Profile">
                        <?php else: ?>
                            <?php echo $profileInitial; ?>
                        <?php endif; ?>
                    </div>
                    <div class="profile-dropdown" id="profileDropdown">
                        <a href="profile.php" class="dropdown-item">
                            <i class="fas fa-user-circle"></i> <?php echo trans('nav_profile'); ?>
                        </a>
                        <a href="saved_routes.php" class="dropdown-item">
                            <i class="fas fa-bookmark"></i> <?php echo trans('nav_saved_routes'); ?>
                        </a>
                        <a href="my_reports.php" class="dropdown-item">
                            <i class="fas fa-exclamation-triangle"></i> আমার রিপোর্ট
                        </a>
                        <a href="logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i> <?php echo trans('nav_logout'); ?>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> <span><?php echo trans('nav_login'); ?></span>
                </a>
            <?php endif; ?>
        </div>
    </nav>

    <script>
        document.getElementById('emergencyBtn').addEventListener('click', function() {
            const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
            const popup = document.getElementById('emergencyPopup');
            const popupMessage = document.getElementById('popupMessage');
            const loginRequiredSection = document.getElementById('loginRequiredSection');
            const emergencyFeatures = document.getElementById('emergencyFeatures');
            const popupButtons = document.getElementById('popupButtons');

            if (!isLoggedIn) {
                popupMessage.innerHTML = "শুধুমাত্র নিবন্ধিত ব্যবহারকারীদের জন্য";
                loginRequiredSection.style.display = 'block';
                emergencyFeatures.style.display = 'none';

                popupButtons.innerHTML = `
                    <button class="popup-btn popup-login-btn" id="goToLogin">
                        <i class="fas fa-sign-in-alt"></i> লগইন করুন
                    </button>
                    <button class="popup-btn popup-close-btn" id="closePopupBtn">
                        <i class="fas fa-times"></i> বন্ধ করুন
                    </button>
                `;
            } else {
                popupMessage.innerHTML = "জরুরি সেবা সমূহ";
                loginRequiredSection.style.display = 'none';
                emergencyFeatures.style.display = 'grid';

                popupButtons.innerHTML = `
                    <button class="popup-btn popup-close-btn" id="closePopupBtn">
                        <i class="fas fa-times"></i> বন্ধ করুন
                    </button>
                `;
            }

            popup.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        document.addEventListener('click', function(e) {
            if (e.target.id === 'closePopup' || e.target.id === 'closePopupBtn') {
                closePopup();
            }

            if (e.target.id === 'goToLogin') {
                window.location.href = 'login.php';
            }
        });

        document.getElementById('emergencyPopup').addEventListener('click', function(e) {
            if (e.target === this) {
                closePopup();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePopup();
            }
        });

        function closePopup() {
            const popup = document.getElementById('emergencyPopup');
            popup.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
    </script>