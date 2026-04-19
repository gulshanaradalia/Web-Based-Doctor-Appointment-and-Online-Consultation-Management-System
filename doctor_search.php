<?php
session_start();

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

// Fetch all doctor names for the dropdown
$all_doctors = [];
$name_results = $conn->query("SELECT DISTINCT name FROM users WHERE role = 'doctor' ORDER BY name ASC");
if ($name_results) {
    while ($row = $name_results->fetch_assoc()) {
        $all_doctors[] = $row['name'];
    }
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
                <ul class="navbar-nav ms-auto me-3">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                    <li class="nav-item"><a class="nav-link active" href="doctor_search.php">Find Doctors</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="online_appointment.php">Online Appointment</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mt-5 pt-5 pb-5">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 border-end pe-4">
                <h4 class="mb-4" style="border-bottom: 2px solid #17a2b8; display: inline-block; padding-bottom: 8px; color: #333;">Find A Doctor</h4>
                <div class="nav flex-column nav-pills me-3" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                    <button class="nav-link active text-start py-3 mb-2 text-muted" id="v-pills-name-tab" data-bs-toggle="pill" data-bs-target="#v-pills-name" type="button" role="tab" style="font-size: 1rem; background: transparent; border-radius: 0;">Doctor Name Wise</button>
                    
                    <button class="nav-link text-start py-3 mb-2 text-muted" id="v-pills-hospital-tab" data-bs-toggle="pill" data-bs-target="#v-pills-hospital" type="button" role="tab" style="font-size: 1rem; background: transparent; border-radius: 0;">Hospital Wise</button>
                    
                    <button class="nav-link text-start py-3 text-muted" id="v-pills-specialty-tab" data-bs-toggle="pill" data-bs-target="#v-pills-specialty" type="button" role="tab" style="font-size: 1rem; background: transparent; border-radius: 0;">Specialty Wise</button>
                </div>
            </div>
            
            <!-- Main Content Area -->
            <div class="col-md-9 ps-4">
                
                <!-- IF NOT SEARCHING, SHOW TABS -->
                <?php if (empty($_GET['name']) && empty($_GET['specialty']) && empty($_GET['location'])): ?>
                
                <div class="tab-content" id="v-pills-tabContent">
                    <!-- Search by Doctor Name Tab -->
                    <div class="tab-pane fade show active" id="v-pills-name" role="tabpanel">
                        <div class="p-3 mb-4 text-center text-white" style="background-color: #17a2b8; font-size: 1.8rem; font-weight: bold; letter-spacing: 1px; font-family: serif;">FIND YOUR DOCTOR</div>
                        <form method="get" action="doctor_search.php">
                            <div class="mt-4">
                                <label style="color: #17a2b8; font-weight: 600; font-size: 0.95rem;" class="mb-2">Search by Doctor Name</label>
                                <div class="search-wrapper">
                                    <input type="text" name="name" id="doctorSearchInput" class="form-control border-1 shadow-none py-2 text-muted" placeholder="Type or select Doctor Name" autocomplete="off">
                                    <div id="doctorSuggestions" class="suggestion-list"></div>
                                </div>

                            </div>
                            <div class="mt-4 text-end">
                                <button type="submit" class="btn text-white px-4" style="background-color: #17a2b8;">Search</button>
                            </div>
                        </form>
                    </div>

                    <!-- Search by Hospital Name Tab -->
                    <div class="tab-pane fade" id="v-pills-hospital" role="tabpanel">
                        <div class="p-3 mb-4 text-center text-white" style="background-color: #17a2b8; font-size: 1.8rem; font-weight: bold; letter-spacing: 1px; font-family: serif;">FIND YOUR DOCTOR</div>
                        <form method="get" action="doctor_search.php">
                            <div class="mt-4">
                                <label style="color: #17a2b8; font-weight: 600; font-size: 0.95rem;" class="mb-2">Search by Hospital Name</label>
                                <select name="location" class="form-select border-1 shadow-none py-2 text-muted">
                                    <option value="">Select Hospital Name</option>
                                    <option value="Dhaka Medical College">Dhaka Medical College Hospital</option>
                                    <option value="Square Hospital">Square Hospital</option>
                                    <option value="Evercare Hospital">Evercare Hospital</option>
                                    <option value="Labaid Hospital">Labaid Hospital</option>
                                    <option value="United Hospital">United Hospital</option>
                                    <option value="Ibne Sina Hospital">Ibne Sina Hospital</option>
                                    <option value="Popular Medical College">Popular Medical College Hospital</option>
                                    <option value="BIRDEM General Hospital">BIRDEM General Hospital</option>
                                    <option value="BSMMU">Bangabandhu Sheikh Mujib Medical University</option>
                                    <option value="Enam Medical College">Enam Medical College & Hospital</option>
                                </select>
                            </div>
                            <div class="mt-4 text-end">
                                <button type="submit" class="btn text-white px-4" style="background-color: #17a2b8;">Search</button>
                            </div>
                        </form>
                    </div>

                    <!-- Search by Specialty Tab -->
                    <div class="tab-pane fade" id="v-pills-specialty" role="tabpanel">
                        <div class="p-3 mb-4 text-center text-white" style="background-color: #17a2b8; font-size: 1.8rem; font-weight: bold; letter-spacing: 1px; font-family: serif;">FIND YOUR DOCTOR</div>
                        <form method="get" action="doctor_search.php">
                            <div class="mt-4">
                                <label style="color: #17a2b8; font-weight: 600; font-size: 0.95rem;" class="mb-2">Search By Speciality/Department name</label>
                                <select name="specialty" class="form-select border-1 shadow-none py-2 text-muted">
                                    <option value="">Search By Speciality/Department name</option>
                                    <option value="Cardiology Specialist">Cardiology Specialist</option>
                                    <option value="Chest Specialist / Pulmonologist">Chest Specialist / Pulmonologist / Respiratory Medicine Specialist</option>
                                    <option value="Dermatologist">Dermatologist</option>
                                    <option value="Diabetic Specialist">Diabetic Specialist</option>
                                    <option value="Diet & Nutritionist">Diet & Nutritionist</option>
                                    <option value="E N T Specialist">E N T Specialist and Head Neck Surgeon</option>
                                    <option value="Endocrinology Specialist">Endocrinology Specialist</option>
                                    <option value="Gastroenterology">Gastroenterology, Liver & Medicine Specialist</option>
                                    <option value="General and Laparoscopic Surgery">General and Laparoscopic Surgery Specialist</option>
                                    <option value="General Practitioner">General Practitioner</option>
                                    <option value="Gynecologist">Gynecologist, Obstetrician and Specialist</option>
                                    <option value="Haematology Specialist">Haematology Specialist</option>
                                    <option value="Hepatology Specialist">Hepatology Specialist</option>
                                    <option value="Medicine & Rheumatology">Medicine & Rheumatology Specialist</option>
                                    <option value="Medicine Specialist">Medicine Specialist</option>
                                    <option value="Nephrology Specialist">Nephrology Specialist</option>
                                    <option value="Neuro Medicine">Neuro Medicine Specialist</option>
                                    <option value="Neurosurgeon">Neurosurgeon</option>
                                    <option value="Oncologist">Oncologist</option>
                                    <option value="Ophthalmology Specialist">Ophthalmology Specialist</option>
                                    <option value="Orthopedic Specialist">Orthopedic Specialist and Surgeon</option>
                                    <option value="Pediatrician">Pediatrician</option>
                                    <option value="Physical Medicine">Physical Medicine Specialist</option>
                                    <option value="Psychiatrist">Psychiatrist</option>
                                    <option value="Urology Specialist">Urology Specialist and Surgeon</option>
                                </select>
                            </div>
                            <div class="mt-4 text-end">
                                <button type="submit" class="btn text-white px-4" style="background-color: #17a2b8;">Search</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php else: ?>
                
                <!-- IF SEARCHING, SHOW RESULTS -->
                <div class="p-3 mb-4 text-center text-white" style="background-color: #17a2b8; font-size: 1.8rem; font-weight: bold; letter-spacing: 1px; font-family: serif;">SEARCH RESULTS</div>
                <div class="mb-4 text-end">
                    <a href="doctor_search.php" class="btn btn-outline-info border-1">Back to Search</a>
                </div>
                
                <?php if (count($doctors) > 0): ?>
                    <div class="row gy-4">
                        <?php foreach ($doctors as $doctor): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card shadow-sm h-100 border-0" style="border-top: 4px solid #17a2b8 !important; border-radius: 8px;">
                                    <div class="card-body p-4">
                                        <h5 class="card-title mb-1 text-dark fw-bold"><?php echo htmlspecialchars($doctor['name']); ?></h5>
                                        <p class="text-info small mb-3 fw-semibold uppercase" style="letter-spacing: 0.5px;"><?php echo htmlspecialchars($doctor['specialty']); ?></p>
                                        <div class="mb-3">
                                            <p class="mb-1 text-muted small"><i class="bi bi-geo-alt me-2"></i><?php echo htmlspecialchars($doctor['location']); ?></p>
                                            <p class="mb-1 text-muted small"><i class="bi bi-envelope me-2"></i><?php echo htmlspecialchars($doctor['email']); ?></p>
                                            <p class="mb-0 text-muted small"><i class="bi bi-telephone me-2"></i><?php echo htmlspecialchars($doctor['phone']); ?></p>
                                        </div>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <a href="book_appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-sm text-white flex-grow-1 py-2" style="background-color: #17a2b8; font-weight: 600;">Book Appointment</a>
                                            <a href="consultation.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-sm btn-outline-success flex-grow-1 py-2" style="font-weight: 600;">Consult Online</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning border-0 shadow-sm p-4 text-center">
                        <p class="text-muted mb-0">No doctors found using the selected filters. Please try broad filters.</p>
                    </div>
                <?php endif; ?>
                
                <?php endif; ?>
                
            </div>
        </div>
         
        <style>
            .nav-pills .nav-link.active {
                background-color: transparent !important;
                color: #17a2b8 !important;
                font-weight: bold;
            }
            .uppercase { text-transform: uppercase; }
        </style>

        <h3 class="mt-5 mb-4">Recommended Doctors</h3>
        <?php
        $query_partner = "SELECT id, name, email, phone, location, specialty FROM users WHERE role = 'doctor' AND status = 'active' LIMIT 4";
        $stmt_partner = $conn->prepare($query_partner);
        if ($stmt_partner) {
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
                                <p class="text-muted small mb-2"><?php echo htmlspecialchars($doctor['specialty']); ?> • <?php echo htmlspecialchars($doctor['location']); ?></p>
                                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($doctor['email']); ?></p>
                                <p class="mb-3"><strong>Phone:</strong> <?php echo htmlspecialchars($doctor['phone']); ?></p>
                                <div class="d-flex gap-2">
                                    <a href="book_appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-primary">Book Appointment</a>
                                    <a href="consultation.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-success">Online Consultation</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No recommended doctors available at the moment.</div>
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
    <script>
        const doctorNames = <?php echo json_encode($all_doctors); ?>;
        const searchInput = document.getElementById('doctorSearchInput');
        const suggestionsBox = document.getElementById('doctorSuggestions');

        if (searchInput && suggestionsBox) {
            searchInput.addEventListener('input', function() {
                const val = this.value.toLowerCase();
                suggestionsBox.innerHTML = '';
                if (!val) {
                    suggestionsBox.style.display = 'none';
                    return;
                }

                const matches = doctorNames.filter(name => name.toLowerCase().includes(val));
                if (matches.length > 0) {
                    matches.forEach(name => {
                        const div = document.createElement('div');
                        div.className = 'suggestion-item';
                        div.textContent = name;
                        div.onclick = function() {
                            searchInput.value = name;
                            suggestionsBox.style.display = 'none';
                        };
                        suggestionsBox.appendChild(div);
                    });
                    suggestionsBox.style.display = 'block';
                } else {
                    suggestionsBox.style.display = 'none';
                }
            });

            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                    suggestionsBox.style.display = 'none';
                }
            });
            
            searchInput.addEventListener('focus', function() {
                if(this.value.length > 0) suggestionsBox.style.display = 'block';
            });
        }
    </script>

</body>

</html>