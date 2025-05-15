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

$mysqli->set_charset("utf8");
$sql = "SELECT id, name, type, region, cost, base_rent, description, file FROM tiles ORDER BY id";
$result = $mysqli->query($sql);

$tiles = [];
if ($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $tiles[] = $row;
        }
    } else {
        // Brak pól w bazie danych
    }
} else {
    echo "Błąd zapytania SQL: " . $mysqli->error;
}
$sql_player ='SELECT p.id as id_player, p.game_id, p.name as name_player, p.coins as coins, p.location as location_player, p.cook_skill as cook_skill, p.tolerance as tolerance,
p.business_acumen as business_acumen, p.belly_capacity as belly_capacity, p.spice_sense as spice_sense, p.prep_time as prep_time,
p.tradition_affinity as tradition_affinity, p.turn_order as turn_order, p.is_turn as is_turn, p.turns_to_miss as turns_to_miss, c.name as name
FROM `players` p join characters c on p.character_id=c.id where p.game_id = ?; ';
$stmt_player = $mysqli->prepare($sql_player);
$stmt_player->bind_param('i', $gameId);
$stmt_player->execute();
$result_player = $stmt_player->get_result();

$player = [];
if ($result_player) {
    if ($result_player->num_rows > 0) {
        while($row1 = $result_player->fetch_assoc()) {
            $player[] = $row1;
        }
    } else {
        // Brak graczy dla danej gry
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
//        dodane najwyzej usuniemy
    }elseif ($id >= 32 && $id <= 40) {
        $classes[] = 'right-edge';
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
    <link rel="stylesheet" href="../css/roll_dice.css">
    <link rel="stylesheet" href="../css/gameboard_inner.css">
    <link rel="icon" href="../zdj/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../zdj/favicon.ico" type="image/x-icon">
</head>
<body>
<div class="monopoly-board" id="monopoly-board">
    <div class="board-center-placeholder">
        <?php
        if (!empty($player)) {
            foreach ($player as $index => $p) { 
                $playerClassNumber = $index + 1; 
                echo "<div class='player-info player" . htmlspecialchars($playerClassNumber) . "'>";
                echo "<p><b>" . htmlspecialchars($p['name_player'])." - " . htmlspecialchars($p['name']). "</b><br>";
                echo "Monety: " . htmlspecialchars($p['coins']). " $ <br>";
                // echo "<b>" . htmlspecialchars($p['name']). "</b><br>";
                echo "<table>";
                echo "<tr><td>Pojemność brzucha:</td><td>" . htmlspecialchars($p['belly_capacity']). "</td>";
                echo "<tr><td>Tolerancja ostrości:</td><td>" . htmlspecialchars($p['tolerance']). "</td>";
                echo "<tr><td>Czas przygotowania:</td><td>" . htmlspecialchars($p['prep_time']). "</td>";
                echo "<tr><td>Przywiązanie do tradycji:</td><td>" . htmlspecialchars($p['tradition_affinity']). "</td>";
                echo "<tr><td>Umiejętności gotowania:</td><td>" . htmlspecialchars($p['cook_skill']). "</td>";
                echo "<tr><td>Zmysł do przypraw:</td><td>" . htmlspecialchars($p['spice_sense']). "</td>";
                echo "<tr><td>Łeb do biznesu:</td><td>" . htmlspecialchars($p['business_acumen']). "</td>";
                echo "</table></div>";
            }
        } else {
            echo "<p>Brak graczy w bazie danych dla tej gry.</p>";
        }
        ?>
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

                    echo '<div class="players-on-tile"></div>';

                    echo '</div>';
                }
            } else {

                echo "<p style='grid-area: 6 / 5 / 8 / 9; z-index: 20; text-align: center;'>Nie udało się pobrać danych pól z bazy danych lub brak pól do wyświetlenia.</p>";
            }
            ?>
        </div>

        <?php
        if (!empty($player)) {
            foreach ($player as $p) {
                echo "<script>
        document.addEventListener('DOMContentLoaded', () => {
            const tile = document.getElementById('space-" . intval($p['location_player']) . "');
            if (tile) {
                const playerDiv = document.createElement('div');
                playerDiv.classList.add('player-token');
                playerDiv.classList.add('player-" . intval($p['id_player']) . "');
                playerDiv.title = '" . htmlspecialchars($p['name_player']) . "';
                tile.querySelector('.players-on-tile').appendChild(playerDiv);
            }
        });
        </script>";
            }
        }
        ?>

        <div class="dice-section">
             <p class="roll-result-text">Wyrzucono: <strong id="wynikTekst">-</strong></p>
             <img id="diceImage" src="../zdj/kostki/1.png" alt="Kostka" class="dice-image">
             <button id="rollDiceButton" class="roll-dice-button">Rzuć kostką</button>
        </div>
        <div class="end-game-div">
             <a href="./end_game.php"><button class="btn-end-game">Zakończ gre</button></a>
        </div>
    </div>

    <script src="../js/gameboard.js"> </script>

</body>
</html>