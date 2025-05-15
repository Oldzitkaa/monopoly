<?php
// Dołącza plik z połączeniem do bazy danych
// Pamiętaj, żeby ścieżka była poprawna względem tego pliku!
include_once './database_connect.php';

// Sprawdza, czy połączenie z bazą danych zostało poprawnie nawiązane
if (!isset($mysqli) || $mysqli->connect_errno) {
    // Jeśli połączenie się nie udało, przerywa działanie skryptu i wyświetla komunikat błędu
    die("Brak aktywnego połączenia z bazą danych po dołączeniu pliku.");
}

// Ustawia kodowanie znaków dla połączenia z bazą danych
$mysqli->set_charset("utf8");

// Zapytanie SQL do pobrania danych wszystkich pól planszy
// Używamy backticków dla nazwy 'file', ponieważ 'file' może być słowem kluczowym w niektórych systemach baz danych.
// SELECT wszystkich potrzebnych kolumn dla pól planszy
$sql = "SELECT id, name, type, region, cost, base_rent, description, file FROM tiles ORDER BY id";

// Wykonuje zapytanie SQL
$result = $mysqli->query($sql);

// Tablica do przechowywania danych pól planszy
$tiles = [];

// Sprawdza, czy zapytanie zakończyło się sukcesem
if ($result) {
    // Sprawdza, czy zapytanie zwróci jakiekolwiek wiersze (pola planszy)
    if ($result->num_rows > 0) {
        // Pobiera każdy wiersz wyników jako tablicę asocjacyjną i dodaje do tablicy $tiles
        while($row = $result->fetch_assoc()) {
            $tiles[] = $row;
        }
    } else {
        // Możesz dodać tutaj obsługę przypadku, gdy brak pól w bazie (np. komunikat)
        // echo "Brak pól w bazie danych do wyświetlenia.";
    }
} else {
    // Jeśli zapytanie SQL się nie powiodło, wyświetla komunikat błędu
    echo "Błąd zapytania SQL: " . $mysqli->error;
}

// Zamyka połączenie z bazą danych po pobraniu danych
$mysqli->close();

/**
 * Funkcja generująca klasy CSS dla danego pola planszy.
 * Klasy te określają typ pola, region oraz pozycję na planszy (narożnik/krawędź).
 *
 * @param array $tile Tablica asocjacyjna z danymi pojedynczego pola planszy.
 * @return string Ciąg znaków z klasami CSS oddzielonymi spacjami.
 */
function get_space_classes($tile) {
    $classes = ['tile']; // Każde pole ma podstawową klasę 'tile'

    // Generuje klasę na podstawie typu pola, zamieniając spacje/podkreślenia/ukośniki na podkreślenia i na małe litery
    $type_class = strtolower(str_replace([' ', '_', '/'], '_', $tile['type']));

    // Dodaje klasy specyficzne dla typu i regionu restauracji
    if ($tile['type'] === 'restaurant' && !empty($tile['region'])) {
        $region_part = strtolower(str_replace([' ', '/'], '_', $tile['region']));
        $classes[] = $region_part . '_restaurant';
    } else {
        // Dla innych typów pól, dodaje tylko klasę typu
        $classes[] = $type_class;
    }

    // Dodaje klasy określające pozycje na planszy (narożniki i krawędzie)
    $id = $tile['id'];
    // Zakładamy, że pola o ID 0, 10, 21, 31 to narożniki (przykład, dostosuj do faktycznej liczby pól)
    if (in_array($id,[0,10,21,31])) { // WARTO SPRAWDZIĆ TE ID Z LICZBĄ PÓL
        $classes[] = 'corner';
    }
    // Przykładowe klasy krawędzi - to zależy od dokładnego ułożenia pól na siatce!
    // Musi odpowiadać logice pozycjonowania w style_gameboard.scss
    elseif ($id >= 1 && $id <= 9) { // Przykład dla dolnej krawędzi
        $classes[] = 'bottom-edge';
    } elseif ($id >= 11 && $id <= 20) { // Przykład dla lewej krawędzi
        $classes[] = 'left-edge'; // Dodano klasę left-edge dla klarowności
    } elseif ($id >= 22 && $id <= 30) { // Przykład dla górnej krawędzi
        $classes[] = 'top-edge';
    } elseif ($id >= 32 && $id <= 40) { // Przykład dla prawej krawędzi (bez rogów) - PRZYKŁADOWY ZAKRES
         $classes[] = 'right-edge'; // Dodano klasę right-edge dla klarowności
    }
    // Dostosuj powyższe zakresy (1-9, 11-20, 22-30, 32-40/43?)
    // do rzeczywistej liczby i rozmieszczenia kafelków między rogami w Twojej bazie.

    // Dodaje klasę, jeśli pole ma przypisany plik obrazka
    if (!empty($tile['file'])) {
        $classes[] = 'has-tile-image';
    }

    // Zwraca wszystkie zebrane klasy jako jeden ciąg znaków
    return implode(' ', $classes);
}

