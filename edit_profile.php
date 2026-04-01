<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';

$user_id = $_SESSION['user_id'];

function clean($v) { return trim(htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8')); }

$errors = [];
$success = false;

$stmt = $conn->prepare('SELECT id, name, email, phone, role, location, specialty FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $phone = clean($_POST['phone'] ?? '');
    $location = clean($_POST['location'] ?? '');
    $specialty = clean($_POST['specialty'] ?? '');

    if ($name === '' || $email === '' || $phone === '') {
        $errors[] = 'Name, Email and Phone are required.';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }

    if (empty($errors)) {
        $exists_stmt = $conn->prepare('SELECT id FROM users WHERE (email = ? OR phone = ?) AND id != ? LIMIT 1');
        $exists_stmt->bind_param('ssi', $email, $phone, $user_id);
        $exists_stmt->execute();
        $exists = $exists_stmt->get_result()->fetch_assoc();
        $exists_stmt->close();

        if ($exists) {
            $errors[] = 'Email or phone is already used by another user.';
        }
    }

    if (empty($errors)) {
        $update_stmt = $conn->prepare('UPDATE users SET name = ?, email = ?, phone = ?, location = ?, specialty = ? WHERE id = ?');
        $update_stmt->bind_param('sssssi', $name, $email, $phone, $location, $specialty, $user_id);
        $update_stmt->execute();
        $update_stmt->close();

        $_SESSION['name'] = $name;
        $success = true;

        $user['name'] = $name;
        $user['email'] = $email;
        $user['phone'] = $phone;
        $user['location'] = $location;
        $user['specialty'] = $specialty;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Edit Profile - Doctor Appointment</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="style.css" rel="stylesheet">
<style>
  body { background: #eef3ff; }
  .back-btn { width: 46px; height: 46px; border-radius: 13px; border: 1px solid #d7dde9; display: inline-flex; align-items: center; justify-content: center; }
  .form-card { border-radius: 18px; border: 1px solid #dbe0eb; box-shadow: 0 14px 28px rgba(0,0,0,0.08); }
</style>
</head>
<body>
<nav class="navbar navbar-light bg-white shadow-sm">
  <div class="container">
    <a href="profile.php" class="back-btn text-dark"><i class="bi bi-arrow-left"></i></a>
    <h4 class="mb-0 ms-2">Edit Profile</h4>
  </div>
</nav>
<main class="container mt-4 mb-5">
  <div class="row justify-content-center">
    <div class="col-lg-7">
      <div class="card form-card p-4">
        <?php if ($success): ?>
          <div class="alert alert-success">Profile updated successfully.</div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?php echo clean($err); ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="POST">
          <div class="mb-3"><label class="form-label">Full name</label><input type="text" name="name" class="form-control" value="<?php echo clean($user['name']); ?>" required></div>
          <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?php echo clean($user['email']); ?>" required></div>
          <div class="mb-3"><label class="form-label">Phone</label><input type="tel" name="phone" class="form-control" value="<?php echo clean($user['phone']); ?>" required></div>
          <div class="mb-3"><label class="form-label">Location</label><input type="text" name="location" class="form-control" value="<?php echo clean($user['location']); ?>"></div>
          <div class="mb-3"><label class="form-label">Specialty</label><input type="text" name="specialty" class="form-control" value="<?php echo clean($user['specialty']); ?>" <?php echo $user['role']==='doctor' ? '' : 'readonly'; ?>></div>

          <button type="submit" class="btn btn-dark w-100"><i class="bi bi-save me-1"></i>Save Changes</button>
        </form>
      </div>
    </div>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>