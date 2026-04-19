<?php
session_start();
require_once 'db.php';

// Ensure the user is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: login.php");
    exit;
}

$patient_id = $_SESSION['user_id'];
$doctor_id = $_GET['doctor_id'] ?? null;
$selected_slot_id = $_GET['slot_id'] ?? null;

// Fetch doctor details
if ($doctor_id) {
    $stmt = $conn->prepare("SELECT id, name, specialty, consultation_fee FROM users WHERE id = ? AND role = 'doctor'");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $doctor = $result->fetch_assoc();
    $stmt->close();

    if (!$doctor) {
        die("Doctor not found.");
    }
}

// Fetch available slots for the doctor
$slots = [];
if ($doctor_id) {
    if ($selected_slot_id) {
        // If a specific slot is selected, fetch it specifically but also fetch others for choice
        $stmt = $conn->prepare("SELECT id, slot_time FROM schedule_slots WHERE doctor_id = ? AND (slot_status = 'available' OR id = ?) AND slot_time > NOW() ORDER BY slot_time ASC");
        $stmt->bind_param("ii", $doctor_id, $selected_slot_id);
    } else {
        $stmt = $conn->prepare("SELECT id, slot_time FROM schedule_slots WHERE doctor_id = ? AND slot_status = 'available' AND slot_time > NOW() ORDER BY slot_time ASC");
        $stmt->bind_param("i", $doctor_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $slots[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - HealthTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        .swal2-popup {
            border-radius: 20px !important;
            font-family: 'Open Sans', sans-serif !important;
        }
        .swal2-confirm {
            background: #17a2b8 !important;
            border-radius: 8px !important;
            padding: 10px 30px !important;
        }
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
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

    <main class="flex-grow-1 p-2">
        <div class="container mt-4 mb-5">
            <h2 class="mb-4 fw-bold">Book Appointment</h2>
            <div class="card mb-4 border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-person-badge-fill me-2"></i>Doctor Information</h5>
                </div>
                <div class="card-body">
                    <h4 class="fw-bold"><?php echo htmlspecialchars($doctor['name']); ?></h4>
                    <p class="text-muted"><i class="bi bi-tag-fill me-2"></i>Specialty: <?php echo htmlspecialchars($doctor['specialty']); ?></p>
                    <p class="mb-0"><i class="bi bi-cash-stack me-2 text-success"></i>Consultation Fee: <strong><?php echo htmlspecialchars($doctor['consultation_fee']); ?> BDT</strong></p>
                </div>
            </div>

            <h4 class="mb-3 fw-bold">Available Slots</h4>
            <?php if (empty($slots)): ?>
                <div class="alert alert-info border-0 shadow-sm rounded-3">No available slots found for this doctor.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover bg-white shadow-sm rounded-4 overflow-hidden align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3 text-uppercase small fw-bold">#</th>
                                <th class="text-uppercase small fw-bold">Slot Time</th>
                                <th class="pe-3 text-uppercase small fw-bold">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($slots as $index => $slot): ?>
                                <tr class="<?php echo ($selected_slot_id == $slot['id']) ? 'table-primary fw-bold' : ''; ?>">
                                    <td class="ps-3"><?php echo $index + 1; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-calendar-event me-2 text-primary"></i>
                                            <?php echo htmlspecialchars((new DateTime($slot['slot_time']))->format('D, Y-m-d h:i A')); ?>
                                            <?php if ($selected_slot_id == $slot['id']): ?>
                                                <span class="badge bg-primary ms-2">Selected</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="pe-3">
                                        <form method="POST" action="book_appointment_action.php" class="d-flex align-items-center gap-2">
                                            <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                                            <select name="appointment_type" class="form-select form-select-sm" style="width: auto;" required>
                                                <option value="offline" selected>Offline Visit</option>
                                                <option value="online">Online Call</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm <?php echo ($selected_slot_id == $slot['id']) ? 'btn-success' : 'btn-primary'; ?> text-nowrap rounded-pill px-3">
                                                <?php echo ($selected_slot_id == $slot['id']) ? 'Confirm Booking' : 'Book Slot'; ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="mt-4">
                <a href="doctor_search.php" class="btn btn-secondary rounded-pill px-4"><i class="bi bi-arrow-left me-2"></i>Back to Doctor Search</a>
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
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php if (isset($_GET['success']) && isset($_SESSION['success_booking'])): ?>
    <script>
        Swal.fire({
            title: 'Booking Confirmed!',
            text: <?php echo json_encode($_SESSION['success_booking']); ?>,
            icon: 'success',
            confirmButtonText: 'OK',
            allowOutsideClick: false,
            backdrop: `rgba(23, 162, 184, 0.1)`
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'patient_dashboard.php';
            }
        });
    </script>
    <?php 
        unset($_SESSION['success_booking']);
    endif; ?>

</body>
</html>