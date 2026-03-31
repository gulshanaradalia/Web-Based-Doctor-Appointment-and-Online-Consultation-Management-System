<?php
session_start();


if(!isset($_SESSION["user_id"])){
    header("Location: login.php");
    exit;
}


if($_SESSION["role"] != "patient"){
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Patient Dashboard</title>
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
                <li class="nav-item"><a class="nav-link" href="doctor_search.php">Search Doctors</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="container mt-5 pt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 text-center">
            <h2 class="mb-3">Hello, <?php echo htmlspecialchars($_SESSION["name"]); ?></h2>
            <p class="text-muted">Use the button below to search and book the right doctor.</p>
            <a class="btn btn-accent btn-lg" href="doctor_search.php">Go to Doctor Search</a>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>