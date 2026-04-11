<?php
// Start session if needed
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Appointment & Online Consultation</title>
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
            <a class="navbar-brand d-flex align-items-center" href="#">
                <i class="bi bi-heart-pulse-fill me-2"></i>
                <span>HealthTech</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarResponsive">
                <ul class="navbar-nav ms-auto me-3">
                    <li class="nav-item"><a class="nav-link active" href="#">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                    <li class="nav-item"><a class="nav-link" href="doctor_search.php">Find Doctors</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link btn btn-light btn-sm text-primary px-3" href="register.php">Online Appointment</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero position-relative text-center text-dark d-flex align-items-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Your Health, Our Technology</h1>
            <p class="lead mb-4">Trusted doctors at your fingertips. Book appointments and online consultations instantly.</p>
            <div class="d-flex flex-column align-items-center">
                <a href="login.php" class="btn btn-lg btn-dark mb-3 w-100" style="max-width: 320px;">Login</a>
                <a href="register.php" class="btn btn-lg btn-accent w-100" style="max-width: 320px;">Register</a>
            </div>
        </div>
    </header>

    <!-- About Section (alternate list design) -->
    <section id="about" class="py-5">
        <div class="container">
            <div class="row justify-content-center mb-5">
                <div class="col-lg-8 text-center">
                    <h2 class="fw-bold">Everything You Need in One Place</h2>
                    <p class="text-muted">Easily search, book and consult with doctors—all from our secure web portal.</p>
                </div>
            </div>
            <div class="row g-4">
                <?php $homeSteps = [
                    ['title' => 'Find Doctors', 'description' => 'Filter by location, specialty or availability.', 'icon' => 'bi-search'],
                    ['title' => 'Appointment Booking', 'description' => 'Schedule your consultation easily at any time that suits you.', 'icon' => 'bi-calendar-check'],
                    ['title' => 'Online Consultation', 'description' => 'Start audio or video calls with specialists from home.', 'icon' => 'bi-camera-video'],
                    ['title' => 'Digital Reports', 'description' => 'Receive prescriptions and feedback online.', 'icon' => 'bi-file-earmark-medical-fill'],
                    ['title' => 'Secure Payments', 'description' => 'Pay consultation fees safely through our portal.', 'icon' => 'bi-credit-card-2-back-fill'],
                ]; ?>
                <?php foreach ($homeSteps as $index => $step): ?>
                    <div class="col-12">
                        <div class="card rounded-4 shadow-sm border-0 p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary text-white" style="width:54px; height:54px; font-size:1.1rem; font-weight:700;">
                                    <?php echo $index + 1; ?>
                                </div>
                                <div>
                                    <h5 class="fw-semibold mb-1"><?php echo htmlspecialchars($step['title']); ?></h5>
                                    <p class="mb-0 text-muted"><?php echo htmlspecialchars($step['description']); ?></p>
                                </div>
                                <div class="ms-auto text-primary fs-2">
                                    <i class="bi <?php echo htmlspecialchars($step['icon']); ?>"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
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

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>