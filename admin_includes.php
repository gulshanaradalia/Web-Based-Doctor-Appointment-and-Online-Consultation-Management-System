<?php
// admin_includes.php - Shared functions and UI components for Admin Panel

/**
 * Log an activity to the database
 */
function log_activity($conn, $user_id, $action, $description) {
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $action, $description);
    $stmt->execute();
    $stmt->close();
}

/**
 * Get the Sidebar HTML
 */
function get_sidebar($active_page) {
    $pages = [
        'admin_dashboard.php' => ['Dashboard', 'speedometer2'],
        'admin_doctors.php' => ['Doctors', 'heart-pulse'],
        'admin_patients.php' => ['Patients', 'people'],
        'admin_appointments.php' => ['Appointments', 'calendar-check'],
        'admin_consultations.php' => ['Consultations', 'chat-dots'],
        'admin_schedules.php' => ['Schedules', 'calendar-event'],
        'admin_payments.php' => ['Payments', 'cash-stack'],
        'admin_notifications.php' => ['Notifications', 'bell'],
        'admin_logs.php' => ['Activity Logs', 'journal-text'],
        'admin_settings.php' => ['System Settings', 'gear'],
        'admin_profile.php' => ['My Profile', 'person-circle']
    ];

    $html = '<ul class="nav flex-column gap-1">';
    foreach ($pages as $url => $info) {
        $active = ($active_page === $url) ? 'btn-primary' : 'btn-light border-0';
        $html .= '<li><a href="'.$url.'" class="btn '.$active.' w-100 text-start"><i class="bi bi-'.$info[1].'"></i> '.$info[0].'</a></li>';
    }
    $html .= '<li><a href="logout.php" class="btn btn-outline-danger w-100 text-start border-0 mt-2"><i class="bi bi-box-arrow-right"></i> Logout</a></li>';
    $html .= '</ul>';
    
    return $html;
}

/**
 * Get System Setting
 */
function get_setting($conn, $key, $default = '') {
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        return $row['setting_value'];
    }
    return $default;
}
?>
