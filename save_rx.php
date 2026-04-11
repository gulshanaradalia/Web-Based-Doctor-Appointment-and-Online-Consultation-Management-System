<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
$patient_id = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
$rx = isset($_POST['rx']) ? trim($_POST['rx']) : '';

if (!$doctor_id || !$patient_id || empty($rx)) {
    echo json_encode(["status" => "error", "message" => "Missing prescription data."]);
    exit;
}

// Create uploads directory if not exists
$uploadFileDir = './uploads/';
if (!is_dir($uploadFileDir)) {
    mkdir($uploadFileDir, 0755, true);
}

// Save the prescription as a text file specific to this doctor and patient consultation
$fileName = $uploadFileDir . "rx_" . $doctor_id . "_" . $patient_id . ".txt";
if (file_put_contents($fileName, htmlspecialchars($rx)) !== false) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to save prescription file."]);
}
?>
