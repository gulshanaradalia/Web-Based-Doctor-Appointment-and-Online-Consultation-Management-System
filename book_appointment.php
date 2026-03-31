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

// Ensure appointments table exists
$conn->query("CREATE TABLE IF NOT EXISTS appointments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  doctor_id INT NOT NULL,
  slot_time DATETIME NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_doctor_slot (doctor_id, slot_time)
)") or die('Create appointments table failed: ' . $conn->error);

$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$doctor = null;

if ($doctor_id > 0) {
    $stmt = $conn->prepare("SELECT id, name, email, phone, location, specialty FROM users WHERE id = ? AND role = 'doctor'");
    $stmt->bind_param('i', $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $doctor = $result->fetch_assoc();
    $stmt->close();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $doctor) {
    $selected_slot = trim($_POST['slot'] ?? '');

    if (!$selected_slot) {
        $error = 'Please select an appointment slot.';
    } else {
        $slot_dt = DateTime::createFromFormat('Y-m-d H:i:s', $selected_slot);
        if (!$slot_dt || $slot_dt < new DateTime()) {
            $error = 'Invalid or past appointment slot selected.';
        } else {
            $stmt = $conn->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND slot_time = ? AND status IN ('pending','approved') LIMIT 1");
            $slot_str = $slot_dt->format('Y-m-d H:i:s');
            $stmt->bind_param('is', $doctor_id, $slot_str);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error = 'Selected slot is no longer available. Please choose another time.';
            } else {
                $stmt->close();
                $insert = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, slot_time, status) VALUES (?, ?, ?, 'pending')");
                $patient_id = $_SESSION['user_id'];
                $insert->bind_param('iis', $patient_id, $doctor_id, $slot_str);
                if ($insert->execute()) {
                    $message = 'Appointment request submitted successfully. Waiting for doctor approval.';
                } else {
                    $error = 'Unable to request appointment: ' . $conn->error;
                }
                $insert->close();
            }
            if ($stmt) $stmt->close();
        }
    }
}

// Build next 7-day slots
$slots = [];
$today = new DateTime('today');
for ($day = 0; $day < 7; $day++) {
    $date = (clone $today)->modify("+$day day");
    for ($hour = 9; $hour <= 16; $hour++) {
        $dt = (clone $date)->setTime($hour, 0, 0);
        if ($dt >= new DateTime()) {
            $slots[] = $dt;
        }
    }
}

$booked = [];
if ($doctor) {
    $stmt = $conn->prepare("SELECT slot_time, status FROM appointments WHERE doctor_id = ? AND slot_time >= NOW() AND status IN ('pending','approved')");
    $stmt->bind_param('i', $doctor_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $booked[$r['slot_time']] = $r['status'];
    }
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
    <h2>Doctor Availability & Booking</h2>
    <?php if (!$doctor): ?>
        <div class="alert alert-warning">Doctor not found. Please go back to search and select a valid doctor.</div>
        <a href="doctor_search.php" class="btn btn-secondary">Back to Search</a>
    <?php else: ?>
        <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <div class="card mb-4">
            <div class="card-body">
                <h4><?php echo htmlspecialchars($doctor['name']); ?> (<?php echo htmlspecialchars($doctor['specialty']); ?>)</h4>
                <p class="mb-1"><strong>Location:</strong> <?php echo htmlspecialchars($doctor['location']); ?></p>
                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($doctor['email']); ?></p>
                <p class="mb-0"><strong>Phone:</strong> <?php echo htmlspecialchars($doctor['phone']); ?></p>
            </div>
        </div>

        <form method="POST" class="mb-4">
            <div class="mb-3">
                <label class="form-label">Available Slots (next 7 days)</label>
                <div class="row g-2">
                    <?php foreach ($slots as $slot):
                        $slotKey = $slot->format('Y-m-d H:i:s');
                        $isBooked = isset($booked[$slotKey]);
                        $btnClass = $isBooked ? 'btn-secondary' : 'btn-outline-success';
                    ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <label class="btn <?php echo $btnClass; ?> w-100<?php echo $isBooked ? ' disabled' : ''; ?>">
                                <input type="radio" name="slot" value="<?php echo $slotKey; ?>" autocomplete="off"<?php echo $isBooked ? ' disabled' : ''; ?>>
                                <?php echo $slot->format('D m/d H:i'); ?>
                                <?php if ($isBooked): ?><small class="d-block text-nowrap">Booked</small><?php endif; ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <input type="hidden" name="doctor_id" value="<?php echo $doctor_id; ?>">
            <button type="submit" class="btn btn-accent">Request Appointment</button>
            <a href="doctor_search.php" class="btn btn-secondary">Back to Search</a>
        </form>

        <h5>Booked/Reserved Slots</h5>
        <?php if (count($booked) === 0): ?>
            <p class="text-muted">No appointments yet for this doctor.</p>
        <?php else: ?>
            <ul class="list-group">
                <?php foreach ($booked as $slot_time => $status): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo (new DateTime($slot_time))->format('D m/d H:i'); ?>
                        <span class="badge bg-<?php echo $status === 'approved' ? 'success' : 'warning'; ?>"><?php echo ucfirst($status); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endif; ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>