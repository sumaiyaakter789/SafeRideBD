<?php
session_start();
include_once 'navbar.php';
include_once 'db_config.php';

$report_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($report_id == 0) {
    header("Location: index.php");
    exit();
}

// Get report details
$sql = "SELECT p.*, a.full_name as publisher_name, i.title as incident_title, i.id as incident_id
        FROM published_reports p
        LEFT JOIN admin_users a ON p.published_by = a.id
        LEFT JOIN incident_reports i ON p.incident_report_id = i.id
        WHERE p.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: index.php");
    exit();
}

$report = $result->fetch_assoc();

// Increment view count
$update_sql = "UPDATE published_reports SET views = views + 1 WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("i", $report_id);
$update_stmt->execute();

// Get related reports (same category)
$related_sql = "SELECT id, title, cover_image, publish_date, views 
                FROM published_reports 
                WHERE category = ? AND id != ? AND status = 'published'
                ORDER BY publish_date DESC 
                LIMIT 3";
$related_stmt = $conn->prepare($related_sql);
$related_stmt->bind_param("si", $report['category'], $report_id);
$related_stmt->execute();
$related_reports = $related_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($report['title']); ?> - SafeRideBD</title>
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
            line-height: 1.8;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 24px;
            flex: 1;
        }

        /* Article Header */
        .article-header {
            margin-bottom: 40px;
            text-align: center;
        }

        .article-category {
            display: inline-block;
            padding: 8px 16px;
            background-color: rgba(255, 107, 74, 0.1);
            color: var(--accent-primary);
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 107, 74, 0.3);
        }

        .article-title {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.3;
            background: linear-gradient(135deg, var(--text-primary), var(--accent-primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .article-meta {
            display: flex;
            justify-content: center;
            gap: 30px;
            color: var(--text-muted);
            font-size: 15px;
            flex-wrap: wrap;
        }

        .article-meta i {
            color: var(--accent-primary);
            margin-right: 8px;
        }

        .article-meta span {
            display: inline-flex;
            align-items: center;
        }

        /* Cover Image */
        .article-cover {
            margin-bottom: 40px;
            border-radius: 16px;
            overflow: hidden;
            border: 2px solid var(--border-color);
            box-shadow: var(--shadow);
        }

        .article-cover img {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
            display: block;
        }

        .cover-placeholder {
            width: 100%;
            height: 300px;
            background: linear-gradient(135deg, var(--bg-card), var(--bg-secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 64px;
            color: var(--accent-primary);
        }

        /* Article Content */
        .article-content {
            background-color: var(--bg-card);
            border-radius: 16px;
            padding: 40px;
            margin-bottom: 40px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
        }

        .article-content p {
            margin-bottom: 20px;
            color: var(--text-secondary);
            font-size: 18px;
        }

        .article-content h2 {
            color: var(--text-primary);
            font-size: 28px;
            margin: 30px 0 15px;
        }

        .article-content h3 {
            color: var(--text-primary);
            font-size: 22px;
            margin: 25px 0 15px;
        }

        .article-content ul, .article-content ol {
            margin: 20px 0;
            padding-left: 30px;
            color: var(--text-secondary);
        }

        .article-content li {
            margin-bottom: 10px;
        }

        .article-content blockquote {
            margin: 30px 0;
            padding: 20px 30px;
            background-color: var(--bg-secondary);
            border-left: 4px solid var(--accent-primary);
            border-radius: 8px;
            font-style: italic;
            color: var(--text-primary);
        }

        /* Source Reference */
        .source-reference {
            background-color: var(--bg-secondary);
            border-radius: 12px;
            padding: 20px;
            margin: 30px 0;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .source-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background-color: rgba(255, 107, 74, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-primary);
            font-size: 20px;
        }

        .source-text {
            flex: 1;
        }

        .source-label {
            color: var(--text-muted);
            font-size: 13px;
            margin-bottom: 5px;
        }

        .source-link {
            color: var(--accent-info);
            text-decoration: none;
            font-weight: 500;
        }

        .source-link:hover {
            text-decoration: underline;
        }

        /* Article Footer */
        .article-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 30px;
            margin-top: 30px;
            border-top: 2px solid var(--border-color);
            flex-wrap: wrap;
            gap: 20px;
        }

        .article-stats {
            display: flex;
            gap: 20px;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
        }

        .stat-item i {
            color: var(--accent-primary);
        }

        .share-buttons {
            display: flex;
            gap: 12px;
        }

        .share-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--bg-hover);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.2s;
        }

        .share-btn:hover {
            transform: translateY(-3px);
        }

        .share-btn.facebook:hover {
            background-color: #1877f2;
            color: white;
            border-color: #1877f2;
        }

        .share-btn.twitter:hover {
            background-color: #1da1f2;
            color: white;
            border-color: #1da1f2;
        }

        .share-btn.whatsapp:hover {
            background-color: #25d366;
            color: white;
            border-color: #25d366;
        }

        .share-btn.linkedin:hover {
            background-color: #0a66c2;
            color: white;
            border-color: #0a66c2;
        }

        /* Related Reports */
        .related-reports {
            margin-top: 40px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text-primary);
        }

        .section-title i {
            color: var(--accent-primary);
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .related-card {
            background-color: var(--bg-card);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            transition: all 0.3s;
            text-decoration: none;
            display: block;
        }

        .related-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent-primary);
            box-shadow: var(--shadow);
        }

        .related-cover {
            height: 160px;
            background-color: var(--bg-secondary);
            position: relative;
        }

        .related-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .related-cover-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            color: white;
            font-size: 32px;
        }

        .related-content {
            padding: 20px;
        }

        .related-title {
            color: var(--text-primary);
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .related-meta {
            display: flex;
            justify-content: space-between;
            color: var(--text-muted);
            font-size: 12px;
        }

        .related-meta i {
            margin-right: 4px;
            color: var(--accent-primary);
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
            .article-title {
                font-size: 32px;
            }

            .article-content {
                padding: 25px;
            }

            .article-meta {
                gap: 15px;
                flex-direction: column;
                align-items: center;
            }

            .related-grid {
                grid-template-columns: 1fr;
            }

            .article-footer {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="javascript:history.back()" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            পূর্ববর্তী পৃষ্ঠায় ফিরে যান
        </a>

        <div class="article-header">
            <div class="article-category">
                <?php 
                    $categories = [
                        'incident' => 'ঘটনা রিপোর্ট',
                        'safety' => 'নিরাপত্তা সতর্কতা',
                        'update' => 'আপডেট',
                        'other' => 'অন্যান্য'
                    ];
                    echo $categories[$report['category']] ?? $report['category'];
                ?>
            </div>
            <h1 class="article-title"><?php echo htmlspecialchars($report['title']); ?></h1>
            <div class="article-meta">
                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($report['author']); ?></span>
                <span><i class="fas fa-calendar"></i> <?php echo date('d F Y', strtotime($report['publish_date'])); ?></span>
                <span><i class="fas fa-eye"></i> <?php echo $report['views']; ?> বার পঠিত</span>
            </div>
        </div>

        <?php if (!empty($report['cover_image']) && file_exists($report['cover_image'])): ?>
            <div class="article-cover">
                <img src="<?php echo $report['cover_image']; ?>" alt="Cover Image">
            </div>
        <?php else: ?>
            <div class="article-cover">
                <div class="cover-placeholder">
                    <i class="fas fa-newspaper"></i>
                </div>
            </div>
        <?php endif; ?>

        <div class="article-content">
            <?php echo nl2br(htmlspecialchars($report['content'])); ?>
        </div>

        <?php if (!empty($report['source']) || $report['incident_report_id']): ?>
            <div class="source-reference">
                <div class="source-icon">
                    <i class="fas fa-link"></i>
                </div>
                <div class="source-text">
                    <div class="source-label">সোর্স / রেফারেন্স</div>
                    <?php if ($report['incident_report_id']): ?>
                        <a href="#" class="source-link">প্রাথমিক রিপোর্ট #<?php echo $report['incident_report_id']; ?></a>
                    <?php else: ?>
                        <span class="source-link"><?php echo htmlspecialchars($report['source']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="article-footer">
            <div class="article-stats">
                <div class="stat-item">
                    <i class="fas fa-eye"></i>
                    <span><?php echo $report['views']; ?> বার পঠিত</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-clock"></i>
                    <span>প্রকাশ: <?php echo date('d M Y', strtotime($report['publish_date'])); ?></span>
                </div>
            </div>

            <div class="share-buttons">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode("https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" target="_blank" class="share-btn facebook" title="Facebook এ শেয়ার করুন">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode($report['title']); ?>&url=<?php echo urlencode("https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" target="_blank" class="share-btn twitter" title="Twitter এ শেয়ার করুন">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="https://wa.me/?text=<?php echo urlencode($report['title'] . " - https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" target="_blank" class="share-btn whatsapp" title="WhatsApp এ শেয়ার করুন">
                    <i class="fab fa-whatsapp"></i>
                </a>
                <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode("https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>&title=<?php echo urlencode($report['title']); ?>" target="_blank" class="share-btn linkedin" title="LinkedIn এ শেয়ার করুন">
                    <i class="fab fa-linkedin-in"></i>
                </a>
            </div>
        </div>

        <?php if ($related_reports->num_rows > 0): ?>
            <div class="related-reports">
                <h3 class="section-title">
                    <i class="fas fa-layer-group"></i>
                    সম্পর্কিত প্রতিবেদন
                </h3>
                <div class="related-grid">
                    <?php while ($related = $related_reports->fetch_assoc()): ?>
                        <a href="view_published_report.php?id=<?php echo $related['id']; ?>" class="related-card">
                            <div class="related-cover">
                                <?php if (!empty($related['cover_image']) && file_exists($related['cover_image'])): ?>
                                    <img src="<?php echo $related['cover_image']; ?>" alt="Cover">
                                <?php else: ?>
                                    <div class="related-cover-placeholder">
                                        <i class="fas fa-newspaper"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="related-content">
                                <h4 class="related-title"><?php echo htmlspecialchars($related['title']); ?></h4>
                                <div class="related-meta">
                                    <span><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($related['publish_date'])); ?></span>
                                    <span><i class="fas fa-eye"></i> <?php echo $related['views']; ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include_once 'footer.php'; ?>
</body>
</html>
<?php $conn->close(); ?>