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

$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
require_once 'db.php';

$doctor = null;
if ($doctor_id > 0) {
    $stmt = $conn->prepare("SELECT id, name, email, phone, location, specialty FROM users WHERE id = ? AND role = 'doctor'");
    $stmt->bind_param('i', $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $doctor = $result->fetch_assoc();
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment</title>
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
                <li class="nav-item"><a class="nav-link" href="doctor_search.php">Doctor Search</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>
<main class="container mt-5 pt-5">
    <h2>Book Appointment</h2>
    <?php if (!$doctor): ?>
        <div class="alert alert-warning">Doctor not found. Please go back to search and select a valid doctor.</div>
        <a href="doctor_search.php" class="btn btn-secondary">Back to Search</a>
    <?php else: ?>
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($doctor['name']); ?></h5>
                <p class="mb-1"><strong>Specialty:</strong> <?php echo htmlspecialchars($doctor['specialty']); ?></p>
                <p class="mb-1"><strong>Location:</strong> <?php echo htmlspecialchars($doctor['location']); ?></p>
                <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($doctor['phone']); ?></p>
                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($doctor['email']); ?></p>
            </div>
        </div>
        <div class="alert alert-info">Appointment booking workflow is not fully implemented yet. Add your booking/availability logic, request workflow, or payment integration here.</div>
        <a href="doctor_search.php" class="btn btn-primary">Back to Search</a>
    <?php endif; ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>