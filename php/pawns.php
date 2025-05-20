<?php
include_once './database_connect.php';
if (!isset($mysqli) || $mysqli->connect_errno) {
    die("Brak aktywnego połączenia z bazą danych po dołączeniu pliku.");
}

$mysqli->set_charset("utf8");

// Zmodyfikowane zapytanie SQL do pobrania danych o polach planszy
$sql = "SELECT
            t.id,
            t.name,
            t.type,
            t.region,
            t.cost,
            t.base_rent,
            t.description,
            t.`file`,
            tg.name AS group_name,
            tg.color AS group_color
        FROM `tiles` t
        LEFT JOIN `tile_groups` tg ON t.group_id = tg.id
        ORDER BY t.id";
$result = $mysqli->query($sql);

$tiles = [];
if ($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $tiles[] = $row;
        }
    } else {
        // Opcjonalnie: logowanie lub obsługa przypadku braku pól
    }
} else {
    echo "Błąd zapytania SQL: " . $mysqli->error;
}

$mysqli->close();

// Zmodyfikowana funkcja get_space_classes
function get_space_classes($tile) {
    $classes = ['tile'];
    $type_class = strtolower(str_replace([' ', '_', '/'], '_', $tile['type']));

    if (!empty($tile['group_name'])) {
        $classes[] = strtolower(str_replace([' ', '_', '/'], '_', $tile['group_name']));
    } elseif ($tile['type'] === 'restaurant' && !empty($tile['region'])) {
        $region_part = strtolower(str_replace([' ', '/'], '_', $tile['region']));
        $classes[] = $region_part . '_restaurant';
    } else {
        $classes[] = $type_class;
    }

    $id = $tile['id'];
    if (in_array($id,[0,10,20,30])){ // Poprawione indeksy narożników
        $classes[] ='corner';
    } elseif ($id >= 1 && $id <= 9) {
        $classes[] ='bottom-edge';
    } elseif ($id >= 11 && $id <= 19) { // Lewa krawędź
        $classes[] = 'left-edge';
    } elseif ($id >= 21 && $id <= 29) { // Górna krawędź
        $classes[] ='top-edge';
    } elseif ($id >= 31 && $id <= 39) { // Prawa krawędź
        $classes[] = 'right-edge';
    }

    if (!empty($tile['file'])) {
        $classes[] = 'has-tile-image';
    }

    return implode(' ', $classes);
}

// Zmodyfikowana funkcja get_space_content
function get_space_content($tile) {
    $content = '<div class="tile-name">';
    $name_class = 'tile-' . htmlspecialchars($tile['type']);
    if (!empty($tile['group_name'])) {
        $name_class .= ' ' . strtolower(str_replace([' ', '/'], '_', $tile['group_name']));
    } elseif (!empty($tile['region'])) {
        $name_class .= ' ' . htmlspecialchars($tile['region']);
    }
    $content .= '<div class="tile-name-text ' . $name_class . '">' . htmlspecialchars($tile['name']) . '</div>';
    $content .= '</div>';
    $content .= '<div class="tile-tile"></div>';

    $color_bar_style = '';
    if (!empty($tile['group_color'])) {
        $color_bar_style = ' style="background-color: ' . htmlspecialchars($tile['group_color']) . ';"';
    }
    $content .= '<div class="tile-color-bar"' . $color_bar_style . '></div>';
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
    <link rel="icon" href="../zdj/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../zdj/favicon.ico" type="image/x-icon">
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
            echo '<div class="' . get_space_classes($tile) . '" id="space-' . $tile['id'] . '" style="--tile-bg: url(\'' . htmlspecialchars($backgroundPath) . '\');">';
            echo get_space_content($tile);
            echo '<div class="players-on-tile">';
            // Pionki są tworzone tylko na polu startowym (ID 0)
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
</div>

<div class="dice-roll-container">
    <button id="rollDiceButton">Rzuć kostką</button>
    <p>Wyrzucono: <span id="diceResult">-</span></p>
</div>

</body>
</html>

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