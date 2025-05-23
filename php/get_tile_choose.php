<?php
session_start();

header('Content-Type: text/html; charset=utf-8');

if (!isset($_SESSION['game_id'])) {
    echo "<p style='color: red;'>Błąd: Brak aktywnej gry.</p>";
    exit();
}
$gameId = $_SESSION['game_id'];
// $current_player_id = $_SESSION['player_id'];
$current_player_id = 37;
$location = isset($_GET['location']) ? (int)$_GET['location'] : -1;
$duel_action = isset($_GET['duel']) ? $_GET['duel'] : '';

include_once './database_connect.php';
if (!isset($mysqli) || $mysqli->connect_errno) {
    echo "<p style='color: red;'>Błąd połączenia z bazą danych: " . ($mysqli->connect_error ?? 'Brak szczegółów błędu.') . "</p>";
    exit();
}
$mysqli->set_charset("utf8");

include_once './random_duel.php';

// gracze
$players = [];
$sql_all_players = "SELECT
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
                p.color as player_color,
                c.name as character_name
            FROM `players` p
            JOIN `characters` c ON p.character_id = c.id
            WHERE p.game_id = ?
            ORDER BY p.turn_order ASC";
$stmt_all_players = $mysqli->prepare($sql_all_players);

if ($stmt_all_players) {
    $stmt_all_players->bind_param('i', $gameId);
    if ($stmt_all_players->execute()) {
        $result_all_players = $stmt_all_players->get_result();
        if ($result_all_players->num_rows > 0) {
            while($row_player = $result_all_players->fetch_assoc()) {
                $players[$row_player['id_player']] = $row_player;
            }
        } else {
            error_log("Brak graczy w bazie danych dla gry o ID: " . $gameId);
        }
        $result_all_players->free();
    } else {
        error_log("Błąd wykonania zapytania SQL dla graczy (gry ID: " . $gameId . "): " . $stmt_all_players->error);
    }
    $stmt_all_players->close();
} else {
    error_log("Błąd przygotowania zapytania SQL dla graczy (gry ID: " . $gameId . "): " . $mysqli->error);
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

$output_html = '';

// pola

// $output_html_message .= '<h3 class = "suprise">Niespodzianka</h3>';

// // losowanie kart niespodzianki
// $drawnCard = getRandomActionCard($mysqli);
// if ($drawnCard) {
//     $output_html_message .= '<p class="surprise-description">' . htmlspecialchars($drawnCard->description) . '</p>';
// } else {
//     $output_html_message .= '<p class="surprise-description">Nie znaleziono kart niespodzianek do wylosowania.</p>';
// }
// if ($duel_action === 'draw_card') {
//     // pojedynek
//     $rival_id = isset($_GET['rival_id']) ? (int)$_GET['rival_id'] : null;

//     if ($rival_id === null) {
//         echo "<p style='color: red;'>Błąd: Brak ID rywala dla pojedynku.</p>";
//         exit();
//     }

//     $drawnCard = getRandomDuelCard($mysqli);

//     // if ($drawnCard) {
//     //     $rival_name = $players[$rival_id]['name_player'] ?? 'Nieznany Rywal';
//     //     echo '<p class="duel-description">Wybrano rywala: ' . htmlspecialchars($rival_name) . '</p>';
//     //     echo '<p class="duel-description">' . htmlspecialchars($drawnCard->description) . '</p>';
//     // } else {
//     //     echo '<p class="duel-description">Nie znaleziono kart pojedynku do wylosowania.</p>';
//     // }
//     // exit();

// } elseif (
//     $location === 2 || $location === 13 || $location === 17 ||
//     $location === 26 || $location === 34 || $location === 41


if (
    $location === 2 || $location === 13 || $location === 17 ||
    $location === 26 || $location === 34 || $location === 41
) {
    $drawnCard = getRandomDuelCard($mysqli);

    $rival_players_data = [];
    foreach ($players as $player_id => $player_data) {
        if ($player_id != $current_player_id) {
            $rival_players_data[] = $player_data;
        }
    }

    if (!empty($rival_players_data)) {
        $output_html .= '<div id="game-actions" class="game-actions"';
        $output_html .= 'data-current-player-id="' . htmlspecialchars($current_player_id) . '" ';
        $output_html .= 'data-location="' . htmlspecialchars($location) . '">';

        $output_html .= '<p id="duel-prompt">Wybierz rywala do pojedynku:</p>';
        foreach ($rival_players_data as $player_data) {
            $output_html .= '<button class="action-button btn-player' . htmlspecialchars($player_data['turn_order']) . '" onclick="randomCard()" data-rival-id="' . htmlspecialchars($player_data['id_player']) . '">' . htmlspecialchars($player_data['name_player']) . '</button>';
        }
        $output_html .= '<div id="duel-card-result" class="duel-card-result"></div>';
        if ($drawnCard) {
            $output_html .= '<p class="duel-description">' . htmlspecialchars($drawnCard->description) . '</p>';
        } else {
            $output_html .= '<p class="duel-description">Nie znaleziono kart pojedynku do wylosowania.</p>';
        }
        $output_html .= '</div>';

    } else {
        $output_html .= '<p>Brak innych graczy do pojedynku.</p>';
    }
} elseif (
    // restauracje
    $location == 1 || $location == 3 || $location == 5 || $location == 6 ||
    $location == 8 || $location == 9 || $location == 12 || $location == 14 ||
    $location == 16 || $location == 18 || $location == 20 || $location == 21 ||
    $location == 24 || $location == 25 || $location == 27 || $location == 28 ||
    $location == 30 || $location == 31 || $location == 32 || $location == 36 ||
    $location == 39 || $location == 40 || $location == 42 || $location == 43
) {
    if ($tile && isset($tile['name'])) {
        $output_html .= '<p>Chcesz kupić restaurację ' . htmlspecialchars($tile['name']) . '?</p>';
    } else {
        $output_html .= '<p>Chcesz kupić tę restaurację? </p>';
    }
    $output_html .= '<button class="action-button restaurant-buy-button" data-action-type="buy_restaurant">Tak, kupuję</button>';
    $output_html .= '<button class="action-button restaurant-notbuy-button" data-action-type="not_interested">Nie jestem zainteresowana</button>';

} elseif (
    // niespodzianka
    $location === 4 || $location === 10 || $location === 19 ||
    $location === 23 || $location === 35 || $location === 38
) {
    $output_html .= '<button class="action-button accept">Super</button>';
} elseif ($location === 11) {
    // szkolenie
    $output_html .= '<button class="action-button accept">Lece się szkolić</button>';

} elseif ($location === 33) {
    // urlop
    $output_html .= '<button class="action-button accept">Super</button>';

} elseif ($location === 0 ){
    $output_html .= '<button class="action-button accept">Super</button>';
} elseif ($location === 22 ){
    $output_html .= '<button class="action-button accept">Ooo super</button>';
}elseif (
    $location === 7 || $location === 15 ||
    $location === 29 || $location === 37
) {
    // kontynenty
    $output_html .= '<button class="action-button accept">Płacę</button>';
}

echo $output_html;
if (isset($mysqli) && $mysqli instanceof mysqli && !$mysqli->connect_errno) {
    $mysqli->close();
}
?>