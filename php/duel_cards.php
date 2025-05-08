<?php
include_once './database_connect.php';

if (!isset($mysqli) || $mysqli->connect_errno) {
    die("Brak aktywnego połączenia z bazą danych po dołączeniu pliku.");
}


$sql = "SELECT id, description, related_stat, effect_json FROM duel_cards ORDER BY RAND() LIMIT 1";
$result = $mysqli->prepare($sql);
$result->execute();
$result1 = $result->get_result();
while ($row = $result1->fetch_object()) {
    echo $row->related_stat,
    $row->description,
    $row->effect_json,
    $row->id;
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
