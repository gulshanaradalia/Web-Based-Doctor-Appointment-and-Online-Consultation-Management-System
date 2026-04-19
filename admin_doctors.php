<?php
session_start();
require_once "db.php";
require_once "admin_includes.php";

// Ensure only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';
$message_type = 'success';

// Handle Post Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Add Doctor
    if ($action === 'add') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $specialty = trim($_POST['specialty']);
        $location = trim($_POST['location']);
        $fee = floatval($_POST['fee']);

        $stmt = $conn->prepare("INSERT INTO users (name, email, phone, role, specialty, location, consultation_fee, password_hash, status) VALUES (?, ?, ?, 'doctor', ?, ?, ?, ?, 'active')");
        $stmt->bind_param('sssssds', $name, $email, $phone, $specialty, $location, $fee, $password);
        
        if ($stmt->execute()) {
            $message = "Doctor added successfully!";
            log_activity($conn, $_SESSION['user_id'], "Doctor Add", "Created new doctor account: $name ($email)");
        } else {
            $message = "Error adding doctor: " . $conn->error;
            $message_type = 'danger';
        }
        $stmt->close();
    }

    // Edit Doctor
    if ($action === 'edit') {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $specialty = trim($_POST['specialty']);
        $location = trim($_POST['location']);
        $fee = floatval($_POST['fee']);

        $stmt = $conn->prepare("UPDATE users SET name = ?, specialty = ?, location = ?, consultation_fee = ? WHERE id = ? AND role = 'doctor'");
        $stmt->bind_param('sssdi', $name, $specialty, $location, $fee, $id);
        
        if ($stmt->execute()) {
            $message = "Doctor information updated!";
            log_activity($conn, $_SESSION['user_id'], "Doctor Edit", "Updated info for doctor ID: $id");
        } else {
            $message = "Error updating doctor: " . $conn->error;
            $message_type = 'danger';
        }
        $stmt->close();
    }

    // Toggle Status
    if ($action === 'toggle_status') {
        $id = intval($_POST['id']);
        $status = $_POST['status'] === 'active' ? 'blocked' : 'active';
        
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'doctor'");
        $stmt->bind_param('si', $status, $id);
        $stmt->execute();
        $stmt->close();
        $message = "Doctor status updated!";
        log_activity($conn, $_SESSION['user_id'], "Doctor Status", "Changed status for doctor ID: $id to $status");
    }

    // Delete Doctor
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'doctor'");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $message = "Doctor deleted permanently.";
            log_activity($conn, $_SESSION['user_id'], "Doctor Delete", "Deleted doctor account ID: $id");
        } else {
            $message = "Error deleting doctor.";
            $message_type = 'danger';
        }
        $stmt->close();
    }

    // Wipe Schedule
    if ($action === 'wipe_schedule') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM schedule_slots WHERE doctor_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        $message = "Doctor's schedule has been cleared.";
    }
}

// Fetch Doctors
$doctors = [];
$res = $conn->query("SELECT * FROM users WHERE role = 'doctor' ORDER BY created_at DESC");
while ($row = $res->fetch_assoc()) {
    $doctors[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Management - Admin Panel</title>
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
            <div class="collapse navbar-collapse" id="navbarResponsive">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link active" href="admin_dashboard.php">Dashboard</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-5 pt-5">
        <div class="row">
            <div class="col-md-2 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-2">
                        <?php echo get_sidebar('admin_doctors.php'); ?>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold"><i class="bi bi-people-fill"></i> Doctor Management</h3>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addDoctorModal">
                        <i class="bi bi-plus-circle"></i> Add New Doctor
                    </button>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Doctor Details</th>
                                        <th>Specialty</th>
                                        <th>Fee (BDT)</th>
                                        <th>Chamber</th>
                                        <th>Status</th>
                                        <th class="pe-3 text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($doctors as $d): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold"><?php echo htmlspecialchars($d['name']); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($d['email']); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($d['phone']); ?></div>
                                        </td>
                                        <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($d['specialty'] ?: 'N/A'); ?></span></td>
                                        <td><?php echo number_format($d['consultation_fee'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($d['location'] ?: 'N/A'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $d['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($d['status']); ?>
                                            </span>
                                        </td>
                                        <td class="pe-3 text-end">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    Manage
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                                    <li><a class="dropdown-item" href="#" onclick='openEditModal(<?php echo json_encode($d); ?>)'><i class="bi bi-pencil"></i> Edit Info</a></li>
                                                    <li>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                                                            <input type="hidden" name="status" value="<?php echo $d['status']; ?>">
                                                            <input type="hidden" name="action" value="toggle_status">
                                                            <button type="submit" class="dropdown-item">
                                                                <i class="bi bi-<?php echo $d['status'] === 'active' ? 'slash-circle' : 'check-circle'; ?>"></i> 
                                                                <?php echo $d['status'] === 'active' ? 'Mark Unavailable' : 'Make Available'; ?>
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Clear all slots for this doctor?')">
                                                            <input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                                                            <input type="hidden" name="action" value="wipe_schedule">
                                                            <button type="submit" class="dropdown-item text-warning"><i class="bi bi-calendar-x"></i> Wipe Schedule</button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this doctor permanently?')">
                                                            <input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                                                            <input type="hidden" name="action" value="delete">
                                                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash"></i> Delete Account</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
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

    <!-- Add Doctor Modal -->
    <div class="modal fade" id="addDoctorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header border-0 bg-success text-white">
                    <h5 class="modal-title">Add New Doctor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Specialty</label>
                            <input type="text" name="specialty" class="form-control" placeholder="e.g. Cardiologist">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fee (BDT)</label>
                            <input type="number" name="fee" class="form-control" value="500">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Chamber / Hospital</label>
                        <input type="text" name="location" class="form-control">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4">Create Account</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Doctor Modal -->
    <div class="modal fade" id="editDoctorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header border-0 bg-primary text-white">
                    <h5 class="modal-title">Edit Doctor Info</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Specialty</label>
                            <input type="text" name="specialty" id="edit_specialty" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fee (BDT)</label>
                            <input type="number" name="fee" id="edit_fee" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Chamber / Hospital</label>
                        <input type="text" name="location" id="edit_location" class="form-control">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Update Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openEditModal(doctor) {
            document.getElementById('edit_id').value = doctor.id;
            document.getElementById('edit_name').value = doctor.name;
            document.getElementById('edit_specialty').value = doctor.specialty || '';
            document.getElementById('edit_location').value = doctor.location || '';
            document.getElementById('edit_fee').value = doctor.consultation_fee;
            
            new bootstrap.Modal(document.getElementById('editDoctorModal')).show();
        }
    </script>
</body>
</html>
