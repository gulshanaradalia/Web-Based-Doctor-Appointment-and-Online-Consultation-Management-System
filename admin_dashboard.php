<?php
session_start();
require_once "db.php";
require_once "admin_includes.php";

// Ensure only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle User Actions (Block/Unblock)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'])) {
    $target_user_id = intval($_POST['user_id']);
    $action = $_POST['action'];
    
    if ($action === 'block' || $action === 'unblock') {
        $new_status = ($action === 'block') ? 'blocked' : 'active';
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ? AND role != 'admin'");
        $stmt->bind_param('si', $new_status, $target_user_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch Stats
$total_doctors = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'doctor'")->fetch_assoc()['c'];
$total_patients = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'patient'")->fetch_assoc()['c'];
$total_appointments = $conn->query("SELECT COUNT(*) as c FROM appointments")->fetch_assoc()['c'];

$pending_appt = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE status = 'pending'")->fetch_assoc()['c'];
$completed_appt = $conn->query("SELECT COUNT(*) as c FROM consultations WHERE session_status = 'completed'")->fetch_assoc()['c'];

// Fetch Users for Management
$users = [];
$res = $conn->query("SELECT id, name, email, phone, role, status, created_at FROM users WHERE role != 'admin' ORDER BY role, created_at DESC LIMIT 50");
while($row = $res->fetch_assoc()) {
    $users[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HealthTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
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
                    <li class="nav-item"><a class="nav-link" href="doctor_search.php">Find Doctors</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link btn btn-light btn-sm text-primary px-3" href="register.php">Online Appointment</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-5 pt-5">
        <div class="row">
            <div class="col-md-2 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-2">
                        <?php echo get_sidebar('admin_dashboard.php'); ?>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                
                <!-- Stats Row -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm bg-primary text-white h-100">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1">Total Doctors</h6>
                                    <h2 class="mb-0 fw-bold"><?php echo $total_doctors; ?></h2>
                                </div>
                                <i class="bi bi-heart-pulse fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm bg-success text-white h-100">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1">Total Patients</h6>
                                    <h2 class="mb-0 fw-bold"><?php echo $total_patients; ?></h2>
                                </div>
                                <i class="bi bi-people fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm bg-warning text-dark h-100">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1">Appointments</h6>
                                    <h2 class="mb-0 fw-bold"><?php echo $total_appointments; ?></h2>
                                    <small class="d-block mt-1">Pending: <?php echo $pending_appt; ?> | Completed: <?php echo $completed_appt; ?></small>
                                </div>
                                <i class="bi bi-calendar-check fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Management Table -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-people-fill"></i> User Management (Doctors & Patients)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Name</th>
                                        <th>Role</th>
                                        <th>Contact</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                        <th class="pe-3 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td class="ps-3 fw-medium"><?php echo htmlspecialchars($u['name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $u['role'] === 'doctor' ? 'info text-dark' : 'secondary'; ?>">
                                                <?php echo ucfirst($u['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="small"><?php echo htmlspecialchars($u['email']); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($u['phone']); ?></div>
                                        </td>
                                        <td>
                                            <?php if ($u['status'] === 'active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Blocked</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                        <td class="pe-3 text-end">
                                            <form method="POST" class="m-0 d-inline-block">
                                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                <?php if ($u['status'] === 'active'): ?>
                                                    <input type="hidden" name="action" value="block">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Block this user?');">Block</button>
                                                <?php else: ?>
                                                    <input type="hidden" name="action" value="unblock">
                                                    <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Unblock this user?');">Unblock</button>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5 w-100">
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