<?php
session_start();

header('Content-Type: text/html; charset=utf-8'); 

if (!isset($_SESSION['game_id'])) {
    echo "<p style='color: red;'>Błąd: Brak aktywnej gry.</p>";
    exit();
}
$gameId = $_SESSION['game_id'];
// $current_player_id = $_SESSION['player_id'];
// losowe id narazie, potem poprawic trzeba
$current_player_id = 88;
$location = isset($_GET['location']) ? (int)$_GET['location'] : -1;

include_once './database_connect.php';
if (!isset($mysqli) || $mysqli->connect_errno) {
    echo "<p style='color: red;'>Błąd połączenia z bazą danych: " . ($mysqli->connect_error ?? 'Brak szczegółów błędu.') . "</p>";
    exit();
}

$mysqli->set_charset("utf8");

include_once './random_action.php';

// gracze
$players = [];
$sql_players = "SELECT id, name FROM players WHERE game_id = ?";
$stmt_players = $mysqli->prepare($sql_players);
if ($stmt_players) {
    $stmt_players->bind_param('i', $gameId);
    if ($stmt_players->execute()) {
        $result_players = $stmt_players->get_result();
        while ($row_player = $result_players->fetch_assoc()) {
            $players[$row_player['id']] = $row_player['name'];
        }
        $result_players->free();
    } else {
        error_log("Błąd wykonania zapytania SQL dla graczy (gry ID: " . $gameId . "): " . $stmt_players->error);
    }
    $stmt_players->close();
} else {
    error_log("Błąd przygotowania zapytania SQL dla graczy (gry ID: " . $gameId . "): " . $mysqli->error);
}

// wlasciciele
$sql_owner="SELECT id, game_id, tile_id, current_owner_id, current_level, is_mortgaged FROM `game_tiles` where game_id=? and current_owner_id=?;";
$stmt_owner = $mysqli->prepare($sql_owner);
$owner = [];
if ($stmt_owner) {
    $stmt_owner->bind_param('ii', $gameId, $current_player_id);
    if ($stmt_owner->execute()) {
        $result_owner = $stmt_owner->get_result();
        if ($result_owner->num_rows > 0) {
            while($row1 = $result_owner->fetch_assoc()) {
                $owner[] = $row1;
            }
        } else {
            error_log("Brak graczy w bazie danych dla gry o ID: " . $gameId);
        }
        $result_owner->free();
    } else {
        error_log("Błąd wykonania zapytania SQL dla graczy (gry ID: " . $gameId . "): " . $stmt_player->error);
    }
    $stmt_owner->close();
}

// pola
$sql_tiles_all = "SELECT
            t.id,
            t.name,
            t.type,
            tg.name AS group_name, 
            tg.color_code AS group_color,
            t.cost,
            t.base_rent,
            t.description,
            t.specialization,
            t.full_description,
            t.`file`,
            t.upgrade_cost,
            gt.current_owner_id AS owner_id,
            gt.current_level AS current_level,
            gt.is_mortgaged AS is_mortgaged
        FROM `tiles` t
        LEFT JOIN `tile_groups` tg ON t.group_id = tg.id
        LEFT JOIN `game_tiles` gt ON t.id = gt.tile_id AND gt.game_id = ?
        ORDER BY t.id";

$stmt_tiles_all = $mysqli->prepare($sql_tiles_all);
$tiles_all = []; 

if ($stmt_tiles_all) {
    $stmt_tiles_all->bind_param('i', $gameId);
    if ($stmt_tiles_all->execute()) {
        $result_tiles_all = $stmt_tiles_all->get_result();
        if ($result_tiles_all->num_rows > 0) {
            while($row_tile = $result_tiles_all->fetch_assoc()) {
                $row_tile['region'] = $row_tile['group_name'];
                $tiles_all[$row_tile['id']] = $row_tile;
            }
        } else {
            error_log("Brak pól w bazie danych do wyświetlenia dla gry o ID: " . $gameId);
        }
        $result_tiles_all->free();
    } else {
        error_log("Błąd wykonania zapytania SQL dla pól (gry ID: " . $gameId . "): " . $stmt_tiles_all->error);
    }
    $stmt_tiles_all->close();
} else {
    error_log("Błąd przygotowania zapytania SQL dla pól (gry ID: " . $gameId . "): " . $mysqli->error);
}
$tile = $tiles_all[$location] ?? null; 
$output_html_message = '';



