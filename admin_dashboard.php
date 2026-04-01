<?php
session_start();

/* User Authentication Check */
if(!isset($_SESSION["user_id"])){
    header("Location: login.php");
    exit;
}

/* Role check (only admin allowed) */
if($_SESSION["role"] != "admin"){
    header("Location: dashboard.php");
    exit;
}

require_once "db.php";

// Fetch all appointments with confirmation status
$stmt = $conn->prepare("SELECT a.id, a.slot_time, a.status, p.name AS patient_name, d.name AS doctor_name, 
                        ac.confirmation_status
                        FROM appointments a 
                        JOIN users p ON a.patient_id = p.id 
                        JOIN users d ON a.doctor_id = d.id 
                        LEFT JOIN appointment_confirmations ac ON a.id = ac.appointment_id
                        ORDER BY a.slot_time DESC");
$stmt->execute();
$result = $stmt->get_result();
$appointments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="style.css" rel="stylesheet">
</head>

<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow">
    <div class="container">
        <a class="navbar-brand" href="admin_dashboard.php">Admin Dashboard</a>
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
    <h2>Admin Dashboard</h2>
    <p>Welcome Admin <?php echo htmlspecialchars($_SESSION["name"]); ?></p>

    <h3 class="mt-4">All Appointments</h3>
    <?php if (count($appointments) === 0): ?>
        <div class="alert alert-info">No appointments in the system.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Slot</th>
                        <th>Status</th>
                        <th>Confirmation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $idx => $apt): ?>
                        <tr>
                            <td><?php echo $idx + 1; ?></td>
                            <td><?php echo htmlspecialchars($apt['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($apt['doctor_name']); ?></td>
                            <td><?php echo htmlspecialchars((new DateTime($apt['slot_time']))->format('Y-m-d H:i')); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $apt['status'] === 'approved' ? 'success' : ($apt['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                    <?php echo ucfirst($apt['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($apt['confirmation_status']): ?>
                                    <span class="badge bg-<?php echo $apt['confirmation_status'] === 'confirmed' ? 'success' : 'info'; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $apt['confirmation_status'])); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">No confirmation</span>
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