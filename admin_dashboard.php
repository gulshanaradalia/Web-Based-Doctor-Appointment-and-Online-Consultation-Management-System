<?php
session_start();

if(!isset($_SESSION["user_id"])){
    header("Location: login.php");
    exit;
}
?>

<h2>Admin Dashboard</h2>

<p>Welcome Admin <?php echo $_SESSION["name"]; ?></p>

<a href="logout.php">Logout</a>