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

function clean($v)
{
  return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile - Doctor Appointment</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
  <link href="style.css" rel="stylesheet">
  <style>
    body {
      background: #eef3ff;
    }

    .profile-container {
      max-width: 420px;
      margin: 6rem auto 1rem;
    }

    .card-profile {
      border-radius: 24px;
      background: #fff;
      border: 1px solid #e1e7f7;
      box-shadow: 0 14px 34px rgba(0, 0, 0, 0.08);
    }

    .avatar {
      width: 96px;
      height: 96px;
      border-radius: 50%;
      margin: 0 auto 0.75rem;
      border: 2px solid #000;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f2f6ff;
      color: #000;
      font-size: 2rem;
    }

    .menu-list .list-group-item {
      border-radius: 14px;
      border: 0;
      margin-bottom: 0.5rem;
      background: #f6f8ff;
    }

    .menu-list .list-group-item a {
      text-decoration: none;
      color: #22306b;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .menu-list .list-group-item:hover {
      background: #e8efff;
    }

    .nav-sm {
      position: fixed;
      left: 10px;
      right: 10px;
      bottom: 10px;
      background: #000;
      border-radius: 20px;
      padding: 10px 8px;
      display: flex;
      justify-content: space-around;
      z-index: 1000;
    }

    .nav-sm a {
      color: #fff;
      font-size: 0.71rem;
      text-align: center;
      text-decoration: none;
    }
  </style>
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
        </ul>
        <ul class="navbar-nav">
          <li class="nav-item"><a class="nav-link" href="<?php echo $_SESSION['role'] === 'doctor' ? 'doctor_dashboard.php' : 'patient_dashboard.php'; ?>"><i class="bi bi-speedometer2"></i> My Dashboard</a></li>
          <li class="nav-item"><a class="nav-link active" href="profile.php"><i class="bi bi-person-circle"></i> Profile</a></li>
          <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
      </div>
    </div>
  </nav>
  <div class="profile-container">
    <div class="card card-profile p-4">
      <div class="text-center mb-3">
        <div class="avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
        <h4 class="mb-0"><?php echo clean($user['name']); ?></h4>
        <?php $displayUsername = strtolower(preg_replace('/\s+/', '', $user['name'])); ?>
        <p class="text-muted mb-1">@<?php echo clean($displayUsername); ?></p>
        <a class="btn btn-dark btn-sm" href="edit_profile.php">Edit Profile</a>
      </div>
      <div class="row gx-2 mb-2">
        <div class="col-6"><small class="text-muted">Email</small>
          <p class="mb-0"><?php echo clean($user['email']); ?></p>
        </div>
        <div class="col-6"><small class="text-muted">Phone</small>
          <p class="mb-0"><?php echo clean($user['phone']); ?></p>
        </div>
      </div>
      <div class="row gx-2 mb-2">
        <div class="col-6"><small class="text-muted">Role</small>
          <p class="mb-0"><?php echo ucfirst(clean($user['role'])); ?></p>
        </div>
        <div class="col-6"><small class="text-muted">Status</small>
          <p class="mb-0"><?php echo ucfirst(clean($user['status'])); ?></p>
        </div>
      </div>
      <div class="row gx-2">
        <div class="col-6"><small class="text-muted">Location</small>
          <p class="mb-0"><?php echo clean($user['location'] ?: '-'); ?></p>
        </div>
        <div class="col-6"><small class="text-muted">Specialty</small>
          <p class="mb-0"><?php echo clean($user['specialty'] ?: '-'); ?></p>
        </div>
      </div>
    </div>
  </div>
  <div class="profile-container menu-list">
    <div class="list-group">
      <div class="list-group-item"><a href="change_password.php"><span><i class="bi bi-lock-fill"></i> Change Password</span><i class="bi bi-chevron-right"></i></a></div>
      <div class="list-group-item"><a href="logout.php"><span><i class="bi bi-box-arrow-right text-danger"></i> <strong class="text-danger">Log out</strong></span><i class="bi bi-chevron-right"></i></a></div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="bg-dark text-light py-4 mt-5">
    <div class="container">
      <div class="row">
        <div class="col-md-12 text-center">
          <h5>Stay Connected</h5>
          <a href="#" class="text-primary me-2"><i class="bi bi-facebook fs-3"></i></a>
          <a href="#" class="text-info me-2"><i class="bi bi-twitter fs-3"></i></a>
          <a href="#" class="text-danger"><i class="bi bi-instagram fs-3"></i></a>
        </div>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>