<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare('SELECT id, name, email, phone, role, location, specialty, status FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$success = isset($_GET['success']) && $_GET['success'] === '1';

function clean($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile - Doctor Appointment</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="style.css" rel="stylesheet">
<style>
    .profile-card { border-radius: 24px; background: #fff; border: 1px solid #e8ecf6; box-shadow: 0 12px 26px rgba(11, 35, 86, 0.10); }
    .profile-avatar { width: 90px; height: 90px; border-radius: 50%; background: #f3f7ff; color: #1f3b72; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 700; margin: auto; }
    .profile-header { font-size: 1.7rem; font-weight: 700; }
    .profile-subtitle { color: #6b7280; letter-spacing: 0.02em; }
</style>
</head>
<body style="background: #f5f8ff;">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm fixed-top">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Doctor Appointment</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" href="profile.php">Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>
<main class="container mt-5 pt-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <?php if ($success): ?>
                <div class="alert alert-success">Your profile has been updated successfully.</div>
            <?php endif; ?>
            <div class="profile-card p-4 mb-4">
                <div class="text-center mb-3">
                    <div class="profile-avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                    <h2 class="profile-header mt-3"><?php echo clean($user['name']); ?></h2>
                    <p class="profile-subtitle">@<?php echo strtolower(preg_replace('/\s+/', '', $user['name'])); ?></p>
                </div>
                <div class="row g-2">
                    <div class="col-6"><strong>Email</strong><br><?php echo clean($user['email']); ?></div>
                    <div class="col-6"><strong>Phone</strong><br><?php echo clean($user['phone']); ?></div>
                </div>
                <div class="row g-2 mt-2">
                    <div class="col-6"><strong>Role</strong><br><?php echo ucfirst(clean($user['role'])); ?></div>
                    <div class="col-6"><strong>Location</strong><br><?php echo clean($user['location'] ?: '-'); ?></div>
                </div>
                <div class="row g-2 mt-2">
                    <div class="col-6"><strong>Specialty</strong><br><?php echo clean($user['specialty'] ?: '-'); ?></div>
                    <div class="col-6"><strong>Status</strong><br><?php echo ucfirst(clean($user['status'])); ?></div>
                </div>
                <div class="mt-4 text-center">
                    <a class="btn btn-dark btn-lg" href="edit_profile.php"><i class="bi bi-pencil-square me-2"></i>Edit Profile</a>
                </div>
            </div>
        </div>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>