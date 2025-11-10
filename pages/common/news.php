<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

require_login();

$page_title = 'News & Updates';
$user_id = $_SESSION['user_id'];

// Get all published news
$stmt = $conn->prepare("
    SELECT n.*, u.full_name as author_name, u.role as author_role
    FROM news_feed n
    JOIN users u ON n.author_id = u.user_id
    WHERE n.status = 'published' AND n.is_public = 1
    ORDER BY n.published_at DESC
    LIMIT 20
");
$stmt->execute();
$news_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include '../../includes/header.php';
?>

<style>
    .news-card {
        transition: transform 0.2s;
    }
    
    .news-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }
    
    .news-meta {
        color: var(--text-secondary);
        font-size: 0.875rem;
        margin-bottom: 1rem;
    }
    
    .news-category {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        background: var(--primary-color);
        color: white;
        border-radius: 15px;
        font-size: 0.75rem;
        margin-bottom: 0.5rem;
    }
</style>

<div class="container" style="padding: 2rem 0;">
    <div class="mb-4">
        <h1>News & Updates 📰</h1>
        <p style="color: var(--text-secondary);">Stay informed about community resources and announcements</p>
    </div>
    
    <?php if (empty($news_items)): ?>
        <div class="card">
            <div class="card-body text-center" style="padding: 3rem;">
                <h3>No news updates</h3>
                <p style="color: var(--text-secondary);">Check back later for announcements and updates.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($news_items as $news): ?>
            <div class="card news-card mb-4">
                <div class="card-body">
                    <?php if ($news['category']): ?>
                        <span class="news-category"><?php echo htmlspecialchars($news['category']); ?></span>
                    <?php endif; ?>
                    
                    <h2><?php echo htmlspecialchars($news['title']); ?></h2>
                    
                    <div class="news-meta">
                        By <?php echo htmlspecialchars($news['author_name']); ?>
                        <?php echo get_role_badge($news['author_role']); ?>
                        • <?php echo time_ago($news['published_at']); ?>
                    </div>
                    
                    <div style="line-height: 1.8;">
                        <?php echo nl2br(htmlspecialchars($news['content'])); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>
