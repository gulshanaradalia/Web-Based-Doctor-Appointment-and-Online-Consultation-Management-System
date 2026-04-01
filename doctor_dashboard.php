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

$message = '';
if (isset($_GET['action'], $_GET['id']) && in_array($_GET['action'], ['approve', 'reject'], true)) {
    $action = $_GET['action'];
    $appointment_id = intval($_GET['id']);
    $status = $action === 'approve' ? 'approved' : 'rejected';

    $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ? AND doctor_id = ? AND status = 'pending'");
    $stmt->bind_param('sii', $status, $appointment_id, $doctor_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // Get appointment details for confirmation
        $stmt2 = $conn->prepare("SELECT patient_id FROM appointments WHERE id = ?");
        $stmt2->bind_param('i', $appointment_id);
        $stmt2->execute();
        $result = $stmt2->get_result();
        $appt = $result->fetch_assoc();
        $stmt2->close();

        // Create confirmation record if approved
        if ($status === 'approved' && $appt) {
            $confirmation_status = 'notification_sent';
            $stmt3 = $conn->prepare("INSERT INTO appointment_confirmations (appointment_id, patient_id, confirmation_status) VALUES (?, ?, ?)");
            $stmt3->bind_param('iis', $appointment_id, $appt['patient_id'], $confirmation_status);
            $stmt3->execute();
            $stmt3->close();
        }

        $message = "Appointment ".($status === 'approved' ? 'approved' : 'rejected')." successfully.";
    } else {
        $message = "Unable to update appointment status. It may have been updated already.";
    }
    $stmt->close();
}

$pendingAppointments = [];
$stmt = $conn->prepare("SELECT a.id, a.slot_time, u.name AS patient_name, u.email AS patient_email, u.phone AS patient_phone FROM appointments a JOIN users u ON a.patient_id = u.id WHERE a.doctor_id = ? AND a.status = 'pending' ORDER BY a.slot_time ASC");
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$pendingAppointments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Doctor Dashboard</title>
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
                <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>
<main class="container mt-5 pt-5">
    <h2>Doctor Dashboard</h2>
    <p>Welcome Dr. <?php echo htmlspecialchars($_SESSION['name']); ?></p>
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <h3 class="mt-4">Pending Appointment Requests</h3>
    <?php if (count($pendingAppointments) === 0): ?>
        <div class="alert alert-info">No pending appointment requests.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Contact</th>
                        <th>Slot</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingAppointments as $idx => $apt): ?>
                        <tr>
                            <td><?php echo $idx + 1; ?></td>
                            <td><?php echo htmlspecialchars($apt['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($apt['patient_email'].' / '.$apt['patient_phone']); ?></td>
                            <td><?php echo htmlspecialchars((new DateTime($apt['slot_time']))->format('Y-m-d H:i')); ?></td>
                            <td>
                                <a href="doctor_dashboard.php?action=approve&id=<?php echo $apt['id']; ?>" class="btn btn-success btn-sm me-2">Approve</a>
                                <a href="doctor_dashboard.php?action=reject&id=<?php echo $apt['id']; ?>" class="btn btn-danger btn-sm">Reject</a>
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