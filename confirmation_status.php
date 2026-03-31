<?php
session_start();

/* User Authentication Check */
if(!isset($_SESSION["user_id"])){
    header("Location: login.php");
    exit;
}

/* Role check (only doctor allowed) */
if($_SESSION["role"] != "doctor"){
    header("Location: dashboard.php");
    exit;
}

require_once 'db.php';

$doctor_id = $_SESSION['user_id'];

// Fetch confirmed appointments
$stmt = $conn->prepare("SELECT a.id, a.slot_time, u.name AS patient_name, u.email, u.phone,
                        ac.confirmed_at, ac.confirmation_status
                        FROM appointments a 
                        JOIN users u ON a.patient_id = u.id 
                        LEFT JOIN appointment_confirmations ac ON a.id = ac.appointment_id
                        WHERE a.doctor_id = ? AND a.status = 'approved'
                        ORDER BY a.slot_time ASC");
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$approved_appointments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Count confirmations
$confirmed_count = 0;
$pending_count = 0;
foreach ($approved_appointments as $apt) {
    if ($apt['confirmation_status'] === 'confirmed') {
        $confirmed_count++;
    } elseif ($apt['confirmation_status'] === 'notification_sent' || $apt['confirmation_status'] === 'pending') {
        $pending_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Appointment Confirmations</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow">
    <div class="container">
        <a class="navbar-brand" href="doctor_dashboard.php">Doctor Dashboard</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link active" href="confirmation_status.php">Confirmations</a></li>
                <li class="nav-item"><a class="nav-link" href="doctor_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="container mt-5 pt-5">
    <h2 class="mb-4">Appointment Confirmations</h2>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h5 class="card-title text-success">✓ Confirmed</h5>
                    <h2 class="text-success"><?php echo $confirmed_count; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h5 class="card-title text-warning">⏳ Pending Confirmation</h5>
                    <h2 class="text-warning"><?php echo $pending_count; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <?php if (count($approved_appointments) === 0): ?>
        <div class="alert alert-info">No approved appointments yet.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Contact</th>
                        <th>Appointment Date</th>
                        <th>Confirmation Status</th>
                        <th>Confirmed At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($approved_appointments as $idx => $apt): ?>
                        <tr>
                            <td><?php echo $idx + 1; ?></td>
                            <td><?php echo htmlspecialchars($apt['patient_name']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($apt['email']); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($apt['phone']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars((new DateTime($apt['slot_time']))->format('Y-m-d H:i')); ?></td>
                            <td>
                                <?php if ($apt['confirmation_status'] === 'confirmed'): ?>
                                    <span class="badge bg-success">✓ Confirmed</span>
                                <?php elseif ($apt['confirmation_status'] === 'notification_sent' || $apt['confirmation_status'] === 'pending'): ?>
                                    <span class="badge bg-warning">⏳ Awaiting Confirmation</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">No Status</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($apt['confirmed_at']): ?>
                                    <small><?php echo htmlspecialchars((new DateTime($apt['confirmed_at']))->format('Y-m-d H:i')); ?></small>
                                <?php else: ?>
                                    <small class="text-muted">Not confirmed</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
