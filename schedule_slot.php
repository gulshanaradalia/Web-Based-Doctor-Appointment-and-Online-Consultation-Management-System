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
    <link href="style.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow">
        <div class="container">
            <a class="navbar-brand" href="doctor_dashboard.php">Doctor Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
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
        <h2>Schedule Slot Management</h2>

        <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <form method="POST" class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label">Add New Slot</label>
                <input type="datetime-local" name="slot_time" class="form-control" required>
            </div>
            <div class="col-md-2 align-self-end">
                <button type="submit" name="add_slot" class="btn btn-accent w-100">Add Slot</button>
            </div>
        </form>

        <h4>All Scheduled Slots</h4>
        <?php if (empty($slots)): ?>
            <div class="alert alert-info">No schedule slots found.</div>
        <?php else: ?>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Slot Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($slots as $idx => $slot): ?>
                        <tr>
                            <td><?php echo $idx + 1; ?></td>
                            <td><?php echo htmlspecialchars((new DateTime($slot['slot_time']))->format('D, Y-m-d g:i A')); ?></td>
                            <td><?php echo ucfirst($slot['slot_status']); ?></td>
                            <td>
                                <a href="schedule_slot.php?action=delete&id=<?php echo $slot['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this slot?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <a href="doctor_dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>