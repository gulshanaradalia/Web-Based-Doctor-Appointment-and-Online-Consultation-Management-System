<?php
session_start();
require_once 'db.php';

// Ensure the user is a patient
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] !== 'patient') {
    header("Location: dashboard.php");
    exit;
}

$patient_id = $_SESSION['user_id'];
$doctor_id = $_GET['doctor_id'] ?? null;

if (!$doctor_id) {
    header("Location: doctor_search.php");
    exit;
}

// Fetch doctor details
$stmt = $conn->prepare("SELECT id, name, specialty, hospital, location, consultation_fee FROM users WHERE id = ? AND role = 'doctor'");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();
$stmt->close();

if (!$doctor) {
    die("Doctor not found.");
}

// Fetch available slots for the doctor
$slots_grouped = [];
$stmt = $conn->prepare("SELECT id, slot_time FROM schedule_slots WHERE doctor_id = ? AND slot_status = 'available' AND slot_time > NOW() ORDER BY slot_time ASC");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $date = (new DateTime($row['slot_time']))->format('Y-m-d');
    $slots_grouped[$date][] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Availability - <?php echo htmlspecialchars($doctor['name']); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <!-- Custom stylesheet -->
    <link href="style.css" rel="stylesheet">
    <style>
        .availability-header {
            background: linear-gradient(135deg, #4a80f0 0%, #50c7c7 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-bottom-left-radius: 2rem;
            border-bottom-right-radius: 2rem;
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
                    <li class="nav-item"><a class="nav-link active" href="doctor_search.php">Find Doctors</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link btn btn-light btn-sm text-primary px-3" href="register.php">Online Appointment</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="availability-header text-center shadow-sm">
        <div class="container">
            <h1 class="display-5 fw-bold"><?php echo htmlspecialchars($doctor['name']); ?></h1>
            <p class="lead mb-0"><?php echo htmlspecialchars($doctor['specialty']); ?> • <?php echo htmlspecialchars($doctor['hospital']); ?></p>
            <div class="mt-3">
                <span class="badge bg-light text-primary py-2 px-3 rounded-pill shadow-sm">
                    <i class="bi bi-geo-alt-fill me-1"></i> <?php echo htmlspecialchars($doctor['location']); ?>
                </span>
                <span class="badge bg-light text-success py-2 px-3 rounded-pill shadow-sm ms-2">
                    <i class="bi bi-cash-stack me-1"></i> <?php echo htmlspecialchars(number_format($doctor['consultation_fee'], 2)); ?> BDT
                </span>
            </div>
        </div>
    </header>

    <main class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold mb-0">Select an Available Slot</h3>
                    <a href="doctor_search.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Search</a>
                </div>

                <?php if (empty($slots_grouped)): ?>
                    <div class="card border-0 shadow-sm p-5 text-center bg-white rounded-4">
                        <div class="mb-3 text-muted">
                            <i class="bi bi-calendar-x fs-1"></i>
                        </div>
                        <h4>No Available Slots</h4>
                        <p class="text-muted">This doctor hasn't scheduled any available time slots for the upcoming days. Please check back later or choose another doctor.</p>
                        <a href="doctor_search.php" class="btn btn-primary mt-3">Find Another Doctor</a>
                    </div>
                <?php else: ?>
                    <div class="day-slot-wrapper">
                        <?php foreach ($slots_grouped as $date => $slots): ?>
                            <?php $formattedDate = (new DateTime($date))->format('l, jS F Y'); ?>
                            <div class="day-card shadow-sm border-0 mb-4 bg-white rounded-4 overflow-hidden">
                                <div class="day-header bg-primary text-white p-3 d-flex align-items-center">
                                    <i class="bi bi-calendar-date me-2 fs-5"></i>
                                    <span class="h5 mb-0"><?php echo $formattedDate; ?></span>
                                </div>
                                <div class="p-3">
                                    <div class="row row-cols-2 row-cols-md-4 g-3">
                                        <?php foreach ($slots as $slot): ?>
                                            <?php $time = (new DateTime($slot['slot_time']))->format('g:i A'); ?>
                                            <div class="col">
                                                <a href="book_appointment.php?doctor_id=<?php echo $doctor_id; ?>&slot_id=<?php echo $slot['id']; ?>" class="btn btn-outline-primary w-100 py-2 shadow-sm rounded-3">
                                                    <strong><?php echo $time; ?></strong>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-light py-5">
        <div class="container">
            <div class="row gy-4">
                <div class="col-md-6 text-center text-md-start">
                    <h5 class="fw-bold text-white mb-3">HealthTech</h5>
                    <p class="text-muted mb-0">Providing accessible and efficient digital healthcare services for a healthier community.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <h5 class="fw-bold text-white mb-3">Stay Connected</h5>
                    <div class="d-flex justify-content-center justify-content-md-end gap-3">
                        <a href="#" class="text-primary fs-3"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-info fs-3"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-danger fs-3"><i class="bi bi-instagram"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4 border-secondary opacity-25">
            <div class="text-center text-muted small">
                © <?php echo date("Y"); ?> HealthTech Management System. All rights reserved.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
