<?php
session_start();
require_once "db.php";
require_once "admin_includes.php";

// Ensure only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';
$message_type = 'success';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Bulk Generate Slots
    if ($action === 'generate') {
        $doctor_id = intval($_POST['doctor_id']);
        $date = $_POST['date'];
        $start_time = $_POST['start_time']; // e.g., 09:00
        $end_time = $_POST['end_time'];     // e.g., 12:00
        $interval = intval($_POST['interval']); // in minutes
        
        $current = new DateTime("$date $start_time");
        $end = new DateTime("$date $end_time");
        $count = 0;

        while ($current < $end) {
            $formatted = $current->format('Y-m-d H:i:s');
            $stmt = $conn->prepare("INSERT IGNORE INTO schedule_slots (doctor_id, slot_time, slot_status) VALUES (?, ?, 'available')");
            $stmt->bind_param('is', $doctor_id, $formatted);
            $stmt->execute();
            if ($stmt->affected_rows > 0) $count++;
            $stmt->close();
            $current->modify("+$interval minutes");
        }
        $message = "Successfully generated $count new slots for the doctor.";
        log_activity($conn, $_SESSION['user_id'], "Schedule Generate", "Generated $count slots for doctor ID: $doctor_id on $date");
    }

    // Mark Holiday (Delete slots for a specific day)
    if ($action === 'holiday') {
        $doctor_id = intval($_POST['doctor_id']);
        $date = $_POST['date'];
        $stmt = $conn->prepare("DELETE FROM schedule_slots WHERE doctor_id = ? AND DATE(slot_time) = ? AND slot_status = 'available'");
        $stmt->bind_param('is', $doctor_id, $date);
        $stmt->execute();
        $message = "All available slots for " . htmlspecialchars($date) . " have been removed (Marked as Holiday).";
        $stmt->close();
        log_activity($conn, $_SESSION['user_id'], "Schedule Holiday", "Removed slots for doctor ID: $doctor_id on $date");
    }
}

// Fetch Doctors for the dropdown
$doctors = [];
$res = $conn->query("SELECT id, name FROM users WHERE role = 'doctor' AND status = 'active'");
while ($row = $res->fetch_assoc()) $doctors[] = $row;

// Fetch Recent Slots
$slots = [];
$res = $conn->query("SELECT s.*, d.name as doctor_name FROM schedule_slots s JOIN users d ON s.doctor_id = d.id ORDER BY s.slot_time DESC LIMIT 50");
while ($row = $res->fetch_assoc()) $slots[] = $row;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="bi bi-heart-pulse-fill me-2"></i>
                <span>HealthTech</span>
            </a>
            <div class="collapse navbar-collapse" id="navbarResponsive">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link active" href="admin_dashboard.php">Dashboard</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-5 pt-5">
        <div class="row">
            <div class="col-md-2 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-2">
                        <?php echo get_sidebar('admin_schedules.php'); ?>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <h3 class="fw-bold mb-4"><i class="bi bi-calendar2-range"></i> Schedule Management</h3>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row g-4">
                    <!-- Generator Section -->
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-success text-white py-3 border-0">
                                <h5 class="mb-0 small fw-bold text-uppercase"><i class="bi bi-magic"></i> Generate Slots</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="generate">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Select Doctor</label>
                                        <select name="doctor_id" class="form-select" required>
                                            <?php foreach ($doctors as $doc): ?>
                                                <option value="<?php echo $doc['id']; ?>"><?php echo htmlspecialchars($doc['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Date</label>
                                        <input type="date" name="date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">From</label>
                                            <input type="time" name="start_time" class="form-control" value="09:00" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">To</label>
                                            <input type="time" name="end_time" class="form-control" value="13:00" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Interval (Minutes)</label>
                                        <select name="interval" class="form-select">
                                            <option value="15">15 mins</option>
                                            <option value="20">20 mins</option>
                                            <option value="30" selected>30 mins</option>
                                            <option value="60">1 hour</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100">Create All Slots</button>
                                </form>
                            </div>
                        </div>

                        <!-- Holiday Section -->
                        <div class="card border-0 shadow-sm border-top border-danger border-4">
                            <div class="card-body">
                                <h5 class="fw-bold fs-6 mb-3 text-danger"><i class="bi bi-calendar-x"></i> Mark Holiday / Off-Day</h5>
                                <form method="POST">
                                    <input type="hidden" name="action" value="holiday">
                                    <div class="mb-3">
                                        <select name="doctor_id" class="form-select form-select-sm" required>
                                            <?php foreach ($doctors as $doc): ?>
                                                <option value="<?php echo $doc['id']; ?>"><?php echo htmlspecialchars($doc['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <input type="date" name="date" class="form-control form-control-sm" required>
                                    </div>
                                    <button type="submit" class="btn btn-outline-danger btn-sm w-100" onclick="return confirm('Remove all available slots for this doctor on this day?')">Mark as Holiday</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Slots List -->
                    <div class="col-md-8">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold fs-6"><i class="bi bi-list-task"></i> Recently Created Slots</h5>
                                <span class="badge bg-light text-dark border">Showing last 50</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-3">Doctor</th>
                                                <th>Time Slot</th>
                                                <th>Status</th>
                                                <th class="pe-3 text-end">ID</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($slots as $s): ?>
                                            <tr>
                                                <td class="ps-3 fw-medium small">Dr. <?php echo htmlspecialchars($s['doctor_name']); ?></td>
                                                <td class="small">
                                                    <?php echo date('M d, Y', strtotime($s['slot_time'])); ?> 
                                                    <span class="text-primary fw-bold ms-1"><?php echo date('h:i A', strtotime($s['slot_time'])); ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge rounded-pill bg-<?php echo $s['slot_status'] === 'available' ? 'success' : ($s['slot_status'] === 'booked' ? 'warning text-dark' : 'secondary'); ?> px-2" style="font-size:0.7rem;">
                                                        <?php echo ucfirst($s['slot_status']); ?>
                                                    </span>
                                                </td>
                                                <td class="pe-3 text-end small text-muted">#<?php echo $s['id']; ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
