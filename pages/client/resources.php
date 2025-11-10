<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

require_login();
require_role('client');

$page_title = 'Resource Management';
$user_id = $_SESSION['user_id'];

// Get available beds
$beds_result = $conn->query("SELECT * FROM beds WHERE status = 'available' ORDER BY facility_name, bed_number");
$available_beds = $beds_result->fetch_all(MYSQLI_ASSOC);

// Get my bed reservations
$stmt = $conn->prepare("SELECT br.*, b.facility_name, b.bed_number FROM bed_reservations br JOIN beds b ON br.bed_id = b.bed_id WHERE br.client_id = ? AND br.status IN ('reserved', 'checked_in') ORDER BY br.check_in DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_beds = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get shower slots for today and tomorrow
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$shower_result = $conn->query("SELECT * FROM shower_slots WHERE booking_date IN ('$today', '$tomorrow') AND status = 'available' ORDER BY booking_date, slot_time");
$shower_slots = $shower_result->fetch_all(MYSQLI_ASSOC);

// Get my shower bookings
$stmt = $conn->prepare("SELECT * FROM shower_slots WHERE booked_by = ? AND booking_date >= CURDATE() ORDER BY booking_date, slot_time");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_showers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get laundry slots
$laundry_result = $conn->query("SELECT * FROM laundry_slots WHERE booking_date IN ('$today', '$tomorrow') AND status = 'available' ORDER BY booking_date, slot_time");
$laundry_slots = $laundry_result->fetch_all(MYSQLI_ASSOC);

// Get my laundry bookings
$stmt = $conn->prepare("SELECT * FROM laundry_slots WHERE booked_by = ? AND booking_date >= CURDATE() ORDER BY booking_date, slot_time");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_laundry = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include '../../includes/header.php';
?>

<style>
    .resource-card {
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .resource-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }
    
    .slot-item {
        padding: 0.75rem;
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        margin-bottom: 0.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background-color 0.2s;
    }
    
    .slot-item:hover {
        background-color: var(--bg-secondary);
    }
</style>

<div class="container" style="padding: 2rem 0;">
    <div class="mb-4">
        <h1>Resource Management 🛏️</h1>
        <p style="color: var(--text-secondary);">Book beds, shower times, and laundry slots</p>
    </div>
    
    <!-- Beds Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">🛏️ Bed Reservations</h3>
        </div>
        <div class="card-body">
            <h4>My Current Reservations</h4>
            <?php if (empty($my_beds)): ?>
                <p style="color: var(--text-secondary);">You don't have any active bed reservations.</p>
            <?php else: ?>
                <div class="table-responsive mb-3">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Facility</th>
                                <th>Bed #</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($my_beds as $bed): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($bed['facility_name']); ?></td>
                                    <td><?php echo htmlspecialchars($bed['bed_number']); ?></td>
                                    <td><?php echo format_datetime($bed['check_in']); ?></td>
                                    <td><?php echo format_datetime($bed['check_out']); ?></td>
                                    <td><?php echo get_status_badge($bed['status']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <h4 class="mt-4">Available Beds</h4>
            <?php if (empty($available_beds)): ?>
                <p style="color: var(--text-secondary);">No beds currently available. Please check back later or contact staff.</p>
            <?php else: ?>
                <div class="row">
                    <?php foreach (array_slice($available_beds, 0, 6) as $bed): ?>
                        <div class="col-12 col-md-4 mb-3">
                            <div class="card resource-card">
                                <div class="card-body">
                                    <h5><?php echo htmlspecialchars($bed['facility_name']); ?></h5>
                                    <p>Bed #: <?php echo htmlspecialchars($bed['bed_number']); ?></p>
                                    <p>Type: <?php echo htmlspecialchars($bed['bed_type'] ?? 'Standard'); ?></p>
                                    <button class="btn btn-sm btn-primary" onclick="alert('Please contact staff to reserve this bed.')">Request Bed</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-center mt-3"><em>To reserve a bed, please contact staff or visit the front desk.</em></p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Shower Slots Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">🚿 Shower Schedule</h3>
        </div>
        <div class="card-body">
            <h4>My Shower Bookings</h4>
            <?php if (empty($my_showers)): ?>
                <p style="color: var(--text-secondary);">You don't have any shower slots booked.</p>
            <?php else: ?>
                <?php foreach ($my_showers as $shower): ?>
                    <div class="slot-item">
                        <div>
                            <strong><?php echo $shower['slot_name']; ?></strong><br>
                            <small><?php echo date('l, M d', strtotime($shower['booking_date'])); ?> at <?php echo date('g:i A', strtotime($shower['slot_time'])); ?></small>
                        </div>
                        <span class="badge badge-success">Booked</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <h4 class="mt-4">Available Shower Times</h4>
            <?php if (empty($shower_slots)): ?>
                <p style="color: var(--text-secondary);">No shower slots available. All slots for today and tomorrow are booked.</p>
            <?php else: ?>
                <?php 
                $current_date = '';
                foreach (array_slice($shower_slots, 0, 10) as $slot): 
                    $slot_date = date('l, F j', strtotime($slot['booking_date']));
                    if ($slot_date !== $current_date):
                        if ($current_date !== '') echo '</div>';
                        echo '<h5 class="mt-3">' . $slot_date . '</h5><div>';
                        $current_date = $slot_date;
                    endif;
                ?>
                    <div class="slot-item">
                        <div>
                            <strong><?php echo $slot['slot_name']; ?></strong><br>
                            <small><?php echo date('g:i A', strtotime($slot['slot_time'])); ?> (<?php echo $slot['duration_minutes']; ?> min)</small>
                        </div>
                        <button class="btn btn-sm btn-primary" onclick="bookShower(<?php echo $slot['slot_id']; ?>)">Book</button>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Laundry Slots Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">🧺 Laundry Schedule</h3>
        </div>
        <div class="card-body">
            <h4>My Laundry Bookings</h4>
            <?php if (empty($my_laundry)): ?>
                <p style="color: var(--text-secondary);">You don't have any laundry slots booked.</p>
            <?php else: ?>
                <?php foreach ($my_laundry as $laundry): ?>
                    <div class="slot-item">
                        <div>
                            <strong>Machine <?php echo $laundry['machine_number']; ?></strong><br>
                            <small><?php echo date('l, M d', strtotime($laundry['booking_date'])); ?> at <?php echo date('g:i A', strtotime($laundry['slot_time'])); ?></small>
                        </div>
                        <span class="badge badge-success">Booked</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <h4 class="mt-4">Available Laundry Times</h4>
            <?php if (empty($laundry_slots)): ?>
                <p style="color: var(--text-secondary);">No laundry slots available for today and tomorrow.</p>
            <?php else: ?>
                <?php 
                $current_date = '';
                foreach (array_slice($laundry_slots, 0, 10) as $slot): 
                    $slot_date = date('l, F j', strtotime($slot['booking_date']));
                    if ($slot_date !== $current_date):
                        if ($current_date !== '') echo '</div>';
                        echo '<h5 class="mt-3">' . $slot_date . '</h5><div>';
                        $current_date = $slot_date;
                    endif;
                ?>
                    <div class="slot-item">
                        <div>
                            <strong>Machine <?php echo $slot['machine_number']; ?></strong><br>
                            <small><?php echo date('g:i A', strtotime($slot['slot_time'])); ?> (<?php echo $slot['duration_minutes']; ?> min)</small>
                        </div>
                        <button class="btn btn-sm btn-primary" onclick="bookLaundry(<?php echo $slot['slot_id']; ?>)">Book</button>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function bookShower(slotId) {
    if (confirm('Book this shower slot?')) {
        fetch('/nov10-crisis/includes/ajax/book_resource.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({type: 'shower', slot_id: slotId})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Success', 'Shower slot booked!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast('Error', data.message, 'danger');
            }
        });
    }
}

function bookLaundry(slotId) {
    if (confirm('Book this laundry slot?')) {
        fetch('/nov10-crisis/includes/ajax/book_resource.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({type: 'laundry', slot_id: slotId})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Success', 'Laundry slot booked!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast('Error', data.message, 'danger');
            }
        });
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
