<?php
//require 'database_connect.php';
include_once './database_connect.php';

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