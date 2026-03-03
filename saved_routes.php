<?php
session_start();
include_once 'navbar.php';
include_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=saved_routes");
    exit();
}

$user_id = $_SESSION['user_id'];
$saved_routes = [];

$sql = "SELECT * FROM saved_routes WHERE user_id = ? ORDER BY saved_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $saved_routes[] = $row;
    }
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>সংরক্ষিত রুট - SafeRideBD</title>
    <style>
        .saved-routes-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .routes-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .route-card {
            background-color: var(--bg-card);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid var(--border-color);
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
        }

        .route-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, var(--accent-primary), var(--accent-secondary));
        }

        .route-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent-primary);
            box-shadow: var(--shadow);
        }

        .route-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
            flex-wrap: wrap;
            gap: 15px;
        }

        .route-title {
            color: var(--text-primary);
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .route-title i {
            color: var(--accent-primary);
            background-color: rgba(255, 107, 74, 0.1);
            padding: 8px;
            border-radius: 8px;
            font-size: 16px;
        }

        .route-date {
            color: var(--text-muted);
            font-size: 13px;
            background-color: var(--bg-secondary);
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
        }

        .route-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .route-stat {
            background-color: var(--bg-secondary);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            border: 1px solid var(--border-color);
        }

        .stat-icon {
            font-size: 20px;
            color: var(--accent-primary);
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 5px 0;
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-muted);
        }

        .stat-value.fare {
            color: var(--accent-primary);
        }

        .stat-value.student-fare {
            color: var(--accent-secondary);
        }

        .bus-tags-container {
            margin-top: 20px;
        }

        .bus-tags-title {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .bus-tags-title i {
            color: var(--accent-primary);
        }

        .bus-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .bus-tag {
            background-color: var(--bg-secondary);
            color: var(--accent-secondary);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            border: 1px solid var(--border-color);
        }

        .route-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        .action-btn {
            padding: 12px 20px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Bornomala', serif;
            font-size: 14px;
            flex: 1;
            justify-content: center;
        }

        .use-route-btn {
            background-color: var(--accent-primary);
            color: white;
        }

        .use-route-btn:hover {
            background-color: #ff5a3a;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(255, 107, 74, 0.3);
        }

        .delete-route-btn {
            background-color: var(--bg-hover);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }

        .delete-route-btn:hover {
            border-color: var(--accent-danger);
            color: var(--accent-danger);
            transform: translateY(-2px);
        }

        .no-routes {
            text-align: center;
            padding: 80px 20px;
            grid-column: 1 / -1;
        }

        .no-routes-icon {
            font-size: 80px;
            color: var(--accent-primary);
            opacity: 0.3;
            margin-bottom: 25px;
        }

        .no-routes h3 {
            color: var(--text-primary);
            margin-bottom: 15px;
            font-size: 24px;
        }

        .no-routes p {
            color: var(--text-muted);
            max-width: 500px;
            margin: 0 auto 30px;
            line-height: 1.8;
        }

        .go-to-calculator {
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

        .go-to-calculator:hover {
            background-color: #ff5a3a;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(255, 107, 74, 0.3);
        }

        @media (max-width: 768px) {
            .routes-list {
                grid-template-columns: 1fr;
            }
            
            .route-stats {
                grid-template-columns: 1fr;
            }
            
            .route-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="saved-routes-container">
        <div class="page-header">
            <h1 class="page-title">আমার সংরক্ষিত রুট</h1>
            <p class="page-subtitle">আপনার ঘন ঘন ব্যবহৃত রুট এবং ভাড়ার তথ্য দ্রুত দেখুন।</p>
        </div>

        <div class="routes-list">
            <?php if (empty($saved_routes)): ?>
                <div class="no-routes">
                    <div class="no-routes-icon">
                        <i class="fas fa-route"></i>
                    </div>
                    <h3>কোন রুট সংরক্ষিত নেই</h3>
                    <p>আপনি এখনও কোন রুট সংরক্ষণ করেননি। আপনার ঘন ঘন ব্যবহৃত রুটের ভাড়া গণনা করুন এবং দ্রুত ব্যবহারের জন্য সংরক্ষণ করুন।</p>
                    <a href="index.php" class="go-to-calculator">
                        <i class="fas fa-calculator"></i> ভাড়া ক্যালকুলেটরে যান
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($saved_routes as $route): ?>
                    <div class="route-card" data-route-id="<?php echo $route['id']; ?>">
                        <div class="route-header">
                            <div class="route-title">
                                <i class="fas fa-route"></i>
                                <?php echo htmlspecialchars($route['from_location']); ?> → <?php echo htmlspecialchars($route['to_location']); ?>
                            </div>
                            <div class="route-date">
                                <i class="far fa-calendar-alt"></i>
                                <?php echo date('d M, Y', strtotime($route['saved_at'])); ?>
                            </div>
                        </div>
                        
                        <div class="route-stats">
                            <div class="route-stat">
                                <div class="stat-icon">
                                    <i class="fas fa-road"></i>
                                </div>
                                <div class="stat-value"><?php echo $route['distance_km']; ?> কিমি</div>
                                <div class="stat-label">দূরত্ব</div>
                            </div>
                            
                            <div class="route-stat">
                                <div class="stat-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-value">
                                    <?php
                                    $estimated_minutes = ceil(($route['distance_km'] / 20) * 60);
                                    echo $estimated_minutes;
                                    ?> মিনিট
                                </div>
                                <div class="stat-label">সময়</div>
                            </div>
                            
                            <div class="route-stat">
                                <div class="stat-icon">
                                    <i class="fas fa-ticket-alt"></i>
                                </div>
                                <div class="stat-value fare">৳<?php echo number_format($route['regular_fare'], 0); ?></div>
                                <div class="stat-label">সাধারণ ভাড়া</div>
                            </div>
                            
                            <div class="route-stat">
                                <div class="stat-icon">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div class="stat-value student-fare">৳<?php echo number_format($route['student_fare'], 0); ?></div>
                                <div class="stat-label">ছাত্র ভাড়া</div>
                            </div>
                        </div>
                        
                        <?php if (!empty($route['operating_bus'])): ?>
                            <div class="bus-tags-container">
                                <div class="bus-tags-title">
                                    <i class="fas fa-bus"></i>
                                    <span>চলাচলকারী বাস</span>
                                </div>
                                <div class="bus-tags">
                                    <?php 
                                    $buses = explode(',', $route['operating_bus']);
                                    foreach ($buses as $bus): 
                                        $bus = trim($bus);
                                        if (!empty($bus)):
                                    ?>
                                        <span class="bus-tag"><?php echo htmlspecialchars($bus); ?></span>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="route-actions">
                            <button class="action-btn use-route-btn" onclick="useRoute('<?php echo $route['from_location']; ?>', '<?php echo $route['to_location']; ?>')">
                                <i class="fas fa-directions"></i> রুট ব্যবহার করুন
                            </button>
                            <button class="action-btn delete-route-btn" onclick="deleteRoute(<?php echo $route['id']; ?>)">
                                <i class="fas fa-trash-alt"></i> মুছে ফেলুন
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function useRoute(from, to) {
            sessionStorage.setItem('fromLocation', from);
            sessionStorage.setItem('toLocation', to);
            window.location.href = 'index.php';
        }

        function deleteRoute(routeId) {
            if (confirm('আপনি কি এই রুটটি মুছে ফেলতে চান?')) {
                const routeCard = document.querySelector(`[data-route-id="${routeId}"]`);
                if (routeCard) {
                    routeCard.style.opacity = '0';
                    routeCard.style.transform = 'scale(0.9)';
                }
                
                fetch('delete_route.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ route_id: routeId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        setTimeout(() => {
                            if (routeCard) {
                                routeCard.remove();
                                if (!document.querySelector('.route-card')) {
                                    setTimeout(() => location.reload(), 300);
                                }
                            }
                        }, 300);
                    } else {
                        alert('রুট মুছে ফেলতে ব্যর্থ হয়েছে: ' + data.message);
                        if (routeCard) {
                            routeCard.style.opacity = '1';
                            routeCard.style.transform = 'none';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('রুট মুছে ফেলতে ব্যর্থ হয়েছে। আবার চেষ্টা করুন।');
                    if (routeCard) {
                        routeCard.style.opacity = '1';
                        routeCard.style.transform = 'none';
                    }
                });
            }
        }

        window.addEventListener('load', function() {
            const from = sessionStorage.getItem('fromLocation');
            const to = sessionStorage.getItem('toLocation');
            
            if (from && to && window.location.pathname.includes('index.php')) {
                document.getElementById('fromLocation').value = from;
                document.getElementById('toLocation').value = to;
                
                sessionStorage.removeItem('fromLocation');
                sessionStorage.removeItem('toLocation');
            }
        });
    </script>
</body>
</html>
<?php
$conn->close();
include_once 'footer.php';
?>