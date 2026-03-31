<?php
session_start();

if(!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if($_SESSION["role"] != "patient") {
    header("Location: dashboard.php");
    exit;
}

require_once "db.php";

$query = "SELECT id, name, email, phone, location, specialty FROM users WHERE role = 'doctor'";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Search</title>
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
                    <li class="nav-item"><a class="nav-link active" href="doctor_search.php">Doctor Search</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
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

        <?php if (count($doctors) > 0): ?>
            <div class="row gy-3">
                <?php foreach ($doctors as $doctor): ?>
                    <div class="col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="card-title mb-1"><?php echo htmlspecialchars($doctor['name']); ?></h5>
                                <p class="text-muted small mb-2"><?php echo htmlspecialchars($doctor['specialty']); ?> • <?php echo htmlspecialchars($doctor['location']); ?></p>
                                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($doctor['email']); ?></p>
                                <p class="mb-3"><strong>Phone:</strong> <?php echo htmlspecialchars($doctor['phone']); ?></p>
                                <a href="book_appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-primary">Book Appointment</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No doctors found using the selected filters. Please adjust your search criteria.</div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>