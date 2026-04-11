<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: register.php");
    exit;
}

require_once "db.php";

$message = '';
$msg_type = 'success';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $doctor_id = intval($_POST['doctor_id'] ?? 0);
    $slot_time = $_POST['appointment_date'] ?? '';
    // Format the date string if needed, but MySQL allows standard Y-m-d gracefully.
    // Ensure there is some default parsing just in case.
    $patient_id = $_SESSION["user_id"];
    
    if ($doctor_id > 0 && !empty($slot_time)) {
        // basic format check: if it's just a date, append time
        if (strlen($slot_time) <= 10) {
            $slot_time .= " 10:00:00"; // default to 10 am if no time provided by user
        }
        
        $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, slot_time, status) VALUES (?, ?, ?, 'pending')");
        $stmt->bind_param("iis", $patient_id, $doctor_id, $slot_time);
        if ($stmt->execute()) {
            $message = "Appointment request sent successfully! Please wait for the doctor's confirmation.";
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

$doctors = [];
$stmt = $conn->prepare("SELECT id, name, specialty FROM users WHERE role = 'doctor' AND status = 'active'");
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $doctors[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Appointment</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        /* Modern Premium Aesthetic */
        body { 
            padding-top: 80px; 
            background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);
            min-height: 100vh;
            font-family: 'Inter', 'Open Sans', sans-serif; 
        }
        .page-title { 
            color: #1ba5c6; 
            font-size: 0.95rem; 
            font-weight: 700; 
            text-align: center; 
            margin-bottom: 8px; 
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .page-heading { 
            color: #1e293b; 
            font-weight: 800; 
            text-align: center; 
            margin-bottom: 45px; 
            font-size: 2.2rem;
        }
        /* Glass/Premium Card */
        .card-custom { 
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.7);
            border-radius: 20px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.04), 0 5px 15px rgba(0,0,0,0.02); 
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.06), 0 5px 15px rgba(0,0,0,0.03); 
        }
        
        .form-control-custom, .form-select-custom {
            background-color: #f8fafc;
            border: 2px solid transparent;
            border-radius: 12px;
            padding: 14px 15px;
            padding-left: 48px; /* space for icon */
            font-weight: 500;
            color: #334155;
            transition: all 0.25s ease;
        }
        
        .form-control-custom:focus, .form-select-custom:focus {
            background-color: #ffffff;
            border-color: #1ba5c6;
            box-shadow: 0 0 0 4px rgba(27, 165, 198, 0.1);
            outline: none;
        }
        
        .input-icon-wrapper { position: relative; }
        .input-icon-wrapper i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.2rem;
            transition: color 0.2s ease;
        }
        /* Icon glows when input is focused */
        .input-icon-wrapper:focus-within i {
            color: #1ba5c6;
        }

        .select-wrapper { position: relative; }
        .select-no-icon { padding-left: 18px !important; }
        
        .form-label { 
            font-weight: 600; 
            color: #64748b; 
            margin-bottom: 6px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-premium {
            background: linear-gradient(135deg, #1ba5c6 0%, #107b95 100%);
            color: #ffffff;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            padding: 14px 35px;
            font-size: 1.05rem;
            box-shadow: 0 8px 20px rgba(27, 165, 198, 0.25);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(27, 165, 198, 0.35);
            color: #ffffff;
        }
        .btn-premium:active {
            transform: translateY(1px);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="bi bi-heart-pulse-fill me-2"></i>
                <span>HealthTech</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive">
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
                    <li class="nav-item"><a class="nav-link active" href="online_appointment.php">Online Appointment</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="page-title">Online Appointment</div>
        <h2 class="page-heading">Drop us a message for any query</h2>

        <?php if ($message): ?>
            <div class="row justify-content-center mb-4">
                <div class="col-lg-8">
                    <div class="alert alert-<?php echo $msg_type; ?> shadow-sm text-center fw-bold">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="row g-4 justify-content-center">
                <!-- Left Column: Select Doctor -->
                <div class="col-lg-4">
                    <div class="card card-custom h-100 p-4">
                        <h5 class="mb-4" style="font-weight: 800; color: #1e293b;">Select Doctor</h5>
                        <div class="select-wrapper">
                            <select name="doctor_id" class="form-select form-select-custom select-no-icon w-100" required>
                                <option value="">Search by consultant name</option>
                                <?php foreach ($doctors as $doc): ?>
                                    <option value="<?php echo htmlspecialchars($doc['id']); ?>">
                                        <?php echo htmlspecialchars($doc['name'] . " (" . $doc['specialty'] . ")"); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

            <!-- Right Column: Make an Appointment -->
            <div class="col-lg-8">
                <div class="card card-custom p-4">
                    <h5 class="mb-4" style="font-weight: 800; color: #1e293b;">Make An Appointment -</h5>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label text-muted small mb-1">Patient Name</label>
                                <div class="input-icon-wrapper">
                                    <i class="bi bi-person"></i>
                                    <input type="text" name="patient_name" class="form-control form-control-custom" placeholder="Enter Patient Name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small mb-1">Email</label>
                                <div class="input-icon-wrapper">
                                    <i class="bi bi-envelope"></i>
                                    <input type="email" name="patient_email" class="form-control form-control-custom" placeholder="Enter Your Email" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small mb-1">Phone</label>
                                <div class="input-icon-wrapper">
                                    <i class="bi bi-phone"></i>
                                    <input type="text" name="patient_phone" class="form-control form-control-custom" placeholder="Enter Your Phone" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small mb-1">Address</label>
                                <div class="input-icon-wrapper">
                                    <i class="bi bi-building"></i>
                                    <input type="text" name="address" class="form-control form-control-custom" placeholder="Enter Your Address">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small mb-1">Gender</label>
                                <div class="input-icon-wrapper">
                                    <i class="bi bi-person-badge"></i>
                                    <select name="gender" class="form-select form-select-custom">
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small mb-1">Appointment Date</label>
                                <div class="input-icon-wrapper">
                                    <i class="bi bi-calendar"></i>
                                    <input type="date" name="appointment_date" class="form-control form-control-custom" required>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 text-end">
                            <button type="submit" class="btn btn-premium w-100"><i class="bi bi-send me-2"></i> Submit Booking Request</button>
                        </div>
                </div>
            </div>
        </div>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
