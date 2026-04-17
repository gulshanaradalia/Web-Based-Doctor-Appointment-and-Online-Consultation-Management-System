<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {
    require_once 'db.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    exit;
}

// Dummy payment gateway logic embedded here
function getSupportedPaymentMethods()
{
    return [
        'credit_card' => 'Credit Card',
        'debit_card' => 'Debit Card',
        'mobile_banking' => 'Mobile Banking',
        'net_banking' => 'Net Banking',
        'wallet' => 'Digital Wallet'
    ];
}

function dummyProcessPayment($amount, $method)
{
    if (!in_array($method, array_keys(getSupportedPaymentMethods()))) {
        return ['success' => false, 'message' => 'Unsupported payment method.'];
    }

    if ($amount <= 0) {
        return ['success' => false, 'message' => 'Invalid payment amount.'];
    }

    $success = (rand(1, 100) > 10); // 90% success chance
    $txid = 'TXN_' . strtoupper(uniqid()) . '_' . time();

    return [
        'success' => $success,
        'transaction_id' => $txid,
        'message' => $success ? 'Payment completed.' : 'Payment failed due to gateway error.'
    ];
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? '';

try {
    switch ($action) {
        case 'initiate_payment':
            initiatePayment($conn, $user_id);
            break;

        case 'process_payment':
            processPayment($conn, $user_id);
            break;

        case 'get_history':
            getPaymentHistory($conn, $user_id);
            break;

        case 'get_status':
            getPaymentStatus($conn, $user_id);
            break;

        case 'verify_payment':
            verifyPayment($conn, $user_id);
            break;

        case 'get_transactions':
            if ($user_type !== 'admin') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Admin access required']);
                exit;
            }
            getAdminTransactions($conn);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            exit;
    }
} catch (Throwable $e) {
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json');
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

// ============ PAYMENT FUNCTIONS ============

function initiatePayment($conn, $user_id)
{
    $appointment_id = $_POST['appointment_id'] ?? null;

    if (!$appointment_id) {
        throw new Exception('Appointment ID required');
    }

    // Fetch appointment details
    $query = "SELECT a.*, u.consultation_fee, u.name as doctor_name
              FROM appointments a
              JOIN users u ON a.doctor_id = u.id
              WHERE a.id = ? AND a.patient_id = ? AND a.status = 'approved'";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Query prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('ii', $appointment_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $appointment = $result->fetch_assoc();
    $stmt->close();

    if (!$appointment) {
        throw new Exception('Appointment not found or not approved');
    }

    $amount = floatval($appointment['consultation_fee'] ?? 500);

    // Get supported payment methods
    $methods = getSupportedPaymentMethods();

    // Check if already paid
    $check_query = "SELECT id, status FROM payments WHERE appointment_id = ? LIMIT 1";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param('i', $appointment_id);
    $check_stmt->execute();
    $payment_check = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();

    $already_paid = false;
    if ($payment_check) {
        $already_paid = ($payment_check['status'] === 'completed');
    }

    echo json_encode([
        'success' => true,
        'appointment_id' => $appointment_id,
        'doctor' => $appointment['doctor_name'],
        'amount' => $amount,
        'methods' => $methods,
        'already_paid' => $already_paid,
        'appointment_date' => $appointment['appointment_date']
    ]);
}

function processPayment($conn, $user_id)
{
    $appointment_id = $_POST['appointment_id'] ?? null;
    $payment_method = trim($_POST['payment_method'] ?? '');
    $raw_amount = trim($_POST['amount'] ?? '');

    if (!$appointment_id || !$payment_method) {
        throw new Exception('Missing required payment details');
    }

    // Fetch appointment and verify ownership
    $query = "SELECT * FROM appointments WHERE id = ? AND patient_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $appointment_id, $user_id);
    $stmt->execute();
    $appointment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$appointment) {
        throw new Exception('Appointment not found');
    }

    // Use appointment fee when no amount is provided or invalid amount is submitted
    if ($raw_amount === '' || !is_numeric($raw_amount) || floatval($raw_amount) <= 0) {
        $amount = floatval($appointment['consultation_fee'] ?? 0);
    } else {
        $amount = floatval($raw_amount);
    }

    if ($amount <= 0) {
        throw new Exception('Invalid amount');
    }

    if ($payment_method === 'cash') {
        $payment_method = 'wallet';
    }

    // Process payment through dummy gateway
    $result = dummyProcessPayment($amount, $payment_method);

    if (!$result['success']) {
        throw new Exception('Payment processing failed: ' . $result['message']);
    }

    // Insert payment record into database
    $insert_query = "INSERT INTO payments 
                     (appointment_id, patient_id, doctor_id, amount, method, status, transaction_id, invoice_number) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $insert_stmt = $conn->prepare($insert_query);
    if (!$insert_stmt) {
        throw new Exception('Payment insertion failed: ' . $conn->error);
    }

    $status = $result['success'] ? 'completed' : 'failed';
    $invoice_number = 'INV_' . $appointment_id . '_' . time();

    $insert_stmt->bind_param(
        'iiidssss',
        $appointment_id,
        $appointment['patient_id'],
        $appointment['doctor_id'],
        $amount,
        $payment_method,
        $status,
        $result['transaction_id'],
        $invoice_number
    );

    if (!$insert_stmt->execute()) {
        throw new Exception('Failed to record payment: ' . $insert_stmt->error);
    }

    $insert_stmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Payment processed successfully',
        'transaction_id' => $result['transaction_id'],
        'invoice_number' => $invoice_number,
        'status' => $status,
        'amount' => $amount
    ]);
}

