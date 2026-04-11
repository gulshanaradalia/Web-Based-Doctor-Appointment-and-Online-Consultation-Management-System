<?php
session_start();

/* User Authentication Check */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

/* Role check (only doctor allowed) */
if ($_SESSION["role"] != "doctor") {
    header("Location: dashboard.php");
    exit;
}

require_once 'db.php';
$doctor_id = $_SESSION['user_id'];

$message = '';
$message_type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $action = $_POST['action'];
    $appointment_id = intval($_POST['id']);

    if (in_array($action, ['approve', 'reject'], true) && $appointment_id > 0) {
        $new_status = ($action === 'approve') ? 'approved' : 'rejected';

        // Check if appointment exists and belongs to the doctor
        $checkStmt = $conn->prepare("SELECT id, patient_id, status 
                                     FROM appointments 
                                     WHERE id = ? AND doctor_id = ? 
                                     LIMIT 1");
        $checkStmt->bind_param('ii', $appointment_id, $doctor_id);
        $checkStmt->execute();
        $appt = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if (!$appt) {
            $message = "Appointment not found.";
            $message_type = 'danger';
        } elseif ($appt['status'] === $new_status) {
            $message = "Appointment is already " . ucfirst($new_status) . ".";
            $message_type = 'warning';
        } else {
            // Update appointment status to approved or rejected
            $updateStmt = $conn->prepare("UPDATE appointments 
                                          SET status = ? 
                                          WHERE id = ? AND doctor_id = ?");
            $updateStmt->bind_param('sii', $new_status, $appointment_id, $doctor_id);

            if ($updateStmt->execute()) {
                if ($new_status === 'approved') {
                    $checkConfirm = $conn->prepare("SELECT id 
                                                    FROM appointment_confirmations 
                                                    WHERE appointment_id = ? 
                                                    LIMIT 1");
                    $checkConfirm->bind_param('i', $appointment_id);
                    $checkConfirm->execute();
                    $existingConfirm = $checkConfirm->get_result()->fetch_assoc();
                    $checkConfirm->close();

                    if ($existingConfirm) {
                        $confirmation_status = 'notification_sent';
                        $confirmed_at = null;

                        // Reset confirmation if appointment is approved again
                        $resetConfirm = $conn->prepare("UPDATE appointment_confirmations
                                                        SET confirmation_status = ?, confirmed_at = ?
                                                        WHERE appointment_id = ?");
                        $resetConfirm->bind_param('ssi', $confirmation_status, $confirmed_at, $appointment_id);
                        $resetConfirm->execute();
                        $resetConfirm->close();
                    } else {
                        // Insert new confirmation
                        $confirmation_status = 'notification_sent';
                        $insertConfirm = $conn->prepare("INSERT INTO appointment_confirmations (appointment_id, patient_id, confirmation_status)
                                                         VALUES (?, ?, ?)");
                        $insertConfirm->bind_param('iis', $appointment_id, $appt['patient_id'], $confirmation_status);
                        $insertConfirm->execute();
                        $insertConfirm->close();
                    }
                } else {
                    // Delete confirmation if appointment is rejected
                    $deleteConfirm = $conn->prepare("DELETE FROM appointment_confirmations WHERE appointment_id = ?");
                    $deleteConfirm->bind_param('i', $appointment_id);
                    $deleteConfirm->execute();
                    $deleteConfirm->close();
                }

                $message = "Appointment " . ucfirst($new_status) . " successfully.";
                $message_type = 'success';
            } else {
                $message = "Unable to update appointment status.";
                $message_type = 'danger';
            }

            $updateStmt->close();
        }
    }
}

$appointments = [];
$stmt = $conn->prepare("SELECT 
                            a.id,
                            a.patient_id,
                            a.slot_time,
                            a.status,
                            u.name AS patient_name,
                            u.email AS patient_email,
                            u.phone AS patient_phone
                        FROM appointments a
                        JOIN users u ON a.patient_id = u.id
                        WHERE a.doctor_id = ?
                        ORDER BY 
                            CASE 
                                WHEN a.status = 'pending' THEN 1
                                WHEN a.status = 'approved' THEN 2
                                WHEN a.status = 'rejected' THEN 3
                                ELSE 4
                            END,
                            a.slot_time ASC");
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$appointments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($appointments as $idx => $apt) {
    $queueStmt = $conn->prepare("SELECT COUNT(*) AS queue_position
                                 FROM appointments
                                 WHERE doctor_id = ?
                                   AND slot_time <= ?
                                   AND status IN ('pending','approved')");
    $queueStmt->bind_param('is', $doctor_id, $apt['slot_time']);
    $queueStmt->execute();
    $queueRes = $queueStmt->get_result();
    $queueData = $queueRes->fetch_assoc();
    $queueStmt->close();

    $appointments[$idx]['queue_position'] = isset($queueData['queue_position']) ? (int)$queueData['queue_position'] : 1;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <!-- Custom stylesheet -->
    <link href="style.css" rel="stylesheet">
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="bi bi-heart-pulse-fill me-2"></i>
                <span>HealthTech</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarResponsive">
                <ul class="navbar-nav ms-auto me-3">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                    <li class="nav-item"><a class="nav-link" href="doctor_search.php">Find Doctors</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="online_appointment.php">Online Appointment</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mt-5 pt-5">
        <div class="row gy-4">
            <aside class="col-lg-3">
                <div class="card shadow-sm sticky-top" style="top:100px;">
                    <div class="card-body">
                        <h5 class="card-title">Quick Links</h5>
                        <p class="text-muted mb-3">Your dashboard controls.</p>
                        <a href="doctor_dashboard.php" class="btn btn-primary w-100 mb-2"><i class="bi bi-speedometer2"></i> My Dashboard</a>
                        <a href="profile.php" class="btn btn-outline-secondary w-100 mb-2"><i class="bi bi-person-circle"></i> Profile</a>
                        <a href="logout.php" class="btn btn-outline-danger w-100"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </div>
                </div>
            </aside>
            <section class="col-lg-9">
                <div class="bg-white rounded-4 shadow-sm p-4 mb-4">
                    <h2>Doctor Dashboard</h2>
                    <p>Welcome Dr. <?php echo htmlspecialchars($_SESSION['name']); ?></p>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <h3 class="mt-4">Schedule & Availability</h3>
                    <p>Manage your available doctor time slots via a dedicated page.</p>
                    <a href="schedule_slot.php" class="btn btn-accent mb-4">Go to Schedule Slot Management</a>

                    <h3 class="mt-4">All Appointment Requests</h3>
                </div>

                <?php if (count($appointments) === 0): ?>
                    <div class="alert alert-info">No appointment requests found.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Patient</th>
                                    <th>Contact</th>
                                    <th>Slot</th>
                                    <th>Queue #</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $idx => $apt): ?>
                                    <tr>
                                        <td><?php echo $idx + 1; ?></td>
                                        <td><?php echo htmlspecialchars($apt['patient_name']); ?></td>
                                        <td><?php echo htmlspecialchars($apt['patient_email'] . ' / ' . $apt['patient_phone']); ?></td>
                                        <td><?php echo htmlspecialchars((new DateTime($apt['slot_time']))->format('Y-m-d H:i')); ?></td>
                                        <td><?php echo htmlspecialchars($apt['queue_position'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $apt['status'] === 'approved' ? 'success' : ($apt['status'] === 'pending' ? 'warning text-dark' : 'danger'); ?>">
                                                <?php echo ucfirst($apt['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2 flex-wrap">
                                                <!-- Approve Button -->
                                                <?php if ($apt['status'] !== 'approved'): ?>
                                                    <form method="POST" class="m-0" onsubmit="return confirm('Approve this appointment?');">
                                                        <input type="hidden" name="id" value="<?php echo $apt['id']; ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                                    </form>
                                                <?php endif; ?>

                                                <!-- Reject Button -->
                                                <?php if ($apt['status'] !== 'rejected'): ?>
                                                    <form method="POST" class="m-0" onsubmit="return confirm('Reject this appointment?');">
                                                        <input type="hidden" name="id" value="<?php echo $apt['id']; ?>">
                                                        <input type="hidden" name="action" value="reject">
                                                        <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                                    </form>
                                                <?php endif; ?>

                                                <!-- Status Message for the Approved Request -->
                                                <?php if ($apt['status'] === 'approved'): ?>
                                                    <small class="text-muted align-self-center">Approved request can still be rejected</small>
                                                <?php elseif ($apt['status'] === 'rejected'): ?>
                                                    <small class="text-muted align-self-center">Rejected request can still be approved</small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    <h5>Stay Connected</h5>
                    <a href="#" class="text-primary me-2"><i class="bi bi-facebook fs-3"></i></a>
                    <a href="#" class="text-info me-2"><i class="bi bi-twitter fs-3"></i></a>
                    <a href="#" class="text-danger"><i class="bi bi-instagram fs-3"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>