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
  <style>
    body {
      background: linear-gradient(135deg, #667eea, #764ba2);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .card {
      max-width: 420px;
      width: 100%;
      border-radius: 12px;
    }
  </style>
</head>

<body>
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
</body>

</html>