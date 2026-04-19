<?php
session_start();
require_once "db.php";


$errors = [];
$success = "";


function clean($v)
{
    return trim(htmlspecialchars($v ?? "", ENT_QUOTES, "UTF-8"));
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = clean($_POST["name"] ?? "");
    $email = clean($_POST["email"] ?? "");
    $phone = clean($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm_password"] ?? "";
    $role = clean($_POST["role"] ?? "patient");


    // Validation
    if ($name === "" || $email === "" || $phone === "" || $password === "" || $confirm === "") {
        $errors[] = "Please fill up all required fields.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (!in_array($role, ["patient", "doctor"], true)) {
        $errors[] = "Invalid role.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    if ($password !== $confirm) {
        $errors[] = "Password and Confirm Password do not match.";
    }


    // Check duplicate
    if (!$errors) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=? OR phone=? LIMIT 1");
        $stmt->bind_param("ss", $email, $phone);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $errors[] = "Email or phone already registered.";
        }
        $stmt->close();
    }


    // Insert user
    if (!$errors) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (name,email,phone,role,password_hash,status) VALUES (?,?,?,?,?,'active')");
        $stmt->bind_param("sssss", $name, $email, $phone, $role, $hash);
        $stmt->execute();
        $stmt->close();
        $success = "Successfully Registered! Now you can login.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – DoctorApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        body {
            background: #eef3ff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding-top: 5rem;
        }

        .page-content {
            flex: 1 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 20px;
        }

        .auth-card {
            max-width: 500px;
            width: 100%;
            border-radius: 16px;
            border: 1px solid #dbe0eb;
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.08);
            background: #fff;
        }

        .card-body {
            padding: 2.5rem;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
        }

        .btn-register {
            background: #000;
            border: none;
            border-radius: 8px;
            padding: 0.75rem;
            font-weight: 600;
            transition: all 0.2s;
            color: white;
        }

        .btn-register:hover {
            opacity: 0.9;
            color: white;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .required::after {
            content: " *";
            color: #dc3545;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #666;
        }

        .login-link a {
            color: var(--bs-primary);
            text-decoration: none;
            font-weight: 600;
        }

        h2 {
            font-weight: 700;
            color: #22306b;
        }

        .subtitle {
            color: #777;
            margin-bottom: 2rem;
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
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
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
                    <li class="nav-item"><a class="nav-link" href="online_appointment.php">Online Appointment</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="page-content">
        <div class="card auth-card">
            <div class="card-body">
                <h2><i class="bi bi-person-plus-fill"></i> Create Account</h2>
                <p class="subtitle">Register as a Patient or Doctor</p>


                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
                        <a href="login.php" class="btn btn-sm btn-primary ms-2">Go to Login</a>
                    </div>
                <?php else: ?>
                    <?php if ($errors): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong><i class="bi bi-exclamation-circle-fill"></i> Please fix the errors below:</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($errors as $e): ?>
                                    <li><?php echo clean($e); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>


                    <form method="POST" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">Name</label>
                                <input type="text" name="name" class="form-control"
                                    value="<?php echo clean($_POST['name'] ?? ''); ?>" required>
                            </div>


                            <div class="col-md-6">
                                <label class="form-label required">Email</label>
                                <input type="email" name="email" class="form-control"
                                    value="<?php echo clean($_POST['email'] ?? ''); ?>" required>
                            </div>


                            <div class="col-md-6">
                                <label class="form-label required">Phone</label>
                                <input type="tel" name="phone" class="form-control"
                                    value="<?php echo clean($_POST['phone'] ?? ''); ?>" required>
                            </div>


                            <div class="col-md-6">
                                <label class="form-label required">Role</label>
                                <select name="role" class="form-select" required>
                                    <option value="">Select Role</option>
                                    <option value="patient" <?php echo (($_POST['role'] ?? '') === 'patient') ? 'selected' : ''; ?>>Patient</option>
                                    <option value="doctor" <?php echo (($_POST['role'] ?? '') === 'doctor') ? 'selected' : ''; ?>>Doctor</option>
                                </select>
                            </div>


                            <div class="col-md-6">
                                <label class="form-label required">Password</label>
                                <input type="password" name="password" class="form-control" required>
                                <small class="text-muted">At least 6 characters</small>
                            </div>


                            <div class="col-md-6">
                                <label class="form-label required">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                        </div>


                        <button type="submit" class="btn btn-register w-100 mt-4">
                            <i class="bi bi-person-check-fill"></i> Create Account
                        </button>


                        <div class="login-link">
                            Already have an account? <a href="login.php">Login Here</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <footer class="bg-dark text-light py-4 w-100">
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