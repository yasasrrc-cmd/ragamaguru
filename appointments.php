<?php
require_once 'config.php';
requireLogin();

// Get date range
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d', strtotime('+7 days'));

// Get appointments
$appointments = $conn->query("
    SELECT a.*, c.name, c.mobile, s.service_name, s.duration, s.price
    FROM appointments a
    JOIN customers c ON a.customer_id = c.id
    JOIN services s ON a.service_id = s.id
    WHERE a.appointment_date BETWEEN '$start_date' AND '$end_date'
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
");

// Get appointments grouped by date
$appointmentsByDate = [];
$appointments_result = $conn->query("
    SELECT a.*, c.name, c.mobile, s.service_name, s.duration
    FROM appointments a
    JOIN customers c ON a.customer_id = c.id
    JOIN services s ON a.service_id = s.id
    WHERE a.appointment_date BETWEEN '$start_date' AND '$end_date'
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
");

while ($apt = $appointments_result->fetch_assoc()) {
    $date = $apt['appointment_date'];
    if (!isset($appointmentsByDate[$date])) {
        $appointmentsByDate[$date] = [];
    }
    $appointmentsByDate[$date][] = $apt;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - Ragamaguru</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1>Appointments</h1>
            <a href="appointment_add.php" class="btn">+ Book Appointment</a>
        </div>
        
        <div class="section">
            <form method="GET" style="display: flex; gap: 10px; margin-bottom: 20px;">
                <div>
                    <label>From Date</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div>
                    <label>To Date</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div style="align-self: flex-end;">
                    <button type="submit" class="btn">Filter</button>
                    <a href="appointments.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
            
            <?php if (count($appointmentsByDate) > 0): ?>
                <?php foreach ($appointmentsByDate as $date => $dateAppointments): ?>
                    <div style="margin-bottom: 30px;">
                        <h3 style="background: #f0f0f0; padding: 10px; border-radius: 5px;">
                            <?php echo formatDate($date); ?> 
                            <span style="font-size: 14px; font-weight: normal;">(<?php echo count($dateAppointments); ?> appointments)</span>
                        </h3>
                        
                        <table style="margin-top: 10px;">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Customer</th>
                                    <th>Mobile</th>
                                    <th>Service</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dateAppointments as $apt): ?>
                                    <tr>
                                        <td><?php echo formatTime($apt['appointment_time']); ?></td>
                                        <td><?php echo $apt['name']; ?></td>
                                        <td><?php echo $apt['mobile']; ?></td>
                                        <td><?php echo $apt['service_name']; ?></td>
                                        <td><?php echo $apt['duration']; ?> mins</td>
                                        <td>
                                            <span class="status-badge status-<?php echo $apt['status']; ?>">
                                                <?php echo ucfirst($apt['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="appointment_view.php?id=<?php echo $apt['id']; ?>" class="btn btn-sm">View</a>
                                                <a href="appointment_edit.php?id=<?php echo $apt['id']; ?>" class="btn btn-sm btn-success">Edit</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No appointments found for the selected date range.</p>
            <?php endif; ?>
        </div>
        
        <!-- Calendar View -->
        <div class="section">
            <h2>Calendar View</h2>
            <div id="calendar"></div>
        </div>
    </div>
    
    <script>
        // Simple calendar generation
        const startDate = new Date('<?php echo $start_date; ?>');
        const endDate = new Date('<?php echo $end_date; ?>');
        const appointmentDates = <?php echo json_encode(array_keys($appointmentsByDate)); ?>;
        
        function generateCalendar() {
            const calendar = document.getElementById('calendar');
            const currentDate = new Date();
            
            let html = '<div class="calendar">';
            html += '<div class="calendar-header">';
            html += '<button onclick="previousMonth()">&lt;</button>';
            html += '<h3>' + currentDate.toLocaleDateString('en-US', {month: 'long', year: 'numeric'}) + '</h3>';
            html += '<button onclick="nextMonth()">&gt;</button>';
            html += '</div>';
            html += '<div class="calendar-grid">';
            
            // Day headers
            const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            days.forEach(day => {
                html += '<div style="font-weight: bold; text-align: center; padding: 10px;">' + day + '</div>';
            });
            
            // Get first day of month and number of days
            const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1).getDay();
            const daysInMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0).getDate();
            
            // Empty cells for days before month starts
            for (let i = 0; i < firstDay; i++) {
                html += '<div></div>';
            }
            
            // Days of month
            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = currentDate.getFullYear() + '-' + 
                              String(currentDate.getMonth() + 1).padStart(2, '0') + '-' + 
                              String(day).padStart(2, '0');
                const hasAppointment = appointmentDates.includes(dateStr);
                const isToday = dateStr === '<?php echo date('Y-m-d'); ?>';
                
                let classes = 'calendar-day';
                if (hasAppointment) classes += ' has-appointment';
                if (isToday) classes += ' today';
                
                html += '<div class="' + classes + '" onclick="viewDate(\'' + dateStr + '\')">' + day + '</div>';
            }
            
            html += '</div></div>';
            calendar.innerHTML = html;
        }
        
        function viewDate(date) {
            window.location.href = 'appointments.php?start_date=' + date + '&end_date=' + date;
        }
        
        generateCalendar();
    </script>
</body>
</html>