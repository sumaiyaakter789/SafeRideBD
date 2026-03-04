<?php
require_once 'admin_auth.php';
include_once 'db_config.php';

$error = '';
$success = '';

// Get fare ID from URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header("Location: admin_fare_list.php");
    exit();
}

// Fetch existing fare data
$select_sql = "SELECT * FROM fare_chart WHERE id = ?";
$select_stmt = $conn->prepare($select_sql);
$select_stmt->bind_param("i", $id);
$select_stmt->execute();
$result = $select_stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: admin_fare_list.php");
    exit();
}

$fare_data = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $from_location = trim($_POST['from']);
    $to_location = trim($_POST['to']);
    $fare = floatval($_POST['fare']);
    $distance = floatval($_POST['distance']);
    $operating_bus = trim($_POST['operating_bus']);

    // Validation
    if (empty($from_location) || empty($to_location)) {
        $error = "যাত্রা শুরু এবং গন্তব্য স্থান অবশ্যই দিতে হবে!";
    } elseif ($fare <= 0) {
        $error = "ভাড়া অবশ্যই ০ এর বেশি হতে হবে!";
    } elseif ($distance <= 0) {
        $error = "দূরত্ব অবশ্যই ০ এর বেশি হতে হবে!";
    } else {
        // Check if route already exists (excluding current)
        $check_sql = "SELECT id FROM fare_chart WHERE `from` = ? AND `to` = ? AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ssi", $from_location, $to_location, $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "এই রুটের জন্য ইতিমধ্যে ভাড়ার তথ্য রয়েছে!";
        } else {
            // Update fare
            $update_sql = "UPDATE fare_chart SET `from` = ?, `to` = ?, fare = ?, distance_km = ?, operating_bus = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssddsi", $from_location, $to_location, $fare, $distance, $operating_bus, $id);

            if ($update_stmt->execute()) {
                // Log activity
                $log_sql = "INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, 'update', ?, ?)";
                $log_stmt = $conn->prepare($log_sql);
                $details = "ভাড়া তথ্য আপডেট করা হয়েছে: {$fare_data['from']} → {$fare_data['to']} থেকে {$from_location} → {$to_location}";
                $ip = $_SERVER['REMOTE_ADDR'];
                $log_stmt->bind_param("iss", $_SESSION['admin_id'], $details, $ip);
                $log_stmt->execute();
                $log_stmt->close();

                $success = "ভাড়ার তথ্য সফলভাবে আপডেট করা হয়েছে!";
                
                // Refresh data
                $fare_data['from'] = $from_location;
                $fare_data['to'] = $to_location;
                $fare_data['fare'] = $fare;
                $fare_data['distance_km'] = $distance;
                $fare_data['operating_bus'] = $operating_bus;
            } else {
                $error = "ভাড়ার তথ্য আপডেট করতে ব্যর্থ হয়েছে!";
            }
            $update_stmt->close();
        }
        $check_stmt->close();
    }
}

