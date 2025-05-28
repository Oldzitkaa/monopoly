<?php
session_start();
if (!isset($_SESSION['game_id'])) {
    header("Location: index.php");
    exit();
}

$gameId = $_SESSION['game_id'];
include_once './database_connect.php';

// gracze
$sql_player = "SELECT
                p.id as id_player,
                p.game_id,
                p.name as name_player,
                p.coins as coins,
                p.location as location_player,
                p.cook_skill as cook_skill,
                p.tolerance as tolerance,
                p.business_acumen as business_acumen,
                p.belly_capacity as belly_capacity,
                p.spice_sense as spice_sense,
                p.prep_time as prep_time,
                p.tradition_affinity as tradition_affinity,
                p.turn_order as turn_order,
                p.is_current_turn as is_turn,
                p.turns_to_miss as turns_to_miss,
                c.name as character_name
            FROM `players` p
            JOIN `characters` c ON p.character_id = c.id
            WHERE p.game_id = ?
            ORDER BY p.turn_order ASC";
$stmt_player = $mysqli->prepare($sql_player);
if ($stmt_player) {
    $stmt_player->bind_param('i', $gameId);
    if ($stmt_player->execute()) {
        $result_player = $stmt_player->get_result();
        $player = [];
        if ($result_player->num_rows > 0) {
            while($row1 = $result_player->fetch_assoc()) {
                $row1['coins'] = (int)$row1['coins'];
                $row1['location'] = (int)$row1['location_player'];
                $players_data[] = $row1;
            }
        } else {
            error_log("Brak graczy w bazie danych dla gry o ID: " . $gameId);
            echo "<p style='color: red;'>Błąd: Brak danych graczy dla tej gry.</p>";
        }
        $result_player->free();
    } else {
        error_log("Błąd wykonania zapytania SQL dla graczy (gry ID: " . $gameId . "): " . $stmt_player->error);
        echo "<p style='color: red;'>Błąd wykonania zapytania SQL dla graczy: " . $stmt_player->error . "</p>";
    }
    $stmt_player->close();
} else {
    error_log("Błąd przygotowania zapytania SQL dla graczy (gry ID: " . $gameId . "): " . $mysqli->error);
    echo "<p style='color: red;'>Błąd przygotowania zapytania SQL dla graczy: " . $mysqli->error . "</p>";
}
//restauracja
$sql_game_tiles = "SELECT
                    gt.tile_id,
                    gt.current_owner_id,
                    gt.current_level,
                    t.cost,
                    t.type
                FROM `game_tiles` gt
                JOIN `tiles` t ON gt.tile_id = t.id
                WHERE gt.game_id = ? AND t.type = 'restaurant'";
$stmt_game_tiles = $mysqli->prepare($sql_game_tiles);
$game_tiles_data = [];
if ($stmt_game_tiles) {
    $stmt_game_tiles->bind_param('i', $gameId);
    if ($stmt_game_tiles->execute()) {
        $result_game_tiles = $stmt_game_tiles->get_result();
        while($row_tile = $result_game_tiles->fetch_assoc()) {
            $row_tile['cost'] = (int)$row_tile['cost'];
            $row_tile['current_level'] = (int)$row_tile['current_level'];
            $game_tiles_data[] = $row_tile;
        }
        $result_game_tiles->free();
    } else {
        error_log("Błąd wykonania zapytania SQL dla pól gry (ID: " . $gameId . "): " . $stmt_game_tiles->error);
    }
    $stmt_game_tiles->close();
} else {
    error_log("Błąd przygotowania zapytania SQL dla pól gry (ID: " . $gameId . "): " . $mysqli->error);
}

// wycena
$final_player_results = [];
$max_final_value = -1;

foreach ($players_data as $player) {
    $player_id = $player['id_player'];
    $player_coins = $player['coins'];
    $player_name = $player['name_player'];
    $restaurants_owned_count = 0;
    $restaurants_value = 0;

    foreach ($game_tiles_data as $tile) {
        if ($tile['current_owner_id'] == $player_id && $tile['type'] == 'restaurant') {
            $restaurants_owned_count++;
            $tile_value = $tile['cost'];
            $effective_level = (isset($tile['current_level']) ? (int)$tile['current_level'] : 0) + 1;
            $tile_value *= $effective_level;
            $restaurants_value += $tile_value;
        }
    }

    $all_income = $player_coins + $restaurants_value;

    $final_player_results[] = [
        'name' => $player_name,
        'coins' => $player_coins,
        'restaurants_count' => $restaurants_owned_count,
        'final_value' => $all_income
    ];

    if ($all_income > $max_final_value) {
        $max_final_value = $all_income;
    }
}
// zwyciescy
$winners = [];
foreach ($final_player_results as $player_result) {
    if ($player_result['final_value'] == $max_final_value) {
        $winners[] = $player_result['name'];
    }
}
if (isset($mysqli) && $mysqli instanceof mysqli && !$mysqli->connect_errno) {
    $mysqli->close();
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Potęga Smaku - Wyniki</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/end_game.css">
    <link rel="icon" href="../zdj/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../zdj/favicon.ico" type="image/x-icon">
</head>
<body>
    <div class="logo-div">
        <img src="../zdj/logo.png" alt="Potega Smakow" class="logo-zdj">
         <p class="win-player"> 
            <?php
                if (!empty($winners)) {
                    if (count($winners) == 1) {
                        echo "Zwycięzcą zostaje &rarr; " . htmlspecialchars($winners[0]);
                    } else {
                        echo "Oto zwycięzcy: &rarr; " . implode(', ', $winners);
                    }
                } else {
                    echo "Nikt";
                }
            ?>
         </p>
        <table class="player-result">
            <?php
            if (!empty($final_player_results)) {
                echo "<tr> <th>★</th> <th>Monety $</th> <th>Ilość zebranych restauracji</th> <th>Wycena końcowa</th> </tr>";
                foreach ($final_player_results as $player_result) {
                    echo "<tr>";
                    echo "<td>". htmlspecialchars($player_result['name']) ."</td>";
                    echo "<td>". htmlspecialchars($player_result['coins']) ."</td>";
                    echo "<td>". htmlspecialchars($player_result['restaurants_count']) . "</td>";
                    echo "<td>". htmlspecialchars($player_result['final_value']) . "</td>";
                    echo "</tr>";
                }
            }
            ?>
        </table>
        <button class="end-btn" id="endGameButton">OK</button>
    </div>
    <img src="../zdj/tlo3.png" alt="Tło gry" class="backg">

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const endGameButton = document.getElementById('endGameButton');
            if (endGameButton) {
                endGameButton.addEventListener('click', () => {
                    fetch('end_session.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=end_game'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = './index.php';
                        } else {
                            console.error('Błąd podczas kończenia sesji:', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Wystąpił błąd sieciowy:', error);
                    });
                });
            }
        });
    </script>
</body>
</html>