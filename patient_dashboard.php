<?php
session_start();

if(!isset($_SESSION["user_id"])){
    header("Location: login.php");
    exit;
}
?>

<h2>Patient Dashboard</h2>

<p>Welcome <?php echo $_SESSION["name"]; ?></p>

<a href="logout.php">Logout</a>