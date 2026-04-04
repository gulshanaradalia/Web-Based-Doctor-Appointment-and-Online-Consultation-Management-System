<?php
// payment_monitoring.php
session_start();

// Ensure the user is an admin
if ($_SESSION['role_id'] != 1) {
    die("Access Denied! Only admins can view this page.");
}

// Include database connection
require_once 'db.php';

// Fetch payment transaction history from database
$query = "SELECT * FROM payments ORDER BY payment_date DESC";
$result = $conn->query($query);

// Check if the result is valid
if ($result->num_rows > 0) {
    // Display the payment history
    echo "<h1>Payment Transaction History</h1>";
    echo "<table border='1'>";
    echo "<tr><th>Payment ID</th><th>Appointment ID</th><th>Patient ID</th><th>Doctor ID</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['appointment_id'] . "</td>";
        echo "<td>" . $row['patient_id'] . "</td>";
        echo "<td>" . $row['doctor_id'] . "</td>";
        echo "<td>" . $row['amount'] . "</td>";
        echo "<td>" . $row['method'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . $row['payment_date'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No payment transactions found.</p>";
}

// Close the database connection
$conn->close();
?>