<?php
session_start();

/* User Authentication Check */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

/* Role check (only doctor allowed) */
if ($_SESSION["role"] != "doctor") {
    header("Location: dashboard.php");
    exit;
}

require_once 'db.php';
$doctor_id = $_SESSION['user_id'];

$message = '';
$message_type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $action = $_POST['action'];
    $appointment_id = intval($_POST['id']);

    if (in_array($action, ['approve', 'reject'], true) && $appointment_id > 0) {
        $new_status = ($action === 'approve') ? 'approved' : 'rejected';

        // Check if appointment exists and belongs to the doctor
        $checkStmt = $conn->prepare("SELECT id, patient_id, status 
                                     FROM appointments 
                                     WHERE id = ? AND doctor_id = ? 
                                     LIMIT 1");
        $checkStmt->bind_param('ii', $appointment_id, $doctor_id);
        $checkStmt->execute();
        $appt = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if (!$appt) {
            $message = "Appointment not found.";
            $message_type = 'danger';
        } elseif ($appt['status'] === $new_status) {
            $message = "Appointment is already " . ucfirst($new_status) . ".";
            $message_type = 'warning';
        } else {
            // Update appointment status to approved or rejected
            $updateStmt = $conn->prepare("UPDATE appointments 
                                          SET status = ? 
                                          WHERE id = ? AND doctor_id = ?");
            $updateStmt->bind_param('sii', $new_status, $appointment_id, $doctor_id);

            if ($updateStmt->execute()) {
                if ($new_status === 'approved') {
                    $checkConfirm = $conn->prepare("SELECT id 
                                                    FROM appointment_confirmations 
                                                    WHERE appointment_id = ? 
                                                    LIMIT 1");
                    $checkConfirm->bind_param('i', $appointment_id);
                    $checkConfirm->execute();
                    $existingConfirm = $checkConfirm->get_result()->fetch_assoc();
                    $checkConfirm->close();

                    if ($existingConfirm) {
                        $confirmation_status = 'notification_sent';
                        $confirmed_at = null;

                        // Reset confirmation if appointment is approved again
                        $resetConfirm = $conn->prepare("UPDATE appointment_confirmations
                                                        SET confirmation_status = ?, confirmed_at = ?
                                                        WHERE appointment_id = ?");
                        $resetConfirm->bind_param('ssi', $confirmation_status, $confirmed_at, $appointment_id);
                        $resetConfirm->execute();
                        $resetConfirm->close();
                    } else {
                        // Insert new confirmation
                        $confirmation_status = 'notification_sent';
                        $insertConfirm = $conn->prepare("INSERT INTO appointment_confirmations (appointment_id, patient_id, confirmation_status)
                                                         VALUES (?, ?, ?)");
                        $insertConfirm->bind_param('iis', $appointment_id, $appt['patient_id'], $confirmation_status);
                        $insertConfirm->execute();
                        $insertConfirm->close();
                    }
                } else {
                    // Delete confirmation if appointment is rejected
                    $deleteConfirm = $conn->prepare("DELETE FROM appointment_confirmations WHERE appointment_id = ?");
                    $deleteConfirm->bind_param('i', $appointment_id);
                    $deleteConfirm->execute();
                    $deleteConfirm->close();
                }

                $message = "Appointment " . ucfirst($new_status) . " successfully.";
                $message_type = 'success';
            } else {
                $message = "Unable to update appointment status.";
                $message_type = 'danger';
            }

            $updateStmt->close();
        }
    }
}

$appointments = [];
$stmt = $conn->prepare("SELECT 
                            a.id,
                            a.patient_id,
                            a.slot_time,
                            a.status,
                            a.appointment_type,
                            u.name AS patient_name,
                            u.email AS patient_email,
                            u.phone AS patient_phone
                        FROM appointments a
                        JOIN users u ON a.patient_id = u.id
                        WHERE a.doctor_id = ?
                        ORDER BY a.id DESC");
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$appointments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($appointments as $idx => $apt) {
    $queueStmt = $conn->prepare("SELECT COUNT(*) AS queue_position
                                 FROM appointments
                                 WHERE doctor_id = ?
                                   AND slot_time <= ?
                                   AND status IN ('pending','approved')");
    $queueStmt->bind_param('is', $doctor_id, $apt['slot_time']);
    $queueStmt->execute();
    $queueRes = $queueStmt->get_result();
    $queueData = $queueRes->fetch_assoc();
    $queueStmt->close();

    $appointments[$idx]['queue_position'] = isset($queueData['queue_position']) ? (int)$queueData['queue_position'] : 1;
}

/* Fetch Admin Notifications */
$admin_notifications = [];
$notif_stmt = $conn->prepare("SELECT title, message, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$notif_stmt->bind_param('i', $doctor_id);
$notif_stmt->execute();
$notif_res = $notif_stmt->get_result();
while ($row = $notif_res->fetch_assoc()) {
    $admin_notifications[] = $row;
}
$notif_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <!-- Custom stylesheet -->
    <link href="style.css" rel="stylesheet">
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

    <main class="container mt-5 pt-5">
        <div class="row gy-4">
            <aside class="col-lg-3">
                <div class="card shadow-sm sticky-top" style="top:100px;">
                    <div class="card-body">
                        <h5 class="card-title">Quick Links</h5>
                        <p class="text-muted mb-3">Your dashboard controls.</p>
                        <a href="doctor_dashboard.php" class="btn btn-primary w-100 mb-2"><i class="bi bi-speedometer2"></i> My Dashboard</a>
                        <a href="profile.php" class="btn btn-outline-secondary w-100 mb-2"><i class="bi bi-person-circle"></i> Profile</a>
                        <a href="logout.php" class="btn btn-outline-danger w-100"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </div>
                </div>
            </aside>
            <section class="col-lg-9">
                <?php if (!empty($admin_notifications)): ?>
                <!-- Admin Notification / Message Trigger -->
                <div class="alert alert-info border-0 shadow-sm mb-4 d-flex justify-content-between align-items-center bg-white border-start border-4 border-info">
                    <div class="d-flex align-items-center">
                        <div class="bg-info bg-opacity-10 p-2 rounded-circle me-3">
                            <i class="bi bi-bell-fill text-info fs-5"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold">Official Announcements</h6>
                            <small class="text-muted">You have <?php echo count($admin_notifications); ?> important updates from Admin.</small>
                        </div>
                    </div>
                    <button type="button" class="btn btn-info btn-sm text-white px-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#adminNotifModal">
                        <i class="bi bi-envelope-open-fill me-1"></i> Check Notification
                    </button>
                </div>
                <?php endif; ?>

                <div class="bg-white rounded-4 shadow-sm p-4 mb-4">
                    <h2>Doctor Dashboard</h2>
                    <p>Welcome Dr. <?php echo htmlspecialchars($_SESSION['name']); ?></p>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <h3 class="mt-4">Schedule & Availability</h3>
                    <p>Manage your available doctor time slots via a dedicated page.</p>
                    <div class="d-flex gap-2 mb-4">
                        <a href="schedule_slot.php" class="btn btn-accent">Go to Schedule Slot Management</a>
                        <a href="generate_auto_slots.php" class="btn btn-success" onclick="return confirm('Do you want to automatically generate slots for the next 30 days?\n(Mon, Wed, Fri from 3:00 PM to 6:00 PM)');">
                            <i class="bi bi-magic"></i> Auto-Generate 30 Days Slots (Mon,Wed,Fri 3PM-6PM)
                        </a>
                    </div>

                    <h3 class="mt-4">All Appointment Requests</h3>
                </div>

                <?php if (count($appointments) === 0): ?>
                    <div class="alert alert-info">No appointment requests found.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Patient</th>
                                    <th>Contact</th>
                                    <th>Slot</th>
                                    <th>Queue #</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $idx => $apt): ?>
                                    <tr>
                                        <td><?php echo $idx + 1; ?></td>
                                        <td><?php echo htmlspecialchars($apt['patient_name']); ?></td>
                                        <td><?php echo htmlspecialchars($apt['patient_email'] . ' / ' . $apt['patient_phone']); ?></td>
                                        <td><?php echo htmlspecialchars((new DateTime($apt['slot_time']))->format('Y-m-d H:i')); ?></td>
                                        <td><?php echo htmlspecialchars($apt['queue_position'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $apt['status'] === 'approved' ? 'success' : ($apt['status'] === 'pending' ? 'warning text-dark' : 'danger'); ?>">
                                                <?php echo ucfirst($apt['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-2 w-100">
                                                <!-- Consultation Type Label (Always Visible) -->
                                                <div class="consultation-type-badge mb-1">
                                                    <?php if (isset($apt['appointment_type']) && trim(strtolower($apt['appointment_type'])) === 'online'): ?>
                                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle w-100 py-2">
                                                            <i class="bi bi-camera-video-fill me-1"></i> ONLINE CALL
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-info-subtle text-info border border-info-subtle w-100 py-2">
                                                            <i class="bi bi-hospital-fill me-1"></i> OFFLINE VISIT
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="d-flex gap-2 flex-wrap">
                                                    <!-- Approve Button -->
                                                    <?php if ($apt['status'] !== 'approved'): ?>
                                                        <form method="POST" class="m-0" onsubmit="return confirm('Approve this appointment?');">
                                                            <input type="hidden" name="id" value="<?php echo $apt['id']; ?>">
                                                            <input type="hidden" name="action" value="approve">
                                                            <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                                        </form>
                                                    <?php endif; ?>

                                                    <!-- Reject Button -->
                                                    <?php if ($apt['status'] !== 'rejected'): ?>
                                                        <form method="POST" class="m-0" onsubmit="return confirm('Reject this appointment?');">
                                                            <input type="hidden" name="id" value="<?php echo $apt['id']; ?>">
                                                            <input type="hidden" name="action" value="reject">
                                                            <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                                        </form>
                                                    <?php endif; ?>

                                                    <!-- Status Message for Approved/Rejected -->
                                                    <?php if ($apt['status'] === 'approved'): ?>
                                                        <small class="text-muted align-self-center">Approved request can still be rejected</small>
                                                    <?php elseif ($apt['status'] === 'rejected'): ?>
                                                        <small class="text-muted align-self-center">Rejected request can still be approved</small>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Online Consultation Actions (Only if Approved) -->
                                                <?php if ($apt['status'] === 'approved' && isset($apt['appointment_type']) && trim(strtolower($apt['appointment_type'])) === 'online'): ?>
                                                    <?php 
                                                        $slotTime = strtotime($apt['slot_time']);
                                                        $now = time();
                                                        $diff = ($slotTime - $now) / 60;
                                                    ?>
                                                    <div class="p-2 border rounded-3 bg-light mt-2">
                                                        <div class="text-center fw-bold text-primary mb-2" style="font-size:0.75rem;">LIVE CONSULTATION</div>
                                                        
                                                        <?php if ($diff <= 15 && $diff >= -60): ?>
                                                            <a href="consultation.php?doctor_id=<?php echo $doctor_id; ?>&patient_id=<?php echo $apt['patient_id']; ?>" class="btn btn-sm btn-success w-100 mb-2">
                                                                <i class="bi bi-camera-video"></i> Join Live Call
                                                            </a>
                                                        <?php elseif ($diff > 15): ?>
                                                            <div class="mb-2 text-center small text-muted border rounded p-1 w-100 bg-white">
                                                                Call starts <?php echo date('h:i A', $slotTime); ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="mb-2 text-center small text-danger border rounded p-1 w-100 bg-white fw-bold">
                                                                Call Slot Expired
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <div class="d-flex gap-2">
                                                            <button type="button" class="btn btn-xs btn-outline-info flex-grow-1 py-1" onclick="viewPatientReport(<?php echo $doctor_id; ?>, <?php echo $apt['patient_id']; ?>)" style="font-size: 0.75rem;">
                                                                <i class="bi bi-file-medical"></i> Report
                                                            </button>
                                                            <button type="button" class="btn btn-xs btn-outline-primary flex-grow-1 py-1" onclick="openRxModal(<?php echo $doctor_id; ?>, <?php echo $apt['patient_id']; ?>, '<?php echo htmlspecialchars(addslashes($apt['patient_name'])); ?>')" style="font-size: 0.75rem;">
                                                                <i class="bi bi-pencil-square"></i> Rx
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
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

    <!-- Dash Prescription Modal -->
    <div class="modal fade" id="dashPrescriptionModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title"><i class="bi bi-file-medical"></i> Write Prescription</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="dashPrintPrescriptionArea">
            <div class="text-center mb-4 border-bottom pb-3">
                <h3 class="text-primary fw-bold">HealthTech E-Prescription</h3>
                <p class="mb-0"><strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($_SESSION['name']); ?></p>
                <p class="mb-0 text-muted">Patient: <strong id="rxPatientNameDisplay"></strong></p>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold text-success"><i class="bi bi-capsule"></i> Rx (Medicines & Adjustments):</label>
                <textarea class="form-control d-print-none border-success" id="dashRxInput" rows="6" placeholder="Example: Paracetamol 500mg 1+1+1 for 7 days..."></textarea>
                <div class="d-none d-print-block mt-3" style="white-space: pre-wrap; font-size:1.15rem;" id="dashRxPrintView"></div>
            </div>
          </div>
          <div class="modal-footer d-print-none bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-success" id="dashGenerateRxBtn" onclick="submitDashPrescription()"><i class="bi bi-cloud-upload"></i> Save & Send to Patient</button>
            <button type="button" class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Print locally</button>
          </div>
        </div>
      </div>
    </div>

    <script>
        let currentRxDocId = 0;
        let currentRxPatId = 0;
        const rxModal = new bootstrap.Modal(document.getElementById('dashPrescriptionModal'));

        function viewPatientReport(docId, patId) {
            fetch('uploads/report_link_' + docId + '_' + patId + '.txt?time=' + new Date().getTime())
            .then(res => {
                if(!res.ok) throw new Error('Not found');
                return res.text();
            })
            .then(url => {
                if(url.trim() !== '') {
                    window.open(url.trim(), '_blank');
                } else {
                    alert('Patient has not uploaded any report for this session yet.');
                }
            })
            .catch(e => alert('Patient has not uploaded any report yet. Ask them to click "Show Report" in their portal.'));
        }

        function openRxModal(docId, patId, patName) {
            currentRxDocId = docId;
            currentRxPatId = patId;
            document.getElementById('rxPatientNameDisplay').innerText = patName;
            document.getElementById('dashRxInput').value = '';
            rxModal.show();
        }

        function submitDashPrescription() {
            const rxValue = document.getElementById('dashRxInput').value;
            if(!rxValue.trim()) {
                alert('Please write medicine before saving!');
                return;
            }
            
            const btn = document.getElementById('dashGenerateRxBtn');
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';
            btn.disabled = true;

            const fd = new FormData();
            fd.append('doctor_id', currentRxDocId);
            fd.append('patient_id', currentRxPatId);
            fd.append('rx', rxValue);
            
            fetch('save_rx.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                btn.innerHTML = '<i class="bi bi-check-circle"></i> Saved & Sent!';
                document.getElementById('dashRxPrintView').innerText = rxValue;
                setTimeout(() => {
                    btn.innerHTML = '<i class="bi bi-cloud-upload"></i> Save & Send to Patient';
                    btn.disabled = false;
                    rxModal.hide();
                    alert('Prescription successfully uploaded to patient portal!');
                }, 1500);
            })
            .catch(e => {
                alert('Error capturing prescription.');
                btn.innerHTML = '<i class="bi bi-cloud-upload"></i> Save & Send to Patient';
                btn.disabled = false;
            });
        }
    </script>

    <!-- Admin Notification Modal -->
    <div class="modal fade" id="adminNotifModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-info text-white py-3">
                    <h5 class="modal-title fw-bold"><i class="bi bi-megaphone-fill me-2"></i> Official Announcements</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($admin_notifications as $notif): ?>
                        <div class="list-group-item p-4 border-0 border-bottom">
                            <div class="d-flex w-100 justify-content-between mb-2">
                                <h6 class="mb-0 fw-bold text-primary"><?php echo htmlspecialchars($notif['title']); ?></h6>
                                <small class="badge bg-light text-muted border"><?php echo date('M d, Y', strtotime($notif['created_at'])); ?></small>
                            </div>
                            <p class="mb-0 text-secondary small" style="line-height: 1.6;">
                                <?php echo nl2br(htmlspecialchars($notif['message'])); ?>
                            </p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Close</button>
                </div>
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