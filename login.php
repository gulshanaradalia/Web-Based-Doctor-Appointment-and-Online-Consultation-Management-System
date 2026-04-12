<?php
session_start();
require_once "db.php";

$errors = [];

function clean($v)
{
  return trim(htmlspecialchars($v ?? "", ENT_QUOTES, "UTF-8"));
}

if (isset($_SESSION["user_id"])) {
  header("Location: dashboard.php");
  exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = clean($_POST["email"] ?? "");
  $password = $_POST["password"] ?? "";

  if ($email === "" || $password === "") {
    $errors[] = "Please fill up all required fields.";
  }

  if (!$errors) {
    $stmt = $conn->prepare("SELECT id, name, role, password_hash, status FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();

    if (!$user) {
      $errors[] = "User not found.";
    } elseif ($user["status"] !== "active") {
      $errors[] = "Account blocked. Contact Admin.";
    } elseif (!password_verify($password, $user["password_hash"])) {
      $errors[] = "Invalid email or password.";
    } else {
      session_regenerate_id(true);
      $_SESSION["user_id"] = $user["id"];
      $_SESSION["name"] = $user["name"];
      $_SESSION["role"] = $user["role"];

      header("Location: dashboard.php");
      exit;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #667eea, #764ba2);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 5rem 20px 0;
    }

    .page-content {
      flex: 1 0 auto;
      display: flex;
      align-items: center;
      justify-content: center;
      padding-bottom: 2rem;
    }

    .card {
      max-width: 420px;
      width: 100%;
      border-radius: 12px;
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
    <div class="card shadow p-4">
      <h3 class="mb-3 text-center">Login</h3>

      <?php if ($errors): ?>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
              <li><?php echo clean($e); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input class="form-control" type="email" name="email" value="<?php echo clean($_POST["email"] ?? ""); ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Password</label>
          <input class="form-control" type="password" name="password" required>
        </div>

        <button class="btn btn-dark w-100">Login</button>

        <div class="text-center mt-3">
          <a href="forgot.php" class="text-primary fw-bold" style="text-decoration: underline;">Forgot password?</a>
        </div>
      </form>
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