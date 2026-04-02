<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION["role"] !== "patient") {
    header("Location: dashboard.php");
    exit;
}

require_once "db.php";

$patient_id = $_SESSION["user_id"];
$doctor_id = isset($_GET["doctor_id"]) ? intval($_GET["doctor_id"]) : intval($_POST["doctor_id"] ?? 0);

if ($doctor_id <= 0) {
    die("Invalid doctor ID.");
}

$message = "";
$error = "";

// doctor info
$doctorStmt = $conn->prepare("SELECT id, name, specialty, location, consultation_fee
                              FROM users
                              WHERE id = ? AND role = 'doctor' AND status = 'active'
                              LIMIT 1");
$doctorStmt->bind_param("i", $doctor_id);
$doctorStmt->execute();
$doctorResult = $doctorStmt->get_result();
$doctor = $doctorResult->fetch_assoc();
$doctorStmt->close();

if (!$doctor) {
    die("Doctor not found.");
}

// handle booking
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["slot_id"])) {
    $slot_id = intval($_POST["slot_id"]);

    if ($slot_id <= 0) {
        $error = "Invalid slot selected.";
    } else {
        $conn->begin_transaction();

        try {
            $slotStmt = $conn->prepare("SELECT id, doctor_id, slot_time, slot_status
                                        FROM schedule_slots
                                        WHERE id = ? AND doctor_id = ?
                                        LIMIT 1");
            $slotStmt->bind_param("ii", $slot_id, $doctor_id);
            $slotStmt->execute();
            $slotResult = $slotStmt->get_result();
            $slot = $slotResult->fetch_assoc();
            $slotStmt->close();

            if (!$slot) {
                throw new Exception("Selected slot not found.");
            }

            if ($slot["slot_status"] !== "available") {
                throw new Exception("This slot is no longer available.");
            }

            $checkStmt = $conn->prepare("SELECT id
                                         FROM appointments
                                         WHERE doctor_id = ? AND slot_time = ?
                                         LIMIT 1");
            $checkStmt->bind_param("is", $doctor_id, $slot["slot_time"]);
            $checkStmt->execute();
            $exists = $checkStmt->get_result()->fetch_assoc();
            $checkStmt->close();

            if ($exists) {
                throw new Exception("This slot has already been booked.");
            }

            $insertStmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, slot_time, status)
                                          VALUES (?, ?, ?, 'pending')");
            $insertStmt->bind_param("iis", $patient_id, $doctor_id, $slot["slot_time"]);

            if (!$insertStmt->execute()) {
                throw new Exception("Failed to create appointment request.");
            }
            $insertStmt->close();

            $updateSlotStmt = $conn->prepare("UPDATE schedule_slots
                                              SET slot_status = 'booked'
                                              WHERE id = ? AND doctor_id = ?");
            $updateSlotStmt->bind_param("ii", $slot_id, $doctor_id);

            if (!$updateSlotStmt->execute()) {
                throw new Exception("Failed to update slot status.");
            }
            $updateSlotStmt->close();

            $conn->commit();
            $message = "Appointment request submitted successfully. Waiting for doctor approval.";
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

// fetch available slots
$slots = [];
$slotListStmt = $conn->prepare("SELECT id, slot_time, slot_status
                                FROM schedule_slots
                                WHERE doctor_id = ?
                                  AND slot_status = 'available'
                                ORDER BY slot_time ASC");
$slotListStmt->bind_param("i", $doctor_id);
$slotListStmt->execute();
$slotListResult = $slotListStmt->get_result();

while ($row = $slotListResult->fetch_assoc()) {
    $slots[] = $row;
}
$slotListStmt->close();
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
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="doctor_search.php">Search Doctors</a></li>
                    <li class="nav-item"><a class="nav-link" href="patient_dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mt-5 pt-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h2 class="mb-4">Book Appointment</h2>

                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h4 class="card-title mb-2"><?php echo htmlspecialchars($doctor["name"]); ?></h4>
                        <p class="mb-1"><strong>Specialty:</strong> <?php echo htmlspecialchars($doctor["specialty"] ?: "Not set"); ?></p>
                        <p class="mb-1"><strong>Location:</strong> <?php echo htmlspecialchars($doctor["location"] ?: "Not set"); ?></p>
                        <p class="mb-0"><strong>Consultation Fee:</strong> <?php echo htmlspecialchars(number_format((float)$doctor["consultation_fee"], 2)); ?> BDT</p>
                    </div>
                </div>

                <h4 class="mb-3">Available Slots</h4>

                <?php if (empty($slots)): ?>
                    <div class="alert alert-info">No available slots found for this doctor.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Slot Time</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($slots as $index => $slot): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars((new DateTime($slot["slot_time"]))->format("D, Y-m-d h:i A")); ?></td>
                                        <td><span class="badge bg-success"><?php echo ucfirst($slot["slot_status"]); ?></span></td>
                                        <td>
                                            <form method="POST" class="m-0" onsubmit="return confirm('Do you want to book this slot?');">
                                                <input type="hidden" name="doctor_id" value="<?php echo $doctor_id; ?>">
                                                <input type="hidden" name="slot_id" value="<?php echo $slot["id"]; ?>">
                                                <button type="submit" class="btn btn-primary btn-sm">Book This Slot</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <a href="doctor_search.php" class="btn btn-secondary mt-3">Back to Doctor Search</a>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>