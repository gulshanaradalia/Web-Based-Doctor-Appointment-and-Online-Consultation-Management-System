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
    $apt_id = intval($_POST['id']);

    if ($action === 'update_status') {
        $new_status = $_POST['status'];
        if (in_array($new_status, ['pending', 'approved', 'rejected'])) {
            $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
            $stmt->bind_param('si', $new_status, $apt_id);
            $stmt->execute();
            $stmt->close();
            $message = "Appointment status updated to " . ucfirst($new_status);
            log_activity($conn, $_SESSION['user_id'], "Appointment Status", "Updated appointment #$apt_id to $new_status");
        }
    }

    if ($action === 'reschedule') {
        $new_time = $_POST['slot_time'];
        if ($new_time) {
            $stmt = $conn->prepare("UPDATE appointments SET slot_time = ? WHERE id = ?");
            $stmt->bind_param('si', $new_time, $apt_id);
            if ($stmt->execute()) {
                $message = "Appointment successfully rescheduled!";
                log_activity($conn, $_SESSION['user_id'], "Appointment Reschedule", "Rescheduled appointment #$apt_id to $new_time");
            } else {
                $message = "Conflict: This doctor might already have an appointment at this time.";
                $message_type = 'danger';
            }
            $stmt->close();
        }
    }
}

// Fetch all appointments with Doctor and Patient names
$appointments = [];
$res = $conn->query("SELECT a.*, p.name as patient_name, d.name as doctor_name 
                    FROM appointments a
                    JOIN users p ON a.patient_id = p.id
                    JOIN users d ON a.doctor_id = d.id
                    ORDER BY a.id DESC LIMIT 100");
while ($row = $res->fetch_assoc()) {
    $appointments[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Management - Admin Panel</title>
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
                        <?php echo get_sidebar('admin_appointments.php'); ?>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <h3 class="fw-bold mb-4"><i class="bi bi-calendar-week"></i> Appointment Management</h3>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Patient</th>
                                        <th>Doctor</th>
                                        <th>Schedule</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th class="pe-3 text-end">Manage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($appointments as $a): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold"><?php echo htmlspecialchars($a['patient_name']); ?></td>
                                        <td>Dr. <?php echo htmlspecialchars($a['doctor_name']); ?></td>
                                        <td>
                                            <div class="small fw-medium"><?php echo date('Y-m-d', strtotime($a['slot_time'])); ?></div>
                                            <div class="small text-muted"><?php echo date('h:i A', strtotime($a['slot_time'])); ?></div>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $a['appointment_type'] === 'online' ? 'bg-primary' : 'bg-info text-dark'; ?>">
                                                <i class="bi bi-<?php echo $a['appointment_type'] === 'online' ? 'camera-video' : 'hospital'; ?>"></i> 
                                                <?php echo ucfirst($a['appointment_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $a['status'] === 'approved' ? 'success' : ($a['status'] === 'pending' ? 'warning text-dark' : 'danger'); ?>">
                                                <?php echo ucfirst($a['status']); ?>
                                            </span>
                                        </td>
                                        <td class="pe-3 text-end">
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">Action</button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                                    <li>
                                                        <form method="POST">
                                                            <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="status" value="approved">
                                                            <button type="submit" class="dropdown-item text-success"><i class="bi bi-check-circle"></i> Approve</button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST">
                                                            <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="status" value="rejected">
                                                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-x-circle"></i> Reject / Cancel</button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item" href="#" onclick='openRescheduleModal(<?php echo json_encode($a); ?>)'><i class="bi bi-clock-history"></i> Reschedule</a></li>
                                                </ul>
                                            </div>
                                        </td>
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

    <!-- Reschedule Modal -->
    <div class="modal fade" id="rescheduleModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" method="POST">
                <input type="hidden" name="action" value="reschedule">
                <input type="hidden" name="id" id="reschedule_id">
                <div class="modal-header bg-dark text-white border-0">
                    <h5 class="modal-title">Reschedule Appointment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">Manually update the appointment slot for this patient. Note: This updates the appointment record directly.</p>
                    <div class="mb-3">
                        <label class="form-label">New Date & Time</label>
                        <input type="datetime-local" name="slot_time" id="reschedule_time" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark px-4">Update Slot</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openRescheduleModal(apt) {
            document.getElementById('reschedule_id').value = apt.id;
            // Convert MySQL DATETIME to HTML datetime-local format
            const dt = new Date(apt.slot_time);
            const formatted = dt.toISOString().slice(0, 16);
            document.getElementById('reschedule_time').value = formatted;
            new bootstrap.Modal(document.getElementById('rescheduleModal')).show();
        }
    </script>
</body>
</html>
