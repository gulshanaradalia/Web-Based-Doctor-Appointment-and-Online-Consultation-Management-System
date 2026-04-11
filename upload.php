<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

if (!isset($_FILES['report_file']) || $_FILES['report_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["status" => "error", "message" => "File missing or error."]);
    exit;
}

$fileTmpPath = $_FILES['report_file']['tmp_name'];
$fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', $_FILES['report_file']['name']);
$uploadFileDir = './uploads/';

if (!is_dir($uploadFileDir)) {
    mkdir($uploadFileDir, 0755, true);
}

$dest_path = $uploadFileDir . $fileName;

$allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg', 'pdf');
$fileNameCmps = explode(".", $fileName);
$fileExtension = strtolower(end($fileNameCmps));

if (in_array($fileExtension, $allowedfileExtensions)) {
    if(move_uploaded_file($fileTmpPath, $dest_path)) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $fileUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/doctor_appointment/uploads/" . $fileName;
        
        // Save link for doctor
        $docId = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
        $patId = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
        if($docId && $patId) {
            file_put_contents($uploadFileDir . "report_link_" . $docId . "_" . $patId . ".txt", $fileUrl);
        }
        
        echo json_encode(["status" => "success", "file_url" => $fileUrl]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to save file."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid extension."]);
}
?>