// sprawdzanie pola
if (isset($_GET['location'])) {
    $location = (int)$_GET['location'];

    if ($location === 0) {
        $output_html_message .= '<h3 class = "entrance">' . htmlspecialchars($tile['description']) . '</h3>';
    } elseif (
        $location == 1 || $location == 3 || $location == 5 || $location == 6 ||
        $location == 8 || $location == 9 || $location == 12 || $location == 14 ||
        $location == 16 || $location == 18 || $location == 20 || $location == 21 ||
        $location == 24 || $location == 25 || $location == 27 || $location == 28 ||
        $location == 30 || $location == 31 || $location == 32 || $location == 36 ||
        $location == 39 || $location == 40 || $location == 42 || $location == 43
    ) {
        if ($tile && isset($tile['name'])) {
            if ($tile['owner_id'] !== null) {
                if ($tile['owner_id'] == $current_player_id) {
                    $output_html_message .= '<h3 class="restaurant-own">Jesteś w swojej restauracji!</h3>';
                    $output_html_message .= '<p class="restaurant-cost">Twoj stały przychód tutaj to: ' . htmlspecialchars($tile['base_rent']) . ' $</p>';
                } else {
                    $ownerName = $players[$tile['owner_id']] ?? null; 
                    $output_html_message .= '<h3 class="restaurant">Restauracja ' . htmlspecialchars($tile['name']) . '</h3>';
                    if ($ownerName !== null && $ownerName !== '') {
                        $output_html_message .= '<p class="restaurant-owner">Właściciel: ' . htmlspecialchars($ownerName) . '</p>';
                    }
                    $output_html_message .= '<p class="restaurant-cost">Koszty za jedzenie wyniosły Cię: ' . htmlspecialchars($tile['base_rent']) . ' $</p>';
                }
            } else {
                $output_html_message .= '<h3 class="restaurant">Restauracja ' . htmlspecialchars($tile['name']) . ' - ' . htmlspecialchars($tile['cost']) . ' $</h3><p class="restaurant spec">' . htmlspecialchars($tile['specialization']) . '</p>';
            }
        } 
    } elseif (
        $location === 2 || $location === 13 || $location === 17 ||
        $location === 26 || $location === 34 || $location === 41
    ) {
        $output_html_message .= "Stajesz do pojedynku kulinarnego! Wybierz swojego rywala!";
    } elseif (
        $location === 4 || $location === 10 || $location === 19 ||
        $location === 23 || $location === 35 || $location === 38
    ) {
        $output_html_message .= '<h3 class = "suprise">Niespodzianka</h3>';

        // losowanie kart niespodzianki
        $drawnCard = getRandomActionCard($mysqli);
        if ($drawnCard) {
            $output_html_message .= '<p class="surprise-description">' . htmlspecialchars($drawnCard->description) . '</p>';
        } else {
            $output_html_message .= '<p class="surprise-description">Nie znaleziono kart niespodzianek do wylosowania.</p>';
        }
        
    } elseif (
        $location === 7 || $location === 15 || $location === 22 || $location === 29 || $location === 37
    ) {
        if ($tile && isset($tile['name'])) {
            $output_html_message .= '<h3 class = "entrance">' . htmlspecialchars($tile['description']) . '</h3>';
        }
    } elseif ( 
        $location === 11
    ) {
        $output_html_message .= '<h3 class = "traning">Szkolenie</h3><p class = "traning">' . htmlspecialchars($tile['description']) . '</p>';
    
    } elseif ( 
        $location === 33
    ) {
        $output_html_message .= '<h3 class = "vacation">Urlop</h3><p class = "vacation">' . htmlspecialchars($tile['description']) . '</p>';
    } else { 
        $output_html_message .= "Wylądowałeś na polu numer " . $location . ".";
    }
} else { 
    $output_html_message .= "Brak parametru 'location'.";
}

echo $output_html_message;
if (isset($mysqli) && $mysqli instanceof mysqli && !$mysqli->connect_errno) {
    $mysqli->close();
}
?>