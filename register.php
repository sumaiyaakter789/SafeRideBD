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
$user_data = [];

if ($step == 2 && isset($_SESSION['register_data'])) {
    $user_data = $_SESSION['register_data'];
} elseif ($step == 3 && isset($_SESSION['verification_data'])) {
    $user_data = $_SESSION['verification_data'];
}

// Handle resend code
if (isset($_GET['resend']) && $_GET['resend'] == 'true' && $step == 2 && isset($_SESSION['register_data'])) {
    $verification_code = rand(1000, 9999);
    $_SESSION['verification_code'] = $verification_code;
    $_SESSION['code_expiry'] = time() + 60;
    $_SESSION['demo_sms_code'] = $verification_code;
    $success = "নতুন ভেরিফিকেশন কোড পাঠানো হয়েছে!";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $step == 1) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $phone = preg_replace('/[^0-9]/', '', $phone);
    $phone = ltrim($phone, '0');

    if (substr($phone, 0, 3) != '880') {
        $phone = '880' . $phone;
    }

    if (strlen($phone) != 13) {
        $error = "সঠিক বাংলাদেশি ফোন নম্বর দিন (০১ দিয়ে শুরু ১১ ডিজিট)";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "সঠিক ইমেইল আইডি দিন";
    } elseif ($password !== $confirm_password) {
        $error = "পাসওয়ার্ড মিলছে না!";
    } elseif (strlen($password) < 8) {
        $error = "পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে";
    } else {
        $check_sql = "SELECT id FROM users WHERE email = ? OR phone = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $email, $phone);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "প্রদত্ত ইমেইল বা ফোন নম্বর আগে থেকেই নিবন্ধিত!";
        } else {
            $_SESSION['register_data'] = [
                'full_name' => $full_name,
                'email' => $email,
                'phone' => $phone,
                'password' => password_hash($password, PASSWORD_DEFAULT)
            ];

            $verification_code = rand(1000, 9999);
            $_SESSION['verification_code'] = $verification_code;
            $_SESSION['code_expiry'] = time() + 60;
            $_SESSION['demo_sms_code'] = $verification_code;

            header("Location: register.php?step=2");
            exit();
        }
        $check_stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $step == 2) {
    $verification_code = '';
    for ($i = 1; $i <= 4; $i++) {
        if (isset($_POST['digit' . $i])) {
            $verification_code .= $_POST['digit' . $i];
        }
    }

    if (isset($_SESSION['register_data']) && isset($_SESSION['verification_code'])) {
        if ($verification_code == $_SESSION['verification_code'] && time() < $_SESSION['code_expiry']) {
            $_SESSION['verification_data'] = $_SESSION['register_data'];
            $_SESSION['verification_data']['phone_verified'] = true;

            $email_code = rand(1000, 9999);
            $_SESSION['email_code'] = $email_code;
            $_SESSION['email_code_expiry'] = time() + 300;

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'info.afnan27@gmail.com';
                $mail->Password   = 'rokp jusi apxx wjkn';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('info.afnan27@gmail.com', 'SafeRideBD');
                $mail->addAddress($_SESSION['register_data']['email']);

                $mail->isHTML(true);
                $mail->Subject = 'Email Verification - SafeRideBD';
                $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                        <h2 style='color: #ff6b4a;'>ইমেইল ভেরিফিকেশন কোড</h2>
                        <p>হ্যালো " . $_SESSION['register_data']['full_name'] . ",</p>
                        <p>আপনার ইমেইল ভেরিফিকেশন কোড হল: <strong style='font-size: 24px; color: #4ade80;'>$email_code</strong></p>
                        <p>এই কোডটি ৫ মিনিটের মধ্যে ব্যবহার করুন।</p>
                        <p>আপনি যদি এই অনুরোধ না করে থাকেন, দয়া করে এই ইমেইল উপেক্ষা করুন।</p>
                        <br>
                        <p>ধন্যবাদ,<br>SafeRideBD টিম</p>
                    </div>
                ";

                $mail->send();
                header("Location: register.php?step=3");
                exit();
            } catch (Exception $e) {
                $error = "ভেরিফিকেশন ইমেইল পাঠাতে ব্যর্থ হয়েছে। Error: " . $mail->ErrorInfo;
            }
        } else {
            $error = "ভুল বা মেয়াদোত্তীর্ণ কোড!";
        }
    } else {
        $error = "সেশন শেষ হয়ে গেছে। আবার শুরু করুন।";
        header("Location: register.php?step=1");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $step == 3) {
    $email_code = '';
    for ($i = 1; $i <= 4; $i++) {
        if (isset($_POST['email_digit' . $i])) {
            $email_code .= $_POST['email_digit' . $i];
        }
    }

    if (isset($_SESSION['verification_data']) && isset($_SESSION['email_code'])) {
        if ($email_code == $_SESSION['email_code'] && time() < $_SESSION['email_code_expiry']) {
            $sql = "INSERT INTO users (full_name, email, phone, password, phone_verified, email_verified) 
                    VALUES (?, ?, ?, ?, TRUE, TRUE)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssss",
                $_SESSION['verification_data']['full_name'],
                $_SESSION['verification_data']['email'],
                $_SESSION['verification_data']['phone'],
                $_SESSION['verification_data']['password']
            );

            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;

                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $_SESSION['verification_data']['full_name'];
                $_SESSION['user_email'] = $_SESSION['verification_data']['email'];
                $_SESSION['user_phone'] = $_SESSION['verification_data']['phone'];

                unset($_SESSION['register_data']);
                unset($_SESSION['verification_data']);
                unset($_SESSION['verification_code']);
                unset($_SESSION['email_code']);
                unset($_SESSION['demo_sms_code']);
                unset($_SESSION['code_expiry']);
                unset($_SESSION['email_code_expiry']);

                header("Location: index.php");
                exit();
            } else {
                $error = "অ্যাকাউন্ট তৈরি করতে ব্যর্থ হয়েছে। আবার চেষ্টা করুন।";
            }
            $stmt->close();
        } else {
            $error = "ভুল বা মেয়াদোত্তীর্ণ কোড!";
        }
    } else {
        $error = "সেশন শেষ হয়ে গেছে। আবার শুরু করুন।";
        header("Location: register.php?step=1");
        exit();
    }
}

