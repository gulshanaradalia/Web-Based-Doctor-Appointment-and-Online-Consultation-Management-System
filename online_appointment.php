<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: register.php");
    exit;
}

require_once "db.php";

$message = '';
$msg_type = 'success';

// Handle Appointment Submission (POST)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $doctor_id = intval($_POST['doctor_id'] ?? 0);
    $slot_time = $_POST['appointment_date'] ?? '';
    $patient_id = $_SESSION["user_id"];
    
    // Additional fields
    $patient_name = $_POST['patient_name'] ?? '';
    $patient_email = $_POST['patient_email'] ?? '';
    $patient_phone = $_POST['patient_phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $gender = $_POST['gender'] ?? '';

    if ($doctor_id > 0 && !empty($slot_time)) {
        if (strlen($slot_time) <= 10) {
            $slot_time .= " 10:00:00"; 
        }
        
        $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, slot_time, status) VALUES (?, ?, ?, 'pending')");
        $stmt->bind_param("iis", $patient_id, $doctor_id, $slot_time);
        if ($stmt->execute()) {
            $message = "Appointment request sent successfully!";
            $msg_type = "success";
        } else {
            $message = "Error sending request. Please try again.";
            $msg_type = "danger";
        }
        $stmt->close();
    } else {
        $message = "Please select a doctor and appointment date.";
        $msg_type = "danger";
    }
}

// Handle Doctor Filtering (GET) - copied from doctor_search logic
$doctors = [];
$search_query = "SELECT id, name, specialty, location FROM users WHERE role = 'doctor' AND status = 'active'";
$where = [];
$params = [];
$types = "";

if (!empty($_GET['name'])) {
    $where[] = "name LIKE ?";
    $params[] = '%' . $_GET['name'] . '%';
    $types .= 's';
}
if (!empty($_GET['location'])) {
    $where[] = "location LIKE ?";
    $params[] = '%' . $_GET['location'] . '%';
    $types .= 's';
}
if (!empty($_GET['specialty'])) {
    $where[] = "specialty LIKE ?";
    $params[] = '%' . $_GET['specialty'] . '%';
    $types .= 's';
}

if (!empty($where)) {
    $search_query .= ' AND ' . implode(' AND ', $where);
}

$stmt = $conn->prepare($search_query);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
    $stmt->close();
}

