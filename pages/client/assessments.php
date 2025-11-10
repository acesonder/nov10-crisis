<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

require_login();
require_role('client');

$page_title = 'My Assessments';
$user_id = $_SESSION['user_id'];

// Get all assessments for this user
$stmt = $conn->prepare("SELECT * FROM assessments WHERE client_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$assessments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include '../../includes/header.php';
?>

<style>
    .assessment-type-card {
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }
    
    .assessment-type-card:hover {
        border-color: var(--primary-color);
        transform: translateY(-5px);
    }
    
    .assessment-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
    }
</style>

<div class="container" style="padding: 2rem 0;">
    <div class="mb-4">
        <h1>My Assessments 📋</h1>
        <p style="color: var(--text-secondary);">Complete assessments to get matched with appropriate services</p>
    </div>
    
    <!-- Start New Assessment -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Start New Assessment</h3>
        </div>
        <div class="card-body">
            <p class="mb-4">All questions in our assessments are <strong>optional</strong>. Only answer what you're comfortable with. This helps us connect you with the right services.</p>
            
            <div class="row">
                <div class="col-12 col-md-4 mb-3">
                    <a href="/nov10-crisis/pages/client/assessment-substance.php" style="text-decoration: none; color: inherit;">
                        <div class="card assessment-type-card text-center">
                            <div class="card-body">
                                <div class="assessment-icon">💊</div>
                                <h4>Substance Use Assessment</h4>
                                <p style="color: var(--text-secondary);">Get support for substance use challenges</p>
                            </div>
                        </div>
                    </a>
                </div>
                
                <div class="col-12 col-md-4 mb-3">
                    <a href="/nov10-crisis/pages/client/assessment-homelessness.php" style="text-decoration: none; color: inherit;">
                        <div class="card assessment-type-card text-center">
                            <div class="card-body">
                                <div class="assessment-icon">🏠</div>
                                <h4>Housing/Homelessness Assessment</h4>
                                <p style="color: var(--text-secondary);">Find housing resources and support</p>
                            </div>
                        </div>
                    </a>
                </div>
                
                <div class="col-12 col-md-4 mb-3">
                    <a href="/nov10-crisis/pages/client/assessment-general.php" style="text-decoration: none; color: inherit;">
                        <div class="card assessment-type-card text-center">
                            <div class="card-body">
                                <div class="assessment-icon">📝</div>
                                <h4>General Needs Assessment</h4>
                                <p style="color: var(--text-secondary);">Comprehensive support assessment</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Previous Assessments -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Your Assessment History</h3>
        </div>
        <div class="card-body">
            <?php if (empty($assessments)): ?>
                <p style="color: var(--text-secondary);">You haven't completed any assessments yet. Start your first assessment above!</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Risk Level</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assessments as $assessment): ?>
                                <tr>
                                    <td><?php echo ucwords(str_replace('_', ' ', $assessment['assessment_type'])); ?></td>
                                    <td><?php echo get_priority_badge($assessment['risk_level']); ?></td>
                                    <td><?php echo get_status_badge($assessment['status']); ?></td>
                                    <td><?php echo format_datetime($assessment['created_at']); ?></td>
                                    <td>
                                        <a href="/nov10-crisis/pages/client/assessment-view.php?id=<?php echo $assessment['assessment_id']; ?>" class="btn btn-sm btn-primary">View</a>
                                        <?php if ($assessment['status'] === 'draft'): ?>
                                            <a href="/nov10-crisis/pages/client/assessment-edit.php?id=<?php echo $assessment['assessment_id']; ?>" class="btn btn-sm btn-secondary">Continue</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
