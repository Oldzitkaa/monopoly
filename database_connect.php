<?php
$mysqli = new mysqli("localhost", "root", "", "monopoly");

if ($mysqli->connect_errno) {
    echo ("nie udało się połączyć MySQL: ". $mysqli->connect_error);
    exit();
}


?>

