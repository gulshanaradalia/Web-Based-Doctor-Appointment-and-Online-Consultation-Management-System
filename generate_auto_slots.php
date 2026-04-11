<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit;
}

$doctorId = $_SESSION['user_id'];
$daysToAdd = 30;
// We will use Monday, Wednesday, Friday as the "any 3 days"
$allowedDays = ['Monday', 'Wednesday', 'Friday'];
$startTime = '15:00';
$endTime = '18:00'; // 3 PM to 6 PM
$intervalMinutes = 15; // 15-minute slots

$currentDate = new DateTime();
$inserted = 0;

for ($i = 0; $i < $daysToAdd; $i++) {
    $dayName = $currentDate->format('l');
    
    if (in_array($dayName, $allowedDays)) {
        $slotTime = new DateTime($currentDate->format('Y-m-d') . ' ' . $startTime);
        $endSlotTime = new DateTime($currentDate->format('Y-m-d') . ' ' . $endTime);
        
        while ($slotTime < $endSlotTime) {
            $formattedSlot = $slotTime->format('Y-m-d H:i:s');
            
            // Using INSERT IGNORE so we don't crash if slot already exists
            $stmt = $conn->prepare("INSERT IGNORE INTO schedule_slots (doctor_id, slot_time, slot_status) VALUES (?, ?, 'available')");
            $stmt->bind_param('is', $doctorId, $formattedSlot);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $inserted++;
            }
            $stmt->close();
            
            $slotTime->modify("+$intervalMinutes minutes");
        }
    }
    
    $currentDate->modify('+1 day');
}

echo "<script>
    alert('Successfully generated " . $inserted . " new slots (Mon, Wed, Fri from 3 PM to 6 PM)!');
    window.location.href = 'doctor_dashboard.php';
</script>";
?>
