<?php
//require 'database_connect.php';
include_once './database_connect.php';

$sql = "SELECT id, name, description, effect_json FROM action_cards ORDER BY RAND() LIMIT 1";
$result = $mysqli->prepare($sql);
$result->execute();
$result1 = $result->get_result();
while ($row = $result1->fetch_object()) {
    echo $row->name,
    $row->description,
    $row->effect_json,
    $row->id;
}
?>