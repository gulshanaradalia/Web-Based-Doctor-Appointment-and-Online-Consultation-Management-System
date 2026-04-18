<?php
session_start();
require_once 'db.php';

// Ensure the user is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: login.php");
    exit;
}

$patient_id = $_SESSION['user_id'];
$slot_id = $_POST['slot_id'] ?? null;
$appointment_type = $_POST['appointment_type'] ?? 'offline';
if (!in_array($appointment_type, ['online', 'offline'])) $appointment_type = 'offline';

$status = 'success';
$message = 'Your appointment has been booked successfully.<br>Please wait for the doctor\'s confirmation.';
$title = 'Booking Confirmed!';
$icon = 'bi-check-lg';
$color_scheme = 'success'; // success or danger

if (!$slot_id || $slot_id <= 0) {
    $status = 'error';
    $title = 'Oops!';
    $message = 'Invalid slot selection. Please try again.';
    $icon = 'bi-exclamation-triangle';
    $color_scheme = 'danger';
} else {
    // Fetch slot details
    $stmt = $conn->prepare("SELECT id, doctor_id, slot_time, slot_status FROM schedule_slots WHERE id = ? AND slot_status = 'available' LIMIT 1");
    $stmt->bind_param('i', $slot_id);
    $stmt->execute();
    $slotResult = $stmt->get_result();
    $slot = $slotResult->fetch_assoc();
    $stmt->close();

    if (!$slot) {
        $status = 'error';
        $title = 'Slot Unavailable';
        $message = 'This slot has already been booked or is no longer available.';
        $icon = 'bi-x-circle';
        $color_scheme = 'danger';
    } else {
        // Insert appointment record
        $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, slot_time, status, appointment_type) VALUES (?, ?, ?, 'pending', ?)");
        $stmt->bind_param('iiss', $patient_id, $slot['doctor_id'], $slot['slot_time'], $appointment_type);
        $stmt->execute();
        $stmt->close();

        // Update slot status to 'booked'
        $stmt = $conn->prepare("UPDATE schedule_slots SET slot_status = 'booked' WHERE id = ?");
        $stmt->bind_param('i', $slot_id);
        $stmt->execute();
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Public Sans', sans-serif;
            background-color: #f7fafc;
        }
        .modal-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(4px);
            display: flex; align-items: center; justify-content: center;
            z-index: 1000;
        }
        .modal-card {
            background: #ffffff;
            padding: 3rem 2rem;
            border-radius: 12px;
            max-width: 480px; width: 90%;
            text-align: center;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            animation: modalFadeIn 0.3s ease-out;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.95) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .icon-circle {
            width: 100px; height: 100px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem auto;
            border-width: 4px; border-style: solid;
        }
        /* Success Theme */
        .scheme-success .icon-circle { background-color: #f0fff4; border-color: #c6f6d5; }
        .scheme-success .icon-circle i { color: #48bb78; }
        /* Danger Theme */
        .scheme-danger .icon-circle { background-color: #fff5f5; border-color: #fed7d7; }
        .scheme-danger .icon-circle i { color: #f56565; }

        .icon-circle i { font-size: 3.5rem; }
        
        .modal-card h2 {
            font-size: 2rem; font-weight: 700;
            color: #2d3748; margin-bottom: 1.25rem;
        }
        .modal-card p {
            font-size: 1.15rem; color: #718096;
            line-height: 1.6; margin-bottom: 2.5rem;
        }
        .btn-action {
            background-color: #3b82f6;
            color: #ffffff;
            padding: 0.85rem 3.5rem;
            border-radius: 8px;
            font-size: 1.1rem; font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-block;
            border: none;
        }
        .btn-action:hover {
            background-color: #2563eb; color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);
        }
    </style>
</head>
<body>
    <div class="modal-overlay">
        <div class="modal-card scheme-<?php echo $color_scheme; ?>">
            <div class="icon-circle">
                <i class="bi <?php echo $icon; ?>"></i>
            </div>
            <h2><?php echo $title; ?></h2>
            <p><?php echo $message; ?></p>
            <a href="<?php echo ($status==='success' ? 'patient_dashboard.php' : 'doctor_search.php'); ?>" class="btn-action">
                <?php echo ($status==='success' ? 'OK' : 'Try Again'); ?>
            </a>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>