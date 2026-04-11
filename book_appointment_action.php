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

if ($slot_id <= 0) {
    die("Invalid slot ID.");
}

// Fetch slot details
$stmt = $conn->prepare("SELECT id, doctor_id, slot_time, slot_status FROM schedule_slots WHERE id = ? AND slot_status = 'available' LIMIT 1");
$stmt->bind_param('i', $slot_id);
$stmt->execute();
$slotResult = $stmt->get_result();
$slot = $slotResult->fetch_assoc();
$stmt->close();

if (!$slot) {
    die("Slot not available.");
}

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

echo "Appointment booked successfully! Please wait for the doctor's confirmation.";
?>