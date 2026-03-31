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
$success = "";
$token = clean($_GET["token"] ?? $_POST["token"] ?? "");

if (!$token) {
    $errors[] = "Invalid reset token.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && !$errors) {
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm_password"] ?? "";

    if ($password === "" || $confirm === "") {
        $errors[] = "Please fill all required fields.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $errors[] = "Password and Confirm Password do not match.";
    }

    if (!$errors) {
        $stmt = $conn->prepare("SELECT u.id FROM users u JOIN password_resets r ON u.id = r.user_id WHERE r.token=? AND r.expires_at > NOW() LIMIT 1");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $errors[] = "Invalid or expired reset link.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET password_hash=? WHERE id=?");
            $stmt->bind_param("si", $hash, $user["id"]);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id=?");
            $stmt->bind_param("i", $user["id"]);
            $stmt->execute();
            $stmt->close();

            $success = "Password has been reset successfully. You can now log in.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reset Password</title>
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
        <h3 class="mb-3 text-center">Reset Password</h3>

        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?php echo clean($e); ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo clean($success); ?> <a href="login.php">Login</a></div>
        <?php endif; ?>

        <?php if (!$success): ?>
            <form method="POST" novalidate>
                <input type="hidden" name="token" value="<?php echo clean($token); ?>">

                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input class="form-control" type="password" name="password" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input class="form-control" type="password" name="confirm_password" required>
                </div>

                <button class="btn btn-dark w-100">Reset Password</button>
                <div class="text-center mt-3"><a href="login.php" class="text-decoration-none">Back to Login</a></div>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>