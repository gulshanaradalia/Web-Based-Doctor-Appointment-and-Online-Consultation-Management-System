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
    <title>Profile - HealthTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        body {
            background: #eef3ff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding-top: 5rem;
        }

        .page-content {
            flex: 1 0 auto;
        }

        .profile-container {
            max-width: 420px;
            margin: 2rem auto 1rem;
        }

        .card-profile {
            border-radius: 24px;
            background: #fff;
            border: 1px solid #e1e7f7;
            box-shadow: 0 14px 34px rgba(0, 0, 0, 0.08);
            width: 100%;
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
                    <li class="nav-item"><a class="nav-link" href="doctor_search.php">Find Doctors</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="online_appointment.php">Online Appointment</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="page-content">
        <div class="profile-container">
            <div class="card card-profile p-4">
                <div class="text-center mb-3">
                    <div class="avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                    <h4 class="mb-0 fw-bold"><?php echo clean($user['name']); ?></h4>
                    <?php $displayUsername = strtolower(preg_replace('/\s+/', '', $user['name'])); ?>
                    <p class="text-muted mb-3">@<?php echo clean($displayUsername); ?></p>
                    <div class="d-grid gap-2">
                        <a class="btn btn-dark btn-sm rounded-pill" href="edit_profile.php"><i class="bi bi-pencil-square me-2"></i>Edit Profile</a>
                        <a class="btn btn-primary btn-sm rounded-pill" href="<?php echo $_SESSION['role'] === 'doctor' ? 'doctor_dashboard.php' : 'patient_dashboard.php'; ?>"><i class="bi bi-speedometer2 me-2"></i>My Dashboard</a>
                    </div>
                </div>
                <hr class="opacity-10">
                <div class="row gx-2 mb-3">
                    <div class="col-6"><small class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">Email</small>
                        <p class="mb-0 small fw-medium"><?php echo clean($user['email']); ?></p>
                    </div>
                    <div class="col-6"><small class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">Phone</small>
                        <p class="mb-0 small fw-medium"><?php echo clean($user['phone']); ?></p>
                    </div>
                </div>
                <div class="row gx-2 mb-3">
                    <div class="col-6"><small class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">Role</small>
                        <p class="mb-0 small fw-medium"><?php echo ucfirst(clean($user['role'])); ?></p>
                    </div>
                    <div class="col-6"><small class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">Status</small>
                        <p class="mb-0 small"><span class="badge bg-success-subtle text-success border border-success-subtle"><?php echo ucfirst(clean($user['status'])); ?></span></p>
                    </div>
                </div>
                <div class="row gx-2">
                    <div class="col-6"><small class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">Location</small>
                        <p class="mb-0 small fw-medium"><?php echo clean($user['location'] ?: '-'); ?></p>
                    </div>
                    <div class="col-6"><small class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">Specialty</small>
                        <p class="mb-0 small fw-medium"><?php echo clean($user['specialty'] ?: '-'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="profile-container menu-list">
            <div class="list-group shadow-sm">
                <div class="list-group-item"><a href="change_password.php"><span><i class="bi bi-lock-fill me-2"></i> Change Password</span><i class="bi bi-chevron-right"></i></a></div>
                <div class="list-group-item"><a href="logout.php"><span><i class="bi bi-box-arrow-right text-danger me-2"></i> <strong class="text-danger">Log out</strong></span><i class="bi bi-chevron-right"></i></a></div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-auto">
        <div class="container text-center">
            <h5>Stay Connected</h5>
            <a href="#" class="text-primary me-2"><i class="bi bi-facebook fs-3"></i></a>
            <a href="#" class="text-info me-2"><i class="bi bi-twitter fs-3"></i></a>
            <a href="#" class="text-danger"><i class="bi bi-instagram fs-3"></i></a>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>