<?php

$host = "localhost";
$user = "root"; 
$password = ""; 
$database = "monopoly";
mysqli_report(MYSQLI_REPORT_OFF);

$mysqli = @new mysqli($host, $user, $password, $database);

if ($mysqli->connect_errno) {
    
    error_log("Database connection error: " . $mysqli->connect_error);
   
}
