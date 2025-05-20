<?php
session_start();
if (!isset($_SESSION['game_id'])) {
    header("Location: index.php");
    exit();
}

$gameId = $_SESSION['game_id'];
include_once './database_connect.php';

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
                p.is_turn as is_turn,
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
                $player[] = $row1;
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
    <link rel="stylesheet" href="../css/end_game.css">
    <link rel="icon" href="../zdj/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../zdj/favicon.ico" type="image/x-icon">
</head>
<body>
    <div class="logo-div">
        <!-- <p class="step-title">MONOPOLY</p>  -->
        <img src="../zdj/logo.png" alt="Potega Smakow" class="logo-zdj">

        <!-- Tabela wynikow -->
         <p class="win-player">Zwycięzcą zostaje &rarr; 
            <?php

            ?>
         </p>
        <table class="player-result">
            <?php
            if (!empty($player)) {
                echo "<tr> <th>✫</th> <th>Monety $</th> <th>Ilość zebranych restauracji</th> </tr>";
                foreach ($player as $index => $p) {
                    $playerClassNumber = $index + 1;
                    echo "<tr> <td>". htmlspecialchars($p['name_player']) ."</td><td>". htmlspecialchars($p['coins']) ."</td><td>". "</td></tr>";
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