<?php
require_once '../config.php';
check_admin_login();

$message = '';
$error = '';

// Handle availability update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_hours') {
        $day = clean_input($_POST['day']);
        $start_time = clean_input($_POST['start_time']);
        $end_time = clean_input($_POST['end_time']);
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        
        $stmt = $conn->prepare("
            INSERT INTO availability (day_of_week, start_time, end_time, is_available) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            start_time = VALUES(start_time), 
            end_time = VALUES(end_time), 
            is_available = VALUES(is_available)
        ");
        $stmt->bind_param("sssi", $day, $start_time, $end_time, $is_available);
        
        if ($stmt->execute()) {
            $message = 'Business hours updated successfully!';
        } else {
            $error = 'Failed to update business hours.';
        }
    } elseif ($action === 'block_date') {
        $block_date = clean_input($_POST['block_date']);
        $reason = clean_input($_POST['reason']);
        
        $stmt = $conn->prepare("INSERT INTO blocked_dates (block_date, reason) VALUES (?, ?)");
        $stmt->bind_param("ss", $block_date, $reason);
        
        if ($stmt->execute()) {
            $message = 'Date blocked successfully!';
        } else {
            $error = 'Failed to block date.';
        }
    } elseif ($action === 'unblock_date') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM blocked_dates WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = 'Date unblocked successfully!';
        } else {
            $error = 'Failed to unblock date.';
        }
    } elseif ($action === 'block_timeslot') {
        $block_date = clean_input($_POST['block_date']);
        $start_time = clean_input($_POST['start_time']);
        $end_time = clean_input($_POST['end_time']);
        $reason = clean_input($_POST['reason']);
        
        $stmt = $conn->prepare("INSERT INTO blocked_time_slots (block_date, start_time, end_time, reason) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $block_date, $start_time, $end_time, $reason);
        
        if ($stmt->execute()) {
            $message = 'Time slot blocked successfully!';
        } else {
            $error = 'Failed to block time slot.';
        }
    } elseif ($action === 'unblock_timeslot') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM blocked_time_slots WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = 'Time slot unblocked successfully!';
        } else {
            $error = 'Failed to unblock time slot.';
        }
    }
}

// Get availability
$availability = $conn->query("SELECT * FROM availability ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')");

// Get blocked dates
$blocked_dates = $conn->query("SELECT * FROM blocked_dates WHERE block_date >= CURDATE() ORDER BY block_date ASC");

// Get blocked time slots
$blocked_slots = $conn->query("SELECT * FROM blocked_time_slots WHERE block_date >= CURDATE() ORDER BY block_date ASC, start_time ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Availability Management - Admin Panel</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>⏰ Availability Management</h1>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Business Hours -->
            <div class="dashboard-section">
                <h2>Business Hours</h2>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($day = $availability->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $day['day_of_week']; ?></strong></td>
                                <td><?php echo date('h:i A', strtotime($day['start_time'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($day['end_time'])); ?></td>
                                <td><span class="badge badge-<?php echo $day['is_available'] ? 'active' : 'inactive'; ?>"><?php echo $day['is_available'] ? 'Open' : 'Closed'; ?></span></td>
                                <td>
                                    <button class="btn btn-primary btn-sm" onclick='editHours(<?php echo json_encode($day); ?>)'>Edit</button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Block Dates -->
            <div class="dashboard-section" style="margin-top: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>Blocked Dates</h2>
                    <button class="btn btn-primary" onclick="openBlockDateModal()">+ Block Date</button>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Reason</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($blocked_dates->num_rows > 0): ?>
                                <?php while ($date = $blocked_dates->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo format_date($date['block_date']); ?></td>
                                    <td><?php echo $date['reason'] ?: 'N/A'; ?></td>
                                    <td>
                                        <button class="btn btn-danger btn-sm" onclick="unblockDate(<?php echo $date['id']; ?>)">Unblock</button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; padding: 20px;">No blocked dates</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Block Time Slots -->
            <div class="dashboard-section" style="margin-top: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>Blocked Time Slots</h2>
                    <button class="btn btn-primary" onclick="openBlockTimeModal()">+ Block Time Slot</button>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Reason</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($blocked_slots->num_rows > 0): ?>
                                <?php while ($slot = $blocked_slots->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo format_date($slot['block_date']); ?></td>
                                    <td><?php echo format_time($slot['start_time']); ?></td>
                                    <td><?php echo format_time($slot['end_time']); ?></td>
                                    <td><?php echo $slot['reason'] ?: 'N/A'; ?></td>
                                    <td>
                                        <button class="btn btn-danger btn-sm" onclick="unblockTimeSlot(<?php echo $slot['id']; ?>)">Unblock</button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 20px;">No blocked time slots</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Edit Hours Modal -->
    <div id="hoursModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Business Hours</h2>
                <button class="modal-close" onclick="closeModal('hoursModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_hours">
                <input type="hidden" name="day" id="editDay">
                
                <div class="form-group">
                    <label>Day</label>
                    <input type="text" id="displayDay" class="form-control" readonly>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="time" name="start_time" id="editStartTime" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>End Time</label>
                        <input type="time" name="end_time" id="editEndTime" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_available" id="editAvailable">
                        Available for booking
                    </label>
                </div>
                
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('hoursModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Block Date Modal -->
    <div id="blockDateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Block Date</h2>
                <button class="modal-close" onclick="closeModal('blockDateModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="block_date">
                
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="block_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label>Reason</label>
                    <input type="text" name="reason" class="form-control" placeholder="e.g., Holiday, Maintenance">
                </div>
                
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('blockDateModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Block Date</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Block Time Slot Modal -->
    <div id="blockTimeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Block Time Slot</h2>
                <button class="modal-close" onclick="closeModal('blockTimeModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="block_timeslot">
                
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="block_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Start Time *</label>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>End Time *</label>
                        <input type="time" name="end_time" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Reason</label>
                    <input type="text" name="reason" class="form-control" placeholder="e.g., Lunch break, Special event">
                </div>
                
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('blockTimeModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Block Time Slot</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function editHours(day) {
            document.getElementById('hoursModal').classList.add('active');
            document.getElementById('editDay').value = day.day_of_week;
            document.getElementById('displayDay').value = day.day_of_week;
            document.getElementById('editStartTime').value = day.start_time;
            document.getElementById('editEndTime').value = day.end_time;
            document.getElementById('editAvailable').checked = day.is_available == 1;
        }
        
        function openBlockDateModal() {
            document.getElementById('blockDateModal').classList.add('active');
        }
        
        function openBlockTimeModal() {
            document.getElementById('blockTimeModal').classList.add('active');
        }
        
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }
        
        function unblockDate(id) {
            if (confirm('Are you sure you want to unblock this date?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="unblock_date"><input type="hidden" name="id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function unblockTimeSlot(id) {
            if (confirm('Are you sure you want to unblock this time slot?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="unblock_timeslot"><input type="hidden" name="id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>