<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "monopoly";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$mysqli = new mysqli($host, $user, $password, $database);

if ($mysqli->connect_errno) {
    error_log("Database connection error: " . $mysqli->connect_error);
}

?>