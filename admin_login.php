<?php
session_start();
include_once 'db_config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM admin_users WHERE (username = ? OR email = ?) AND is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();

        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_role'] = $admin['role'];

            // Update last login
            $update_sql = "UPDATE admin_users SET last_login = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $admin['id']);
            $update_stmt->execute();
            $update_stmt->close();

            // Log login
            $log_sql = "INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, 'login', 'Admin logged in', ?)";
            $log_stmt = $conn->prepare($log_sql);
            $ip = $_SERVER['REMOTE_ADDR'];
            $log_stmt->bind_param("is", $admin['id'], $ip);
            $log_stmt->execute();
            $log_stmt->close();

            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "ভুল পাসওয়ার্ড!";
        }
    } else {
        $error = "ব্যবহারকারী পাওয়া যায়নি!";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>অ্যাডমিন লগইন - SafeRideBD</title>
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
            --shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
        }

        body {
            font-family: 'Bornomala', serif;
            background: linear-gradient(135deg, var(--bg-primary), var(--bg-secondary));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            max-width: 450px;
            width: 100%;
        }

        .logo-area {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-img {
            height: 80px;
            width: auto;
            filter: brightness(0) invert(1);
            margin-bottom: 15px;
        }

        .logo-text {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .logo-subtext {
            color: var(--accent-primary);
            font-size: 16px;
            margin-top: 5px;
        }

        .login-card {
            background-color: var(--bg-card);
            border-radius: 16px;
            padding: 32px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h2 {
            color: var(--text-primary);
            font-size: 24px;
            margin-bottom: 8px;
        }

        .login-header p {
            color: var(--text-muted);
            font-size: 14px;
        }

        .error-message {
            background-color: rgba(239, 68, 68, 0.1);
            border-left: 4px solid var(--accent-danger);
            color: var(--accent-danger);
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: <?php echo $error ? 'block' : 'none'; ?>;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-secondary);
            font-size: 15px;
        }

        .form-group label i {
            color: var(--accent-primary);
            margin-right: 8px;
        }

        .input-container {
            position: relative;
        }

        .input-container i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 16px;
        }

        .input-container input {
            width: 100%;
            padding: 14px 20px 14px 48px;
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-family: 'Bornomala', serif;
            font-size: 15px;
            transition: all 0.2s;
        }

        .input-container input:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(255, 107, 74, 0.2);
        }

        .input-container input::placeholder {
            color: var(--text-muted);
        }

        .toggle-password {
            position: absolute;
            left: 330px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            cursor: pointer;
            background: none;
            border: none;
            font-size: 16px;
        }

        .toggle-password:hover {
            color: var(--text-primary);
        }

        .login-btn {
            width: 100%;
            padding: 16px;
            background-color: var(--accent-primary);
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
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .login-btn:hover {
            background-color: #ff5a3a;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(255, 107, 74, 0.3);
        }

        .security-note {
            text-align: center;
            margin-top: 20px;
            color: var(--text-muted);
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .security-note i {
            color: var(--accent-secondary);
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-link a:hover {
            color: var(--accent-primary);
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 24px;
            }
            
            .logo-text {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-area">
            <img src="saferidebd_removebg_main.png" alt="SafeRideBD" class="logo-img">
            <div class="logo-text">SafeRideBD</div>
            <div class="logo-subtext">অ্যাডমিন প্যানেল</div>
        </div>

        <div class="login-card">
            <div class="login-header">
                <h2>অ্যাডমিন লগইন</h2>
                <p>শুধুমাত্র অনুমোদিত অ্যাডমিনদের জন্য</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="admin_login.php">
                <div class="form-group">
                    <label>
                        <i class="fas fa-user"></i>
                        ইউজারনেম বা ইমেইল
                    </label>
                    <div class="input-container">
                        <i class="fas fa-at"></i>
                        <input type="text" name="username" placeholder="ইউজারনেম বা ইমেইল লিখুন" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-lock"></i>
                        পাসওয়ার্ড
                    </label>
                    <div class="input-container">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" placeholder="পাসওয়ার্ড লিখুন" required>
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    লগইন করুন
                </button>
            </form>

            <div class="security-note">
                <i class="fas fa-shield-alt"></i>
                <span>নিরাপদ অ্যাডমিন অ্যাক্সেস</span>
            </div>

            <div class="back-link">
                <a href="index.php">
                    <i class="fas fa-arrow-left"></i>
                    মূল সাইটে ফিরে যান
                </a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.querySelector('.toggle-password i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>