// Get all locations for suggestions
$locations = [];
$loc_query = "SELECT DISTINCT `from` as location FROM fare_chart UNION SELECT DISTINCT `to` as location FROM fare_chart ORDER BY location";
$loc_result = $conn->query($loc_query);
while ($row = $loc_result->fetch_assoc()) {
    $locations[] = $row['location'];
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ভাড়া সম্পাদনা - SafeRideBD অ্যাডমিন</title>
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

        /* Sidebar (same as add fare) */
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
            max-width: 800px;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .alert-success {
            background-color: rgba(74, 222, 128, 0.1);
            border-left: 4px solid var(--accent-secondary);
            color: var(--accent-secondary);
            display: block;
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            border-left: 4px solid var(--accent-danger);
            color: var(--accent-danger);
            display: block;
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

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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
        }

        .btn-primary {
            background-color: var(--accent-warning);
            color: var(--bg-primary);
            flex: 1;
        }

        .btn-primary:hover {
            background-color: #f59e0b;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: var(--bg-hover);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            flex: 1;
        }

        .btn-secondary:hover {
            background-color: var(--border-color);
            color: var(--text-primary);
        }

        .info-text {
            color: var(--text-muted);
            font-size: 13px;
            margin-top: 5px;
        }

        .suggestions-list {
            list-style: none;
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            max-height: 200px;
            overflow-y: auto;
            display: none;
        }

        .suggestions-list li {
            padding: 10px 16px;
            cursor: pointer;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border-color);
        }

        .suggestions-list li:hover {
            background-color: var(--bg-hover);
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
        <div class="page-header">
            <h1 class="page-title">ভাড়া তথ্য সম্পাদনা</h1>
            <p class="page-subtitle">রুটের ভাড়ার তথ্য আপডেট করুন</p>
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
            <form method="POST" action="admin_edit_fare.php?id=<?php echo $id; ?>" id="fareForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <i class="fas fa-map-marker-alt"></i>
                            যাত্রা শুরু <span style="color: var(--accent-danger);">*</span>
                        </label>
                        <input type="text" name="from" class="form-control" 
                               placeholder="যেমন: মতিঝিল" required
                               value="<?php echo htmlspecialchars($fare_data['from']); ?>"
                               onkeyup="showSuggestions(this.value, 'fromSuggestions')"
                               autocomplete="off">
                        <ul class="suggestions-list" id="fromSuggestions"></ul>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-flag-checkered"></i>
                            গন্তব্য <span style="color: var(--accent-danger);">*</span>
                        </label>
                        <input type="text" name="to" class="form-control" 
                               placeholder="যেমন: গুলিস্তান" required
                               value="<?php echo htmlspecialchars($fare_data['to']); ?>"
                               onkeyup="showSuggestions(this.value, 'toSuggestions')"
                               autocomplete="off">
                        <ul class="suggestions-list" id="toSuggestions"></ul>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <i class="fas fa-taka-sign"></i>
                            ভাড়া (৳) <span style="color: var(--accent-danger);">*</span>
                        </label>
                        <input type="number" name="fare" class="form-control" 
                               placeholder="যেমন: 30" step="0.01" min="1" required
                               value="<?php echo $fare_data['fare']; ?>">
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-road"></i>
                            দূরত্ব (কিমি) <span style="color: var(--accent-danger);">*</span>
                        </label>
                        <input type="number" name="distance" class="form-control" 
                               placeholder="যেমন: 5.5" step="0.1" min="0.1" required
                               value="<?php echo $fare_data['distance_km']; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-bus"></i>
                        চলাচলকারী বাস (কমা দ্বারা পৃথক করুন)
                    </label>
                    <textarea name="operating_bus" class="form-control"><?php echo htmlspecialchars($fare_data['operating_bus']); ?></textarea>
                    <div class="info-text">
                        <i class="fas fa-info-circle"></i>
                        একাধিক বাস নম্বর কমা (,) দিয়ে আলাদা করুন
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        আপডেট করুন
                    </button>
                    <a href="admin_fare_list.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        বাতিল করুন
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const locations = <?php echo json_encode($locations); ?>;

        function showSuggestions(value, suggestionId) {
            const suggestionsList = document.getElementById(suggestionId);
            suggestionsList.innerHTML = '';
            
            if (value.length < 1) {
                suggestionsList.style.display = 'none';
                return;
            }

            const filtered = locations.filter(loc => 
                loc.toLowerCase().includes(value.toLowerCase())
            );

            if (filtered.length > 0) {
                filtered.forEach(loc => {
                    const li = document.createElement('li');
                    li.textContent = loc;
                    li.onclick = function() {
                        document.querySelector(`[name="${suggestionId === 'fromSuggestions' ? 'from' : 'to'}"]`).value = loc;
                        suggestionsList.style.display = 'none';
                    };
                    suggestionsList.appendChild(li);
                });
                suggestionsList.style.display = 'block';
            } else {
                suggestionsList.style.display = 'none';
            }
        }

        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.matches('[name="from"], [name="to"]')) {
                document.getElementById('fromSuggestions').style.display = 'none';
                document.getElementById('toSuggestions').style.display = 'none';
            }
        });

        // Form validation
        document.getElementById('fareForm').addEventListener('submit', function(e) {
            const from = document.querySelector('[name="from"]').value.trim();
            const to = document.querySelector('[name="to"]').value.trim();
            const fare = parseFloat(document.querySelector('[name="fare"]').value);
            const distance = parseFloat(document.querySelector('[name="distance"]').value);

            if (from.toLowerCase() === to.toLowerCase()) {
                e.preventDefault();
                alert('যাত্রা শুরু এবং গন্তব্য একই হতে পারে না!');
                return;
            }

            if (isNaN(fare) || fare <= 0) {
                e.preventDefault();
                alert('ভাড়া অবশ্যই ০ এর বেশি হতে হবে!');
                return;
            }

            if (isNaN(distance) || distance <= 0) {
                e.preventDefault();
                alert('দূরত্ব অবশ্যই ০ এর বেশি হতে হবে!');
                return;
            }
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