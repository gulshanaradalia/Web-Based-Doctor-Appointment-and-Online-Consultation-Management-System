<?php
session_start();
require_once "db.php";
require_once "admin_includes.php";

// Ensure only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch all consultations with details
$consultations = [];
$res = $conn->query("SELECT c.*, p.name as patient_name, d.name as doctor_name, a.appointment_type
                    FROM consultations c
                    JOIN users p ON c.patient_id = p.id
                    JOIN users d ON c.doctor_id = d.id
                    LEFT JOIN appointments a ON c.appointment_id = a.id
                    ORDER BY c.consultation_date DESC LIMIT 100");
while ($row = $res->fetch_assoc()) {
    $consultations[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation Monitor - Admin Panel</title>
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
                        <?php echo get_sidebar('admin_consultations.php'); ?>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <h3 class="fw-bold mb-4"><i class="bi bi-activity"></i> Consultation Monitor</h3>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Patient</th>
                                        <th>Doctor</th>
                                        <th>Session Date</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th class="pe-3 text-end">Record</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($consultations)): ?>
                                        <tr><td colspan="6" class="text-center py-4 text-muted">No consultations found in the system.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($consultations as $c): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold"><?php echo htmlspecialchars($c['patient_name']); ?></td>
                                        <td>Dr. <?php echo htmlspecialchars($c['doctor_name']); ?></td>
                                        <td class="small"><?php echo date('Y-m-d h:i A', strtotime($c['consultation_date'])); ?></td>
                                        <td>
                                            <span class="badge border text-dark">
                                                <i class="bi bi-<?php echo ($c['appointment_type'] ?? 'offline') === 'online' ? 'camera-video' : 'hospital'; ?>"></i> 
                                                <?php echo ucfirst($c['appointment_type'] ?? 'offline'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $c['session_status'] === 'completed' ? 'success' : ($c['session_status'] === 'scheduled' ? 'info' : 'secondary'); ?>">
                                                <?php echo ucfirst($c['session_status']); ?>
                                            </span>
                                        </td>
                                        <td class="pe-3 text-end">
                                            <button class="btn btn-sm btn-outline-dark" onclick='viewRecord(<?php echo json_encode($c); ?>)'>
                                                <i class="bi bi-eye"></i> View Note
                                            </button>
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

    <!-- Record View Modal -->
    <div class="modal fade" id="recordModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0">
                <div class="modal-header bg-dark text-white border-0">
                    <h5 class="modal-title"><i class="bi bi-file-earmark-medical"></i> Consultation Record Log</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="text-muted small text-uppercase">Patient</label>
                            <div class="fw-bold fs-5" id="rec_patient"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small text-uppercase">Doctor</label>
                            <div class="fw-bold fs-5" id="rec_doctor"></div>
                        </div>
                    </div>
                    <hr>
                    <div class="mb-4">
                        <label class="badge bg-info text-dark mb-2">Prescription / Rx</label>
                        <div class="p-3 bg-light border rounded" style="white-space: pre-wrap;" id="rec_rx"></div>
                    </div>
                    <div>
                        <label class="badge bg-secondary mb-2">Session Notes</label>
                        <div class="p-3 bg-light border rounded" style="white-space: pre-wrap;" id="rec_notes"></div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Close Record</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewRecord(c) {
            document.getElementById('rec_patient').innerText = c.patient_name;
            document.getElementById('rec_doctor').innerText = 'Dr. ' + c.doctor_name;
            document.getElementById('rec_rx').innerText = c.prescription || 'No prescription issued.';
            document.getElementById('rec_notes').innerText = c.report_notes || 'No clinical notes recorded.';
            new bootstrap.Modal(document.getElementById('recordModal')).show();
        }
    </script>
</body>
</html>
