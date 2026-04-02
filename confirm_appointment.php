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
    <title>Confirm Appointment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow">
    <div class="container">
        <a class="navbar-brand" href="patient_dashboard.php">Patient Dashboard</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="doctor_search.php">Search Doctors</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="container mt-5 pt-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <h2 class="mb-4">Appointment Confirmation</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <a href="patient_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            <?php elseif ($confirmation): ?>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Doctor: <?php echo htmlspecialchars($confirmation['doctor_name']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars($confirmation['specialty']); ?></p>

                        <div class="alert alert-info">
                            <strong>Appointment Date & Time:</strong><br>
                            <?php echo htmlspecialchars((new DateTime($confirmation['slot_time']))->format('l, Y-m-d at H:i')); ?>
                        </div>

                        <div class="mb-3">
                            <strong>Confirmation Status:</strong><br>
                            <span class="badge bg-<?php echo $confirmation['confirmation_status'] === 'confirmed' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $confirmation['confirmation_status'])); ?>
                            </span>
                        </div>

                        <?php if ($message): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                        <?php endif; ?>

                        <?php if ($confirmation['confirmation_status'] !== 'confirmed'): ?>
                            <p class="mb-3">Please confirm that you will attend this appointment.</p>
                            <form method="POST">
                                <button type="submit" class="btn btn-success btn-lg w-100">Confirm Appointment</button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <strong>✓ Appointment Confirmed!</strong><br>
                                You have confirmed your appointment. Please arrive 10 minutes early.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <a href="patient_dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
            <?php endif; ?>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
