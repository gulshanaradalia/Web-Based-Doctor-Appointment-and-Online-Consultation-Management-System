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
                <span>Doctor Appointment and Online Consultation</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarResponsive">
                <ul class="navbar-nav ms-auto me-3">
                    <li class="nav-item"><a class="nav-link active" href="#">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#doctors">Doctors</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="login.php"><i class="bi bi-box-arrow-in-right"></i> Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php"><i class="bi bi-person-plus"></i> Register</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero position-relative text-center text-dark d-flex align-items-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Your Health, Our Technology</h1>
            <p class="lead mb-4">Trusted doctors at your fingertips. Book appointments and online consultations instantly.</p>
            <div>
                <a href="register.php" class="btn btn-lg btn-accent me-2">Book Appointment</a>

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
                <div class="col-12">
                    <div class="d-flex align-items-center feature-item p-3">
                        <i class="bi bi-search fs-1 text-primary me-4"></i>
                        <div>
                            <h5 class="fw-semibold mb-1">Find Doctors</h5>
                            <p class="mb-0 text-muted">Filter by location, specialty or availability.</p>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="d-flex align-items-center feature-item p-3">
                        <i class="bi bi-calendar-check fs-1 text-primary me-4"></i>
                        <div>
                            <h5 class="fw-semibold mb-1">Appointment Booking</h5>
                            <p class="mb-0 text-muted">Schedule your consultation easily at any time that suits you.</p>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="d-flex align-items-center feature-item p-3">
                        <i class="bi bi-telephone-fill fs-1 text-primary me-4"></i>
                        <div>
                            <h5 class="fw-semibold mb-1">Audio/Video Calls</h5>
                            <p class="mb-0 text-muted">Connect with doctors remotely for consultation.</p>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="d-flex align-items-center feature-item p-3">
                        <i class="bi bi-file-earmark-medical-fill fs-1 text-primary me-4"></i>
                        <div>
                            <h5 class="fw-semibold mb-1">Digital Reports</h5>
                            <p class="mb-0 text-muted">Receive prescriptions and feedback online.</p>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="d-flex align-items-center feature-item p-3">
                        <i class="bi bi-credit-card-2-back-fill fs-1 text-primary me-4"></i>
                        <div>
                            <h5 class="fw-semibold mb-1">Secure Payments</h5>
                            <p class="mb-0 text-muted">Pay consultation fees safely through our portal.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h4>Contact Us</h4>
                    <p>Dhaka, Bangladesh<br>
                        Phone: +880 1234 567890<br>
                        Email: healthtech@email.com</p>
                </div>
                <div class="col-md-6">
                    <h4>Stay Connected</h4>
                    <a href="#" class="text-primary me-2"><i class="bi bi-facebook fs-3"></i></a>
                    <a href="#" class="text-info me-2"><i class="bi bi-twitter fs-3"></i></a>
                    <a href="#" class="text-danger"><i class="bi bi-instagram fs-3"></i></a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container d-flex justify-content-between align-items-center">
            <small>&copy; <?php echo date('Y'); ?> Doctor Appointment and Online Consultation System. All rights reserved.</small>
            <ul class="list-inline mb-0">
                <li class="list-inline-item"><a href="#" class="text-light">Privacy Policy</a></li>
                <li class="list-inline-item"><a href="#" class="text-light">Terms of Use</a></li>
            </ul>
        </div>
    </footer>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>