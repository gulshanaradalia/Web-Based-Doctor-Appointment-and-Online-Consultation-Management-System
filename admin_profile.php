<?php
session_start();
require_once "db.php";
require_once "admin_includes.php";

// Ensure only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$admin_id = $_SESSION['user_id'];
$message = '';
$message_type = 'success';

// Fetch Current Admin Data
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle Post Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Update Profile
    if ($action === 'update_profile') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $email, $admin_id);
        if ($stmt->execute()) {
            $_SESSION['name'] = $name;
            $admin['name'] = $name;
            $admin['email'] = $email;
            $message = "Profile updated successfully!";
            log_activity($conn, $admin_id, "Profile Update", "Admin updated their name/email.");
        }
        $stmt->close();
    }

    // Change Password
    if ($action === 'change_password') {
        $old = $_POST['old_pass'];
        $new = $_POST['new_pass'];
        $confirm = $_POST['confirm_pass'];

        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!password_verify($old, $user['password_hash'])) {
            $message = "Incorrect current password.";
            $message_type = "danger";
        } elseif ($new !== $confirm) {
            $message = "New passwords do not match.";
            $message_type = "danger";
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed, $admin_id);
            $stmt->execute();
            $stmt->close();
            $message = "Password changed successfully!";
            log_activity($conn, $admin_id, "Security", "Admin changed their account password.");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php"><i class="bi bi-heart-pulse-fill me-2"></i><span>HealthTech</span></a>
            <div class="collapse navbar-collapse" id="navbarResponsive">
                <ul class="navbar-nav ms-auto"><li class="nav-item"><a class="nav-link active" href="admin_dashboard.php">Dashboard</a></li></ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-5 pt-5">
        <div class="row">
            <div class="col-md-2 mb-4">
                <div class="card shadow-sm border-0"><div class="card-body p-2"><?php echo get_sidebar('admin_profile.php'); ?></div></div>
            </div>

            <div class="col-md-10">
                <h3 class="fw-bold mb-4"><i class="bi bi-person-circle"></i> Profile & Password Management</h3>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show"><?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-3"><h5 class="mb-0 fs-6 fw-bold text-uppercase">Update Personal Info</h5></div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_profile">
                                    <div class="mb-3"><label class="form-label small fw-bold">Full Name</label><input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($admin['name']); ?>" required></div>
                                    <div class="mb-3"><label class="form-label small fw-bold">Email Address</label><input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin['email']); ?>" required></div>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-3 text-danger"><h5 class="mb-0 fs-6 fw-bold text-uppercase">Change Password</h5></div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="change_password">
                                    <div class="mb-3"><label class="form-label small fw-bold">Current Password</label><input type="password" name="old_pass" class="form-control" required></div>
                                    <div class="mb-3"><label class="form-label small fw-bold">New Password</label><input type="password" name="new_pass" class="form-control" required></div>
                                    <div class="mb-3"><label class="form-label small fw-bold">Confirm New Password</label><input type="password" name="confirm_pass" class="form-control" required></div>
                                    <button type="submit" class="btn btn-danger">Update Password</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
