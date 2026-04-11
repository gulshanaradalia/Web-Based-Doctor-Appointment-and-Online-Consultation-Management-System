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
    foreach ($_POST as $key => $value) {
        if ($key !== 'submit') {
            $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("sss", $key, $value, $value);
            $stmt->execute();
            $stmt->close();
        }
    }
    $message = "System settings updated successfully!";
    log_activity($conn, $_SESSION['user_id'], "Settings Update", "Admin updated site configurations.");
}

// Default Values
$site_name = get_setting($conn, 'site_name', 'HealthTech');
$contact_email = get_setting($conn, 'contact_email', 'support@healthtech.com');
$contact_phone = get_setting($conn, 'contact_phone', '01700000000');
$default_fee = get_setting($conn, 'default_fee', '500');
$slot_duration = get_setting($conn, 'slot_duration', '30');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Admin Panel</title>
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
                <div class="card shadow-sm border-0"><div class="card-body p-2"><?php echo get_sidebar('admin_settings.php'); ?></div></div>
            </div>

            <div class="col-md-10">
                <h3 class="fw-bold mb-4"><i class="bi bi-gear-fill text-secondary"></i> System Configuration</h3>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show"><?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3"><h5 class="mb-0 fs-6 fw-bold text-uppercase">General Settings</h5></div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small fw-bold">Website Name</label>
                                    <input type="text" name="site_name" class="form-control" value="<?php echo htmlspecialchars($site_name); ?>">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label small fw-bold">Default Consult Fee (৳)</label>
                                    <input type="number" name="default_fee" class="form-control" value="<?php echo htmlspecialchars($default_fee); ?>">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label small fw-bold">Slot Duration (Min)</label>
                                    <input type="number" name="slot_duration" class="form-control" value="<?php echo htmlspecialchars($slot_duration); ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small fw-bold">Support Email</label>
                                    <input type="email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars($contact_email); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small fw-bold">Contact Phone</label>
                                    <input type="text" name="contact_phone" class="form-control" value="<?php echo htmlspecialchars($contact_phone); ?>">
                                </div>
                            </div>
                            <hr>
                            <button type="submit" name="submit" class="btn btn-primary px-5">Save Configuration</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
