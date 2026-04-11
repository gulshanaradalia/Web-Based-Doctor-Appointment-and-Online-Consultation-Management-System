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
    $stmt = $conn->prepare("SELECT id, slot_time FROM schedule_slots WHERE doctor_id = ? AND slot_status = 'available' ORDER BY slot_time ASC");
    $stmt->bind_param("i", $doctor_id);
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
    <title>Book Appointment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Book Appointment</h2>
        <div class="card mb-4">
            <div class="card-body">
                <h4><?php echo htmlspecialchars($doctor['name']); ?></h4>
                <p>Specialty: <?php echo htmlspecialchars($doctor['specialty']); ?></p>
                <p>Consultation Fee: <?php echo htmlspecialchars($doctor['consultation_fee']); ?> BDT</p>
            </div>
        </div>

        <h4>Available Slots</h4>
        <?php if (empty($slots)): ?>
            <div class="alert alert-info">No available slots found for this doctor.</div>
        <?php else: ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Slot Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($slots as $index => $slot): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars((new DateTime($slot['slot_time']))->format('Y-m-d H:i')); ?></td>
                            <td>
                                <form method="POST" action="book_appointment_action.php" class="d-flex align-items-center gap-2">
                                    <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                                    <select name="appointment_type" class="form-select form-select-sm" style="width: auto;" required>
                                        <option value="offline" selected>Offline Visit</option>
                                        <option value="online">Online Call</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary whitespace-nowrap text-nowrap">Book Slot</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <a href="doctor_search.php" class="btn btn-secondary">Back to Doctor Search</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>