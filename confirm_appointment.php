<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['role'] != 'patient') {
    header('Location: dashboard.php');
    exit;
}

require_once 'db.php';

$patient_id = $_SESSION['user_id'];
$message = '';
$error = '';
$confirmation = null;

$confirmation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($confirmation_id > 0) {
    $stmt = $conn->prepare("SELECT 
                                ac.id,
                                ac.appointment_id,
                                ac.confirmation_status,
                                ac.confirmed_at,
                                a.slot_time,
                                a.status AS appointment_status,
                                u.name AS doctor_name,
                                u.specialty
                            FROM appointment_confirmations ac
                            JOIN appointments a ON ac.appointment_id = a.id
                            JOIN users u ON a.doctor_id = u.id
                            WHERE ac.id = ? AND ac.patient_id = ?");
    $stmt->bind_param('ii', $confirmation_id, $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $confirmation = $result->fetch_assoc();
    $stmt->close();

    if (!$confirmation) {
        $error = 'Invalid confirmation link.';
    } elseif ($confirmation['appointment_status'] !== 'approved') {
        $error = 'This appointment is no longer approved, so confirmation is not available.';
    } else {
        if ($confirmation['confirmation_status'] === 'confirmed') {
            $message = 'This appointment has already been confirmed.';
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $confirmed_at = date('Y-m-d H:i:s');
            $confirmed_status = 'confirmed';

            $update_stmt = $conn->prepare("UPDATE appointment_confirmations
                                           SET confirmed_at = ?, confirmation_status = ?
                                           WHERE id = ? AND patient_id = ?");
            $update_stmt->bind_param('ssii', $confirmed_at, $confirmed_status, $confirmation_id, $patient_id);

            if ($update_stmt->execute()) {
                $message = 'Appointment confirmed successfully! See you on ' . (new DateTime($confirmation['slot_time']))->format('Y-m-d at H:i') . '.';
            } else {
                $error = 'Unable to confirm appointment.';
            }
            $update_stmt->close();

            $stmt = $conn->prepare("SELECT 
                                        ac.id,
                                        ac.appointment_id,
                                        ac.confirmation_status,
                                        ac.confirmed_at,
                                        a.slot_time,
                                        a.status AS appointment_status,
                                        u.name AS doctor_name,
                                        u.specialty
                                    FROM appointment_confirmations ac
                                    JOIN appointments a ON ac.appointment_id = a.id
                                    JOIN users u ON a.doctor_id = u.id
                                    WHERE ac.id = ? AND ac.patient_id = ?");
            $stmt->bind_param('ii', $confirmation_id, $patient_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $confirmation = $result->fetch_assoc();
            $stmt->close();
        }
    }
} else {
    $error = 'No confirmation ID provided.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Appointment - HealthTech</title>
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
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <h2 class="mb-4 fw-bold text-center">Appointment Confirmation</h2>

                <?php if ($error): ?>
                    <div class="alert alert-danger border-0 shadow-sm rounded-4 p-4 text-center">
                        <i class="bi bi-exclamation-triangle-fill fs-1 mb-3 d-block text-danger"></i>
                        <p class="mb-4"><?php echo htmlspecialchars($error); ?></p>
                        <a href="patient_dashboard.php" class="btn btn-secondary rounded-pill px-4">Back to Dashboard</a>
                    </div>
                <?php elseif ($confirmation): ?>
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="card-header bg-primary text-white py-3 border-0">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-calendar-check me-2"></i>Confirmation Details</h5>
                        </div>
                        <div class="card-body p-4">
                            <h5 class="card-title fw-bold">Doctor: <?php echo htmlspecialchars($confirmation['doctor_name']); ?></h5>
                            <p class="text-muted"><i class="bi bi-tag-fill me-2"></i><?php echo htmlspecialchars($confirmation['specialty']); ?></p>

                            <div class="alert alert-info border-0 shadow-sm rounded-3">
                                <strong>Appointment Date & Time:</strong><br>
                                <i class="bi bi-clock-fill me-1"></i> <?php echo htmlspecialchars((new DateTime($confirmation['slot_time']))->format('l, Y-m-d at H:i')); ?>
                            </div>

                            <div class="mb-4">
                                <strong class="d-block mb-1">Confirmation Status:</strong>
                                <span class="badge rounded-pill <?php echo $confirmation['confirmation_status'] === 'confirmed' ? 'bg-success' : 'bg-warning text-dark'; ?> px-3 py-2">
                                    <i class="bi <?php echo $confirmation['confirmation_status'] === 'confirmed' ? 'bi-check-circle-fill' : 'bi-hourglass-split'; ?> me-1"></i>
                                    <?php echo ucfirst(str_replace('_', ' ', $confirmation['confirmation_status'])); ?>
                                </span>
                            </div>

                            <?php if ($message): ?>
                                <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4">
                                    <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($message); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($confirmation['confirmation_status'] !== 'confirmed'): ?>
                                <p class="mb-3 text-muted">Please confirm that you will attend this appointment.</p>
                                <form method="POST">
                                    <button type="submit" class="btn btn-success btn-lg w-100 rounded-pill"><i class="bi bi-hand-thumbs-up-fill me-2"></i>Confirm Appointment</button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-success border-0 shadow-sm rounded-3">
                                    <strong>✓ Appointment Confirmed!</strong><br>
                                    You have confirmed your appointment. Please arrive 10 minutes early.
                                </div>
                            <?php endif; ?>
                            
                            <div class="text-center mt-3">
                                <a href="patient_dashboard.php" class="text-decoration-none text-muted">Back to Dashboard</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
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
