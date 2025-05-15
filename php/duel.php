<?php

session_start();
if (!isset($_SESSION['game_id'])) {
    header("Location: index.php");
    // echo $_SESSION['game_id'];
    exit();
}
$gameId = $_SESSION['game_id'];

include_once './database_connect.php';

if (!isset($mysqli) || $mysqli->connect_errno) {
    die("Brak aktywnego połączenia z bazą danych po dołączeniu pliku.");
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