<?php
session_start();
include_once 'db_config.php';

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success = '';
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$method = isset($_GET['method']) ? $_GET['method'] : 'email';
$reset_data = [];

if ($step == 2 && isset($_SESSION['reset_data'])) {
    $reset_data = $_SESSION['reset_data'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $step == 1) {
    $identifier = trim($_POST['identifier']);
    $method = $_POST['method'];

    if ($method == 'email' && !filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
        $error = "সঠিক ইমেইল ঠিকানা দিন";
    } elseif ($method == 'phone') {
        $identifier = preg_replace('/[^0-9]/', '', $identifier);
        if (substr($identifier, 0, 3) != '880') {
            $identifier = '880' . ltrim($identifier, '0');
        }
        if (strlen($identifier) != 13) {
            $error = "সঠিক বাংলাদেশি ফোন নম্বর দিন (০১ দিয়ে শুরু ১১ ডিজিট)";
        }
    }

    if (!$error) {
        $sql = "SELECT * FROM users WHERE ";
        if ($method == 'email') {
            $sql .= "email = ?";
        } else {
            $sql .= "phone = ?";
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            $token = bin2hex(random_bytes(32));
            $expiry = gmdate('Y-m-d H:i:s', time() + 3600);

            $delete_sql = "DELETE FROM password_resets WHERE email = ? OR phone = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $email = $method == 'email' ? $identifier : $user['email'];
            $phone = $method == 'phone' ? $identifier : $user['phone'];
            $delete_stmt->bind_param("ss", $email, $phone);
            $delete_stmt->execute();
            $delete_stmt->close();

            $reset_sql = "INSERT INTO password_resets (email, phone, token, expiry) VALUES (?, ?, ?, ?)";
            $reset_stmt = $conn->prepare($reset_sql);
            $reset_stmt->bind_param("ssss", $email, $phone, $token, $expiry);
            $reset_stmt->execute();
            $reset_stmt->close();

            if ($method == 'email') {
                $reset_link = "http://localhost/saferidebd/forget_password.php?step=2&method=email&token=$token";

                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'info.afnan27@gmail.com';
                    $mail->Password = 'rokp jusi apxx wjkn';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    $mail->setFrom('info.afnan27@gmail.com', 'SafeRideBD');
                    $mail->addAddress($identifier);

                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset - SafeRideBD';
                    $mail->Body = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #1e2429; padding: 30px; border-radius: 12px;'>
                            <h2 style='color: #ff6b4a;'>পাসওয়ার্ড রিসেট</h2>
                            <p style='color: #b0b8c2;'>হ্যালো " . htmlspecialchars($user['full_name']) . ",</p>
                            <p style='color: #b0b8c2;'>আমরা আপনার পাসওয়ার্ড রিসেট করার অনুরোধ পেয়েছি। নিচের বাটনে ক্লিক করে পাসওয়ার্ড রিসেট করুন:</p>
                            <div style='text-align: center; margin: 30px 0;'>
                                <a href='$reset_link' style='background: #ff6b4a; color: white; padding: 14px 28px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: 600;'>
                                    পাসওয়ার্ড রিসেট করুন
                                </a>
                            </div>
                            <p style='color: #8a929c; font-size: 14px;'>অথবা এই লিংক কপি করে ব্রাউজারে পেস্ট করুন:</p>
                            <p style='background: #2a323a; padding: 12px; border-radius: 6px; color: #4ade80; word-break: break-all;'>$reset_link</p>
                            <p style='color: #8a929c; font-size: 13px;'>এই লিংক ১ ঘন্টার মধ্যে ব্যবহার করুন। আপনি যদি এই অনুরোধ না করে থাকেন, দয়া করে এই ইমেইল উপেক্ষা করুন।</p>
                            <br>
                            <p style='color: #b0b8c2;'>ধন্যবাদ,<br>SafeRideBD টিম</p>
                        </div>
                    ";

                    $mail->send();
                    $success = "পাসওয়ার্ড রিসেট লিংক আপনার ইমেইলে পাঠানো হয়েছে!";
                } catch (Exception $e) {
                    $error = "ইমেইল পাঠাতে ব্যর্থ হয়েছে। Error: " . $mail->ErrorInfo;
                }
            } else {
                $verification_code = rand(1000, 9999);
                $_SESSION['sms_code'] = $verification_code;
                $_SESSION['sms_expiry'] = time() + 300;
                $_SESSION['reset_token'] = $token;
                $_SESSION['reset_identifier'] = $identifier;
                $_SESSION['demo_sms_code'] = $verification_code;

                header("Location: forget_password.php?step=2&method=phone");
                exit();
            }
        } else {
            $error = "এই " . ($method == 'email' ? 'ইমেইল' : 'ফোন নম্বর') . " দিয়ে কোন ব্যবহারকারী পাওয়া যায়নি";
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $step == 2 && $method == 'phone') {
    $code = $_POST['code'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($code != $_SESSION['sms_code'] || time() > $_SESSION['sms_expiry']) {
        $error = "ভুল বা মেয়াদোত্তীর্ণ কোড!";
    } elseif ($new_password !== $confirm_password) {
        $error = "পাসওয়ার্ড মিলছে না!";
    } elseif (strlen($new_password) < 8) {
        $error = "পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে";
    } else {
        $current_utc = gmdate('Y-m-d H:i:s');
        $sql = "SELECT * FROM password_resets WHERE token = ? AND phone = ? AND expiry > ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $_SESSION['reset_token'], $_SESSION['reset_identifier'], $current_utc);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET password = ? WHERE phone = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ss", $hashed_password, $_SESSION['reset_identifier']);

            if ($update_stmt->execute()) {
                $delete_sql = "DELETE FROM password_resets WHERE token = ?";
                $delete_stmt = $conn->prepare($delete_sql);
                $delete_stmt->bind_param("s", $_SESSION['reset_token']);
                $delete_stmt->execute();
                $delete_stmt->close();
                
                unset($_SESSION['sms_code']);
                unset($_SESSION['sms_expiry']);
                unset($_SESSION['reset_token']);
                unset($_SESSION['reset_identifier']);
                unset($_SESSION['demo_sms_code']);

                $success = "পাসওয়ার্ড সফলভাবে রিসেট হয়েছে! এখন আপনি নতুন পাসওয়ার্ড দিয়ে লগইন করতে পারবেন।";
                $step = 3;
            } else {
                $error = "পাসওয়ার্ড রিসেট করতে ব্যর্থ হয়েছে। আবার চেষ্টা করুন।";
            }
            $update_stmt->close();
        } else {
            $error = "ভুল বা মেয়াদোত্তীর্ণ টোকেন!";
        }
        $stmt->close();
    }
}

if ($step == 2 && $method == 'email' && isset($_GET['token'])) {
    $token = $_GET['token'];
    $current_utc = gmdate('Y-m-d H:i:s');
    
    $sql = "SELECT * FROM password_resets WHERE token = ? AND expiry > ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $token, $current_utc);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $error = "ভুল বা মেয়াদোত্তীর্ণ লিংক!";
        $step = 1;
    } else {
        $reset_data = $result->fetch_assoc();
        $_SESSION['reset_data'] = $reset_data;
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $step == 2 && $method == 'email') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $token = $_POST['token'];

    if ($new_password !== $confirm_password) {
        $error = "পাসওয়ার্ড মিলছে না!";
    } elseif (strlen($new_password) < 8) {
        $error = "পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে";
    } else {
        $current_utc = gmdate('Y-m-d H:i:s');
        
        $sql = "SELECT * FROM password_resets WHERE token = ? AND expiry > ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $token, $current_utc);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $reset = $result->fetch_assoc();
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            if ($reset['email']) {
                $update_sql = "UPDATE users SET password = ? WHERE email = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ss", $hashed_password, $reset['email']);
            } else {
                $update_sql = "UPDATE users SET password = ? WHERE phone = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ss", $hashed_password, $reset['phone']);
            }

            if ($update_stmt->execute()) {
                $delete_sql = "DELETE FROM password_resets WHERE token = ?";
                $delete_stmt = $conn->prepare($delete_sql);
                $delete_stmt->bind_param("s", $token);
                $delete_stmt->execute();
                $delete_stmt->close();
                unset($_SESSION['reset_data']);

                $success = "পাসওয়ার্ড সফলভাবে রিসেট হয়েছে! এখন আপনি নতুন পাসওয়ার্ড দিয়ে লগইন করতে পারবেন।";
                $step = 3;
            } else {
                $error = "পাসওয়ার্ড রিসেট করতে ব্যর্থ হয়েছে। আবার চেষ্টা করুন।";
            }
            $update_stmt->close();
        } else {
            $error = "ভুল বা মেয়াদোত্তীর্ণ টোকেন!";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <?php include_once 'navbar.php'; ?>
    <style>
        .success-message {
            background-color: rgba(74, 222, 128, 0.1);
            border-left: 4px solid var(--accent-secondary);
            color: var(--accent-secondary);
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: none;
        }

        .success-message.active {
            display: block;
            animation: slideDown 0.3s ease;
        }

        .method-selection {
            display: flex;
            gap: 16px;
            margin: 16px 0;
        }

        .method-option {
            flex: 1;
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }

        .method-option:hover {
            border-color: var(--accent-primary);
            background-color: var(--bg-hover);
        }

        .method-option.selected {
            border-color: var(--accent-primary);
            background-color: rgba(255, 107, 74, 0.1);
        }

        .method-option i {
            font-size: 32px;
            color: var(--accent-primary);
            margin-bottom: 12px;
            display: block;
        }

        .method-option h3 {
            color: var(--text-primary);
            font-size: 18px;
            margin-bottom: 8px;
        }

        .method-option p {
            color: var(--text-muted);
            font-size: 13px;
        }

        .info-box {
            background-color: var(--bg-secondary);
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
            text-align: center;
            border: 1px solid var(--border-color);
        }

        .info-icon {
            font-size: 48px;
            color: var(--accent-primary);
            margin-bottom: 16px;
        }

        .info-box h3 {
            color: var(--text-primary);
            font-size: 20px;
            margin-bottom: 8px;
        }

        .info-box p {
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .demo-code {
            background-color: var(--bg-card);
            border: 1px dashed var(--border-color);
            border-radius: 6px;
            padding: 16px;
            margin-top: 16px;
        }

        .demo-code p {
            margin: 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        .demo-code strong {
            color: var(--accent-primary);
            font-size: 18px;
        }

        .code-input {
            width: 100%;
            padding: 16px 20px;
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-family: 'Bornomala', serif;
            font-size: 24px;
            color: var(--text-primary);
            transition: all 0.2s;
            text-align: center;
            letter-spacing: 8px;
        }

        .code-input:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: var(--glow);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 15px;
            margin-top: 20px;
            transition: all 0.2s;
        }

        .back-link:hover {
            color: var(--text-primary);
        }

        .back-link i {
            color: var(--accent-primary);
        }

        .timer {
            color: var(--text-muted);
            font-size: 14px;
            margin: 16px 0;
            text-align: center;
        }

        .timer.expiring {
            color: var(--accent-danger);
        }

        .resend-btn {
            background-color: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            padding: 12px 24px;
            border-radius: 6px;
            font-family: 'Bornomala', serif;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.2s;
            margin: 0 auto 20px;
            display: block;
        }

        .resend-btn:hover:not(:disabled) {
            border-color: var(--accent-primary);
            color: var(--text-primary);
        }

        .resend-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .password-strength {
            margin-top: 8px;
        }

        .strength-bar {
            height: 4px;
            background-color: var(--border-color);
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 5px;
        }

        .strength-fill {
            height: 100%;
            width: 0;
            transition: all 0.3s;
        }

        .strength-text {
            font-size: 12px;
            color: var(--text-muted);
        }

        .password-match {
            font-size: 12px;
            margin-top: 5px;
        }

        .password-match i {
            margin-right: 4px;
        }

        .match-success {
            color: var(--accent-secondary);
        }

        .match-error {
            color: var(--accent-danger);
        }

        .success-check {
            width: 80px;
            height: 80px;
            background-color: var(--accent-secondary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }

        .success-check i {
            font-size: 40px;
            color: white;
        }

        .success-box {
            text-align: center;
            padding: 20px;
        }

        .success-box h2 {
            color: var(--text-primary);
            font-size: 28px;
            margin-bottom: 16px;
        }

        .success-box p {
            color: var(--text-muted);
            font-size: 16px;
            margin-bottom: 24px;
        }

        .login-now-btn {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background-color: var(--accent-primary);
            color: white;
            padding: 14px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }

        .login-now-btn:hover {
            background-color: #ff5a3a;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(255, 107, 74, 0.3);
        }

        @media (max-width: 768px) {
            .method-selection {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">
                <?php
                if ($step == 1)
                    echo "পাসওয়ার্ড রিসেট";
                elseif ($step == 2)
                    echo "নতুন পাসওয়ার্ড সেট করুন";
                else
                    echo "পাসওয়ার্ড রিসেট সফল";
                ?>
            </h1>
            <p class="page-subtitle">
                <?php
                if ($step == 1)
                    echo "পাসওয়ার্ড রিসেট নির্দেশনা পেতে আপনার ইমেইল বা ফোন নম্বর দিন";
                elseif ($step == 2)
                    echo "আপনার নতুন পাসওয়ার্ড দিন";
                else
                    echo "আপনার পাসওয়ার্ড সফলভাবে রিসেট হয়েছে";
                ?>
            </p>
        </div>

        <?php if ($error): ?>
            <div class="error-message active">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message active">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <?php if ($step == 1): ?>
                <h2 class="card-title">
                    <i class="fas fa-key"></i> পাসওয়ার্ড রিসেট
                </h2>

                <form method="POST" action="forget_password.php?step=1" id="resetForm">
                    <div class="form-group">
                        <label for="identifier">
                            <i class="fas fa-user"></i>
                            ইমেইল বা ফোন নম্বর
                        </label>
                        <div class="select-container">
                            <i class="fas fa-at"></i>
                            <input type="text" id="identifier" name="identifier" class="typeahead-input"
                                placeholder="আপনার ইমেইল বা ফোন নম্বর লিখুন" required
                                value="<?php echo isset($_POST['identifier']) ? htmlspecialchars($_POST['identifier']) : ''; ?>">
                        </div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 5px;">
                            উদাহরণ: example@email.com অথবা 01712345678
                        </div>
                    </div>

                    <div class="form-group">
                        <label style="display: block; margin-bottom: 10px;">
                            <i class="fas fa-mobile-alt"></i>
                            রিসেট পদ্ধতি নির্বাচন করুন
                        </label>
                        <div class="method-selection">
                            <label class="method-option" id="emailMethod">
                                <input type="radio" name="method" value="email" style="display: none;" checked>
                                <i class="fas fa-envelope"></i>
                                <h3>ইমেইল</h3>
                                <p>রিসেট লিংক পাঠাবে</p>
                            </label>
                            <label class="method-option" id="phoneMethod">
                                <input type="radio" name="method" value="phone" style="display: none;">
                                <i class="fas fa-mobile-alt"></i>
                                <h3>এসএমএস</h3>
                                <p>ভেরিফিকেশন কোড পাঠাবে</p>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="search-btn">
                        <i class="fas fa-paper-plane"></i> নির্দেশনা পাঠান
                    </button>
                </form>

                <div style="text-align: center; margin-top: 25px;">
                    <a href="login.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> লগইন পৃষ্ঠায় ফিরে যান
                    </a>
                </div>

                <script>
                    document.querySelectorAll('.method-option').forEach(option => {
                        option.addEventListener('click', function() {
                            document.querySelectorAll('.method-option').forEach(opt => {
                                opt.classList.remove('selected');
                            });
                            this.classList.add('selected');
                            this.querySelector('input[type="radio"]').checked = true;
                        });
                    });

                    const selectedMethod = document.querySelector('input[name="method"]:checked');
                    if (selectedMethod) {
                        document.getElementById(selectedMethod.value + 'Method').classList.add('selected');
                    }
                </script>

            <?php elseif ($step == 2 && $method == 'phone'): ?>

                <h2 class="card-title">
                    <i class="fas fa-mobile-alt"></i> ফোন ভেরিফিকেশন
                </h2>

                <div class="info-box">
                    <div class="info-icon">
                        <i class="fas fa-sms"></i>
                    </div>
                    <h3>কোড পাঠানো হয়েছে</h3>
                    <p><?php echo $_SESSION['reset_identifier']; ?> নম্বরে</p>
                    
                    <?php if (isset($_SESSION['demo_sms_code'])): ?>
                        <div class="demo-code">
                            <p><strong>ডেমো কোড: <?php echo $_SESSION['demo_sms_code']; ?></strong></p>
                            <p style="font-size: 12px;">(ডেভেলপমেন্ট মোড - ৫ মিনিটের মধ্যে ব্যবহার করুন)</p>
                        </div>
                    <?php endif; ?>
                </div>

                <form method="POST" action="forget_password.php?step=2&method=phone" id="smsForm">
                    <div class="form-group">
                        <label for="code">
                            <i class="fas fa-shield-alt"></i>
                            ভেরিফিকেশন কোড দিন
                        </label>
                        <input type="text" id="code" name="code" class="code-input"
                            placeholder="______" maxlength="4" pattern="[0-9]{4}" required>
                    </div>

                    <div id="smsCountdown" class="timer">
                        কোডের মেয়াদ: <span id="smsTimer">০৫:০০</span>
                    </div>

                    <button type="button" id="smsResendBtn" class="resend-btn" disabled onclick="resendSmsCode()">
                        <i class="fas fa-redo"></i> নতুন কোড পাঠান
                    </button>

                    <div class="form-group">
                        <label for="new_password">
                            <i class="fas fa-lock"></i>
                            নতুন পাসওয়ার্ড
                        </label>
                        <div class="select-container" style="position: relative;">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="new_password" name="new_password" class="typeahead-input"
                                placeholder="নতুন পাসওয়ার্ড দিন" required onkeyup="checkPasswordStrength(this.value)">
                            <i class="fas fa-eye" id="togglePassword1" 
                               style="position: absolute; left: 440px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted); z-index: 3;"></i>
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar">
                                <div id="strengthBar" class="strength-fill"></div>
                            </div>
                            <div id="strengthText" class="strength-text"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-lock"></i>
                            পাসওয়ার্ড নিশ্চিত করুন
                        </label>
                        <div class="select-container" style="position: relative;">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirm_password" name="confirm_password" class="typeahead-input"
                                placeholder="আবার পাসওয়ার্ড দিন" required>
                            <i class="fas fa-eye" id="togglePassword2" 
                               style="position: absolute; left: 440px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted); z-index: 3;"></i>
                        </div>
                        <div id="passwordMatch" class="password-match"></div>
                    </div>

                    <button type="submit" class="search-btn">
                        <i class="fas fa-check-circle"></i> পাসওয়ার্ড রিসেট করুন
                    </button>

                    <div style="text-align: center; margin-top: 20px;">
                        <a href="forget_password.php" class="back-link">
                            <i class="fas fa-arrow-left"></i> আবার চেষ্টা করুন
                        </a>
                    </div>
                </form>

            <?php elseif ($step == 2 && $method == 'email'): ?>
                <h2 class="card-title">
                    <i class="fas fa-lock"></i> নতুন পাসওয়ার্ড সেট করুন
                </h2>

                <form method="POST" action="forget_password.php?step=2&method=email" id="emailForm">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">

                    <div class="form-group">
                        <label for="new_password">
                            <i class="fas fa-lock"></i>
                            নতুন পাসওয়ার্ড
                        </label>
                        <div class="select-container" style="position: relative;">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="new_password" name="new_password" class="typeahead-input"
                                placeholder="নতুন পাসওয়ার্ড দিন" required onkeyup="checkPasswordStrength(this.value)">
                            <i class="fas fa-eye" id="togglePassword1" 
                               style="position: absolute; left: 440px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted); z-index: 3;"></i>
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar">
                                <div id="strengthBar" class="strength-fill"></div>
                            </div>
                            <div id="strengthText" class="strength-text"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-lock"></i>
                            পাসওয়ার্ড নিশ্চিত করুন
                        </label>
                        <div class="select-container" style="position: relative;">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirm_password" name="confirm_password" class="typeahead-input"
                                placeholder="আবার পাসওয়ার্ড দিন" required>
                            <i class="fas fa-eye" id="togglePassword2" 
                               style="position: absolute; left: 440px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted); z-index: 3;"></i>
                        </div>
                        <div id="passwordMatch" class="password-match"></div>
                    </div>

                    <button type="submit" class="search-btn">
                        <i class="fas fa-check-circle"></i> পাসওয়ার্ড রিসেট করুন
                    </button>
                </form>

            <?php else: ?>
                <div class="success-box">
                    <div class="success-check">
                        <i class="fas fa-check"></i>
                    </div>
                    <h2>পাসওয়ার্ড রিসেট সফল!</h2>
                    <p>আপনার পাসওয়ার্ড সফলভাবে রিসেট হয়েছে। এখন আপনি নতুন পাসওয়ার্ড দিয়ে লগইন করতে পারবেন।</p>
                    <a href="login.php" class="login-now-btn">
                        <i class="fas fa-sign-in-alt"></i> লগইন করুন
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        <?php if ($step == 2 && ($method == 'phone' || $method == 'email')): ?>
            function checkPasswordStrength(password) {
                let strength = 0;
                let text = '';
                let color = '';

                if (password.length >= 8) strength++;
                if (/[A-Z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                if (/[^A-Za-z0-9]/.test(password)) strength++;

                const strengthBar = document.getElementById('strengthBar');
                const strengthText = document.getElementById('strengthText');

                switch(strength) {
                    case 0:
                    case 1:
                        text = 'খুব দুর্বল';
                        color = '#ef4444';
                        break;
                    case 2:
                        text = 'দুর্বল';
                        color = '#f97316';
                        break;
                    case 3:
                        text = 'ভালো';
                        color = '#4ade80';
                        break;
                    case 4:
                        text = 'শক্তিশালী';
                        color = '#22c55e';
                        break;
                }

                strengthBar.style.width = (strength * 25) + '%';
                strengthBar.style.backgroundColor = color;
                strengthText.textContent = 'পাসওয়ার্ড শক্তি: ' + text;
                strengthText.style.color = color;

                const confirmPassword = document.getElementById('confirm_password').value;
                updatePasswordMatch(password, confirmPassword);
            }

            function updatePasswordMatch(password, confirmPassword) {
                const matchDiv = document.getElementById('passwordMatch');

                if (confirmPassword) {
                    if (password === confirmPassword) {
                        matchDiv.innerHTML = '<i class="fas fa-check-circle match-success"></i> পাসওয়ার্ড মিলেছে';
                        matchDiv.className = 'password-match match-success';
                    } else {
                        matchDiv.innerHTML = '<i class="fas fa-times-circle match-error"></i> পাসওয়ার্ড মেলেনি';
                        matchDiv.className = 'password-match match-error';
                    }
                } else {
                    matchDiv.innerHTML = '';
                }
            }

            document.getElementById('password').addEventListener('input', function() {
                const password = this.value;
                const confirmPassword = document.getElementById('confirm_password').value;
                updatePasswordMatch(password, confirmPassword);
            });

            document.getElementById('confirm_password').addEventListener('input', function() {
                const password = document.getElementById('new_password').value;
                const confirmPassword = this.value;
                updatePasswordMatch(password, confirmPassword);
            });

            document.getElementById('togglePassword1').addEventListener('click', function() {
                const password = document.getElementById('new_password');
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });

            document.getElementById('togglePassword2').addEventListener('click', function() {
                const password = document.getElementById('confirm_password');
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });

            <?php if ($method == 'phone'): ?>
                const codeInput = document.getElementById('code');
                codeInput.addEventListener('input', function(e) {
                    this.value = this.value.replace(/[^0-9]/g, '');
                });

                let timeLeft = <?php echo isset($_SESSION['sms_expiry']) ? max(0, $_SESSION['sms_expiry'] - time()) : 300; ?>;
                
                const timerElement = document.getElementById('smsTimer');
                const timerContainer = document.getElementById('smsCountdown');
                const resendBtn = document.getElementById('smsResendBtn');

                function updateSmsCountdown() {
                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;
                    timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

                    if (timeLeft <= 0) {
                        clearInterval(timerInterval);
                        resendBtn.disabled = false;
                        timerContainer.innerHTML = 'কোডের মেয়াদ শেষ';
                        timerContainer.style.color = 'var(--accent-danger)';
                    } else {
                        if (timeLeft <= 60) {
                            timerContainer.classList.add('expiring');
                        }
                    }
                    timeLeft--;
                }

                if (timeLeft > 0) {
                    let timerInterval = setInterval(updateSmsCountdown, 1000);
                    updateSmsCountdown();
                } else {
                    resendBtn.disabled = false;
                    timerContainer.innerHTML = 'কোডের মেয়াদ শেষ';
                    timerContainer.style.color = 'var(--accent-danger)';
                }

                function resendSmsCode() {
                    if (confirm('নতুন ভেরিফিকেশন কোড পাঠাতে চান?')) {
                        window.location.href = 'forget_password.php?step=2&method=phone&resend=true';
                    }
                }
            <?php endif; ?>
        <?php endif; ?>
    </script>

    <?php include_once 'footer.php'; ?>
</body>

</html>
<?php $conn->close(); ?>