/**
 * Funkcja generująca wewnętrzną zawartość HTML dla danego pola planszy.
 * Zawiera nazwę pola i podstawowe divy do stylizacji/pozycjonowania.
 *
 * @param array $tile Tablica asocjacyjna z danymi pojedynczego pola planszy.
 * @return string Ciąg znaków z wewnętrznym kodem HTML pola.
 */
function get_space_content($tile) {
    $content = '<div class="tile-name">';
    // Używamy htmlspecialchars na danych z bazy, aby zapobiec XSS
    $content .= '<div class="tile-name-text tile-' . htmlspecialchars($tile['type']) . ' '. htmlspecialchars($tile['region']).'">' . htmlspecialchars($tile['name']) . '</div>';
    $content .= '</div>';
    // Puste divy do wykorzystania przez CSS/JS (np. na pionki graczy, wskaźniki)
    $content .= '<div class="tile-tile"></div>'; // Prawdopodobnie do kolorowego paska lub wskaźnika
    // $content .= '<div class="tile-color-bar"></div>'; // Dodatkowy element, np. na dolny pasek koloru restauracji
    // Możesz tutaj dodać więcej elementów, np. na cenę, czynsz, ikony, itp.
    // $content .= '<div class="tile-price">' . htmlspecialchars($tile['cost']) . '</div>';
    // $content .= '<div class="tile-rent">' . htmlspecialchars($tile['base_rent']) . '</div>';
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
    <link rel="icon" href="../zdj/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../zdj/favicon.ico" type="image/x-icon">
</head>
<body>

    <div class="game-container">

        <div class="monopoly-board" id="monopoly-board">
            <div class="board-center-placeholder">
                MONOPOLY
                <span>Custom Edition</span>
            </div>

            <?php
            // Generowanie kafelków planszy na podstawie danych z bazy
            // Sprawdza, czy udało się pobrać dane pól z bazy
            if (!empty($tiles)) {
                // Iteruje przez wszystkie pola pobrane z bazy danych
                foreach ($tiles as $tile) {
                    // Tworzy HTML dla pojedynczego pola planszy
                    // Klasy CSS są generowane dynamicznie przez funkcję get_space_classes
                    // ID pola jest używane jako ID elementu div (np. id="space-0", id="space-1"...)
                    // Styl CSS --tile-bg ustawia zmienną CSS z URL obrazka tła pola (jeśli 'file' nie jest puste)
                    echo '<div class="' . get_space_classes($tile) . '" id="space-' . $tile['id'] . '" ';
                    // Dodaj styl inline dla obrazka tła kafelka, jeśli istnieje
                    if (!empty($tile['file'])) {
                         echo 'style="--tile-bg: url(\'../zdj/pola/' . htmlspecialchars($tile['file']) . '\');"';
                    }
                    echo '>';

                    // Generuj wewnętrzną zawartość HTML dla pola
                    echo get_space_content($tile);

                    // Możesz dodać tu elementy, które mają być ZAWSZE w każdym kafelku,
                    // np. kontener na znaczniki graczy
                    echo '<div class="players-on-tile"></div>'; // Kontener na znaczniki graczy w tym kafelku

                    echo '</div>'; // Zamknięcie div kafelka
                }
            } else {
                // Wyświetla komunikat, jeśli nie udało się pobrać danych pól
                // Umieszczony w siatce, żeby był widoczny w centrum
                echo "<p style='grid-area: 6 / 5 / 8 / 9; z-index: 20; text-align: center;'>Nie udało się pobrać danych pól z bazy danych lub brak pól do wyświetlenia.</p>";
            }
            ?>
        </div>
        <div class="dice-section">
             <p class="roll-result-text">Wyrzucono: <strong id="wynikTekst">-</strong></p>
             <img id="diceImage" src="../zdj/kostki/1.png" alt="Kostka" class="dice-image">
             <button id="rollDiceButton" class="roll-dice-button">Rzuć kostką</button>
        </div>
    </div>
    
    <script src="../js/gameboard.js"></script>

</body>
</html>