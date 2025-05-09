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


<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>

</body>
</html>

