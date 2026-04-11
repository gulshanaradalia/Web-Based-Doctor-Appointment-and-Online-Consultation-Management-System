<?php
require_once "db.php";
$email = "admin@healthtech.com";
$password = password_hash("admin123", PASSWORD_DEFAULT);

$stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
if($stmt->get_result()->num_rows === 0) {
    $stmt->close();
    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, role, password_hash, status) VALUES ('System Admin', ?, '0000000000', 'admin', ?, 'active')");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    echo "Admin created: admin@healthtech.com / admin123\n";
} else {
    echo "Admin already exists.\n";
}
?>
