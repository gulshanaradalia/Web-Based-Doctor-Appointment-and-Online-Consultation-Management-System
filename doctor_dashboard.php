<?php
session_start();

if(!isset($_SESSION["user_id"])){
    header("Location: login.php");
    exit;
}
?>

<h2>Doctor Dashboard</h2>

<p>Welcome Dr. <?php echo $_SESSION["name"]; ?></p>

<a href="logout.php">Logout</a>