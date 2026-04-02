<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION["role"] != "patient") {
    header("Location: dashboard.php");
    exit;
}

require_once "db.php";

$patient_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT a.id, a.slot_time, a.status, u.id AS doctor_id, u.name AS doctor_name, 
                        u.specialty, u.consultation_fee,
                        ac.id as confirmation_id, ac.confirmation_status 
                        FROM appointments a 
                        JOIN users u ON a.doctor_id = u.id 
                        LEFT JOIN appointment_confirmations ac ON a.id = ac.appointment_id 
                        WHERE a.patient_id = ? 
                        ORDER BY a.slot_time DESC");
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$appointments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$slotDurationMinutes = 15;

$appointmentIds = array_column($appointments, 'id');
$paymentsByAppointment = [];
if (!empty($appointmentIds)) {
    $placeholders = implode(',', array_fill(0, count($appointmentIds), '?'));
    $types = str_repeat('i', count($appointmentIds));
    $sql = "SELECT appointment_id, amount, method, status, transaction_id, invoice_number, payment_date 
            FROM payments 
            WHERE appointment_id IN ($placeholders)";
    $stmtPayments = $conn->prepare($sql);
    $stmtPayments->bind_param($types, ...$appointmentIds);
    $stmtPayments->execute();
    $paymentResults = $stmtPayments->get_result();
    while ($pr = $paymentResults->fetch_assoc()) {
        $paymentsByAppointment[$pr['appointment_id']] = $pr;
    }
    $stmtPayments->close();
}

