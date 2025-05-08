<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "monopoly";

$mysqli = new mysqli($host, $user, $password, $database);

if ($mysqli->connect_errno) {
    echo ("nie udało się połączyć MySQL: ". $mysqli->connect_error);
    exit();
}


?>

