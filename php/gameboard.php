<?php
session_start();

if (!isset($_SESSION['game_id'])) {
    header("Location: index.php");
    exit();
}
$gameId = $_SESSION['game_id'];
include_once './database_connect.php'; // Upewnij się, że ten plik prawidłowo łączy się z bazą danych i zwraca obiekt $mysqli

if (!isset($mysqli) || $mysqli->connect_errno) {
    die("Brak aktywnego połączenia z bazą danych po dołączeniu pliku database_connect.php: " . ($mysqli->connect_error ?? 'Brak szczegółów błędu.'));
}

$mysqli->set_charset("utf8");

// Zapytanie SQL do pobrania danych o polach planszy (zmodyfikowane dla nowego schematu, ale z myślą o starym wyglądzie)
$sql = "SELECT
            t.id,
            t.name,
            t.type,
            tg.name AS group_name,      -- Używamy group_name jako 'region' dla celów wyświetlania
            tg.color_code AS group_color, -- Kolor grupy dla paska
            t.cost,
            t.base_rent,
            t.description,
            t.`file`,
            t.upgrade_cost,
            gt.current_owner_id AS owner_id,
            gt.current_level AS current_level,
            gt.is_mortgaged AS is_mortgaged
        FROM `tiles` t
        LEFT JOIN `tile_groups` tg ON t.group_id = tg.id
        LEFT JOIN `game_tiles` gt ON t.id = gt.tile_id AND gt.game_id = ?
        ORDER BY t.id";

$stmt_tiles = $mysqli->prepare($sql);
$tiles = [];

if ($stmt_tiles) {
    $stmt_tiles->bind_param('i', $gameId);
    if ($stmt_tiles->execute()) {
        $result = $stmt_tiles->get_result();
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // Map 'group_name' to 'region' for compatibility with get_space_classes and get_space_content
                $row['region'] = $row['group_name'];
                $tiles[] = $row;
            }
        } else {
            error_log("Brak pól w bazie danych do wyświetlenia dla gry o ID: " . $gameId);
            echo "<p style='color: red;'>Błąd: Brak danych pól planszy w bazie danych.</p>";
        }
        $result->free();
    } else {
        error_log("Błąd wykonania zapytania SQL dla pól (gry ID: " . $gameId . "): " . $stmt_tiles->error);
        echo "<p style='color: red;'>Błąd wykonania zapytania SQL dla pól: " . $stmt_tiles->error . "</p>";
    }
    $stmt_tiles->close();
} else {
    error_log("Błąd przygotowania zapytania SQL dla pól (gry ID: " . $gameId . "): " . $mysqli->error);
    echo "<p style='color: red;'>Błąd przygotowania zapytania SQL dla pól: " . $mysqli->error . "</p>";
}

// Zapytanie SQL do pobrania danych o graczach
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
                p.is_current_turn as is_current_turn,
                p.turns_to_miss as turns_to_miss,
                p.color as player_color,
                c.name as character_name
            FROM `players` p
            JOIN `characters` c ON p.character_id = c.id
            WHERE p.game_id = ?
            ORDER BY p.turn_order ASC";
$stmt_player = $mysqli->prepare($sql_player);
$player = [];

