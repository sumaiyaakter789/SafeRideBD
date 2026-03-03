<?php
session_start();
include_once 'db_config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login']);
    $password = $_POST['password'];

    if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
        $sql = "SELECT * FROM users WHERE email = ?";
    } else {
        $login = preg_replace('/[^0-9]/', '', $login);
        if (substr($login, 0, 3) != '880') {
            $login = '880' . ltrim($login, '0');
        }
        $sql = "SELECT * FROM users WHERE phone = ?";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_phone'] = $user['phone'];
            $_SESSION['profile_image'] = $user['profile_image'];

            header("Location: index.php");
            exit();
        } else {
            $error = "ভুল পাসওয়ার্ড!";
        }
    } else {
        $error = "কোন ব্যবহারকারীর তথ্য পাওয়া যায়নি!";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <?php include_once 'navbar.php'; ?>
    <style>
        .forgot-link {
            color: var(--accent-primary) !important;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .forgot-link:hover {
            color: var(--text-primary) !important;
            text-decoration: underline !important;
        }

        .google-btn {
            width: 100%;
            padding: 16px 24px;
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-family: 'Bornomala', serif;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            color: var(--text-primary);
        }

        .google-btn:hover {
            background-color: var(--bg-hover);
            border-color: var(--accent-primary);
            transform: translateY(-2px);
        }

        .google-btn img {
            width: 20px;
            height: 20px;
        }

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

        .security-note {
            text-align: center;
            margin-top: 30px;
            color: var(--text-muted);
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .security-note i {
            color: var(--accent-secondary);
        }

        .register-prompt {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid var(--border-color);
        }

        .register-prompt p {
            color: var(--text-muted);
            margin-bottom: 20px;
            font-size: 15px;
        }

        .register-btn {
            display: inline-block;
            padding: 14px 32px;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-family: 'Bornomala', serif;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
        }

        .register-btn:hover {
            background-color: var(--bg-hover);
            border-color: var(--accent-primary);
            transform: translateY(-2px);
        }

        .register-btn i {
            color: var(--accent-primary);
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">SafeRideBD-তে স্বাগতম</h1>
            <p class="page-subtitle">আপনার সংরক্ষিত রুট এবং ভাড়ার তথ্য দেখতে সাইন ইন করুন</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message active">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2 class="card-title">
                <i class="fas fa-sign-in-alt"></i> আপনার অ্যাকাউন্টে সাইন ইন করুন
            </h2>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="login">
                        <i class="fas fa-user"></i>
                        ইমেইল বা ফোন নম্বর
                    </label>
                    <div class="select-container">
                        <i class="fas fa-at"></i>
                        <input type="text" id="login" name="login" class="typeahead-input"
                            placeholder="আপনার ইমেইল বা ফোন নম্বর লিখুন" required
                            value="<?php echo isset($_POST['login']) ? htmlspecialchars($_POST['login']) : ''; ?>">
                    </div>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 5px;">
                        উদাহরণ: example@email.com অথবা 01712345678
                    </div>
                </div>

                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label for="password" style="margin-bottom: 0;">
                            <i class="fas fa-lock"></i>
                            পাসওয়ার্ড
                        </label>
                        <a href="forget_password.php" class="forgot-link">
                            <i class="fas fa-key"></i> পাসওয়ার্ড ভুলে গেছেন?
                        </a>
                    </div>
                    <div class="select-container" style="position: relative;">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" class="typeahead-input"
                            placeholder="আপনার পাসওয়ার্ড লিখুন" required>
                        <i class="fas fa-eye" id="togglePassword"
                            style="position: absolute; left: 440px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted); z-index: 3;"></i>
                    </div>
                </div>

                <button type="submit" class="search-btn">
                    <i class="fas fa-sign-in-alt"></i> সাইন ইন করুন
                </button>
            </form>

            <div class="register-prompt">
                <p>অ্যাকাউন্ট নেই?</p>
                <a href="register.php" class="register-btn">
                    <i class="fas fa-user-plus"></i> নতুন অ্যাকাউন্ট খুলুন
                </a>

                <div class="divider">
                    <span>অথবা</span>
                </div>

                <button type="button" class="google-btn" onclick="handleGoogleSignIn()">
                    <img src="https://img.icons8.com/color/48/google-logo.png" alt="Google Logo">
                    Google অ্যাকাউন্ট দিয়ে লগইন করুন
                </button>
            </div>
        </div>

        <div class="security-note">
            <i class="fas fa-shield-alt"></i>
            <span>আপনার নিরাপত্তা আমাদের অগ্রাধিকার। সমস্ত ডেটা এনক্রিপ্টেড এবং সুরক্ষিত।</span>
        </div>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

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