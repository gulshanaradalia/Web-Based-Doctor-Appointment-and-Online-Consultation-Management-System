<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['role'] != 'doctor') {
    header('Location: dashboard.php');
    exit;
}

require_once 'db.php';
$doctor_id = $_SESSION['user_id'];

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_slot'])) {
    $newSlotTime = trim($_POST['slot_time'] ?? '');
    if ($newSlotTime === '') {
        $error = 'Please select a date and time.';
    } else {
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $newSlotTime);
        if (!$dt) {
            $error = 'Invalid date format.';
        } elseif ($dt <= new DateTime()) {
            $error = 'Time must be in the future.';
        } else {
            $formatted = $dt->format('Y-m-d H:i:s');
            $insert = $conn->prepare("INSERT INTO schedule_slots (doctor_id, slot_time, slot_status) VALUES (?, ?, 'available')");
            $insert->bind_param('is', $doctor_id, $formatted);
            if ($insert->execute()) {
                $message = 'Slot added successfully.';
            } else {
                $error = 'Unable to add slot: ' . $conn->error;
            }
            $insert->close();
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $slotId = intval($_GET['id']);
    $delStmt = $conn->prepare("DELETE FROM schedule_slots WHERE id = ? AND doctor_id = ?");
    $delStmt->bind_param('ii', $slotId, $doctor_id);
    if ($delStmt->execute()) {
        $message = 'Slot deleted successfully.';
    } else {
        $error = 'Unable to delete slot.';
    }
    $delStmt->close();
    header('Location: schedule_slot.php');
    exit;
}

$slots = [];
$stmt = $conn->prepare("SELECT id, slot_time, slot_status FROM schedule_slots WHERE doctor_id = ? ORDER BY slot_time ASC");
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $slots[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Slot Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
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
    <main class="container mt-5 pt-5 mb-5 flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">Schedule Slot Management</h2>
            <a href="doctor_dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
        </div>

        <?php if ($message): ?><div class="alert alert-success border-0 shadow-sm"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger border-0 shadow-sm"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <form method="POST" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Add New Slot</label>
                    <input type="datetime-local" name="slot_time" class="form-control" required>
                </div>
                <div class="col-md-3 align-self-end">
                    <button type="submit" name="add_slot" class="btn btn-primary w-100 rounded-pill px-4">Add Slot</button>
                </div>
            </form>
        </div>

        <h4 class="mb-3 fw-bold">All Scheduled Slots</h4>
        <?php if (empty($slots)): ?>
            <div class="alert alert-info border-0 shadow-sm">No schedule slots found.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover bg-white shadow-sm rounded-4 overflow-hidden align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Slot Time</th>
                            <th>Status</th>
                            <th class="pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($slots as $idx => $slot): ?>
                            <tr>
                                <td class="ps-3"><?php echo $idx + 1; ?></td>
                                <td><i class="bi bi-calendar-event me-2 text-primary"></i><?php echo htmlspecialchars((new DateTime($slot['slot_time']))->format('D, Y-m-d g:i A')); ?></td>
                                <td>
                                    <?php if ($slot['slot_status'] === 'available'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle"><?php echo ucfirst($slot['slot_status']); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><?php echo ucfirst($slot['slot_status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-3">
                                    <a href="schedule_slot.php?action=delete&id=<?php echo $slot['id']; ?>" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Delete this slot?');"><i class="bi bi-trash me-1"></i>Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
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