// Fetch all doctor names for the dropdown
$all_doctor_names = [];
$name_results = $conn->query("SELECT DISTINCT name FROM users WHERE role = 'doctor' ORDER BY name ASC");
if ($name_results) {
    while ($row = $name_results->fetch_assoc()) {
        $all_doctor_names[] = $row['name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Appointment | HealthTech</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        body { padding-top: 80px; background-color: #f8fafc; }
        .nav-pills .nav-link.active { background-color: transparent !important; color: #17a2b8 !important; font-weight: bold; }
        .card-custom { border-radius: 12px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .form-label { font-weight: 600; color: #64748b; font-size: 0.85rem; }
        .btn-premium { background-color: #17a2b8; color: white; font-weight: 600; }
        .banner-teal { background-color: #17a2b8; color: white; font-size: 1.5rem; font-weight: bold; font-family: serif; }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-info fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center fw-bold" href="index.php">
                <i class="bi bi-heart-pulse-fill me-2"></i> HealthTech
            </a>
            <div class="collapse navbar-collapse" id="navbarResponsive">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="doctor_search.php">Find Doctors</a></li>
                    <li class="nav-item"><a class="nav-link active" href="online_appointment.php">Online Appointment</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mt-5 pb-5">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 border-end pe-4">
                <h4 class="mb-4" style="border-bottom: 2px solid #17a2b8; display: inline-block; padding-bottom: 8px; color: #333;">Book Appointment</h4>
                <div class="nav flex-column nav-pills me-3" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                    <button class="nav-link active text-start py-3 mb-2 text-muted" id="v-pills-name-tab" data-bs-toggle="pill" data-bs-target="#v-pills-name" type="button" role="tab" style="border-radius: 0;">Doctor Name Wise</button>
                    <button class="nav-link text-start py-3 mb-2 text-muted" id="v-pills-hospital-tab" data-bs-toggle="pill" data-bs-target="#v-pills-hospital" type="button" role="tab" style="border-radius: 0;">Hospital Wise</button>
                    <button class="nav-link text-start py-3 text-muted" id="v-pills-specialty-tab" data-bs-toggle="pill" data-bs-target="#v-pills-specialty" type="button" role="tab" style="border-radius: 0;">Specialty Wise</button>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="col-md-9 ps-4">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $msg_type; ?> shadow-sm text-center fw-bold mb-4">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Step 1: Search Section -->
                <div class="tab-content mb-5" id="v-pills-tabContent">
                    <!-- Name Tab -->
                    <div class="tab-pane fade show active" id="v-pills-name" role="tabpanel">
                        <div class="p-3 mb-4 text-center banner-teal">FIND YOUR DOCTOR</div>
                        <form method="get">
                            <label class="form-label mb-2">Search by Doctor Name</label>
                            <select name="name" class="form-select mb-3">
                                <option value="">Select Doctor Name</option>
                                <?php foreach ($all_doctor_names as $dn): ?>
                                    <option value="<?php echo htmlspecialchars($dn); ?>"><?php echo htmlspecialchars($dn); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-premium px-4 float-end">Filter List</button>
                        </form>
                    </div>

                    <!-- Hospital Tab -->
                    <div class="tab-pane fade" id="v-pills-hospital" role="tabpanel">
                        <div class="p-3 mb-4 text-center banner-teal">FIND YOUR DOCTOR</div>
                        <form method="get">
                            <label class="form-label mb-2">Search by Hospital Name</label>
                            <select name="location" class="form-select mb-3">
                                <option value="">Select Hospital</option>
                                <option value="Dhaka Medical College">Dhaka Medical College Hospital</option>
                                <option value="Square Hospital">Square Hospital</option>
                                <option value="Evercare Hospital">Evercare Hospital</option>
                                <option value="Labaid Hospital">Labaid Hospital</option>
                                <option value="United Hospital">United Hospital</option>
                                <option value="Ibne Sina Hospital">Ibne Sina Hospital</option>
                                <option value="Popular Medical College">Popular Medical College Hospital</option>
                                <option value="BIRDEM General Hospital">BIRDEM General Hospital</option>
                                <option value="BSMMU">Bangabandhu Sheikh Mujib Medical University</option>
                            </select>
                            <button type="submit" class="btn btn-premium px-4 float-end">Filter List</button>
                        </form>
                    </div>

                    <!-- Specialty Tab -->
                    <div class="tab-pane fade" id="v-pills-specialty" role="tabpanel">
                        <div class="p-3 mb-4 text-center banner-teal">FIND YOUR DOCTOR</div>
                        <form method="get">
                            <label class="form-label mb-2">Search By Speciality/Department name</label>
                            <select name="specialty" class="form-select mb-3">
                                <option value="">Select Specialty</option>
                                <option value="Cardiology Specialist">Cardiology Specialist</option>
                                <option value="Pediatrician">Pediatrician</option>
                                <option value="Medicine Specialist">Medicine Specialist</option>
                                <option value="Dermatologist">Dermatologist</option>
                                <option value="Neurosurgeon">Neurosurgeon</option>
                            </select>
                            <button type="submit" class="btn btn-premium px-4 float-end">Filter List</button>
                        </form>
                    </div>
                </div>

                <div class="clearfix"></div>

                <!-- Step 2: Booking Form -->
                <div class="card card-custom p-4 mt-4" style="background: white; border-top: 5px solid #17a2b8;">
                    <div class="p-3 mb-4 text-center banner-teal">MAKE AN APPOINTMENT</div>
                    <form action="online_appointment.php" method="POST">
                        <div class="row g-4">
                            <div class="col-12">
                                <label class="form-label">Step 1: Select Doctor from Result</label>
                                <select name="doctor_id" class="form-select border-info" required>
                                    <option value="">-- Select Available Doctor --</option>
                                    <?php foreach ($doctors as $doc): ?>
                                        <option value="<?php echo $doc['id']; ?>" <?php echo (isset($_GET['name']) && $_GET['name'] == $doc['name']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($doc['name'] . " (" . $doc['specialty'] . ") - " . $doc['location']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="text-muted small mt-1"><i class="bi bi-info-circle me-1"></i> The list above is filtered by your search selection.</p>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Patient Name</label>
                                <input type="text" name="patient_name" class="form-control" placeholder="Enter Name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="patient_email" class="form-control" placeholder="Email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="patient_phone" class="form-control" placeholder="Phone" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-select">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Appointment Date</label>
                                <input type="date" name="appointment_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control" placeholder="City/Area">
                            </div>
                        </div>
                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-premium w-100 py-3"><i class="bi bi-calendar-check me-2"></i> Confirm Booking Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
