<?php
session_start();
require_once "db.php";

function clean($v)
{
    return trim(htmlspecialchars($v ?? "", ENT_QUOTES, "UTF-8"));
}

// ensure password_resets table exists
$conn->query("CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(128) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(user_id)
)") or die($conn->error);

$errors = [];
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = clean($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm_password"] ?? "";

    if ($email === "" || $password === "" || $confirm === "") {
        $errors[] = "Please fill up all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $errors[] = "Password and Confirm Password do not match.";
    }

    if (!$errors) {
        $stmt = $conn->prepare("SELECT id, status FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $errors[] = "Email not found.";
        } elseif ($user["status"] !== "active") {
            $errors[] = "Account is blocked. Contact admin.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET password_hash=? WHERE id=?");
            $stmt->bind_param("si", $hash, $user["id"]);
            $stmt->execute();
            $stmt->close();

            $message = "Password has been reset successfully. You can now login.";
        }
    }
}

// Auto redirect if success
if ($message && !$errors) {
    header("refresh:3;url=login.php");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Forgot Password - HealthTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea, #764ba2);
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

        .card {
            max-width: 420px;
            width: 100%;
            border-radius: 12px;
        }
    </style>
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
                    <li class="nav-item"><a class="nav-link" href="doctor_search.php">Find Doctors</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="online_appointment.php">Online Appointment</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="page-content">
        <div class="card shadow p-4">
            <h3 class="mb-3 text-center">Forgot Password</h3>

            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?php echo clean($e); ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="alert alert-success">
                    <strong>✓ <?php echo clean($message); ?></strong><br>
                    <small>Redirecting to login in 3 seconds...</small>
                </div>
            <?php endif; ?>

            <?php if (!$message): ?>
                <form method="POST" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input class="form-control" type="email" name="email" value="<?php echo clean($_POST['email'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input class="form-control" type="password" name="password" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm new password</label>
                        <input class="form-control" type="password" name="confirm_password" required>
                    </div>

                    <button class="btn btn-dark w-100">Reset Password</button>
                    <div class="text-center mt-3"><a href="login.php" class="text-decoration-none">Back to Login</a></div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 w-100 mt-auto">
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