<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

require_login();
require_role('service_provider');

$page_title = 'Service Provider Dashboard';
$user_id = $_SESSION['user_id'];

// Get provider info
$stmt = $conn->prepare("SELECT * FROM service_providers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$provider_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

$provider_id = $provider_info['provider_id'] ?? 0;

// Get statistics
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM referrals WHERE provider_id = ? AND status = 'pending'");
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$pending_referrals = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM referrals WHERE provider_id = ? AND status = 'accepted'");
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$active_clients = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Get recent referrals
$stmt = $conn->prepare("SELECT r.*, u.full_name as client_name, s.full_name as staff_name FROM referrals r JOIN users u ON r.client_id = u.user_id LEFT JOIN users s ON r.staff_id = s.user_id WHERE r.provider_id = ? ORDER BY r.created_at DESC LIMIT 5");
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$recent_referrals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include '../../includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <div class="mb-4">
        <h1><?php echo htmlspecialchars($provider_info['organization_name'] ?? 'Service Provider'); ?> Dashboard 🏢</h1>
        <p style="color: var(--text-secondary);">Manage referrals and client services</p>
    </div>
    
    <!-- Stats Overview -->
    <div class="row mb-4">
        <div class="col-12 col-md-4">
            <div class="card text-center">
                <div class="widget">
                    <span class="widget-icon">📬</span>
                    <span class="widget-value"><?php echo $pending_referrals; ?></span>
                    <span class="widget-label">Pending Referrals</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card text-center">
                <div class="widget">
                    <span class="widget-icon">👥</span>
                    <span class="widget-value"><?php echo $active_clients; ?></span>
                    <span class="widget-label">Active Clients</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card text-center">
                <div class="widget">
                    <span class="widget-icon">📊</span>
                    <span class="widget-value"><?php echo $provider_info['available_slots'] ?? 0; ?>/<?php echo $provider_info['capacity'] ?? 0; ?></span>
                    <span class="widget-label">Available Slots</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Organization Info -->
    <?php if ($provider_info): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Organization Information</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-12 col-md-6">
                    <p><strong>Service Type:</strong> <?php echo htmlspecialchars($provider_info['service_type']); ?></p>
                    <p><strong>Status:</strong> <?php echo get_status_badge($provider_info['status']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($provider_info['phone'] ?? 'N/A'); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($provider_info['email'] ?? 'N/A'); ?></p>
                </div>
                <div class="col-12 col-md-6">
                    <p><strong>Capacity:</strong> <?php echo $provider_info['capacity']; ?> slots</p>
                    <p><strong>Available:</strong> <?php echo $provider_info['available_slots']; ?> slots</p>
                    <p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($provider_info['address'] ?? 'N/A')); ?></p>
                </div>
            </div>
            <a href="/nov10-crisis/pages/provider/profile.php" class="btn btn-primary mt-2">Edit Organization Info</a>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Recent Referrals -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Referrals</h3>
        </div>
        <div class="card-body">
            <?php if (empty($recent_referrals)): ?>
                <p style="color: var(--text-secondary);">No referrals yet. You will receive referrals from case managers.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Referred By</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_referrals as $referral): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($referral['client_name']); ?></td>
                                    <td><?php echo htmlspecialchars($referral['staff_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo get_priority_badge($referral['priority']); ?></td>
                                    <td><?php echo get_status_badge($referral['status']); ?></td>
                                    <td><?php echo time_ago($referral['created_at']); ?></td>
                                    <td>
                                        <a href="/nov10-crisis/pages/provider/referral-view.php?id=<?php echo $referral['referral_id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <a href="/nov10-crisis/pages/provider/referrals.php" class="btn btn-secondary">View All Referrals</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
