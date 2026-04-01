<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';

$user_id = $_SESSION['user_id'];

$errors = [];
$success = false;

$stmt = $conn->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($old_password === '' || $new_password === '' || $confirm_password === '') {
        $errors[] = 'Old password, new password and confirm password are required.';
    } else {
        if (!password_verify($old_password, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        }
        if (strlen($new_password) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        }
        if ($new_password !== $confirm_password) {
            $errors[] = 'New password and confirm password do not match.';
        }
    }

    if (empty($errors)) {
        $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
        $update_stmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $update_stmt->bind_param('si', $new_hash, $user_id);
        $update_stmt->execute();
        $update_stmt->close();

        $success = true;
        $user['password_hash'] = $new_hash;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Change Password - Doctor Appointment</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="style.css" rel="stylesheet">
<style>
  body { background: #eef3ff; }
  .auth-container { max-width: 420px; margin: 2rem auto; }
  .card { border-radius: 16px; border: 1px solid #dbe0eb; box-shadow: 0 14px 28px rgba(0,0,0,0.08); }
</style>
</head>
<body>
<nav class="navbar navbar-light bg-white shadow-sm">
  <div class="container">
    <a href="profile.php" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    <h5 class="mb-0">Change Password</h5>
  </div>
</nav>
<div class="auth-container">
  <div class="card p-4">
    <?php if ($success): ?>
      <div class="alert alert-success">Password updated successfully.</div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Current Password</label>
        <input type="password" name="old_password" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">New Password</label>
        <input type="password" name="new_password" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Confirm Password</label>
        <input type="password" name="confirm_password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-dark w-100"><i class="bi bi-key-fill me-1"></i>Update Password</button>
    </form>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
