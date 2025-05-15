<?php
session_start();
if (!isset($_SESSION['game_id'])) {
    header("Location: index.php");
    exit();
}
$gameId = $_SESSION['game_id'];

include_once './database_connect.php';

if (!isset($mysqli) || $mysqli->connect_errno) {
    die("Brak aktywnego połączenia z bazą danych po dołączeniu pliku.");
}

$mysqli->set_charset("utf8");

// --- Pobierz dane pól planszy ---
$sql_tiles = "SELECT id, name, type, region, cost, base_rent, description, file FROM tiles ORDER BY id";
$result_tiles = $mysqli->query($sql_tiles);

$tiles = [];
if ($result_tiles) {
    if ($result_tiles->num_rows > 0) {
        while($row = $result_tiles->fetch_assoc()) {
            $tiles[] = $row;
        }
    } else {
        // Brak pól w bazie danych
    }
} else {
    error_log("SQL error fetching tiles in gameboard.php: " . $mysqli->error);
    // Możesz dodać komunikat błędu na stronie
}

// --- Pobierz dane graczy w tej grze ---
// Używamy przygotowanej instrukcji dla bezpieczeństwa
$sql_players ='SELECT p.id as id_player, p.game_id, p.name as name_player, p.coins as coins, p.location as location_player, p.cook_skill as cook_skill, p.tolerance as tolerance,
p.business_acumen as business_acumen, p.belly_capacity as belly_capacity, p.spice_sense as spice_sense, p.prep_time as prep_time,
p.tradition_affinity as tradition_affinity, p.turn_order as turn_order, p.is_turn as is_turn, p.turns_to_miss as turns_to_miss, c.name as name_character
FROM `players` p JOIN characters c ON p.character_id = c.id WHERE p.game_id = ? ORDER BY p.turn_order ASC'; // Dodano ORDER BY
$stmt_players = $mysqli->prepare($sql_players);

$players = []; // Zmieniono nazwę zmiennej na players, żeby uniknąć konfliktu jeśli gdzieś używasz $player dla pojedynczego gracza
if ($stmt_players) {
    $stmt_players->bind_param('i', $gameId);
    if ($stmt_players->execute()) {
        $result_players = $stmt_players->get_result();
        if ($result_players->num_rows > 0) {
            while($row = $result_players->fetch_assoc()) {
                $players[] = $row;
            }
        } else {
            // Brak graczy dla danej gry
        }
        $stmt_players->close();
    } else {
        error_log("SQL execute error fetching players in gameboard.php: " . $stmt_players->error);
    }
} else {
    error_log("SQL prepare error fetching players in gameboard.php: " . $mysqli->error);
}

// --- Określ ID gracza, którego jest aktualnie tura ---
$currentPlayerId = null;
if (!empty($players)) {
    foreach ($players as $player_data) {
        if ($player_data['is_turn'] == 1) {
            $currentPlayerId = $player_data['id_player'];
            break;
        }
    }
}

// --- Funkcje pomocnicze do generowania HTML pól ---
function get_space_classes($tile) {
    $classes = ['tile'];
    $type_class = strtolower(str_replace([' ', '_', '/'], '_', $tile['type']));

    // Dodaj klasę region_restaurant jeśli to restauracja i ma region
    if ($tile['type'] === 'restaurant' && !empty($tile['region'])) {
        $region_part = strtolower(str_replace([' ', '/'], '_', $tile['region']));
        $classes[] = $region_part . '_restaurant';
    } else {
        // Dodaj klasę typu dla innych pól (start, event, duel, etc.)
        $classes[] = $type_class;
    }

    $id = $tile['id'];
     // Upewnij się, że logikę dla klas narożnych/krawędziowych masz poprawną
     // dla rzeczywistych ID pól w Twojej bazie (0-39 czy 0-40?)
     // Przykład dla 40 pól (0-39):
     if ($id === 0 || $id === 10 || $id === 20 || $id === 30){
         $classes[] ='corner';
     } elseif ($id >= 1 && $id <= 9) {
         $classes[] ='bottom-edge';
     } elseif ($id >= 11 && $id <= 19) {
          $classes[] ='left-edge';
     } elseif ($id >= 21 && $id <= 29) {
          $classes[] ='top-edge';
     } elseif ($id >= 31 && $id <= 39) {
          $classes[] = 'right-edge';
     }

    return implode(' ', $classes);
}

function get_space_content($tile) {
    $content = '<div class="tile-name">';
    $content .= '<div class="tile-name-text tile-' . htmlspecialchars($tile['type']) . ' '. htmlspecialchars($tile['region']).'">' . htmlspecialchars($tile['name']) . '</div>';
    $content .= '</div>';
    // tile-tile będzie stylowane kolorem paska w CSS na podstawie klasy regionu/typu
    $content .= '<div class="tile-tile"></div>';
    return $content;
}

// --- Koniec funkcji pomocniczych ---
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MONOPOLY</title>
    <link rel="stylesheet" href="../css/style_gameboard.css">
    <link rel="stylesheet" href="../css/pawns.css">
    <link rel="icon" href="../zdj/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../zdj/favicon.ico" type="image/x-icon">
