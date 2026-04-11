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

// Handle Actions (Update Status)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_id'], $_POST['status'])) {
    $pid = intval($_POST['payment_id']);
    $new_status = $_POST['status'];
    
    if (in_array($new_status, ['pending', 'completed', 'failed'])) {
        $stmt = $conn->prepare("UPDATE payments SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $new_status, $pid);
        if ($stmt->execute()) {
            $message = "Payment status updated successfully!";
            log_activity($conn, $_SESSION['user_id'], "Payment Verify", "Updated payment ID: $pid to $new_status");
        } else {
            $message = "Error updating payment.";
            $message_type = 'danger';
        }
        $stmt->close();
    }
}

// Stats
$total_revenue = $conn->query("SELECT SUM(amount) as s FROM payments WHERE status = 'completed'")->fetch_assoc()['s'] ?? 0;
$pending_payments = $conn->query("SELECT COUNT(*) as c FROM payments WHERE status = 'pending'")->fetch_assoc()['c'] ?? 0;

// Fetch Transactions
$payments = [];
$res = $conn->query("SELECT pay.*, p.name as patient_name, d.name as doctor_name 
                    FROM payments pay
                    JOIN users p ON pay.patient_id = p.id
                    JOIN users d ON pay.doctor_id = d.id
                    ORDER BY pay.payment_date DESC LIMIT 100");
while ($row = $res->fetch_assoc()) {
    $payments[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - Admin Panel</title>
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
                        <?php echo get_sidebar('admin_payments.php'); ?>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                
                <!-- Revenue Header -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h3 class="fw-bold"><i class="bi bi-currency-exchange"></i> Payment Management</h3>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-dark text-white shadow-sm border-0">
                            <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                                <span class="small fw-bold text-uppercase opacity-75">Pending Appts</span>
                                <span class="fs-4 fw-bold"><?php echo $pending_payments; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white shadow-sm border-0">
                            <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                                <span class="small fw-bold text-uppercase opacity-75">Total Revenue</span>
                                <span class="fs-4 fw-bold">৳<?php echo number_format($total_revenue, 0); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">TXN #</th>
                                        <th>Patient</th>
                                        <th>Doctor</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th class="pe-3 text-end">Verify</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($payments)): ?>
                                        <tr><td colspan="8" class="text-center py-4 text-muted">No payments recorded yet.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($payments as $pay): ?>
                                    <tr>
                                        <td class="ps-3 small fw-bold text-primary">
                                            <?php echo htmlspecialchars($pay['transaction_id'] ?: 'N/A'); ?>
                                            <div class="text-muted small fw-normal">INV: <?php echo htmlspecialchars($pay['invoice_number'] ?: '#'.$pay['id']); ?></div>
                                        </td>
                                        <td class="small fw-medium"><?php echo htmlspecialchars($pay['patient_name']); ?></td>
                                        <td class="small">Dr. <?php echo htmlspecialchars($pay['doctor_name']); ?></td>
                                        <td class="fw-bold">৳<?php echo number_format($pay['amount'], 2); ?></td>
                                        <td><span class="badge bg-light text-dark border"><?php echo strtoupper($pay['method']); ?></span></td>
                                        <td class="small"><?php echo date('M d, Y', strtotime($pay['payment_date'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $pay['status'] === 'completed' ? 'success' : ($pay['status'] === 'pending' ? 'warning text-dark' : 'danger'); ?>">
                                                <?php echo ucfirst($pay['status']); ?>
                                            </span>
                                        </td>
                                        <td class="pe-3 text-end">
                                            <div class="btn-group btn-group-sm shadow-sm">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="payment_id" value="<?php echo $pay['id']; ?>">
                                                    <input type="hidden" name="status" value="completed">
                                                    <button type="submit" class="btn btn-outline-success" title="Mark as Paid" <?php echo $pay['status'] === 'completed' ? 'disabled' : ''; ?>><i class="bi bi-check-lg"></i></button>
                                                </form>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="payment_id" value="<?php echo $pay['id']; ?>">
                                                    <input type="hidden" name="status" value="failed">
                                                    <button type="submit" class="btn btn-outline-danger" title="Mark as Failed" <?php echo $pay['status'] === 'failed' ? 'disabled' : ''; ?>><i class="bi bi-x"></i></button>
                                                </form>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
