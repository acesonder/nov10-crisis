<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

require_login();
require_role('client');

$page_title = 'Client Dashboard';
$user_id = $_SESSION['user_id'];

// Get user stats
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM assessments WHERE client_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$assessment_count = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM tasks WHERE client_id = ? AND status != 'completed'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$active_task_count = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM referrals WHERE client_id = ? AND status = 'pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_referral_count = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Get recent tasks
$stmt = $conn->prepare("SELECT * FROM tasks WHERE client_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get recent assessments
$stmt = $conn->prepare("SELECT * FROM assessments WHERE client_id = ? ORDER BY created_at DESC LIMIT 3");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_assessments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include '../../includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <div class="mb-4">
        <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>! 👋</h1>
        <p style="color: var(--text-secondary);">Here's your overview for today</p>
    </div>
    
    <!-- Stats Overview -->
    <div class="row mb-4">
        <div class="col-12 col-md-4">
            <div class="card text-center">
                <div class="widget">
                    <span class="widget-icon">📋</span>
                    <span class="widget-value"><?php echo $assessment_count; ?></span>
                    <span class="widget-label">Assessments Completed</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card text-center">
                <div class="widget">
                    <span class="widget-icon">✅</span>
                    <span class="widget-value"><?php echo $active_task_count; ?></span>
                    <span class="widget-label">Active Tasks/Goals</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card text-center">
                <div class="widget">
                    <span class="widget-icon">🤝</span>
                    <span class="widget-value"><?php echo $pending_referral_count; ?></span>
                    <span class="widget-label">Pending Referrals</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Quick Actions</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-12 col-md-3 mb-2">
                    <a href="/nov10-crisis/pages/client/assessments.php?new=1" class="btn btn-primary w-100">
                        📋 New Assessment
                    </a>
                </div>
                <div class="col-12 col-md-3 mb-2">
                    <a href="/nov10-crisis/pages/client/resources.php" class="btn btn-info w-100">
                        🛏️ Book Resources
                    </a>
                </div>
                <div class="col-12 col-md-3 mb-2">
                    <a href="/nov10-crisis/pages/common/messages.php?new=1" class="btn btn-secondary w-100">
                        💬 Send Message
                    </a>
                </div>
                <div class="col-12 col-md-3 mb-2">
                    <a href="/nov10-crisis/pages/client/documents.php" class="btn btn-success w-100">
                        📄 Upload Document
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Tasks -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Recent Tasks & Goals</h3>
        </div>
        <div class="card-body">
            <?php if (empty($recent_tasks)): ?>
                <p style="color: var(--text-secondary);">No tasks assigned yet. Your case manager will create tasks to help you achieve your goals.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Due Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_tasks as $task): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($task['title']); ?></td>
                                    <td><?php echo get_priority_badge($task['priority']); ?></td>
                                    <td><?php echo get_status_badge($task['status']); ?></td>
                                    <td><?php echo format_date($task['due_date']); ?></td>
                                    <td>
                                        <a href="/nov10-crisis/pages/client/task-view.php?id=<?php echo $task['task_id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <a href="/nov10-crisis/pages/client/tasks.php" class="btn btn-secondary">View All Tasks</a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Assessments -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">My Assessments</h3>
        </div>
        <div class="card-body">
            <?php if (empty($recent_assessments)): ?>
                <p style="color: var(--text-secondary);">You haven't completed any assessments yet. Start with a new assessment to get matched with appropriate services.</p>
                <a href="/nov10-crisis/pages/client/assessments.php?new=1" class="btn btn-primary">Start Assessment</a>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($recent_assessments as $assessment): ?>
                        <div class="col-12 col-md-4 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h4><?php echo ucwords(str_replace('_', ' ', $assessment['assessment_type'])); ?></h4>
                                    <p><?php echo get_status_badge($assessment['status']); ?> <?php echo get_priority_badge($assessment['risk_level']); ?></p>
                                    <p style="color: var(--text-secondary); font-size: 0.875rem;">
                                        Completed: <?php echo format_datetime($assessment['created_at']); ?>
                                    </p>
                                    <a href="/nov10-crisis/pages/client/assessment-view.php?id=<?php echo $assessment['assessment_id']; ?>" class="btn btn-sm btn-primary">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a href="/nov10-crisis/pages/client/assessments.php" class="btn btn-secondary">View All Assessments</a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- News Feed -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Community News & Updates</h3>
        </div>
        <div class="card-body">
            <?php
            $news_stmt = $conn->prepare("SELECT n.*, u.full_name FROM news_feed n JOIN users u ON n.author_id = u.user_id WHERE n.status = 'published' AND n.is_public = 1 ORDER BY n.published_at DESC LIMIT 3");
            $news_stmt->execute();
            $news_items = $news_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $news_stmt->close();
            
            if (empty($news_items)):
            ?>
                <p style="color: var(--text-secondary);">No news updates at this time.</p>
            <?php else: ?>
                <?php foreach ($news_items as $news): ?>
                    <div class="mb-3 pb-3" style="border-bottom: 1px solid var(--border-color);">
                        <h4><?php echo htmlspecialchars($news['title']); ?></h4>
                        <p style="color: var(--text-secondary); font-size: 0.875rem;">
                            By <?php echo htmlspecialchars($news['full_name']); ?> • <?php echo time_ago($news['published_at']); ?>
                        </p>
                        <p><?php echo nl2br(htmlspecialchars(substr($news['content'], 0, 200))); ?><?php echo strlen($news['content']) > 200 ? '...' : ''; ?></p>
                    </div>
                <?php endforeach; ?>
                <a href="/nov10-crisis/pages/common/news.php" class="btn btn-secondary">View All News</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
