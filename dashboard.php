<?php
session_start();

if(!isset($_SESSION["user_id"])){
    header("Location: login.php");
    exit;
}

$role = $_SESSION["role"];

if($role == "patient"){
    header("Location: patient_dashboard.php");
}
elseif($role == "doctor"){
    header("Location: doctor_dashboard.php");
}
elseif($role == "admin"){
    header("Location: admin_dashboard.php");
}
?>