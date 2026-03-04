<?php
session_start();
include_once 'navbar.php';
include_once 'db_config.php';

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 9;
$offset = ($page - 1) * $limit;

// Category filter
$category = isset($_GET['category']) ? $_GET['category'] : 'all';

// Build query
$count_sql = "SELECT COUNT(*) as total FROM published_reports WHERE status = 'published'";
$sql = "SELECT id, title, cover_image, author, publish_date, views, category,
        SUBSTRING(content, 1, 200) as excerpt 
        FROM published_reports 
        WHERE status = 'published'";

if ($category != 'all') {
    $count_sql .= " AND category = '$category'";
    $sql .= " AND category = '$category'";
}

$count_result = $conn->query($count_sql);
$total_reports = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_reports / $limit);

$sql .= " ORDER BY publish_date DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Get categories with counts
$categories_sql = "SELECT category, COUNT(*) as count 
                   FROM published_reports 
                   WHERE status = 'published' 
                   GROUP BY category 
                   ORDER BY count DESC";
$categories_result = $conn->query($categories_sql);
$categories = [];
while ($cat = $categories_result->fetch_assoc()) {
    $categories[] = $cat;
}
?>

<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>সকল প্রতিবেদন - SafeRideBD</title>
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
            --accent-info: #3b82f6;
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
            max-width: 1200px;
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

        /* Categories */
        .categories-section {
            margin-bottom: 40px;
        }

        .categories-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
        }

        .category-btn {
            padding: 10px 24px;
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 30px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 15px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .category-btn:hover {
            border-color: var(--accent-primary);
            color: var(--text-primary);
            transform: translateY(-2px);
        }

        .category-btn.active {
            background-color: var(--accent-primary);
            color: white;
            border-color: var(--accent-primary);
        }

        .category-count {
            background-color: var(--bg-hover);
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 12px;
            color: var(--text-muted);
        }

        .category-btn.active .category-count {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }

        /* Reports Grid */
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .report-card {
            background-color: var(--bg-card);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            transition: all 0.3s;
            text-decoration: none;
            display: block;
            height: 100%;
        }

        .report-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent-primary);
            box-shadow: var(--shadow);
        }

        .report-cover {
            height: 200px;
            background-color: var(--bg-secondary);
            position: relative;
            overflow: hidden;
        }

        .report-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .report-card:hover .report-cover img {
            transform: scale(1.05);
        }

        .report-cover-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            color: white;
            font-size: 48px;
        }

        .report-category {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: var(--bg-card);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            z-index: 2;
        }

        .report-content {
            padding: 20px;
        }

        .report-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .report-excerpt {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .report-meta {
            display: flex;
            justify-content: space-between;
            color: var(--text-secondary);
            font-size: 13px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
        }

        .report-meta i {
            color: var(--accent-primary);
            margin-right: 5px;
        }

        .no-reports {
            text-align: center;
            padding: 60px;
            background-color: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            grid-column: 1 / -1;
        }

        .no-reports i {
            font-size: 48px;
            color: var(--text-muted);
            margin-bottom: 15px;
            opacity: 0.3;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 40px;
        }

        .page-link {
            padding: 10px 16px;
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s;
            min-width: 45px;
            text-align: center;
        }

        .page-link:hover {
            border-color: var(--accent-primary);
            color: var(--text-primary);
        }

        .page-link.active {
            background-color: var(--accent-primary);
            color: white;
            border-color: var(--accent-primary);
        }

        .page-link.disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        /* Back Button */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-secondary);
            text-decoration: none;
            margin-bottom: 30px;
            transition: all 0.2s;
        }

        .back-btn:hover {
            border-color: var(--accent-primary);
            color: var(--text-primary);
            transform: translateX(-5px);
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 32px;
            }

            .reports-grid {
                grid-template-columns: 1fr;
            }

            .pagination {
                flex-wrap: wrap;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            হোম পৃষ্ঠায় ফিরে যান
        </a>

        <div class="page-header">
            <h1 class="page-title">সকল প্রতিবেদন</h1>
            <p class="page-subtitle">নিরাপত্তা সতর্কতা, ঘটনা রিপোর্ট এবং অন্যান্য গুরুত্বপূর্ণ তথ্য</p>
        </div>

        <!-- Categories -->
        <div class="categories-section">
            <div class="categories-grid">
                <a href="all_reports.php" class="category-btn <?php echo $category == 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-layer-group"></i>
                    সবগুলো
                    <span class="category-count"><?php echo $total_reports; ?></span>
                </a>
                <?php foreach ($categories as $cat): ?>
                    <?php
                    $cat_names = [
                        'incident' => 'ঘটনা',
                        'safety' => 'নিরাপত্তা',
                        'update' => 'আপডেট',
                        'other' => 'অন্যান্য'
                    ];
                    $cat_icons = [
                        'incident' => 'fa-exclamation-triangle',
                        'safety' => 'fa-shield-alt',
                        'update' => 'fa-sync',
                        'other' => 'fa-file'
                    ];
                    ?>
                    <a href="all_reports.php?category=<?php echo $cat['category']; ?>" 
                       class="category-btn <?php echo $category == $cat['category'] ? 'active' : ''; ?>">
                        <i class="fas <?php echo $cat_icons[$cat['category']] ?? 'fa-file'; ?>"></i>
                        <?php echo $cat_names[$cat['category']] ?? $cat['category']; ?>
                        <span class="category-count"><?php echo $cat['count']; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Reports Grid -->
        <div class="reports-grid">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($report = $result->fetch_assoc()): ?>
                    <a href="view_published_report.php?id=<?php echo $report['id']; ?>" class="report-card">
                        <div class="report-cover">
                            <?php if (!empty($report['cover_image']) && file_exists($report['cover_image'])): ?>
                                <img src="<?php echo $report['cover_image']; ?>" alt="Cover">
                            <?php else: ?>
                                <div class="report-cover-placeholder">
                                    <i class="fas fa-newspaper"></i>
                                </div>
                            <?php endif; ?>
                            <div class="report-category">
                                <?php
                                $cat_names = [
                                    'incident' => 'ঘটনা',
                                    'safety' => 'নিরাপত্তা',
                                    'update' => 'আপডেট',
                                    'other' => 'অন্যান্য'
                                ];
                                echo $cat_names[$report['category']] ?? $report['category'];
                                ?>
                            </div>
                        </div>
                        <div class="report-content">
                            <h3 class="report-title"><?php echo htmlspecialchars($report['title']); ?></h3>
                            <p class="report-excerpt"><?php echo htmlspecialchars($report['excerpt']); ?>...</p>
                            <div class="report-meta">
                                <span>
                                    <i class="fas fa-user"></i>
                                    <?php echo htmlspecialchars($report['author']); ?>
                                </span>
                                <span>
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('d M Y', strtotime($report['publish_date'])); ?>
                                </span>
                                <span>
                                    <i class="fas fa-eye"></i>
                                    <?php echo $report['views']; ?>
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-reports">
                    <i class="fas fa-newspaper"></i>
                    <p>কোন প্রকাশিত প্রতিবেদন পাওয়া যায়নি</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <a href="?page=<?php echo max(1, $page - 1); ?>&category=<?php echo $category; ?>" 
                   class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&category=<?php echo $category; ?>" 
                       class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <a href="?page=<?php echo min($total_pages, $page + 1); ?>&category=<?php echo $category; ?>" 
                   class="page-link <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php include_once 'footer.php'; ?>
</body>

</html>
<?php $conn->close(); ?>