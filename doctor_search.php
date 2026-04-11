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

$query = "SELECT id, name, email, phone, location, hospital, specialty FROM users WHERE role = 'doctor'";
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
    $query .= ' AND ' . implode(' AND ', $where);
}

$stmt = $conn->prepare($query);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $doctors = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    die('Database query error: ' . $conn->error);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Search</title>
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
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#about">About</a></li>
                    <li class="nav-item"><a class="nav-link active" href="doctor_search.php">Find Doctors</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mt-5 pt-5">

        <h2 class="mb-4">Search Doctors</h2>
        <form method="get" class="row g-3 mb-4">
            <div class="col-md-4">
                <label for="name" class="form-label">Doctor Name</label>
                <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Dr. Rahman" value="<?php echo isset($_GET['name']) ? htmlspecialchars($_GET['name']) : ''; ?>">
            </div>
            <div class="col-md-4">
                <label for="location" class="form-label">Location</label>
                <input type="text" name="location" id="location" class="form-control" placeholder="e.g. Dhaka" value="<?php echo isset($_GET['location']) ? htmlspecialchars($_GET['location']) : ''; ?>">
            </div>
            <div class="col-md-4">
                <label for="specialty" class="form-label">Specialty</label>
                <input type="text" name="specialty" id="specialty" class="form-control" placeholder="e.g. Cardiology" value="<?php echo isset($_GET['specialty']) ? htmlspecialchars($_GET['specialty']) : ''; ?>">
            </div>
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-accent">Search</button>
                <a href="doctor_search.php" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>

        <div class="row gy-3">
            <div class="col-lg-8">
                <?php if (count($doctors) > 0): ?>
                    <div class="row gy-3">
                        <?php foreach ($doctors as $doctor): ?>
                            <div class="col-md-6">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body">
                                        <h5 class="card-title mb-1"><?php echo htmlspecialchars($doctor['name']); ?></h5>
                                        <p class="text-muted small mb-2"><?php echo htmlspecialchars($doctor['specialty']); ?> • <?php echo htmlspecialchars($doctor['hospital']); ?> • <?php echo htmlspecialchars($doctor['location']); ?></p>
                                        <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($doctor['email']); ?></p>
                                        <p class="mb-3"><strong>Phone:</strong> <?php echo htmlspecialchars($doctor['phone']); ?></p>
                                        <div class="d-flex gap-2">
                                            <a href="view_availability.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-outline-primary shadow-sm">View Availability</a>
                                            <a href="book_appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-primary shadow-sm">Book Appointment</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">No doctors found using the selected filters. Please adjust your search criteria.</div>
                <?php endif; ?>
            </div>
            <div class="col-lg-4">
                <div class="sticky-top" style="top:100px;">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Quick Access</h5>
                            <p class="text-muted mb-3">Your profile and dashboard are here, just above the footer.</p>
                            <a href="patient_dashboard.php" class="btn btn-outline-primary w-100 mb-2"><i class="bi bi-speedometer2"></i> My Dashboard</a>
                            <a href="profile.php" class="btn btn-outline-secondary w-100 mb-2"><i class="bi bi-person-circle"></i> Profile</a>
                            <a href="logout.php" class="btn btn-outline-danger w-100"><i class="bi bi-box-arrow-right"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="mt-5 mb-4">Doctors from Our Partner Hospitals</h3>
        <?php
        $partner_hospitals = ['Dhaka Medical College Hospital', 'Square Hospitals Ltd.', 'Evercare Hospital Dhaka', 'Apollo Hospitals Dhaka', 'LabAid Specialized Hospital'];
        $placeholders = str_repeat('?,', count($partner_hospitals) - 1) . '?';
        $query_partner = "SELECT id, name, email, phone, location, hospital, specialty FROM users WHERE role = 'doctor' AND hospital IN ($placeholders) AND status = 'active'";
        $stmt_partner = $conn->prepare($query_partner);
        if ($stmt_partner) {
            $stmt_partner->bind_param(str_repeat('s', count($partner_hospitals)), ...$partner_hospitals);
            $stmt_partner->execute();
            $result_partner = $stmt_partner->get_result();
            $partner_doctors = $result_partner->fetch_all(MYSQLI_ASSOC);
            $stmt_partner->close();
        } else {
            $partner_doctors = [];
        }
        ?>
        <?php if (count($partner_doctors) > 0): ?>
            <div class="row gy-3">
                <?php foreach ($partner_doctors as $doctor): ?>
                    <div class="col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="card-title mb-1"><?php echo htmlspecialchars($doctor['name']); ?></h5>
                                <p class="text-muted small mb-2"><?php echo htmlspecialchars($doctor['specialty']); ?> • <?php echo htmlspecialchars($doctor['hospital']); ?> • <?php echo htmlspecialchars($doctor['location']); ?></p>
                                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($doctor['email']); ?></p>
                                <p class="mb-3"><strong>Phone:</strong> <?php echo htmlspecialchars($doctor['phone']); ?></p>
                                <div class="d-flex gap-2">
                                    <a href="view_availability.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-outline-primary shadow-sm">View Availability</a>
                                    <a href="book_appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-primary shadow-sm">Book Appointment</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No doctors available from partner hospitals at the moment.</div>
        <?php endif; ?>
    </main>

    <section id="about" class="py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center text-center mb-4">
                <div class="col-lg-8">
                    <h2 class="fw-bold">Find the Right Doctor Fast</h2>
                    <p class="text-muted">Search by doctor name, location or specialty and book your appointment securely with confidence.</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-4 h-100">
                        <div class="text-primary mb-3"><i class="bi bi-search fs-1"></i></div>
                        <h5 class="mb-2">Simple Search</h5>
                        <p class="mb-0 text-muted">Use filters to quickly locate a specialist that matches your needs.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-4 h-100">
                        <div class="text-primary mb-3"><i class="bi bi-calendar-check fs-1"></i></div>
                        <h5 class="mb-2">Instant Booking</h5>
                        <p class="mb-0 text-muted">Book appointment slots directly from the search results page.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-4 h-100">
                        <div class="text-primary mb-3"><i class="bi bi-person-check fs-1"></i></div>
                        <h5 class="mb-2">Trusted Doctors</h5>
                        <p class="mb-0 text-muted">Connect with licensed professionals across top specialties.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
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
</body>

</html>