if ($stmt_player) {
    $stmt_player->bind_param('i', $gameId);
    if ($stmt_player->execute()) {
        $result_player = $stmt_player->get_result();
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

// Pobranie nieruchomości graczy
$playerProperties = [];
if (!empty($player)) {
    foreach ($player as $p) {
        $sql_properties = "SELECT
                                t.name,
                                t.cost,
                                t.base_rent,
                                tg.name AS region,
                                t.type,
                                gt.current_level AS level,
                                gt.is_mortgaged AS mortgaged
                            FROM tiles t
                            JOIN game_tiles gt ON t.id = gt.tile_id
                            LEFT JOIN tile_groups tg ON t.group_id = tg.id
                            WHERE gt.current_owner_id = ? AND gt.game_id = ?
                            ORDER BY t.name ASC";
        $stmt_properties = $mysqli->prepare($sql_properties);
        if ($stmt_properties) {
            $stmt_properties->bind_param('ii', $p['id_player'], $gameId);
            if ($stmt_properties->execute()) {
                $result_properties = $stmt_properties->get_result();
                if ($result_properties->num_rows > 0) {
                    while($row_prop = $result_properties->fetch_assoc()) {
                        $playerProperties[$p['id_player']][] = $row_prop;
                    }
                }
                $result_properties->free();
            } else {
                error_log("Błąd wykonania zapytania SQL dla nieruchomości gracza " . $p['id_player'] . ": " . $stmt_properties->error);
            }
            $stmt_properties->close();
        } else {
            error_log("Błąd przygotowania zapytania SQL dla nieruchomości gracza " . $p['id_player'] . ": " . $mysqli->error);
        }
    }
}

if (isset($mysqli) && $mysqli instanceof mysqli && !$mysqli->connect_errno) {
    $mysqli->close();
}

// Zmodyfikowana funkcja get_space_classes aby odzwierciedlać stary wygląd
function get_space_classes($tile) {
    $classes = ['tile'];

    // Użyj 'group_name' z zapytania SQL (który jest aliasem dla 'region' w tej funkcji)
    // Jeśli 'group_name' istnieje, użyj go do klas CSS
    if (!empty($tile['group_name'])) {
        $group_class = strtolower(str_replace([' ', '_', '/'], '_', $tile['group_name']));

        // Specjalne klasy dla restauracji z regionem
        if ($tile['type'] === 'restaurant') {
            $classes[] = $group_class . '_restaurant';
        } else {
            // Dla innych typów pól, które mają grupę
            $classes[] = $group_class;
        }
    } else {
        // Domyślna klasa na podstawie 'type' jeśli 'group_name' jest puste
        $classes[] = strtolower(str_replace([' ', '_', '/'], '_', $tile['type']));
    }

    $id = $tile['id'];
    if (in_array($id,[0,10,20,30])){
        $classes[] ='corner';
    }
    elseif ($id >= 1 && $id <= 9) {
        $classes[] ='bottom-edge';
    }
    elseif ($id >= 11 && $id <= 19) {
        $classes[] = 'left-edge';
    }
    elseif ($id >= 21 && $id <= 29) {
        $classes[] ='top-edge';
    }
    elseif ($id >= 31 && $id <= 39) {
        $classes[] = 'right-edge';
    }

    if (!empty($tile['file'])) {
        $classes[] = 'has-tile-image';
    }
    return implode(' ', $classes);
}

// Zmodyfikowana funkcja get_space_content aby odzwierciedlać stary wygląd
function get_space_content($tile) {
    $content = '<div class="tile-name">';
    // Użyj 'group_name' jako 'region' dla klasy 'tile-name-text'
    $regionClass = !empty($tile['group_name']) ? strtolower(str_replace([' ', '/'], '_', $tile['group_name'])) : '';
    $content .= '<div class="tile-name-text tile-' . htmlspecialchars($tile['type']) . ' ' . $regionClass . '">' . htmlspecialchars($tile['name']) . '</div>';
    $content .= '</div>';

    // Stara struktura board_inner.css ma div 'tile-tile' i 'tile-color-bar'
    // Dodajemy styl inline z kolorem z bazy danych dla tile-tile
    $tile_tile_style = '';
    if (!empty($tile['group_color'])) {
        $tile_tile_style = ' style="background-color: ' . htmlspecialchars($tile['group_color']) . ';"';
    }
    $content .= '<div class="tile-tile"' . $tile_tile_style . '></div>'; // ZMODYFIKOWANA LINIA

    // Dodajemy styl inline z kolorem z bazy danych dla paska koloru
    $color_bar_style = '';
    if (!empty($tile['group_color'])) {
        $color_bar_style = ' style="background-color: ' . htmlspecialchars($tile['group_color']) . ';"';
    }
    $content .= '<div class="tile-color-bar"' . $color_bar_style . '></div>';

    return $content;
}

// Funkcja do generowania tabeli statystyk gracza (dla sidebara)
// Ta funkcja będzie teraz nieużywana w sidebarze, ale zostawiam ją, jeśli jest używana gdzie indziej
function generatePlayerStatsTable($playerData) {
    $html = '<div class="player-stats-table-container">';
    $html .= '<h3>Statystyki Gracza</h3>';
    $html .= '<table class="player-stats-table">';
    $html .= '<thead>';
    $html .= '<tr><th>Cecha</th><th>Wartość</th></tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    $html .= '<tr><td>Monety</td><td class="numeric">' . htmlspecialchars($playerData['coins']) . ' zł</td></tr>';
    $html .= '<tr><td>Pozycja</td><td>Pole ' . htmlspecialchars($playerData['location_player']) . '</td></tr>';
    $html .= '<tr><td>Umiejętność Gotowania</td><td class="numeric">' . htmlspecialchars($playerData['cook_skill']) . '</td></tr>';
    $html .= '<tr><td>Tolerancja</td><td class="numeric">' . htmlspecialchars($playerData['tolerance']) . '</td></tr>';
    $html .= '<tr><td>Zmysł Biznesowy</td><td class="numeric">' . htmlspecialchars($playerData['business_acumen']) . '</td></tr>';
    $html .= '<tr><td>Pojemność Żołądka</td><td class="numeric">' . htmlspecialchars($playerData['belly_capacity']) . '</td></tr>';
    $html .= '<tr><td>Zmysł Smaku Przypraw</td><td class="numeric">' . htmlspecialchars($playerData['spice_sense']) . '</td></tr>';
    $html .= '<tr><td>Czas Przygotowania</td><td class="numeric">' . htmlspecialchars($playerData['prep_time']) . '</td></tr>';
    $html .= '<tr><td>Tradycyjne Powiązania</td><td class="numeric">' . htmlspecialchars($playerData['tradition_affinity']) . '</td></tr>';
    $html .= '<tr><td>Kolejka (Tura)</td><td>' . htmlspecialchars($playerData['turn_order']) . '</td></tr>';
    $html .= '<tr><td>Tura Aktywna</td><td class="boolean" data-value="' . (isset($playerData['is_current_turn']) && $playerData['is_current_turn'] ? 'true' : 'false') . '">' . (isset($playerData['is_current_turn']) && $player['is_current_turn'] ? 'TAK' : 'NIE') . '</td></tr>';
    $html .= '<tr><td>Tury do pominięcia</td><td class="numeric">' . htmlspecialchars($playerData['turns_to_miss']) . '</td></tr>';
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';
    return $html;
}

// Funkcja do generowania tabeli nieruchomości (dla sidebara)
function generatePlayerPropertiesTable($properties) {
    $html = '<div class="player-properties-table-container">';
    $html .= '<h3>Nieruchomości</h3>'; // Added H3 for clarity
    if (!empty($properties)) {
        $html .= '<table class="player-properties-table">';
        $html .= '<thead>';
        $html .= '<tr><th>Nazwa</th><th>Typ</th><th>Region</th><th>Koszt</th><th>Czynsz bazowy</th><th>Poziom</th><th>Zastawiono</th></tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        foreach ($properties as $prop) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($prop['name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($prop['type']) . '</td>';
            $html .= '<td class="property-type-region">' . htmlspecialchars($prop['region']) . '</td>';
            $html .= '<td class="numeric property-cost-rent">' . htmlspecialchars($prop['cost']) . ' zł</td>';
            $html .= '<td class="numeric property-cost-rent">' . htmlspecialchars($prop['base_rent']) . ' zł</td>';
            $html .= '<td class="numeric">' . htmlspecialchars($prop['level']) . '</td>';
            $html .= '<td>' . ($prop['mortgaged'] ? 'Tak' : 'Nie') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';
    } else {
        $html .= '<p>Brak posiadanych nieruchomości.</p>';
    }
    $html .= '</div>';
    return $html;
}


$currentPlayerId = null;
if (!empty($player)) {
    foreach ($player as $p) {
        if (isset($p['is_current_turn']) && $p['is_current_turn'] == 1) {
            $currentPlayerId = $p['id_player'];
            break;
        }
    }
}
if ($currentPlayerId === null && !empty($player)) {
    error_log("Błąd: Nie znaleziono gracza, którego jest tura dla gry ID: " . $gameId);
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
<?php
    $colors = ['red', 'blue', 'green', 'yellow'];

    echo '<style>';
        for ($i = 1; $i <= 20; $i++) {
            $color = $colors[($i - 1) % count($colors)];
            echo ".player-token.player-$i { background-color: $color; }";
        }
        echo '</style>';

?>
</head>
<body>
<div class="game-wrapper">
    <div class="monopoly-board-container">
        <div class="monopoly-board" id="monopoly-board">
            <div class="board-center-placeholder">
                <?php
                // START: Zmodyfikowany blok dla wyświetlania statystyk graczy w centrum planszy
                if (!empty($player)) {
                    foreach ($player as $index => $p) {
                        $playerClassNumber = $index + 1; // Generuje klasy player1, player2 itd.
                        echo "<div class='player-info player" . htmlspecialchars($playerClassNumber) . "'>";
                        echo "<p><b>" . htmlspecialchars($p['name_player'])." - " . htmlspecialchars($p['character_name']). "</b><br>";
                        echo "Monety: " . htmlspecialchars($p['coins']). " zł <br>"; // Zmieniono "$" na "zł"
                        echo "<table>";
                        echo "<tr><td>Pojemność brzucha:</td><td>" . htmlspecialchars($p['belly_capacity']). "</td></tr>";
                        echo "<tr><td>Tolerancja ostrości:</td><td>" . htmlspecialchars($p['tolerance']). "</td></tr>";
                        echo "<tr><td>Czas przygotowania:</td><td>" . htmlspecialchars($p['prep_time']). "</td></tr>";
                        echo "<tr><td>Tradycyjne Powiązania:</td><td>" . htmlspecialchars($p['tradition_affinity']). "</td></tr>"; // Poprawiona nazwa
                        echo "<tr><td>Umiejętności gotowania:</td><td>" . htmlspecialchars($p['cook_skill']). "</td></tr>";
                        echo "<tr><td>Zmysł do przypraw:</td><td>" . htmlspecialchars($p['spice_sense']). "</td></tr>";
                        echo "<tr><td>Łeb do biznesu:</td><td>" . htmlspecialchars($p['business_acumen']). "</td></tr>";
                        echo "</table>";
                        // USUNIĘTO: Element z menu hamburgerowym:
                        // echo "<div class='handle-all handle" . htmlspecialchars($playerClassNumber) ."'><span> </span><span> </span><span> </span></div></div>";
                        echo "</div>"; // Zamknięcie div.player-info
                    }
                } else {
                    echo "<p>Brak graczy w bazie danych dla tej gry lub błąd ładowania.</p>";
                }
                // END: Zmodyfikowany blok
                ?>
            </div>
            <?php
            $tile_counter = 0;
            if (!empty($tiles)) {
                foreach ($tiles as $tile) {
                    $tile_counter++;
                    $style_attribute = '';
                    // Obrazki dla pól specjalnych (nie restauracji) są ustawiane jako tło z 'file'
                    if (!empty($tile['file']) && $tile['type'] !== 'restaurant') {
                        $style_attribute = ' style="background-image: url(\'../zdj/pola/' . htmlspecialchars($tile['file']) . '\'); background-size: cover; background-position: center;"';
                    }
                    echo '<div class="' . get_space_classes($tile) . '" id="space-' . $tile['id'] . '"' . $style_attribute . '>';
                    echo get_space_content($tile);
                    echo '<div class="players-on-tile"></div>'; // Kontener na pionki graczy
                    echo '</div>';
                }
            } else {
                echo "<p style='grid-area: 6 / 5 / 8 / 9; z-index: 20; text-align: center; color: black;'>Nie udało się pobrać danych pól z bazy danych lub brak pól do wyświetlenia.</p>";
            }
            ?>
        </div>
    </div>

    <div class="game-sidebar">
        <div class="player-info-container" id="playerInfoContainer">
            <?php
            if (!empty($player)) {
                foreach ($player as $index => $p) {
                    // Stara struktura sidebar player info box
                    echo "<div class='player-info-box' data-player-id='" . htmlspecialchars($p['id_player']) . "'>";
                    // Kolor ramki nagłówka z koloru gracza
                    echo "<div class='player-header' style='border-color: " . htmlspecialchars($p['player_color']) . ";'>";
                    echo "<div class='name'>" . htmlspecialchars($p['name_player']) . " - " . htmlspecialchars($p['character_name']) . "</div>";
                    echo "</div>"; // Close player-header
                    echo "<div class='properties-and-skills-wrapper'>";
                    // USUNIĘTO: generatePlayerStatsTable($p); // Usunięto wyświetlanie statystyk gracza tutaj
                    echo generatePlayerPropertiesTable(isset($playerProperties[$p['id_player']]) ? $playerProperties[$p['id_player']] : []);
                    echo "</div>"; // close .properties-and-skills-wrapper
                    echo "</div>"; // close .player-info-box
                }
            } else {
                echo "<div class='player-info-box'><div class='name'>Brak graczy</div></div>";
            }
            ?>
        </div>

        <div class="card-slots-container">
            <div class="card-slot card-text">
            <?php
                // include_once './get_tile_message.php';
            ?>
            </div>
            <div class="card-slot card-choose"></div>
        </div>

        <div class="game-controls-container">
            <div class="dice-section">
                <p class="roll-result-text">Wyrzucono: <strong id="wynikTekst">-</strong></p>
                <img id="diceImage" src="../zdj/kostki/1.png" alt="Kostka" class="dice-image">
                <button id="rollDiceButton" class="roll-dice-button">Rzuć kostką</button>
            </div>
        </div>

        <div class="end-game-div">
            <a href="./end_game.php"><button class="btn-end-game">Zakończ gre</button></a>
        </div>
    </div>
</div>

<script>
    const gameId = <?= json_encode($gameId); ?>;
    const currentPlayerId = <?= json_encode($currentPlayerId); ?>;
    console.log('gameId ustawione dla JS:', gameId);
    console.log('currentPlayerId ustawione dla JS:', currentPlayerId);

    const players = <?php echo json_encode($player); ?>;
    console.log(players);

    // Funkcja do aktualizacji pozycji pionków
    function updatePlayerPawns() {
        // Usuń wszystkie istniejące pionki
        document.querySelectorAll('.player-token').forEach(pawn => pawn.remove());

        colors = ['red', 'green', 'yellow', 'blue']
        let playerIndex = 0
        // Przenieś pionki na ich aktualne pozycje
        players.forEach(player => {
            const pawn = document.createElement('div');
            pawn.classList.add('player-token'); // Zmieniono na 'player-token'
            pawn.classList.add(`player-${player.id_player}`);
            pawn.dataset.playerId = player.id_player;
            pawn.title = player.name_player;
            pawn.style.backgroundColor = colors[playerIndex];

            const playerTile = document.querySelector(`#space-${player.location_player} .players-on-tile`);
            if (playerTile) {
                playerTile.appendChild(pawn);
            } else {
                console.error(`Nie znaleziono kontenera .players-on-tile w polu space-${player.location_player} dla gracza ${player.name_player}`);
            }
            playerIndex += 1
        });
    }

    // Wywołaj funkcję przy ładowaniu strony, aby umieścić pionki
    document.addEventListener('DOMContentLoaded', updatePlayerPawns);
</script>

<script src="../js/gameboard.js"> </script>
<script src="../js/gameboard_inner.js"></script>

</body>
</html>