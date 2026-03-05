<?php
session_start();

/* User Authentication check */
if(!isset($_SESSION["user_id"])){
    header("Location: login.php");
    exit;
}

/* Role based redirect */
$role = $_SESSION["role"];

if($role == "patient"){
    header("Location: patient_dashboard.php");
    exit;
}
elseif($role == "doctor"){
    header("Location: doctor_dashboard.php");
    exit;
}
elseif($role == "admin"){
    header("Location: admin_dashboard.php");
    exit;
}
else{
    echo "Invalid user role";
}
?>