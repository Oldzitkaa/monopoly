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
$sql = "SELECT id, name, type, region, cost, base_rent, description, `file` FROM tiles ORDER BY id";
$result = $mysqli->query($sql);
$tiles = [];

if ($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $tiles[] = $row;
        }
    } else {
        error_log("Brak pól w bazie danych do wyświetlenia dla gry o ID: " . $gameId);
        echo "<p style='color: red;'>Błąd: Brak danych pól planszy w bazie danych.</p>";
    }
    $result->free();
} else {
    error_log("Błąd zapytania SQL dla pól (gry ID: " . $gameId . "): " . $mysqli->error);
    echo "<p style='color: red;'>Błąd zapytania SQL dla pól: " . $mysqli->error . "</p>";
}

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
// Użyj przygotowanej instrukcji, aby bezpiecznie przekazać $gameId
$stmt_player = $mysqli->prepare($sql_player);
if ($stmt_player) {
    // Bindowanie parametru (integer: gameId)
    $stmt_player->bind_param('i', $gameId);
    // Wykonanie zapytania
    if ($stmt_player->execute()) {
        $result_player = $stmt_player->get_result();
        $player = []; // Tablica do przechowywania danych graczy
        // Sprawdź, czy zapytanie zwróciło wyniki
        if ($result_player->num_rows > 0) {
            // Pobierz każdy wiersz wyniku jako tablicę asocjacyjną i dodaj do tablicy $player
            while($row1 = $result_player->fetch_assoc()) {
                $player[] = $row1;
            }
        } else {
            // Obsłuż przypadek, gdy dla danej gry nie ma graczy (nie powinno się zdarzyć przy poprawnym tworzeniu gry)
            error_log("Brak graczy w bazie danych dla gry o ID: " . $gameId);
            echo "<p style='color: red;'>Błąd: Brak danych graczy dla tej gry.</p>";
        }
        // Zwolnij pamięć zajmowaną przez wynik zapytania
        $result_player->free();
    } else {
        // Obsłuż błąd wykonania zapytania
        error_log("Błąd wykonania zapytania SQL dla graczy (gry ID: " . $gameId . "): " . $stmt_player->error);
        echo "<p style='color: red;'>Błąd wykonania zapytania SQL dla graczy: " . $stmt_player->error . "</p>";
        // W przypadku błędu tablica $player pozostanie pusta
    }
    // Zamknij przygotowaną instrukcję
    $stmt_player->close();
} else {
    // Obsłuż błąd przygotowania zapytania
    error_log("Błąd przygotowania zapytania SQL dla graczy (gry ID: " . $gameId . "): " . $mysqli->error);
    echo "<p style='color: red;'>Błąd przygotowania zapytania SQL dla graczy: " . $mysqli->error . "</p>";
    // W przypadku błędu tablica $player pozostanie pusta
}
// Zamknij połączenie z bazą danych po pobraniu wszystkich potrzebnych danych
if (isset($mysqli) && $mysqli instanceof mysqli && !$mysqli->connect_errno) {
    $mysqli->close();
}
// --- Funkcje pomocnicze do generowania HTML pól ---
// Funkcja generująca klasy CSS dla elementu pola na podstawie jego danych
function get_space_classes($tile) {
    $classes = ['tile']; // Domyślna klasa dla każdego pola
    // Generuj klasę na podstawie typu pola (np. 'restaurant', 'special', 'corner')
    // Zamienia spacje, podkreślenia i slashe na podkreślenia w nazwie typu
    $type_class = strtolower(str_replace([' ', '_', '/'], '_', $tile['type']));
    // Jeśli pole jest typu 'restaurant' i ma zdefiniowany region, dodaj klasę regionu + '_restaurant'
    if ($tile['type'] === 'restaurant' && !empty($tile['region'])) {
        $region_part = strtolower(str_replace([' ', '/'], '_', $tile['region']));
        $classes[] = $region_part . '_restaurant';
    } else {
        // W przeciwnym razie dodaj tylko klasę typu
        $classes[] = $type_class;
    }
    // Dodaj klasy specyficzne dla pozycji na krawędziach (na podstawie ID pola)
    $id = $tile['id'];
    // Narożniki
    if (in_array($id,[0,10,20,30])){ // Zakładając 40 pól (0-39) lub 41 (0-40), ID narożników to 0, 10, 20, 30
        $classes[] ='corner';
    }
    // Krawędzie (bez narożników) - dostosuj zakresy ID do swojej planszy
    elseif ($id >= 1 && $id <= 9) { // Dolna krawędź (pola 1-9)
        $classes[] ='bottom-edge';
    }
    // Zakładając, że pola idą od 0 w prawo, w górę, w lewo, w dół:
    elseif ($id >= 11 && $id <= 19) { // Lewa krawędź (pola 11-19)
        $classes[] = 'left-edge';
    }
    elseif ($id >= 21 && $id <= 29) { // Górna krawędź (pola 21-29)
        $classes[] ='top-edge';
    }
    elseif ($id >= 31 && $id <= 39) { // Prawa krawędź (pola 31-39)
        $classes[] = 'right-edge';
    }
    // Jeśli masz 41 pól (0-40), narożniki to 0, 10, 20, 31, a zakresy krawędzi będą inne.
    // Narożniki: 0, 10, 21, 31 (według poprzedniego snippeta) - dostosuj if/elseif poniżej
    /*
    if (in_array($id,[0,10,21,31])){
        $classes[] ='corner';
    } elseif ($id >= 1 && $id <= 9) { // Dolna krawędź
        $classes[] ='bottom-edge';
    } elseif ($id >= 11 && $id <= 20) { // Lewa krawędź
        $classes[] = 'left-edge';
    } elseif ($id >= 22 && $id <= 30) { // Górna krawędź
        $classes[] ='top-edge';
    } elseif ($id >= 32 && $id <= 40) { // Prawa krawędź
        $classes[] = 'right-edge';
    }
    */
    // Dodaj klasę, jeśli pole ma przypisany plik graficzny tła
    if (!empty($tile['file'])) {
        $classes[] = 'has-tile-image';
    }
    // Zwróć klasy jako pojedynczy ciąg oddzielony spacjami
    return implode(' ', $classes);
}
// Funkcja generująca wewnętrzną strukturę HTML dla elementu pola
function get_space_content($tile) {
    $content = '<div class="tile-name">';
    // Wyświetl nazwę pola. Dodatkowe klasy dla elementu tekstowego mogą być użyte do stylizacji specyficznej dla typu/regionu.
    $content .= '<div class="tile-name-text tile-' . htmlspecialchars($tile['type']) . ' '. htmlspecialchars($tile['region']).'">' . htmlspecialchars($tile['name']) . '</div>';
    $content .= '</div>';
    // Element reprezentujący kolorowy pasek na polach własnościowych
    $content .= '<div class="tile-tile"></div>';
    // Element reprezentujący ewentualny pasek koloru/oznaczenia (np. na polach akcji)
    $content .= '<div class="tile-color-bar"></div>';
    // Można dodać tutaj więcej elementów, np. do wyświetlania ceny, czynszu itp.
    // $content .= '<div class="tile-price">' . htmlspecialchars($tile['cost']) . '</div>';
    // $content .= '<div class="tile-rent">' . htmlspecialchars($tile['base_rent']) . '</div>';
    return $content;
}
// --- Logika PHP do znalezienia ID gracza, którego jest tura ---
$currentPlayerId = null;
if (!empty($player)) {
    foreach ($player as $p) {
        // Sprawdź, czy pole 'is_turn' ma wartość 1 (lub inną wartość oznaczającą aktualną turę)
        if (isset($p['is_turn']) && $p['is_turn'] == 1) {
            $currentPlayerId = $p['id_player'];
            break; // Zakończ pętlę po znalezieniu gracza
        }
    }
}
// Jeśli currentPlayerId nadal jest null, może to wskazywać na błąd w danych gry,
// bo zawsze powinien być jeden gracz, którego jest tura. Możesz dodać logowanie błędu.
if ($currentPlayerId === null && !empty($player)) {
    error_log("Błąd: Nie znaleziono gracza, którego jest tura dla gry ID: " . $gameId);
    // Możesz zdecydować, jak obsłużyć tę sytuację na stronie
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
                echo "<p><b>" . htmlspecialchars($p['name_player'])." - " . htmlspecialchars($p['character_name']). "</b><br>";
                echo "Monety: " . htmlspecialchars($p['coins']). " $ <br>";
                echo "<table>";
                echo "<tr><td>Pojemność brzucha:</td><td>" . htmlspecialchars($p['belly_capacity']). "</td></tr>";
                echo "<tr><td>Tolerancja ostrości:</td><td>" . htmlspecialchars($p['tolerance']). "</td></tr>";
                echo "<tr><td>Czas przygotowania:</td><td>" . htmlspecialchars($p['prep_time']). "</td></tr>";
                echo "<tr><td>Przywiązanie do tradycji:</td><td>" . htmlspecialchars($p['tradition_affinity']). "</td></tr>";
                echo "<tr><td>Umiejętności gotowania:</td><td>" . htmlspecialchars($p['cook_skill']). "</td></tr>";
                echo "<tr><td>Zmysł do przypraw:</td><td>" . htmlspecialchars($p['spice_sense']). "</td></tr>";
                echo "<tr><td>Łeb do biznesu:</td><td>" . htmlspecialchars($p['business_acumen']). "</td></tr>";
                echo "</table>";
                echo "<div class='handle-all handle" . htmlspecialchars($playerClassNumber) ."'><span> </span><span> </span><span> </span></div></div>";
            }
        } else {
            echo "<p>Brak graczy w bazie danych dla tej gry lub błąd ładowania.</p>";
        }
        ?>
        <div class="player-more more1"></div>
        <div class="player-more more2"></div>
        <div class="player-more more3"></div>
        <div class="player-more more4"></div>
        <!-- <div class="card card-left"></div>
        <div class="card card-right"></div> -->
    </div>
    <?php
    $tile_counter = 0;
    if (!empty($tiles)) {
        foreach ($tiles as $tile) {
            $tile_counter++; // Inkrementacja licznika
            // --- ZModyfikowany kod: Warunkowe dodawanie atrybutu style dla tła, z pominięciem restauracji ---
            $style_attribute = ''; // Domyślnie brak atrybutu style
            // Sprawdzamy, czy pole 'file' nie jest puste ORAZ czy typ pola NIE JEST 'restaurant'.
            // Jeśli chcesz pominąć inne typy pól, dodaj je do warunku (np. && $tile['type'] !== 'special')
            if (!empty($tile['file']) && $tile['type'] !== 'restaurant') {
                // Jeśli warunek jest spełniony, budujemy ciąg znaków dla atrybutu style
                // Używamy htmlspecialchars, aby zabezpieczyć nazwę pliku
                $style_attribute = ' style="--tile-bg: url(\'../zdj/pola/' . htmlspecialchars($tile['file']) . '\');"';
            }
            // Koniec zmodyfikowanego kodu
            // Generuj div dla pojedynczego pola, dodając $style_attribute
            echo '<div class="' . get_space_classes($tile) . '" id="space-' . $tile['id'] . '"' . $style_attribute . '>';
            // Generuj wewnętrzną zawartość pola (nazwa, paski koloru itp.)
            echo get_space_content($tile);
            // Kontener na pionki graczy. JS umieści pionki w tych kontenerach.
            echo '<div class="players-on-tile"></div>';
            echo '</div>'; // Zamknięcie div.tile
        }
    } else {
        // Komunikat, jeśli nie udało się pobrać danych pól
        echo "<p style='grid-area: 6 / 5 / 8 / 9; z-index: 20; text-align: center; color: black;'>Nie udało się pobrać danych pól z bazy danych lub brak pól do wyświetlenia.</p>";
    }
    ?>
