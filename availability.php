<?php
require_once 'config.php';
requireLogin();

$success = '';
$error = '';

// Handle Add Blocked Slot
if (isset($_POST['add_block'])) {
    $block_date = cleanInput($_POST['block_date']);
    $block_type = cleanInput($_POST['block_type']);
    $start_time = $block_type === 'full_day' ? null : cleanInput($_POST['start_time']);
    $end_time = $block_type === 'full_day' ? null : cleanInput($_POST['end_time']);
    $reason = cleanInput($_POST['reason']);
    
    $stmt = $conn->prepare("INSERT INTO blocked_slots (block_date, start_time, end_time, reason, created_by) VALUES (?, ?, ?, ?, ?)");
    $admin_id = $_SESSION['admin_id'];
    $stmt->bind_param("ssssi", $block_date, $start_time, $end_time, $reason, $admin_id);
    
    if ($stmt->execute()) {
        $success = "Blocked slot added successfully!";
    } else {
        $error = "Error adding blocked slot: " . $conn->error;
    }
}

// Handle Delete Blocked Slot
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($conn->query("DELETE FROM blocked_slots WHERE id = $id")) {
        $success = "Blocked slot removed successfully!";
    } else {
        $error = "Error removing blocked slot: " . $conn->error;
    }
}

// Handle Update Business Hours
if (isset($_POST['update_hours'])) {
    $day_id = intval($_POST['day_id']);
    $start_time = cleanInput($_POST['start_time']);
    $end_time = cleanInput($_POST['end_time']);
    $is_open = isset($_POST['is_open']) ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE business_hours SET start_time = ?, end_time = ?, is_open = ? WHERE id = ?");
    $stmt->bind_param("ssii", $start_time, $end_time, $is_open, $day_id);
    
    if ($stmt->execute()) {
        $success = "Business hours updated successfully!";
    } else {
        $error = "Error updating hours: " . $conn->error;
    }
}

// Get business hours
$business_hours = $conn->query("SELECT * FROM business_hours ORDER BY day_of_week ASC");

// Get blocked slots
$blocked_slots = $conn->query("
    SELECT b.*, a.full_name as created_by_name 
    FROM blocked_slots b
    LEFT JOIN admins a ON b.created_by = a.id
    WHERE b.block_date >= CURDATE()
    ORDER BY b.block_date ASC, b.start_time ASC
");

$days_of_week = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Availability - Ragamaguru</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h1>Manage Availability</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Business Hours -->
        <div class="section">
            <h2>Business Hours</h2>
            <p>Set your regular working hours for each day of the week.</p>
            
            <table>
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
                    <?php while ($hours = $business_hours->fetch_assoc()): ?>
                        <tr>
                            <form method="POST" style="display: contents;">
                                <input type="hidden" name="day_id" value="<?php echo $hours['id']; ?>">
                                <td><strong><?php echo $days_of_week[$hours['day_of_week']]; ?></strong></td>
                                <td>
                                    <input type="time" name="start_time" value="<?php echo $hours['start_time']; ?>" 
                                           <?php echo !$hours['is_open'] ? 'disabled' : ''; ?>>
                                </td>
                                <td>
                                    <input type="time" name="end_time" value="<?php echo $hours['end_time']; ?>" 
                                           <?php echo !$hours['is_open'] ? 'disabled' : ''; ?>>
                                </td>
                                <td>
                                    <label>
                                        <input type="checkbox" name="is_open" <?php echo $hours['is_open'] ? 'checked' : ''; ?>>
                                        Open
                                    </label>
                                </td>
                                <td>
                                    <button type="submit" name="update_hours" class="btn btn-sm">Update</button>
                                </td>
                            </form>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Add Blocked Slot -->
        <div class="section">
            <h2>Block Dates / Time Slots</h2>
            <p>Block specific dates or time slots when you're not available (holidays, events, etc.)</p>
            
            <form method="POST" id="blockForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Date *</label>
                        <input type="date" name="block_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Block Type *</label>
                        <select name="block_type" id="blockType" onchange="toggleTimeInputs()" required>
                            <option value="full_day">Full Day</option>
                            <option value="time_range">Specific Time Range</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-grid" id="timeInputs" style="display: none;">
                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="time" name="start_time" id="startTime">
                    </div>
                    
                    <div class="form-group">
                        <label>End Time</label>
                        <input type="time" name="end_time" id="endTime">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Reason (Optional)</label>
                    <input type="text" name="reason" placeholder="e.g., Public Holiday, Staff Meeting">
                </div>
                
                <button type="submit" name="add_block" class="btn">Add Block</button>
            </form>
        </div>
        
        <!-- Blocked Slots List -->
        <div class="section">
            <h2>Current Blocked Slots</h2>
            
            <?php if ($blocked_slots->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time Range</th>
                            <th>Reason</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($block = $blocked_slots->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo formatDate($block['block_date']); ?></td>
                                <td>
                                    <?php 
                                    if ($block['start_time']) {
                                        echo formatTime($block['start_time']) . ' - ' . formatTime($block['end_time']);
                                    } else {
                                        echo '<strong>Full Day</strong>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $block['reason'] ?: '-'; ?></td>
                                <td><?php echo $block['created_by_name']; ?></td>
                                <td>
                                    <a href="?delete=<?php echo $block['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Remove this block?')">Remove</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No blocked slots currently set.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function toggleTimeInputs() {
            const blockType = document.getElementById('blockType').value;
            const timeInputs = document.getElementById('timeInputs');
            const startTime = document.getElementById('startTime');
            const endTime = document.getElementById('endTime');
            
            if (blockType === 'time_range') {
                timeInputs.style.display = 'grid';
                startTime.required = true;
                endTime.required = true;
            } else {
                timeInputs.style.display = 'none';
                startTime.required = false;
                endTime.required = false;
                startTime.value = '';
                endTime.value = '';
            }
        }
    </script>
</body>
</html>