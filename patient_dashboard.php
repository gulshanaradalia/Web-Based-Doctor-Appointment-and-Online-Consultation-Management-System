<?php
session_start();


if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}


if ($_SESSION["role"] != "patient") {
    header("Location: dashboard.php");
    exit;
}

require_once "db.php";

$patient_id = $_SESSION['user_id'];

// Fetch patient's appointments with confirmation status
$stmt = $conn->prepare("SELECT a.id, a.slot_time, a.status, u.id AS doctor_id, u.name AS doctor_name, u.specialty, 
                        ac.id as confirmation_id, ac.confirmation_status 
                        FROM appointments a 
                        JOIN users u ON a.doctor_id = u.id 
                        LEFT JOIN appointment_confirmations ac ON a.id = ac.appointment_id 
                        WHERE a.patient_id = ? 
                        ORDER BY a.slot_time DESC");
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$appointments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$slotDurationMinutes = 15;
foreach ($appointments as $idx => $apt) {
    $queueStmt = $conn->prepare("SELECT COUNT(*) AS queue_position FROM appointments WHERE doctor_id = ? AND slot_time <= ? AND status IN ('pending','approved')");
    $queueStmt->bind_param('is', $apt['doctor_id'], $apt['slot_time']);
    $queueStmt->execute();
    $queueResult = $queueStmt->get_result();
    $queueData = $queueResult->fetch_assoc();
    $queueStmt->close();

    $queuePosition = isset($queueData['queue_position']) ? (int)$queueData['queue_position'] : 1;
    $slotDateTime = new DateTime($apt['slot_time']);
    $now = new DateTime();
    $timeUntil = max(0, ceil(($slotDateTime->getTimestamp() - $now->getTimestamp()) / 60));
    $estimatedWait = max($timeUntil, ($queuePosition - 1) * $slotDurationMinutes);

    $appointments[$idx]['queue_position'] = $queuePosition;
    $appointments[$idx]['estimated_wait'] = $estimatedWait;
}


// Fetch pending confirmations
$confirm_stmt = $conn->prepare("SELECT ac.id, ac.appointment_id, a.slot_time, u.name AS doctor_name, u.specialty
                               FROM appointment_confirmations ac
                               JOIN appointments a ON ac.appointment_id = a.id
                               JOIN users u ON a.doctor_id = u.id
                               WHERE ac.patient_id = ? AND ac.confirmation_status IN ('notification_sent', 'pending')
                               ORDER BY a.slot_time ASC");
$confirm_stmt->bind_param('i', $patient_id);
$confirm_stmt->execute();
$confirm_result = $confirm_stmt->get_result();
$pending_confirmations = $confirm_result->fetch_all(MYSQLI_ASSOC);
$confirm_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow">
        <div class="container">
            <a class="navbar-brand" href="patient_dashboard.php">Patient Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="doctor_search.php">Search Doctors</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mt-5 pt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h2 class="mb-3">Hello, <?php echo htmlspecialchars($_SESSION["name"]); ?></h2>
                <p class="text-muted">Use the button below to search and book the right doctor.</p>
                <a class="btn btn-accent btn-lg" href="doctor_search.php">Go to Doctor Search</a>
            </div>
        </div>

        <?php if (!empty($pending_confirmations)): ?>
            <div class="row justify-content-center mt-4">
                <div class="col-lg-10">
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <h5 class="alert-heading">⏳ Pending Confirmations</h5>
                        <p class="mb-0">You have <?php echo count($pending_confirmations); ?> appointment(s) waiting for your confirmation.</p>
                        <hr>
                        <?php foreach ($pending_confirmations as $confirm): ?>
                            <div class="mb-3">
                                <strong><?php echo htmlspecialchars($confirm['doctor_name']); ?></strong> (<?php echo htmlspecialchars($confirm['specialty']); ?>)
                                <br>
                                <small class="text-muted">Slot: <?php echo htmlspecialchars((new DateTime($confirm['slot_time']))->format('Y-m-d H:i')); ?></small>
                                <br>
                                <a href="confirm_appointment.php?id=<?php echo $confirm['confirmation_id']; ?>" class="btn btn-sm btn-success mt-2">Confirm Appointment</a>
                            </div>
                            <?php if ($confirm !== end($pending_confirmations)): ?>
                                <hr><?php endif; ?>
                        <?php endforeach; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center mt-5">
            <div class="col-lg-10">
                <h3 class="mb-4">Your Appointments</h3>
                <?php if (count($appointments) === 0): ?>
                    <div class="alert alert-info">You have no appointments yet. Search for a doctor to book one.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Doctor</th>
                                    <th>Specialty</th>
                                    <th>Slot</th>
                                    <th>Queue #</th>
                                    <th>Est. Wait</th>
                                    <th>Status</th>
                                    <th>Confirmation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $idx => $apt): ?>
                                    <tr>
                                        <td><?php echo $idx + 1; ?></td>
                                        <td><?php echo htmlspecialchars($apt['doctor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($apt['specialty']); ?></td>
                                        <td><?php echo htmlspecialchars((new DateTime($apt['slot_time']))->format('Y-m-d H:i')); ?></td>
                                        <td><?php echo htmlspecialchars($apt['queue_position'] ?? '-'); ?></td>
                                        <td><?php echo isset($apt['estimated_wait']) ? htmlspecialchars($apt['estimated_wait'] . ' mins') : '-'; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $apt['status'] === 'approved' ? 'success' : ($apt['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                                <?php echo ucfirst($apt['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($apt['confirmation_id']): ?>
                                                <span class="badge bg-<?php echo $apt['confirmation_status'] === 'confirmed' ? 'success' : 'info'; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $apt['confirmation_status'])); ?>
                                                </span>
                                                <?php if ($apt['confirmation_status'] !== 'confirmed'): ?>
                                                    <br>
                                                    <a href="confirm_appointment.php?id=<?php echo $apt['confirmation_id']; ?>" class="btn btn-sm btn-primary mt-2">Confirm</a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No confirmation yet</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>