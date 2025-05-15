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
    <link rel="stylesheet" href="../css/pawns.css">
</head>
<body>
<div class="monopoly-board" id="monopoly-board">
    <div class="board-center-placeholder">
        MONOPOLY
        <span>Custom Edition</span>
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
// Pionki
            echo '<div class="' . get_space_classes($tile) . '" id="space-' . $tile['id'] . '" style="--tile-bg: url(\'../zdj/pola/' . htmlspecialchars($tile['file']) . '\');">';
            echo get_space_content($tile);
            echo '<div class="players-on-tile">';
            if ($tile['id'] == 0) {
                echo '<div class="player-marker pawn1" id="player1"></div>';
                echo '<div class="player-marker pawn2" id="player2"></div>';
                echo '<div class="player-marker pawn3" id="player3"></div>';
                echo '<div class="player-marker pawn4" id="player4"></div>';
            }
            echo '</div>';
            echo '</div>';

        }
    } else {
        echo "<p>Nie udało się pobrać danych pól z bazy danych lub brak pól do wyświetlenia.</p>";
    }
    ?>

<!--kostka-->
</div>

<div class="dice-roll-container">
    <button id="rollDiceButton">Rzuć kostką</button>
    <p>Wyrzucono: <span id="diceResult">-</span></p>
</div>

</body>
</html>

<!--skrypt do pionków-->
<script>
    const totalSpaces = <?= count($tiles) ?>;
    const playerPositions = [0, 0, 0, 0];
    let currentPlayer = 0;

    document.getElementById("rollDiceButton").addEventListener("click", () => {
        const diceRoll = Math.floor(Math.random() * 6) + 1;
        document.getElementById("diceResult").textContent = diceRoll;

        movePlayer(currentPlayer, diceRoll);

        // zmiana gracza po 2 sek.
        setTimeout(() => {
            currentPlayer = (currentPlayer + 1) % 4;
            alert("Tura gracza " + (currentPlayer + 1));
        }, diceRoll * 500 + 500);
    });

    function movePlayer(playerIndex, steps) {
        const playerId = "player" + (playerIndex + 1);
        const player = document.getElementById(playerId);

        for (let i = 1; i <= steps; i++) {
            setTimeout(() => {
                playerPositions[playerIndex] = (playerPositions[playerIndex] + 1) % totalSpaces;
                const newTile = document.getElementById("space-" + playerPositions[playerIndex]);
                const playersContainer = newTile.querySelector(".players-on-tile");
                if (playersContainer) {
                    playersContainer.appendChild(player);
                }
            }, i * 500);
        }
    }
</script>


















































































































































































































