<?php
require_once 'admin_auth.php';

// Only super admin can access this page
if (!isSuperAdmin()) {
    header("Location: admin_dashboard.php");
    exit();
}

include_once 'db_config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];

    // Validation
    if (empty($username) || empty($full_name) || empty($email) || empty($password)) {
        $error = "সব ফিল্ড পূরণ করুন!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "সঠিক ইমেইল ঠিকানা দিন!";
    } elseif ($password !== $confirm_password) {
        $error = "পাসওয়ার্ড মিলছে না!";
    } elseif (strlen($password) < 8) {
        $error = "পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে!";
    } else {
        // Check if username exists
        $check_sql = "SELECT id FROM admin_users WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "এই ইউজারনেম বা ইমেইল ইতিমধ্যে ব্যবহার হচ্ছে!";
        } else {
            // Insert new admin
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_sql = "INSERT INTO admin_users (username, full_name, email, password, role, created_by) VALUES (?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sssssi", $username, $full_name, $email, $hashed_password, $role, $_SESSION['admin_id']);

            if ($insert_stmt->execute()) {
                // Log activity
                $log_sql = "INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, 'admin_create', ?, ?)";
                $log_stmt = $conn->prepare($log_sql);
                $details = "নতুন অ্যাডমিন তৈরি করা হয়েছে: {$username} ({$role})";
                $ip = $_SERVER['REMOTE_ADDR'];
                $log_stmt->bind_param("iss", $_SESSION['admin_id'], $details, $ip);
                $log_stmt->execute();
                $log_stmt->close();

                $success = "নতুন অ্যাডমিন সফলভাবে তৈরি করা হয়েছে!";
                
                // Clear form
                $_POST = array();
            } else {
                $error = "অ্যাডমিন তৈরি করতে ব্যর্থ হয়েছে!";
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>নতুন অ্যাডমিন যোগ - SafeRideBD</title>
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

        /* Sidebar (same as before) */
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
            background-color: rgba(255, 107, 74, 0.2);
            color: var(--accent-primary);
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
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-subtitle {
            color: var(--text-muted);
            font-size: 15px;
        }

        .form-card {
            background-color: var(--bg-card);
            border-radius: 12px;
            padding: 30px;
            border: 1px solid var(--border-color);
            max-width: 600px;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: rgba(74, 222, 128, 0.1);
            border-left: 4px solid var(--accent-secondary);
            color: var(--accent-secondary);
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            border-left: 4px solid var(--accent-danger);
            color: var(--accent-danger);
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

        .form-control {
            width: 100%;
            padding: 14px 16px;
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
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

        .form-control::placeholder {
            color: var(--text-muted);
        }

        .role-select {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }

        .role-option {
            flex: 1;
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }

        .role-option:hover {
            border-color: var(--accent-primary);
        }

        .role-option.selected {
            border-color: var(--accent-primary);
            background-color: rgba(255, 107, 74, 0.1);
        }

        .role-option input[type="radio"] {
            display: none;
        }

        .role-option i {
            font-size: 24px;
            color: var(--accent-primary);
            margin-bottom: 10px;
        }

        .role-option h4 {
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .role-option p {
            color: var(--text-muted);
            font-size: 12px;
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

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 14px 28px;
            border-radius: 8px;
            font-family: 'Bornomala', serif;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            flex: 1;
        }

        .btn-primary {
            background-color: var(--accent-primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: #ff5a3a;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(255, 107, 74, 0.3);
        }

        .btn-secondary {
            background-color: var(--bg-hover);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background-color: var(--border-color);
            color: var(--text-primary);
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .role-select {
                flex-direction: column;
            }
            
            .form-actions {
                flex-direction: column;
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
            <div class="admin-role">সুপার অ্যাডমিন</div>
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
            <li class="nav-item">
                <a href="admin_bulk_upload.php" class="nav-link">
                    <i class="fas fa-file-upload"></i>
                    বাল্ক আপলোড
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_manage_admins.php" class="nav-link active">
                    <i class="fas fa-users-cog"></i>
                    অ্যাডমিন ব্যবস্থাপনা
                </a>
            </li>
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
            <h1 class="page-title">নতুন অ্যাডমিন যোগ</h1>
            <p class="page-subtitle">নতুন অ্যাডমিন ব্যবহারকারী তৈরি করুন</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" action="admin_recruit_admin.php" id="recruitForm">
                <div class="form-group">
                    <label>
                        <i class="fas fa-user"></i>
                        ইউজারনেম <span style="color: var(--accent-danger);">*</span>
                    </label>
                    <input type="text" name="username" class="form-control" 
                           placeholder="ইউজারনেম লিখুন" required
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-user-circle"></i>
                        পুরো নাম <span style="color: var(--accent-danger);">*</span>
                    </label>
                    <input type="text" name="full_name" class="form-control" 
                           placeholder="পুরো নাম লিখুন" required
                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-envelope"></i>
                        ইমেইল <span style="color: var(--accent-danger);">*</span>
                    </label>
                    <input type="email" name="email" class="form-control" 
                           placeholder="ইমেইল লিখুন" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-lock"></i>
                        পাসওয়ার্ড <span style="color: var(--accent-danger);">*</span>
                    </label>
                    <input type="password" name="password" id="password" class="form-control" 
                           placeholder="পাসওয়ার্ড লিখুন" required onkeyup="checkPasswordStrength(this.value)">
                    <div class="password-strength">
                        <div class="strength-bar">
                            <div id="strengthBar" class="strength-fill"></div>
                        </div>
                        <div id="strengthText" class="strength-text"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-lock"></i>
                        পাসওয়ার্ড নিশ্চিত করুন <span style="color: var(--accent-danger);">*</span>
                    </label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" 
                           placeholder="আবার পাসওয়ার্ড লিখুন" required onkeyup="checkPasswordMatch()">
                    <div id="passwordMatch" style="font-size: 12px; margin-top: 5px;"></div>
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-user-tag"></i>
                        অ্যাডমিন রোল <span style="color: var(--accent-danger);">*</span>
                    </label>
                    <div class="role-select">
                        <label class="role-option <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : 'selected'; ?>">
                            <input type="radio" name="role" value="admin" <?php echo (!isset($_POST['role']) || $_POST['role'] == 'admin') ? 'checked' : ''; ?>>
                            <i class="fas fa-user-shield"></i>
                            <h4>অ্যাডমিন</h4>
                            <p>ভাড়ার তথ্য দেখতে, যোগ, সম্পাদনা ও মুছতে পারবে</p>
                        </label>
                        <label class="role-option <?php echo (isset($_POST['role']) && $_POST['role'] == 'super_admin') ? 'selected' : ''; ?>">
                            <input type="radio" name="role" value="super_admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'super_admin') ? 'checked' : ''; ?>>
                            <i class="fas fa-crown"></i>
                            <h4>সুপার অ্যাডমিন</h4>
                            <p>সম্পূর্ণ নিয়ন্ত্রণ + অ্যাডমিন ব্যবস্থাপনা</p>
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i>
                        অ্যাডমিন তৈরি করুন
                    </button>
                    <a href="admin_manage_admins.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        বাতিল
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
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

            checkPasswordMatch();
        }

        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('passwordMatch');

            if (confirm) {
                if (password === confirm) {
                    matchDiv.innerHTML = '<i class="fas fa-check-circle" style="color: #4ade80;"></i> পাসওয়ার্ড মিলেছে';
                    matchDiv.style.color = '#4ade80';
                } else {
                    matchDiv.innerHTML = '<i class="fas fa-times-circle" style="color: #ef4444;"></i> পাসওয়ার্ড মেলেনি';
                    matchDiv.style.color = '#ef4444';
                }
            } else {
                matchDiv.innerHTML = '';
            }
        }

        document.querySelectorAll('.role-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.role-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
<?php $conn->close(); ?>