foreach ($appointments as $idx => $apt) {
    $queueStmt = $conn->prepare("SELECT COUNT(*) AS queue_position 
                                 FROM appointments 
                                 WHERE doctor_id = ? AND slot_time <= ? AND status IN ('pending','approved')");
    $queueStmt->bind_param('is', $apt['doctor_id'], $apt['slot_time']);
    $queueStmt->execute();
    $queueResult = $queueStmt->get_result();
    $queueData = $queueResult->fetch_assoc();
    $queueStmt->close();

    $queuePosition = isset($queueData['queue_position']) ? (int)$queueData['queue_position'] : 1;
    $slotDateTime = new DateTime($apt['slot_time']);
    $now = new DateTime();
    $timeUntil = max(0, ceil(($slotDateTime->getTimestamp() - $now->getTimestamp()) / 60));
    $estimatedWait = max($timeUntil, ($queuePosition - 1) * $slotDurationMinutes);

    $appointments[$idx]['queue_position'] = $queuePosition;
    $appointments[$idx]['estimated_wait'] = $estimatedWait;
    $appointments[$idx]['payment_info'] = $paymentsByAppointment[$apt['id']] ?? null;
}

$confirm_stmt = $conn->prepare("SELECT ac.id, ac.appointment_id, a.slot_time, u.name AS doctor_name, u.specialty
                               FROM appointment_confirmations ac
                               JOIN appointments a ON ac.appointment_id = a.id
                               JOIN users u ON a.doctor_id = u.id
                               WHERE ac.patient_id = ?
                                 AND a.status = 'approved'
                                 AND ac.confirmation_status IN ('notification_sent', 'pending')
                               ORDER BY a.slot_time ASC");
$confirm_stmt->bind_param('i', $patient_id);
$confirm_stmt->execute();
$confirm_result = $confirm_stmt->get_result();
$pending_confirmations = $confirm_result->fetch_all(MYSQLI_ASSOC);
$confirm_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow">
        <div class="container">
            <a class="navbar-brand" href="patient_dashboard.php">Patient Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="doctor_search.php">Search Doctors</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mt-5 pt-5">
        <div id="payment-message"></div>

        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h2 class="mb-3">Hello, <?php echo htmlspecialchars($_SESSION["name"]); ?></h2>
                <p class="text-muted">Use the button below to search and book the right doctor.</p>
                <a class="btn btn-accent btn-lg" href="doctor_search.php">Go to Doctor Search</a>
            </div>
        </div>

        <?php if (!empty($pending_confirmations)): ?>
            <div class="row justify-content-center mt-4">
                <div class="col-lg-10">
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <h5 class="alert-heading">⏳ Pending Confirmations</h5>
                        <p class="mb-0">You have <?php echo count($pending_confirmations); ?> appointment(s) waiting for your confirmation.</p>
                        <hr>
                        <?php foreach ($pending_confirmations as $confirm): ?>
                            <div class="mb-3">
                                <strong><?php echo htmlspecialchars($confirm['doctor_name']); ?></strong>
                                (<?php echo htmlspecialchars($confirm['specialty']); ?>)
                                <br>
                                <small class="text-muted">
                                    Slot: <?php echo htmlspecialchars((new DateTime($confirm['slot_time']))->format('Y-m-d H:i')); ?>
                                </small>
                                <br>
                                <a href="confirm_appointment.php?id=<?php echo $confirm['id']; ?>" class="btn btn-sm btn-success mt-2">
                                    Confirm Appointment
                                </a>
                            </div>
                            <?php if ($confirm !== end($pending_confirmations)): ?>
                                <hr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center mt-5">
            <div class="col-lg-10">
                <h3 class="mb-4">Your Appointments</h3>

                <?php if (count($appointments) === 0): ?>
                    <div class="alert alert-info">You have no appointments yet. Search for a doctor to book one.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Doctor</th>
                                    <th>Specialty</th>
                                    <th>Slot</th>
                                    <th>Consultation Fee</th>
                                    <th>Queue</th>
                                    <th>Waiting Time</th>
                                    <th>Booking Status</th>
                                    <th>Payment</th>
                                    <th>Confirmation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $idx => $apt): ?>
                                    <tr>
                                        <td><?php echo $idx + 1; ?></td>
                                        <td><?php echo htmlspecialchars($apt['doctor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($apt['specialty']); ?></td>
                                        <td><?php echo htmlspecialchars((new DateTime($apt['slot_time']))->format('Y-m-d H:i')); ?></td>
                                        <td><?php echo htmlspecialchars(number_format((float)$apt['consultation_fee'], 2)); ?> BDT</td>
                                        <td><?php echo htmlspecialchars($apt['queue_position'] ?? '-'); ?></td>
                                        <td><?php echo isset($apt['estimated_wait']) ? htmlspecialchars($apt['estimated_wait'] . ' mins') : '-'; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $apt['status'] === 'approved' ? 'success' : ($apt['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                                <?php echo ucfirst($apt['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($apt['payment_info'])): ?>
                                                <span class="badge bg-<?php echo $apt['payment_info']['status'] === 'completed' ? 'success' : ($apt['payment_info']['status'] === 'pending' ? 'warning text-dark' : 'danger'); ?>">
                                                    <?php echo ucfirst($apt['payment_info']['status']); ?>
                                                </span>
                                                <br>
                                                <small>
                                                    <?php echo htmlspecialchars($apt['payment_info']['method']); ?> /
                                                    <?php echo htmlspecialchars($apt['payment_info']['amount']); ?> BDT
                                                </small>
                                            <?php elseif ($apt['status'] === 'approved'): ?>
                                                <span class="badge bg-warning text-dark">Unpaid</span><br>
                                                <button type="button"
                                                        class="btn btn-sm btn-success pay-now-btn mt-2"
                                                        data-appointment="<?php echo $apt['id']; ?>"
                                                        data-amount="<?php echo htmlspecialchars($apt['consultation_fee']); ?>">
                                                    Pay Now
                                                </button>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Not available</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($apt['status'] === 'approved' && $apt['confirmation_id']): ?>
                                                <span class="badge bg-<?php echo $apt['confirmation_status'] === 'confirmed' ? 'success' : 'info'; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $apt['confirmation_status'])); ?>
                                                </span>
                                                <?php if ($apt['confirmation_status'] !== 'confirmed'): ?>
                                                    <br>
                                                    <a href="confirm_appointment.php?id=<?php echo $apt['confirmation_id']; ?>" class="btn btn-sm btn-primary mt-2">
                                                        Confirm
                                                    </a>
                                                <?php endif; ?>
                                            <?php elseif ($apt['status'] === 'approved'): ?>
                                                <span class="badge bg-secondary">No confirmation yet</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Not available</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <div class="modal fade" id="paymentSuccessModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Payment Successful</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2"><strong>Your payment has been completed successfully.</strong></p>
                    <p class="mb-1">Transaction ID: <span id="successTransactionId"></span></p>
                    <p class="mb-0">Invoice: <span id="successInvoiceId"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="paymentMethodModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Choose Payment Method</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="paymentMethodForm">
                        <input type="hidden" id="selectedAppointmentId">

                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-control"
                                id="displayAmount"
                                placeholder="Enter amount (optional)">
                            <small class="text-muted">You can change it or leave it blank.</small>
                        </div>

                        <div class="mb-3">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select id="payment_method" class="form-select" required>
                                <option value="credit_card">Credit Card</option>
                                <option value="debit_card">Debit Card</option>
                                <option value="mobile_banking">Mobile Banking</option>
                                <option value="net_banking">Net Banking</option>
                                <option value="wallet">Digital Wallet</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Confirm Payment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        var paymentMethodModal = new bootstrap.Modal(document.getElementById('paymentMethodModal'));
        var paymentSuccessModal = new bootstrap.Modal(document.getElementById('paymentSuccessModal'));

        document.querySelectorAll('.pay-now-btn').forEach(function(button) {
            button.addEventListener('click', function() {
                var appointmentId = this.getAttribute('data-appointment');
                var amount = this.getAttribute('data-amount');

                document.getElementById('selectedAppointmentId').value = appointmentId;
                document.getElementById('displayAmount').value = amount;

                paymentMethodModal.show();
            });
        });

        document.getElementById('paymentMethodForm').addEventListener('submit', function(e) {
            e.preventDefault();

            var appointmentId = document.getElementById('selectedAppointmentId').value;
            var amount = document.getElementById('displayAmount').value.trim();
            var paymentMethod = document.getElementById('payment_method').value;

            var submitBtn = this.querySelector('button[type="submit"]');
            var originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Processing...';

            var formData = new FormData();
            formData.append('action', 'process_payment');
            formData.append('appointment_id', appointmentId);
            formData.append('amount', amount);
            formData.append('payment_method', paymentMethod);

            fetch('payment.php', {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(result) {
                if (!result.success) {
                    throw new Error(result.error || 'Failed to process payment.');
                }

                paymentMethodModal.hide();

                document.getElementById('successTransactionId').textContent = result.transaction_id || '-';
                document.getElementById('successInvoiceId').textContent = result.invoice_number || '-';

                paymentSuccessModal.show();

                document.getElementById('paymentSuccessModal').addEventListener('hidden.bs.modal', function () {
                    window.location.reload();
                }, { once: true });
            })
            .catch(function(error) {
                showMessage(error.message || 'Payment service is unavailable.', 'danger');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });

        function showMessage(message, type) {
            document.getElementById('payment-message').innerHTML =
                '<div class="alert alert-' + type + ' alert-dismissible fade show mt-3" role="alert">' +
                message +
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                '</div>';
        }
    </script>
</body>
</html>