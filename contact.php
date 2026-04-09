<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact | HealthTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>

<body style="display:flex; flex-direction:column; min-height:100vh;">
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
                    <li class="nav-item"><a class="nav-link active" href="contact.php">Contact</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="login.php"><i class="bi bi-box-arrow-in-right"></i> Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php"><i class="bi bi-person-plus"></i> Register</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="hero position-relative text-center text-dark d-flex align-items-center" style="min-height: 320px; padding-top: 6rem;">
        <div class="container">
            <h1 class="display-5 fw-bold mb-3">Contact HealthTech</h1>
            <p class="lead mb-4">Reach the right hospital and support team for appointments and urgent care.</p>
        </div>
    </header>

    <section class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-stretch gy-4">
                <div class="col-lg-6">
                    <div class="bg-white rounded-4 shadow-sm p-4 h-100">
                        <div class="d-flex align-items-center mb-4">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width:56px; height:56px;">
                                <i class="bi bi-telephone-fill fs-4"></i>
                            </div>
                            <div>
                                <h2 class="h4 mb-1">Hospital Contacts</h2>
                                <p class="mb-0 text-muted">Contact details for Dhaka hospitals and appointment support.</p>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="border rounded-3 p-3">
                                    <h5 class="mb-1">Dhaka Medical College Hospital</h5>
                                    <p class="mb-1 text-muted">Shahid Tajuddin Ahmed Rd, Dhaka 1000</p>
                                    <p class="mb-0"><strong>Phone:</strong> 01312345601</p>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border rounded-3 p-3">
                                    <h5 class="mb-1">Square Hospitals Ltd.</h5>
                                    <p class="mb-1 text-muted">Plot 10, Road 18, Banani, Dhaka 1213</p>
                                    <p class="mb-0"><strong>Phone:</strong> 01312345602</p>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border rounded-3 p-3">
                                    <h5 class="mb-1">Evercare Hospital Dhaka</h5>
                                    <p class="mb-1 text-muted">Plot 1, Bir Uttam C.R. Dutta Rd, Dhaka 1205</p>
                                    <p class="mb-0"><strong>Phone:</strong> 01312345603</p>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border rounded-3 p-3">
                                    <h5 class="mb-1">Apollo Hospitals Dhaka</h5>
                                    <p class="mb-1 text-muted">House 67, Road 11, Block E, Banani, Dhaka 1213</p>
                                    <p class="mb-0"><strong>Phone:</strong> 01312345604</p>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border rounded-3 p-3">
                                    <h5 class="mb-1">LabAid Specialized Hospital</h5>
                                    <p class="mb-1 text-muted">34 North South Rd, Dhaka 1212</p>
                                    <p class="mb-0"><strong>Phone:</strong> 01312345605</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="bg-white rounded-4 shadow-sm p-4 h-100">
                        <h5 class="mb-4">General Support</h5>
                        <p class="mb-3"><strong>Address:</strong> Dhaka, Bangladesh</p>
                        <p class="mb-3"><strong>Phone:</strong> 01312345678</p>
                        <p class="mb-3"><strong>Email:</strong> Healthtech@gmail.com</p>
                        <div class="ratio ratio-16x9 rounded-4 overflow-hidden shadow-sm mt-4">
                            <iframe class="w-100 h-100" style="border:0;" loading="lazy" allowfullscreen
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3651.9028233388807!2d90.3882573744847!3d23.75696709102795!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3755c7d8b0844465%3A0x7b0e1c4f8d7fb8df!2sDhaka%20Medical%20College%20Hospital!5e0!3m2!1sen!2sbd!4v1700000000000!5m2!1sen!2sbd">
                            </iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-dark text-light py-4 mt-auto">
        <div class="container text-center">
            <h5>Stay Connected</h5>
            <a href="#" class="text-primary me-2"><i class="bi bi-facebook fs-3"></i></a>
            <a href="#" class="text-info me-2"><i class="bi bi-twitter fs-3"></i></a>
            <a href="#" class="text-danger"><i class="bi bi-instagram fs-3"></i></a>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>