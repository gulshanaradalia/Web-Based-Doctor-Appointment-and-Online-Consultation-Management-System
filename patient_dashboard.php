<?php
session_start();


if(!isset($_SESSION["user_id"])){
    header("Location: login.php");
    exit;
}


if($_SESSION["role"] != "patient"){
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Patient Dashboard</title>
</head>

<body>

<h2>Patient Dashboard</h2>

<p>Welcome <?php echo $_SESSION["name"]; ?></p>

<br>

<a href="logout.php">Logout</a>

</body>
</html>