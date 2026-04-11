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

    // Global Announcement
    if ($action === 'send_all') {
        $title = trim($_POST['title']);
        $text = trim($_POST['message']);
        
        // Fetch all doctors and patients
        $res = $conn->query("SELECT id FROM users WHERE role != 'admin'");
        $count = 0;
        while ($row = $res->fetch_assoc()) {
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $row['id'], $title, $text);
            $stmt->execute();
            $stmt->close();
            $count++;
        }
        $message = "Announcement sent to $count users!";
        log_activity($conn, $_SESSION['user_id'], "Notification", "Sent global alert: $title");
    }
}

// Fetch Recent Notifications
$notifications = [];
$res = $conn->query("SELECT n.*, u.name as user_name FROM notifications n LEFT JOIN users u ON n.user_id = u.id ORDER BY n.created_at DESC LIMIT 50");
while ($row = $res->fetch_assoc()) {
    $notifications[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Admin Panel</title>
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
                <div class="card shadow-sm border-0"><div class="card-body p-2"><?php echo get_sidebar('admin_notifications.php'); ?></div></div>
            </div>

            <div class="col-md-10">
                <h3 class="fw-bold mb-4"><i class="bi bi-bell-fill text-warning"></i> Notifications & Announcements</h3>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show"><?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-5 mb-4">
                        <div class="card border-0 shadow-sm border-top border-primary border-4">
                            <div class="card-header bg-white py-3"><h5 class="mb-0 fs-6 fw-bold text-uppercase">Broadcast to All Users</h5></div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="send_all">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Subject / Title</label>
                                        <input type="text" name="title" class="form-control" placeholder="e.g., Scheduled Maintenance" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Message Content</label>
                                        <textarea name="message" class="form-control" rows="4" placeholder="Write your announcement here..." required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Broadcast Now</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-7 mb-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-3"><h5 class="mb-0 fs-6 fw-bold text-uppercase">Sent Notification History</h5></div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-3">Date</th>
                                                <th>To</th>
                                                <th>Title</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($notifications as $n): ?>
                                            <tr>
                                                <td class="ps-3 small text-muted"><?php echo date('M d, H:i', strtotime($n['created_at'])); ?></td>
                                                <td class="small fw-bold"><?php echo htmlspecialchars($n['user_name'] ?: 'Public'); ?></td>
                                                <td class="small"><?php echo htmlspecialchars($n['title']); ?></td>
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
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
