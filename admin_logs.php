<?php
session_start();
require_once "db.php";
require_once "admin_includes.php";

// Ensure only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch Logs with User Names
$logs = [];
$res = $conn->query("SELECT l.*, u.name as user_name 
                    FROM activity_logs l 
                    LEFT JOIN users u ON l.user_id = u.id 
                    ORDER BY l.created_at DESC LIMIT 200");
while ($row = $res->fetch_assoc()) {
    $logs[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Admin Panel</title>
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
                <div class="card shadow-sm border-0"><div class="card-body p-2"><?php echo get_sidebar('admin_logs.php'); ?></div></div>
            </div>

            <div class="col-md-10">
                <h3 class="fw-bold mb-4"><i class="bi bi-journal-text"></i> Activity & Audit Logs</h3>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fs-6 fw-bold text-uppercase">System Activity Tracker</h5>
                        <span class="badge bg-light text-dark border">Last 200 Actions</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3" style="width: 180px;">Timestamp</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($logs)): ?>
                                        <tr><td colspan="4" class="text-center py-4 text-muted">No activity logs recorded yet.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($logs as $l): ?>
                                    <tr>
                                        <td class="ps-3 small text-muted"><?php echo date('M d, Y | h:i A', strtotime($l['created_at'])); ?></td>
                                        <td class="small fw-bold text-primary"><?php echo htmlspecialchars($l['user_name'] ?: 'System'); ?></td>
                                        <td><span class="badge bg-info text-dark" style="font-size: 0.75rem;"><?php echo htmlspecialchars($l['action']); ?></span></td>
                                        <td class="small"><?php echo htmlspecialchars($l['description']); ?></td>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