if ($step == 2 && !isset($_SESSION['verification_code']) && isset($_SESSION['register_data'])) {
    $verification_code = rand(1000, 9999);
    $_SESSION['verification_code'] = $verification_code;
    $_SESSION['code_expiry'] = time() + 60;
    $_SESSION['demo_sms_code'] = $verification_code;
}
?>

<!DOCTYPE html>
<html lang="bn">

<head>
    <?php include_once 'navbar.php'; ?>
    <style>
        .divider {
            position: relative;
            text-align: center;
            margin: 30px 0 20px;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background-color: var(--border-color);
        }

        .divider span {
            position: relative;
            background-color: var(--bg-card);
            padding: 0 20px;
            color: var(--text-muted);
            font-size: 14px;
            z-index: 1;
        }

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

        .progress-steps {
            max-width: 600px;
            margin: 30px auto 0;
            position: relative;
        }

        .progress-bar {
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 3px;
            background-color: var(--border-color);
            z-index: 1;
        }

        .progress-fill {
            position: absolute;
            top: 15px;
            left: 0;
            height: 3px;
            background-color: var(--accent-primary);
            z-index: 2;
            transition: width 0.3s ease;
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            position: relative;
            z-index: 3;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .step-number {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--bg-card);
            border: 2px solid var(--border-color);
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            transition: all 0.2s;
        }

        .step.active .step-number {
            background-color: var(--accent-primary);
            border-color: var(--accent-primary);
            color: white;
        }

        .step.completed .step-number {
            background-color: var(--accent-secondary);
            border-color: var(--accent-secondary);
            color: white;
        }

        .step-label {
            font-size: 13px;
            color: var(--text-muted);
        }

        .step.active .step-label {
            color: var(--accent-primary);
        }

        .step.completed .step-label {
            color: var(--accent-secondary);
        }

        .phone-display {
            font-family: monospace;
            font-size: 18px;
            background-color: var(--bg-secondary);
            padding: 8px 16px;
            border-radius: 6px;
            display: inline-block;
            margin-top: 5px;
            color: var(--text-primary);
        }

        .otp-container {
            display: flex;
            justify-content: center;
            margin: 30px 0;
        }

        .otp-inputs {
            display: flex;
            gap: 16px;
            justify-content: center;
        }

        .otp-digit {
            width: 70px;
            height: 70px;
            text-align: center;
            font-size: 28px;
            font-weight: 600;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            transition: all 0.2s;
        }

        .otp-digit:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(255, 107, 74, 0.2);
        }

        .otp-digit.filled {
            border-color: var(--accent-secondary);
            background-color: rgba(74, 222, 128, 0.1);
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
            margin-top: 16px;
        }

        .resend-btn:hover:not(:disabled) {
            border-color: var(--accent-primary);
            color: var(--text-primary);
        }

        .resend-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .timer {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 10px;
        }

        .timer.expiring {
            color: var(--accent-danger);
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

        .info-box {
            background-color: var(--bg-secondary);
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
            text-align: center;
            border: 1px solid var(--border-color);
        }

        .info-icon {
            font-size: 40px;
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

        @media (max-width: 768px) {
            .otp-digit {
                width: 60px;
                height: 60px;
                font-size: 24px;
            }

            .otp-inputs {
                gap: 12px;
            }
        }

        @media (max-width: 480px) {
            .otp-digit {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }

            .otp-inputs {
                gap: 8px;
            }
        }

        .shake {
            animation: shake 0.5s ease-in-out;
            border-color: var(--accent-danger) !important;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            10%,
            30%,
            50%,
            70%,
            90% {
                transform: translateX(-5px);
            }

            20%,
            40%,
            60%,
            80% {
                transform: translateX(5px);
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">
                <?php
                if ($step == 1) echo "SafeRideBD-তে যোগ দিন";
                elseif ($step == 2) echo "আপনার ফোন নম্বর যাচাই করুন";
                else echo "আপনার ইমেইল যাচাই করুন";
                ?>
            </h1>
            <p class="page-subtitle">
                <?php
                if ($step == 1) echo "আপনার পছনের রুট সংরক্ষণ এবং ভাড়ার তথ্য পেতে অ্যাকাউন্ট খুলুন";
                elseif ($step == 2) echo "আমরা আপনার ফোনে একটি ৪-অঙ্কের কোড পাঠিয়েছি";
                else echo "আমরা আপনার ইমেইলে একটি ৪-অঙ্কের কোড পাঠিয়েছি";
                ?>
            </p>

            <div class="progress-steps">
                <div class="progress-bar"></div>
                <div class="progress-fill" style="width: <?php echo ($step - 1) * 50; ?>%;"></div>

                <div class="step-indicator">
                    <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
                        <div class="step-number">1</div>
                        <div class="step-label">তথ্য</div>
                    </div>
                    <div class="step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
                        <div class="step-number">2</div>
                        <div class="step-label">ফোন</div>
                    </div>
                    <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>">
                        <div class="step-number">3</div>
                        <div class="step-label">ইমেইল</div>
                    </div>
                </div>
            </div>
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
                    <i class="fas fa-user-plus"></i> নতুন অ্যাকাউন্ট খুলুন
                </h2>

                <form method="POST" action="register.php?step=1" id="registerForm">
                    <div class="form-group">
                        <label for="full_name">
                            <i class="fas fa-user"></i>
                            আপনার নাম
                        </label>
                        <div class="select-container">
                            <i class="fas fa-user"></i>
                            <input type="text" id="full_name" name="full_name" class="typeahead-input"
                                placeholder="আপনার পুরো নাম লিখুন" required
                                value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i>
                            ইমেইল
                        </label>
                        <div class="select-container">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" class="typeahead-input"
                                placeholder="আপনার ইমেইল আইডি লিখুন" required
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="phone">
                            <i class="fas fa-phone"></i>
                            ফোন নম্বর (বাংলাদেশ)
                        </label>
                        <div class="select-container">
                            <i class="fas fa-phone"></i>
                            <input type="tel" id="phone" name="phone" class="typeahead-input"
                                placeholder="০১XXX-XXXXXX" required
                                value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                pattern="01[0-9]{9}"
                                title="০১ দিয়ে শুরু ১১ ডিজিটের নম্বর দিন">
                        </div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 5px;">
                            উদাহরণ: ০১৭১২৩৪৫৬৭৮
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i>
                            পাসওয়ার্ড
                        </label>
                        <div class="select-container" style="position: relative;">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" class="typeahead-input"
                                placeholder="শক্তিশালী পাসওয়ার্ড দিন" required
                                onkeyup="checkPasswordStrength(this.value)">
                            <i class="fas fa-eye" id="togglePassword1"
                                style="position: absolute; left: 440px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted); z-index: 3;"></i>
                        </div>
                        <div class="password-strength" id="passwordStrength">
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
                                placeholder="আবার পাসওয়ার্ড লিখুন" required>
                            <i class="fas fa-eye" id="togglePassword2"
                                style="position: absolute; left: 440px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted); z-index: 3;"></i>
                        </div>
                        <div id="passwordMatch" class="password-match"></div>
                    </div>

                    <button type="submit" class="search-btn" id="registerBtn">
                        <i class="fas fa-arrow-right"></i> পরবর্তী ধাপে যান
                    </button>
                </form>

                <div class="divider">
                    <span>অথবা</span>
                </div>

                <div style="text-align: center;">
                    <p style="color: var(--text-muted); margin-bottom: 20px;">
                        ইতিমধ্যে অ্যাকাউন্ট আছে?
                    </p>
                    <a href="login.php" class="register-btn" style="display: inline-block; color: white;">
                        <i class="fas fa-sign-in-alt"></i> লগইন করুন
                    </a>
                </div>

            <?php elseif ($step == 2): ?>
                <?php
                $display_phone = '';
                if (isset($user_data['phone'])) {
                    $phone_num = $user_data['phone'];
                    if (substr($phone_num, 0, 3) == '880') {
                        $display_phone = '0' . substr($phone_num, 3);
                    } else {
                        $display_phone = $phone_num;
                    }
                }
                ?>

                <h2 class="card-title">
                    <i class="fas fa-mobile-alt"></i> ফোন ভেরিফিকেশন
                </h2>

                <div class="info-box">
                    <div class="info-icon">
                        <i class="fas fa-sms"></i>
                    </div>
                    <h3>কোড পাঠানো হয়েছে</h3>
                    <p><span class="phone-display"><?php echo $display_phone; ?></span> নম্বরে</p>
                    <p style="font-size: 14px;">আমরা একটি ৪-অঙ্কের ভেরিফিকেশন কোড পাঠিয়েছি। নিচে সেটি দিন।</p>

                    <?php if (isset($_SESSION['demo_sms_code'])): ?>
                        <div class="demo-code">
                            <p><strong>ডেমো কোড: <?php echo $_SESSION['demo_sms_code']; ?></strong></p>
                            <p style="font-size: 12px; margin-top: 8px;">(ডেভেলপমেন্ট মোড - ১ মিনিটের মধ্যে ব্যবহার করুন)</p>
                        </div>
                    <?php endif; ?>
                </div>

                <form method="POST" action="register.php?step=2" id="verificationForm">
                    <div class="form-group">
                        <label style="margin-bottom: 15px; display: block; text-align: center;">
                            <i class="fas fa-shield-alt"></i>
                            ৪-অঙ্কের কোড দিন
                        </label>

                        <div class="otp-container">
                            <div class="otp-inputs">
                                <input type="text" name="digit1" maxlength="1" pattern="[0-9]" required class="otp-digit" autocomplete="off" autofocus>
                                <input type="text" name="digit2" maxlength="1" pattern="[0-9]" required class="otp-digit" autocomplete="off">
                                <input type="text" name="digit3" maxlength="1" pattern="[0-9]" required class="otp-digit" autocomplete="off">
                                <input type="text" name="digit4" maxlength="1" pattern="[0-9]" required class="otp-digit" autocomplete="off">
                            </div>
                        </div>
                    </div>

                    <div style="text-align: center; margin-bottom: 25px;">
                        <div class="timer" id="countdown">
                            কোডের মেয়াদ: <span id="timer">০১:০০</span>
                        </div>
                        <button type="button" id="resendBtn" class="resend-btn" disabled onclick="resendCode()">
                            <i class="fas fa-redo"></i> নতুন কোড পাঠান
                        </button>
                    </div>

                    <button type="submit" class="search-btn">
                        <i class="fas fa-check-circle"></i> যাচাই করুন
                    </button>

                    <div style="text-align: center; margin-top: 20px;">
                        <a href="register.php?step=1" class="back-link">
                            <i class="fas fa-arrow-left"></i> আগের ধাপে ফিরে যান
                        </a>
                    </div>
                </form>

            <?php else: ?>
                <h2 class="card-title">
                    <i class="fas fa-envelope"></i> ইমেইল ভেরিফিকেশন
                </h2>

                <div class="info-box">
                    <div class="info-icon">
                        <i class="fas fa-envelope-open-text"></i>
                    </div>
                    <h3>কোড পাঠানো হয়েছে</h3>
                    <p><strong><?php echo isset($user_data['email']) ? htmlspecialchars($user_data['email']) : ''; ?></strong> ঠিকানায়</p>
                    <p style="font-size: 14px;">আপনার ইমেইলে একটি ৪-অঙ্কের ভেরিফিকেশন কোড পাঠানো হয়েছে।</p>
                </div>

                <form method="POST" action="register.php?step=3" id="emailVerificationForm">
                    <div class="form-group">
                        <label style="margin-bottom: 15px; display: block; text-align: center;">
                            <i class="fas fa-shield-alt"></i>
                            ৪-অঙ্কের কোড দিন
                        </label>

                        <div class="otp-container">
                            <div class="otp-inputs">
                                <input type="text" name="email_digit1" maxlength="1" pattern="[0-9]" required class="otp-digit" autocomplete="off" autofocus>
                                <input type="text" name="email_digit2" maxlength="1" pattern="[0-9]" required class="otp-digit" autocomplete="off">
                                <input type="text" name="email_digit3" maxlength="1" pattern="[0-9]" required class="otp-digit" autocomplete="off">
                                <input type="text" name="email_digit4" maxlength="1" pattern="[0-9]" required class="otp-digit" autocomplete="off">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="search-btn">
                        <i class="fas fa-check-circle"></i> নিবন্ধন সম্পন্ন করুন
                    </button>

                    <div style="text-align: center; margin-top: 20px;">
                        <a href="register.php?step=2" class="back-link">
                            <i class="fas fa-arrow-left"></i> আগের ধাপে ফিরে যান
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        <?php if ($step == 1): ?>

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

                switch (strength) {
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
                const password = document.getElementById('password').value;
                const confirmPassword = this.value;
                updatePasswordMatch(password, confirmPassword);
            });

            document.getElementById('togglePassword1').addEventListener('click', function() {
                const password = document.getElementById('password');
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

            document.getElementById('phone').addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 0 && !value.startsWith('01')) {
                    value = '01' + value.replace(/^01/, '');
                }
                if (value.length > 11) {
                    value = value.substring(0, 11);
                }
                e.target.value = value;
            });

        <?php elseif ($step == 2): ?>
            const otpDigits = document.querySelectorAll('.otp-digit');

            otpDigits.forEach((digit, index) => {
                digit.addEventListener('input', (e) => {
                    e.target.value = e.target.value.replace(/[^0-9]/g, '');

                    if (e.target.value.length === 1) {
                        e.target.classList.add('filled');
                    } else {
                        e.target.classList.remove('filled');
                    }

                    if (e.target.value.length === 1 && index < otpDigits.length - 1) {
                        otpDigits[index + 1].focus();
                    }

                    if (Array.from(otpDigits).every(d => d.value.length === 1)) {
                        setTimeout(() => {
                            document.getElementById('verificationForm').submit();
                        }, 100);
                    }
                });

                digit.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && e.target.value.length === 0 && index > 0) {
                        otpDigits[index - 1].focus();
                        otpDigits[index - 1].classList.remove('filled');
                    }

                    if (e.key === 'ArrowLeft' && index > 0) {
                        otpDigits[index - 1].focus();
                    }
                    if (e.key === 'ArrowRight' && index < otpDigits.length - 1) {
                        otpDigits[index + 1].focus();
                    }
                });

                digit.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '');

                    if (pastedData.length === 4) {
                        otpDigits.forEach((d, i) => {
                            if (i < 4) {
                                d.value = pastedData[i] || '';
                                if (d.value.length === 1) {
                                    d.classList.add('filled');
                                }
                            }
                        });

                        if (Array.from(otpDigits).every(d => d.value.length === 1)) {
                            setTimeout(() => {
                                document.getElementById('verificationForm').submit();
                            }, 100);
                        } else {
                            otpDigits[3].focus();
                        }
                    }
                });
            });

            let timeLeft = <?php echo isset($_SESSION['code_expiry']) ? max(0, $_SESSION['code_expiry'] - time()) : 60; ?>;

            const countdownElement = document.getElementById('timer');
            const timerContainer = document.getElementById('countdown');
            const resendBtn = document.getElementById('resendBtn');

            function updateCountdown() {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                countdownElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    resendBtn.disabled = false;
                    timerContainer.innerHTML = 'কোডের মেয়াদ শেষ';
                    timerContainer.style.color = 'var(--accent-danger)';

                    otpDigits.forEach(digit => {
                        digit.classList.add('shake');
                    });
                } else {
                    if (timeLeft <= 10) {
                        timerContainer.classList.add('expiring');
                    } else {
                        timerContainer.classList.remove('expiring');
                    }
                }
                timeLeft--;
            }

            if (timeLeft > 0) {
                let timerInterval = setInterval(updateCountdown, 1000);
                updateCountdown();
            } else {
                resendBtn.disabled = false;
                timerContainer.innerHTML = 'কোডের মেয়াদ শেষ';
                timerContainer.style.color = 'var(--accent-danger)';
            }

            function resendCode() {
                if (confirm('নতুন ভেরিফিকেশন কোড পাঠাতে চান?')) {
                    window.location.href = 'register.php?step=2&resend=true';
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                if (otpDigits[0]) {
                    otpDigits[0].focus();
                }
            });

        <?php elseif ($step == 3): ?>
            const emailOtpDigits = document.querySelectorAll('#emailVerificationForm .otp-digit');

            emailOtpDigits.forEach((digit, index) => {
                digit.addEventListener('input', (e) => {
                    e.target.value = e.target.value.replace(/[^0-9]/g, '');

                    if (e.target.value.length === 1) {
                        e.target.classList.add('filled');
                    } else {
                        e.target.classList.remove('filled');
                    }

                    if (e.target.value.length === 1 && index < emailOtpDigits.length - 1) {
                        emailOtpDigits[index + 1].focus();
                    }

                    if (Array.from(emailOtpDigits).every(d => d.value.length === 1)) {
                        setTimeout(() => {
                            document.getElementById('emailVerificationForm').submit();
                        }, 100);
                    }
                });

                digit.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && e.target.value.length === 0 && index > 0) {
                        emailOtpDigits[index - 1].focus();
                        emailOtpDigits[index - 1].classList.remove('filled');
                    }

                    if (e.key === 'ArrowLeft' && index > 0) {
                        emailOtpDigits[index - 1].focus();
                    }
                    if (e.key === 'ArrowRight' && index < emailOtpDigits.length - 1) {
                        emailOtpDigits[index + 1].focus();
                    }
                });

                digit.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '');

                    if (pastedData.length === 4) {
                        emailOtpDigits.forEach((d, i) => {
                            if (i < 4) {
                                d.value = pastedData[i] || '';
                                if (d.value.length === 1) {
                                    d.classList.add('filled');
                                }
                            }
                        });

                        if (Array.from(emailOtpDigits).every(d => d.value.length === 1)) {
                            setTimeout(() => {
                                document.getElementById('emailVerificationForm').submit();
                            }, 100);
                        } else {
                            emailOtpDigits[3].focus();
                        }
                    }
                });
            });

            document.addEventListener('DOMContentLoaded', function() {
                if (emailOtpDigits[0]) {
                    emailOtpDigits[0].focus();
                }
            });
        <?php endif; ?>

        function handleGoogleSignIn() {
            const googleAuthUrl = 'https://accounts.google.com/o/oauth2/auth';
            const params = {
                client_id: '21830070433-8ntms8h3k10jtqamv9tumv769cp3bm2r.apps.googleusercontent.com',
                redirect_uri: 'http://localhost/saferidebd/google_callback.php',
                response_type: 'code',
                scope: 'email profile',
                access_type: 'offline',
                prompt: 'consent'
            };

            const urlParams = new URLSearchParams(params).toString();
            window.location.href = `${googleAuthUrl}?${urlParams}`;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const emergencyBtn = document.getElementById('emergencyBtn');
            if (emergencyBtn) {
                emergencyBtn.addEventListener('click', function() {
                    const popup = document.getElementById('emergencyPopup');
                    const popupMessage = document.getElementById('popupMessage');
                    const loginRequiredSection = document.getElementById('loginRequiredSection');
                    const emergencyFeatures = document.getElementById('emergencyFeatures');
                    const popupButtons = document.getElementById('popupButtons');

                    popupMessage.innerHTML = "শুধুমাত্র নিবন্ধিত ব্যবহারকারীদের জন্য";
                    loginRequiredSection.style.display = 'block';
                    emergencyFeatures.style.display = 'none';

                    popupButtons.innerHTML = `
                        <button class="popup-btn popup-login-btn" onclick="window.location.href='login.php'">
                            <i class="fas fa-sign-in-alt"></i> লগইন করুন
                        </button>
                        <button class="popup-btn popup-close-btn" id="closePopupBtn">
                            <i class="fas fa-times"></i> বন্ধ করুন
                        </button>
                    `;

                    popup.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });
            }
        });
    </script>

    <?php include_once 'footer.php'; ?>
</body>

</html>
<?php $conn->close(); ?>