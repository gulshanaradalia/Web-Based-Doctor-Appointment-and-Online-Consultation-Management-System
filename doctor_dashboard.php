<?php
session_start();

/* User Authentication Check */
if(!isset($_SESSION["user_id"])){
    header("Location: login.php");
    exit;
}

/* Role check (only doctor allowed) */
if($_SESSION["role"] != "doctor"){
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Doctor Dashboard</title>
</head>

<body>

<h2>Doctor Dashboard</h2>

<p>Welcome Dr. <?php echo $_SESSION["name"]; ?></p>

<br>

<a href="logout.php">Logout</a>

</body>
</html>