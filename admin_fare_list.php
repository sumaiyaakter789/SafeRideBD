<?php
require_once 'admin_auth.php';
include_once 'db_config.php';

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Get fare details for log
    $select_sql = "SELECT `from`, `to` FROM fare_chart WHERE id = ?";
    $select_stmt = $conn->prepare($select_sql);
    $select_stmt->bind_param("i", $id);
    $select_stmt->execute();
    $result = $select_stmt->get_result();
    $fare = $result->fetch_assoc();
    
    // Delete
    $delete_sql = "DELETE FROM fare_chart WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $id);
    
    if ($delete_stmt->execute()) {
        // Log activity
        $log_sql = "INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, 'delete', ?, ?)";
        $log_stmt = $conn->prepare($log_sql);
        $details = "ভাড়া এন্ট্রি মুছে ফেলা হয়েছে: {$fare['from']} → {$fare['to']}";
        $ip = $_SERVER['REMOTE_ADDR'];
        $log_stmt->bind_param("iss", $_SESSION['admin_id'], $details, $ip);
        $log_stmt->execute();
        $log_stmt->close();
        
        $success = "ভাড়া এন্ট্রি সফলভাবে মুছে ফেলা হয়েছে!";
    } else {
        $error = "মুছে ফেলতে ব্যর্থ হয়েছে!";
    }
    $delete_stmt->close();
}

// Get all fare entries
$fares = [];
$query = "SELECT * FROM fare_chart ORDER BY `from`, `to`";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $fares[] = $row;
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ভাড়া তালিকা - SafeRideBD অ্যাডমিন</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-subtitle {
            color: var(--text-muted);
            font-size: 15px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-family: 'Bornomala', serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
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

        .btn-success {
            background-color: var(--accent-secondary);
            color: var(--bg-primary);
        }

        .btn-success:hover {
            background-color: #3bcc6c;
            transform: translateY(-2px);
        }

        .btn-warning {
            background-color: var(--accent-warning);
            color: var(--bg-primary);
        }

        .btn-danger {
            background-color: var(--accent-danger);
            color: white;
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

        /* Table Styles */
        .table-container {
            background-color: var(--bg-card);
            border-radius: 12px;
            padding: 24px;
            border: 1px solid var(--border-color);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px 10px;
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 14px;
            border-bottom: 2px solid var(--border-color);
        }

        td {
            padding: 15px 10px;
            color: var(--text-primary);
            border-bottom: 1px solid var(--border-color);
        }

        tr:hover td {
            background-color: var(--bg-hover);
        }

        .bus-list {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--text-muted);
            font-size: 13px;
        }

        .action-cell {
            display: flex;
            gap: 8px;
        }

        .action-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.2s;
        }

        .edit-icon {
            background-color: var(--accent-warning);
        }

        .edit-icon:hover {
            background-color: #f59e0b;
            transform: translateY(-2px);
        }

        .delete-icon {
            background-color: var(--accent-danger);
        }

        .delete-icon:hover {
            background-color: #dc2626;
            transform: translateY(-2px);
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.3;
        }

        .search-box {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }

        .search-input {
            flex: 1;
            padding: 12px 16px;
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-family: 'Bornomala', serif;
            font-size: 14px;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent-primary);
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
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
                <a href="admin_fare_list.php" class="nav-link active">
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
            <div>
                <h1 class="page-title">ভাড়া তালিকা</h1>
                <p class="page-subtitle">সকল ভাড়ার তথ্য দেখুন এবং পরিচালনা করুন</p>
            </div>
            <div class="action-buttons">
                <a href="admin_add_fare.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    নতুন ভাড়া যোগ
                </a>
                <?php if (isSuperAdmin()): ?>
                <a href="admin_bulk_upload.php" class="btn btn-success">
                    <i class="fas fa-upload"></i>
                    বাল্ক আপলোড
                </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <div class="search-box">
                <input type="text" class="search-input" id="searchInput" placeholder="অনুসন্ধান করুন (যেমন: মতিঝিল, গুলিস্তান...)">
                <button class="btn btn-primary" onclick="searchTable()">
                    <i class="fas fa-search"></i>
                    খুঁজুন
                </button>
            </div>

            <?php if (empty($fares)): ?>
                <div class="no-data">
                    <i class="fas fa-database"></i>
                    <h3>কোন ভাড়ার তথ্য পাওয়া যায়নি</h3>
                    <p>নতুন ভাড়ার তথ্য যোগ করতে "নতুন ভাড়া যোগ" বাটনে ক্লিক করুন</p>
                </div>
            <?php else: ?>
                <table id="fareTable">
                    <thead>
                        <tr>
                            <th>আইডি</th>
                            <th>থেকে</th>
                            <th>যাওয়ার গন্তব্য</th>
                            <th>ভাড়া (৳)</th>
                            <th>দূরত্ব (কিমি)</th>
                            <th>চলাচলকারী বাস</th>
                            <th>সর্বশেষ আপডেট</th>
                            <th>অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fares as $fare): ?>
                            <tr>
                                <td>#<?php echo $fare['id']; ?></td>
                                <td><?php echo htmlspecialchars($fare['from']); ?></td>
                                <td><?php echo htmlspecialchars($fare['to']); ?></td>
                                <td><span style="color: var(--accent-primary); font-weight: 600;">৳<?php echo $fare['fare']; ?></span></td>
                                <td><span style="color: var(--accent-secondary);"><?php echo $fare['distance_km']; ?> কিমি</span></td>
                                <td class="bus-list" title="<?php echo htmlspecialchars($fare['operating_bus']); ?>">
                                    <?php 
                                    $buses = explode(',', $fare['operating_bus']);
                                    echo implode(', ', array_slice($buses, 0, 2));
                                    if (count($buses) > 2) echo '...';
                                    ?>
                                </td>
                                <td><?php echo date('d M, Y', strtotime($fare['created_at'])); ?></td>
                                <td class="action-cell">
                                    <a href="admin_edit_fare.php?id=<?php echo $fare['id']; ?>" class="action-icon edit-icon" title="সম্পাদনা">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $fare['id']; ?>, '<?php echo htmlspecialchars($fare['from']); ?>', '<?php echo htmlspecialchars($fare['to']); ?>')" class="action-icon delete-icon" title="মুছে ফেলুন">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function searchTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('fareTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                const tdFrom = tr[i].getElementsByTagName('td')[1];
                const tdTo = tr[i].getElementsByTagName('td')[2];
                if (tdFrom || tdTo) {
                    const fromValue = tdFrom.textContent || tdFrom.innerText;
                    const toValue = tdTo.textContent || tdTo.innerText;
                    if (fromValue.toLowerCase().indexOf(filter) > -1 || toValue.toLowerCase().indexOf(filter) > -1) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        }

        function confirmDelete(id, from, to) {
            if (confirm(`আপনি কি "${from} → ${to}" এই ভাড়ার তথ্যটি মুছে ফেলতে চান?`)) {
                window.location.href = `admin_fare_list.php?delete=${id}`;
            }
        }

        // Real-time search
        document.getElementById('searchInput').addEventListener('keyup', function() {
            searchTable();
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>