<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$consultation_id = intval($_POST['consultation_id'] ?? $_GET['consultation_id'] ?? 0);

if (!$consultation_id) {
    echo json_encode(["status" => "error", "message" => "Consultation ID missing"]);
    exit;
}

if ($action === 'get_data') {
    $stmt = $conn->prepare("SELECT file_url, doctor_notes FROM reports WHERE consultation_id = ? ORDER BY report_id DESC LIMIT 1");
    $stmt->bind_param("i", $consultation_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    echo json_encode(["status" => "success", "data" => $result ?: ["file_url" => null, "doctor_notes" => null]]);
    exit;
}

if ($action === 'upload_report' && $_SESSION['role'] === 'patient') {
    if (!isset($_FILES['report_file']) || $_FILES['report_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(["status" => "error", "message" => "File upload failed or missing."]);
        exit;
    }
    
    $fileTmpPath = $_FILES['report_file']['tmp_name'];
    $fileName = time() . '_' . $_FILES['report_file']['name'];
    $uploadFileDir = './uploads/';
    $dest_path = $uploadFileDir . $fileName;
    
    if(move_uploaded_file($fileTmpPath, $dest_path)) {
        // Check if report exists
        $stmt = $conn->prepare("SELECT report_id FROM reports WHERE consultation_id = ? LIMIT 1");
        $stmt->bind_param("i", $consultation_id);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($exists) {
            $stmt = $conn->prepare("UPDATE reports SET file_url = ? WHERE consultation_id = ?");
            $stmt->bind_param("si", $fileName, $consultation_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO reports (consultation_id, file_url) VALUES (?, ?)");
            $stmt->bind_param("is", $consultation_id, $fileName);
        }
        $stmt->execute();
        $stmt->close();
        echo json_encode(["status" => "success", "file_url" => $fileName]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to move uploaded file."]);
    }
    exit;
}

if ($action === 'save_prescription' && $_SESSION['role'] === 'doctor') {
    $doctor_notes = $_POST['prescription'] ?? '';
    
    $stmt = $conn->prepare("SELECT report_id FROM reports WHERE consultation_id = ? LIMIT 1");
    $stmt->bind_param("i", $consultation_id);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($exists) {
        $stmt = $conn->prepare("UPDATE reports SET doctor_notes = ? WHERE consultation_id = ?");
        $stmt->bind_param("si", $doctor_notes, $consultation_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO reports (consultation_id, doctor_notes) VALUES (?, ?)");
        $stmt->bind_param("is", $consultation_id, $doctor_notes);
    }
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(["status" => "success", "message" => "Prescription saved!"]);
    exit;
}

echo json_encode(["status" => "error", "message" => "Invalid action"]);
?>
