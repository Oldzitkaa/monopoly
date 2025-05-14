<?php
include_once './database_connect.php';
if (!isset($mysqli) || $mysqli->connect_errno) {
    die("Brak aktywnego połączenia z bazą danych po dołączeniu pliku.");
}

$mysqli->set_charset("utf8");
$sql = "SELECT id, name, type, region, cost, base_rent, description, file FROM tiles ORDER BY id"; // Używamy backticków dla nazwy `file`
$result = $mysqli->query($sql);

$tiles = [];
if ($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $tiles[] = $row;
        }
    } else {
    }
} else {
    echo "Błąd zapytania SQL: " . $mysqli->error;
}
$sql_player ='SELECT p.id as id_player, p.game_id, p.name as name_player, p.coins as coins, p.location as location_player, p.cook_skill as cook_skill, p.tolerance as tolerance, 
p.business_acumen as business_acumen, p.belly_capacity as belly_capacity, p.spice_sense as spice_sense, p.prep_time as prep_time, 
p.tradition_affinity as tradition_affinity, p.turn_order as turn_order, p.is_turn as is_turn, p.turns_to_miss as turns_to_miss, c.name as name 
FROM `players` p join characters c on p.character_id=c.id; ';
$result_player = $mysqli->query($sql_player);

$player = [];
if ($result_player) {
    if ($result_player->num_rows > 0) {
        while($row1 = $result_player->fetch_assoc()) {
            $player[] = $row1;
        }
    } else {
    }
} else {
    echo "Błąd zapytania SQL: " . $mysqli->error;
}

$mysqli->close();
function get_space_classes($tile) {
    $classes = ['tile'];
    $type_class = strtolower(str_replace([' ', '_', '/'], '_', $tile['type']));

    if ($tile['type'] === 'restaurant' && !empty($tile['region'])) {
        $region_part = strtolower(str_replace([' ', '/'], '_', $tile['region']));
        $classes[] = $region_part . '_restaurant';
    } else {
        $classes[] = $type_class;
    }

    $id = $tile['id'];
    if (in_array($id,[0,10,21,31])){
        $classes[] ='corner';
    }elseif ($id >= 1 && $id <= 9) {
        $classes[] ='bottom-edge';
    }elseif ($id >= 22 && $id <= 30) {
        $classes[] ='top-edge';
    }

    if (!empty($tile['file'])) {
        $classes[] = 'has-tile-image';
    }

    return implode(' ', $classes);
}
function get_space_content($tile) {
    $content = '<div class="tile-name">';
    $content .= '<div class="tile-name-text tile-' . htmlspecialchars($tile['type']) . ' '. htmlspecialchars($tile['region']).'">' . htmlspecialchars($tile['name']) . '</div>';
    $content .= '</div>';
    $content .= '<div class="tile-tile"></div>';
    $content .= '<div class="tile-color-bar"></div>';
    return $content;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MONOPOLY</title>
    <link rel="stylesheet" href="../css/style_gameboard.css">
    <link rel="stylesheet" href="../css/gameboard_inner.css">
</head>
<body>
<div class="monopoly-board" id="monopoly-board">
    <div class="board-center-placeholder">
        <?php
if (!empty($player)) {
    foreach ($player as $p) {
        echo "<div class='player-info player". htmlspecialchars($p['id_player']) ."'>";
        echo "<p>" . htmlspecialchars($p['name_player']). "<br>";
        echo htmlspecialchars($p['name']). "<br>";
        echo "Pojemność brzucha: " . htmlspecialchars($p['belly_capacity']). "<br>";
        echo "Tolerancja ostrości: " . htmlspecialchars($p['tolerance']). "<br>";
        echo "Czas przygotowania: " . htmlspecialchars($p['prep_time']). "<br>";
        echo "Przywiązanie do tradycji: " . htmlspecialchars($p['tradition_affinity']). "<br>";
        echo "Umiejętności gotowania: " . htmlspecialchars($p['cook_skill']). "<br>";
        echo "Zmysł do przypraw: " . htmlspecialchars($p['spice_sense']). "<br>";
        echo "Łeb do biznesu: " . htmlspecialchars($p['business_acumen']). "<br>";
        echo "</div>";
    }
} else {
    echo "<p>Brak graczy w bazie danych.</p>";
}
?>

        
        <!-- MONOPOLY
        <span>Custom Edition</span> -->

    </div>
    <?php
    $tile_counter = 0;
    if (!empty($tiles)) {
        foreach ($tiles as $tile) {
            $tile_counter++;
            $backgroundPath = '';
            if (!empty($tile['file'])) {
                $backgroundPath = '../zdj/pola/' . $tile['file'];
            }
            echo '<div class="' . get_space_classes($tile) . '" id="space-' . $tile['id'] . '" style="--tile-bg: url(\'../zdj/pola/' . htmlspecialchars($tile['file']) . '\');">';

            echo get_space_content($tile);
            echo '</div>';
        }
    } else {
        echo "<p>Nie udało się pobrać danych pól z bazy danych lub brak pól do wyświetlenia.</p>";
    }
    ?>
</div>
</body>
</html>






























































































































































































































































































































































































































