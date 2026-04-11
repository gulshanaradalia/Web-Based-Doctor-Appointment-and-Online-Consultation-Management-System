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

    // Edit Patient Info
    if ($action === 'edit') {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);

        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ? AND role = 'patient'");
        $stmt->bind_param('sssi', $name, $email, $phone, $id);
        
        if ($stmt->execute()) {
            $message = "Patient information updated!";
            log_activity($conn, $_SESSION['user_id'], "Patient Edit", "Updated info for patient ID: $id");
        } else {
            $message = "Error updating patient information.";
            $message_type = 'danger';
        }
        $stmt->close();
    }

    // Toggle Status (Block/Unblock)
    if ($action === 'toggle_status') {
        $id = intval($_POST['id']);
        $status = $_POST['status'] === 'active' ? 'blocked' : 'active';
        
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'patient'");
        $stmt->bind_param('si', $status, $id);
        $stmt->execute();
        $stmt->close();
        $message = "Patient account status changed.";
        log_activity($conn, $_SESSION['user_id'], "Patient Status", "Changed status for patient ID: $id to $status");
    }

    // Delete Patient Account
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'patient'");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $message = "Patient account deleted permanently.";
            log_activity($conn, $_SESSION['user_id'], "Patient Delete", "Deleted patient account ID: $id");
        } else {
            $message = "Error deleting patient account.";
            $message_type = 'danger';
        }
        $stmt->close();
    }
}

// Fetch Patients with Appointment Counts
$patients = [];
$res = $conn->query("SELECT p.*, (SELECT COUNT(*) FROM appointments a WHERE a.patient_id = p.id) as appt_count 
                    FROM users p 
                    WHERE p.role = 'patient' 
                    ORDER BY p.created_at DESC");
while ($row = $res->fetch_assoc()) {
    $patients[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Management - Admin Panel</title>
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
                        <?php echo get_sidebar('admin_patients.php'); ?>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <h3 class="fw-bold mb-4"><i class="bi bi-people-fill"></i> Patient Management</h3>

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
                                        <th class="ps-3">Patient Details</th>
                                        <th>Contact</th>
                                        <th>Appts</th>
                                        <th>Status</th>
                                        <th>Member Since</th>
                                        <th class="pe-3 text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($patients as $p): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold text-primary"><?php echo htmlspecialchars($p['name']); ?></div>
                                            <div class="small">ID: #<?php echo $p['id']; ?></div>
                                        </td>
                                        <td>
                                            <div class="small fw-medium"><?php echo htmlspecialchars($p['email']); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($p['phone']); ?></div>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?php echo $p['appt_count']; ?></span></td>
                                        <td>
                                            <span class="badge bg-<?php echo $p['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($p['status']); ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted"><?php echo date('M d, Y', strtotime($p['created_at'])); ?></td>
                                        <td class="pe-3 text-end">
                                            <button class="btn btn-sm btn-outline-secondary me-1" onclick='openEditModal(<?php echo json_encode($p); ?>)' title="Edit Info">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                                <input type="hidden" name="status" value="<?php echo $p['status']; ?>">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <button type="submit" class="btn btn-sm btn-outline-<?php echo $p['status'] === 'active' ? 'warning' : 'success'; ?> me-1" title="<?php echo $p['status'] === 'active' ? 'Block Patient' : 'Unblock Patient'; ?>">
                                                    <i class="bi bi-<?php echo $p['status'] === 'active' ? 'slash-circle' : 'check-circle'; ?>"></i>
                                                </button>
                                            </form>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Permanently delete this patient? All their history will be lost.')">
                                                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Account">
                                                    <i class="bi bi-trash"></i>
                                                </button>
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

    <!-- Edit Patient Modal -->
    <div class="modal fade" id="editPatientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header border-0 bg-primary text-white">
                    <h5 class="modal-title">Edit Patient Info</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Patient Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control" required>
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
        function openEditModal(patient) {
            document.getElementById('edit_id').value = patient.id;
            document.getElementById('edit_name').value = patient.name;
            document.getElementById('edit_email').value = patient.email;
            document.getElementById('edit_phone').value = patient.phone;
            
            new bootstrap.Modal(document.getElementById('editPatientModal')).show();
        }
    </script>
</body>
</html>