</div> <div class="game-controls-container">
    <div class="dice-section">
        <p class="roll-result-text">Wyrzucono: <strong id="wynikTekst">-</strong></p>
        <img id="diceImage" src="../zdj/kostki/1.png" alt="Kostka" class="dice-image">
        <button id="rollDiceButton" class="roll-dice-button">Rzuć kostką</button>
    </div>
</div> <?php
if (!empty($player)) {
    foreach ($player as $p) {
        echo "<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Znajdź pole planszy odpowiadające aktualnej lokalizacji gracza
        const tile = document.getElementById('space-" . intval($p['location_player']) . "');
        if (tile) {
            // Utwórz nowy element div reprezentujący pionek gracza
            const playerDiv = document.createElement('div');
            // Dodaj klasy CSS do pionka (player-token dla podstawy, player-X dla koloru)
            playerDiv.classList.add('player-token');
            playerDiv.classList.add('player-" . intval($p['id_player']) . "'); // Używamy ID gracza
            // Ustaw atrybut title, aby po najechaniu myszą wyświetlał nick gracza
            playerDiv.title = '" . htmlspecialchars($p['name_player']) . "';
            // Znajdź kontener na pionki w bieżącym polu i dodaj do niego pionek
            const playersContainer = tile.querySelector('.players-on-tile');
            if(playersContainer) {
                playersContainer.appendChild(playerDiv);
            } else {
                console.error('Nie znaleziono kontenera .players-on-tile w polu space-" . intval($p['location_player']) . "');
            }
        } else {
            console.error('Nie znaleziono pola planszy o ID space-" . intval($p['location_player']) . " dla gracza " . htmlspecialchars($p['name_player']) . "');
        }
    });
    </script>";
    }
}
?>
<script>
    // Ustaw zmienną gameId w JavaScript, używając wartości z PHP
    // Używamy json_encode, aby poprawnie przekazać wartość numeryczną/string i zabezpieczyć przed XSS
    const gameId = <?= json_encode($gameId); ?>;
    // Ustaw zmienną currentPlayerId w JavaScript, używając wartości znalezionej w PHP
    const currentPlayerId = <?= json_encode($currentPlayerId); ?>;
    // Opcjonalne: console.log do sprawdzenia, czy zmienne są ustawione
    console.log('gameId ustawione dla JS:', gameId);
    console.log('currentPlayerId ustawione dla JS:', currentPlayerId);
</script>
<div class="end-game-div">
    <a href="./end_game.php"><button class="btn-end-game">Zakończ gre</button></a>
</div>
<script src="../js/gameboard.js"> </script>
<script src="../js/gameboard_inner.js"></script>
</body>
</html>