function getPaymentHistory($conn, $user_id)
{
    $query = "SELECT p.*, a.slot_time AS appointment_date, u.name as doctor_name
              FROM payments p
              JOIN appointments a ON p.appointment_id = a.id
              JOIN users u ON a.doctor_id = u.id
              WHERE p.patient_id = ?
              ORDER BY p.payment_date DESC
              LIMIT 20";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'history' => $history,
        'total' => count($history)
    ]);
}

function getPaymentStatus($conn, $user_id)
{
    $appointment_id = $_GET['appointment_id'] ?? null;

    if (!$appointment_id) {
        throw new Exception('Appointment ID required');
    }

    // Verify appointment belongs to user
    $verify_query = "SELECT id FROM appointments WHERE id = ? AND patient_id = ?";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param('ii', $appointment_id, $user_id);
    $verify_stmt->execute();
    if (!$verify_stmt->get_result()->fetch_assoc()) {
        throw new Exception('Appointment not found');
    }
    $verify_stmt->close();

    // Get payment status
    $query = "SELECT * FROM payments WHERE appointment_id = ? ORDER BY payment_date DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $appointment_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    echo json_encode([
        'success' => true,
        'payment' => $payment ?? null,
        'status' => $payment['status'] ?? 'pending'
    ]);
}

function verifyPayment($conn, $user_id)
{
    $transaction_id = $_POST['transaction_id'] ?? null;
    $appointment_id = $_POST['appointment_id'] ?? null;

    if (!$transaction_id || !$appointment_id) {
        throw new Exception('Transaction ID and Appointment ID required');
    }

    // Verify payment exists and belongs to user
    $query = "SELECT * FROM payments 
              WHERE transaction_id = ? AND appointment_id = ? AND patient_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('sii', $transaction_id, $appointment_id, $user_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$payment) {
        throw new Exception('Payment record not found');
    }

    echo json_encode([
        'success' => true,
        'verified' => true,
        'payment' => $payment,
        'message' => 'Payment verified successfully'
    ]);
}

function getAdminTransactions($conn)
{
    $limit = $_GET['limit'] ?? 50;
    $offset = $_GET['offset'] ?? 0;

    $query = "SELECT p.*, 
                     a.id AS appointment_id, a.slot_time AS appointment_date,
                     patient.name as patient_name, patient.email as patient_email,
                     doctor.name as doctor_name, doctor.specialty
              FROM payments p
              JOIN appointments a ON p.appointment_id = a.id
              JOIN users patient ON p.patient_id = patient.id
              JOIN users doctor ON p.doctor_id = doctor.id
              ORDER BY p.payment_date DESC
              LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    $stmt->close();

    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM payments";
    $count_result = $conn->query($count_query);
    $count_row = $count_result->fetch_assoc();
    $total = $count_row['total'];

    echo json_encode([
        'success' => true,
        'transactions' => $transactions,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ]);
}
