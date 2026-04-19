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
    <title>Appointment Confirmations - HealthTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        body {
            background: #eef3ff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding-top: 5rem;
        }
    </style>
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

    <main class="container mt-4 mb-5 flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">Appointment Confirmations</h2>
            <a href="doctor_dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 bg-white">
                    <div class="card-body text-center p-4">
                        <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-check-circle-fill text-success fs-3"></i>
                        </div>
                        <h5 class="text-muted fw-bold mb-1">Confirmed</h5>
                        <h2 class="text-success fw-bold mb-0"><?php echo $confirmed_count; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 bg-white">
                    <div class="card-body text-center p-4">
                        <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-hourglass-split text-warning fs-3"></i>
                        </div>
                        <h5 class="text-muted fw-bold mb-1">Awaiting</h5>
                        <h2 class="text-warning fw-bold mb-0"><?php echo $pending_count; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <?php if (count($approved_appointments) === 0): ?>
            <div class="alert alert-info border-0 shadow-sm rounded-4 p-4 text-center">
                <i class="bi bi-info-circle fs-2 mb-2 d-block"></i>
                No approved appointments yet.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover bg-white shadow-sm rounded-4 overflow-hidden align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Patient</th>
                            <th>Contact</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th class="pe-3">Confirmed At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($approved_appointments as $idx => $apt): ?>
                            <tr>
                                <td class="ps-3"><?php echo $idx + 1; ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($apt['patient_name']); ?></td>
                                <td>
                                    <div class="small"><i class="bi bi-envelope me-1 text-muted"></i><?php echo htmlspecialchars($apt['email']); ?></div>
                                    <div class="small text-muted"><i class="bi bi-phone me-1"></i><?php echo htmlspecialchars($apt['phone']); ?></div>
                                </td>
                                <td><i class="bi bi-calendar-event me-2 text-primary"></i><?php echo htmlspecialchars((new DateTime($apt['slot_time']))->format('Y-m-d H:i')); ?></td>
                                <td>
                                    <?php if ($apt['confirmation_status'] === 'confirmed'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3">
                                            <i class="bi bi-check-circle-fill me-1"></i>Confirmed
                                        </span>
                                    <?php elseif ($apt['confirmation_status'] === 'notification_sent' || $apt['confirmation_status'] === 'pending'): ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 text-dark">
                                            <i class="bi bi-hourglass-split me-1"></i>Awaiting
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3">No Status</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-3">
                                    <?php if ($apt['confirmed_at']): ?>
                                        <span class="small text-muted"><i class="bi bi-clock-history me-1"></i><?php echo htmlspecialchars((new DateTime($apt['confirmed_at']))->format('Y-m-d H:i')); ?></span>
                                    <?php else: ?>
                                        <span class="small text-muted">Not confirmed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-auto">
        <div class="container text-center">
            <h5>Stay Connected</h5>
            <a href="#" class="text-primary me-2"><i class="bi bi-facebook fs-3"></i></a>
            <a href="#" class="text-info me-2"><i class="bi bi-twitter fs-3"></i></a>
            <a href="#" class="text-danger"><i class="bi bi-instagram fs-3"></i></a>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