</head>
<body>
    <div class="game-container">

        <div class="monopoly-board" id="monopoly-board">
            <div class="board-center-placeholder">
                 <?php
                 // Możesz wyświetlić logo, info o graczach lub puste miejsce
                 // Jeśli CSS dla .board-center-placeholder wyświetla info o graczach, upewnij się, że zmienna $players jest pobrana i niepusta
                 /*
                 if (!empty($players)) {
                     foreach ($players as $index => $p) {
                         $playerClassNumber = $index + 1;
                         echo "<div class='player-info player" . htmlspecialchars($playerClassNumber) . "'>";
                         echo "<p><b>" . htmlspecialchars($p['name_player'])." - " . htmlspecialchars($p['name_character']). "</b><br>";
                         echo "Monety: " . htmlspecialchars($p['coins']). " $ <br>";
                         // Dodaj pozostałe statystyki gracza w tabeli lub innym formacie
                         echo "</div>";
                     }
                 } else {
                      echo "<p>Ładowanie graczy...</p>"; // Komunikat tymczasowy
                 }
                 */
                 // Lub prosty placeholder:
                 echo "MONOPOLY <span>Custom Edition</span>";
                 ?>
            </div>

            <?php
            if (!empty($tiles)) {
                foreach ($tiles as $tile) {
                    $styleAttribute = ''; // Domyślnie brak stylu tła obrazkowego

                    // WARUNEK USUWAJĄCY OBRAZKI TŁA DLA RESTAURACJI Z PLIKAMI
                    // Nie generuj atrybutu style="--tile-bg: url(...)"
                    // jeśli pole ma niepusty 'file' ORAZ jego typ to 'restaurant'.
                    $omitImage = (!empty($tile['file']) && $tile['type'] === 'restaurant');

                    if (!empty($tile['file']) && !$omitImage) {
                        // Jeśli pole ma 'file' ORAZ NIE jest restauracją z plikiem,
                        // wtedy generuj styl tła obrazkowego.
                         $styleAttribute = ' style="--tile-bg: url(\'' . htmlspecialchars('../zdj/pola/' . $tile['file']) . '\');"';
                    }
                    // W przeciwnym razie ($omitImage jest true LUB 'file' jest puste), $styleAttribute pozostaje pusty.


                    echo '<div class="' . get_space_classes($tile) . '" id="space-' . $tile['id'] . '"' . $styleAttribute . '>';

                    echo get_space_content($tile);

                    // Kontener na pionki graczy. JS umieści pionki w tych kontenerach.
                    echo '<div class="players-on-tile"></div>';

                    echo '</div>';
                }
            } else {
                echo "<p style='grid-area: 6 / 5 / 8 / 9; z-index: 20; text-align: center;'>Nie udało się pobrać danych pól z bazy danych lub brak pól do wyświetlenia.</p>";
            }
            ?>
        </div>

        <div class="game-controls-container">
             <div class="dice-section">
                  <p class="roll-result-text">Wyrzucono: <strong id="wynikTekst">-</strong></p>
                  <img id="diceImage" src="../zdj/kostki/1.png" alt="Kostka" class="dice-image">
                  <button id="rollDiceButton" class="roll-dice-button">Rzuć kostką</button>
             </div>
             </div>

    </div> <script>
        // Sprawdź, czy PHP poprawnie pobrało ID gry i gracza
        const gameId = <?= json_encode($gameId) ?>; // Przekazuje ID gry (lub null, jeśli sesja była pusta)
        const currentPlayerId = <?= json_encode($currentPlayerId) ?>; // Przekazuje ID bieżącego gracza (lub null, jeśli nie znaleziono)

        // Możesz dodać proste sprawdzenie na wszelki wypadek, choć PHP powinno przekierować, jeśli gameId jest null
        if (gameId === null || currentPlayerId === null) {
            console.error("Błąd konfiguracji: ID gry lub ID bieżącego gracza nie zostało poprawnie przekazane.");
            // Możesz tutaj ukryć przycisk rzutu, wyświetlić komunikat błędu na stronie itp.
            const rollButton = document.getElementById('rollDiceButton');
            if(rollButton) {
                rollButton.disabled = true;
                rollButton.textContent = 'Błąd Gry';
            }
             const resultText = document.getElementById('wynikTekst');
             if(resultText) {
                 resultText.textContent = 'Błąd';
             }
             // Możesz też przekierować, ale lepiej obsłużyć to łagodnie po stronie klienta
             // window.location.href = 'index.php';
        } else {
             console.log("ID Gry:", gameId);
             console.log("ID Bieżącego Gracza:", currentPlayerId);
        }

    </script>

    <script src="../js/gameboard.js"> </script>

    <?php
    // Zamknij połączenie z bazą danych na końcu skryptu PHP
    if (isset($mysqli) && $mysqli instanceof mysqli && !$mysqli->connect_errno) {
        $mysqli->close();
    }
    ?>
</body>
</html>