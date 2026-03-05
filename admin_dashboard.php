<?php
session_start();

/* User Authentication Check */
if(!isset($_SESSION["user_id"])){
    header("Location: login.php");
    exit;
}

/* Role check (only admin allowed) */
if($_SESSION["role"] != "admin"){
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
</head>

<body>

<h2>Admin Dashboard</h2>

<p>Welcome Admin <?php echo $_SESSION["name"]; ?></p>

<br>

<a href="logout.php">Logout</a>

</body>
</html>