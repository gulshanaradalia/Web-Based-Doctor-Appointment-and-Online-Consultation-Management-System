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
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $phone === '') {
        $errors[] = 'Name, Email and Phone are required.';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT id FROM users WHERE (email = ? OR phone = ?) AND id != ? LIMIT 1');
        $stmt->bind_param('ssi', $email, $phone, $user_id);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($exists) {
            $errors[] = 'Email or phone already used by another user.';
        }
    }

    if ($old_password !== '' || $new_password !== '' || $confirm_password !== '') {
        if ($old_password === '' || $new_password === '' || $confirm_password === '') {
            $errors[] = 'Fill in all password fields to change password.';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'New password and confirmation do not match.';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'New password should be at least 6 characters.';
        } else {
            $passStmt = $conn->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
            $passStmt->bind_param('i', $user_id);
            $passStmt->execute();
            $passData = $passStmt->get_result()->fetch_assoc();
            $passStmt->close();

            if (!$passData || !password_verify($old_password, $passData['password_hash'])) {
                $errors[] = 'Current password is incorrect.';
            }
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare('UPDATE users SET name = ?, email = ?, phone = ?, location = ?, specialty = ? WHERE id = ?');
        $stmt->bind_param('sssssi', $name, $email, $phone, $location, $specialty, $user_id);
        $stmt->execute();
        $stmt->close();

        if ($old_password !== '' && $new_password !== '' && $confirm_password !== '') {
            $hash = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->bind_param('si', $hash, $user_id);
            $stmt->execute();
            $stmt->close();
        }

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
    body { background: #f5f8ff; }
    .card-custom { border-radius: 20px; border: 1px solid #dfe6ef; box-shadow: 0 12px 26px rgba(0, 0, 0, 0.08); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; border: 1px solid #d7dde9; display: inline-flex; align-items: center; justify-content: center; }
</style>
</head>
<body>
<header class="p-3" style="background: #fff; border-bottom:1px solid #e9edf4;">
    <div class="d-flex align-items-center">
        <a href="profile.php" class="back-btn text-dark"><i class="bi bi-arrow-left"></i></a>
        <h4 class="ms-3 mb-0">Edit Profile</h4>
    </div>
</header>
<main class="container mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card card-custom p-4">
                <?php if ($success): ?>
                    <div class="alert alert-success">Profile updated successfully.</div>
                <?php endif; ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo clean($error); ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3"><label class="form-label">Full Name</label><input type="text" name="name" class="form-control" value="<?php echo clean($user['name']); ?>" required></div>
                    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?php echo clean($user['email']); ?>" required></div>
                    <div class="mb-3"><label class="form-label">Phone</label><input type="tel" name="phone" class="form-control" value="<?php echo clean($user['phone']); ?>" required></div>
                    <div class="mb-3"><label class="form-label">Location</label><input type="text" name="location" class="form-control" value="<?php echo clean($user['location']); ?>"></div>
                    <div class="mb-3"><label class="form-label">Specialty</label><input type="text" name="specialty" class="form-control" value="<?php echo clean($user['specialty']); ?>" <?php echo $user['role'] === 'doctor' ? '' : 'readonly'; ?>></div>

                    <div class="pt-3 pb-2 border-top mt-3"><h6>Change Password (optional)</h6></div>
                    <div class="mb-3"><input type="password" name="old_password" class="form-control" placeholder="Current password"></div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6"><input type="password" name="new_password" class="form-control" placeholder="New password"></div>
                        <div class="col-md-6"><input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password"></div>
                    </div>

                    <button class="btn btn-dark w-100" type="submit"><i class="bi bi-save me-2"></i>Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>