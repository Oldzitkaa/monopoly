<?php
session_start();

header('Content-Type: text/html; charset=utf-8'); 

if (!isset($_SESSION['game_id'])) {
    echo "<p style='color: red;'>Błąd: Brak aktywnej gry.</p>";
    exit();
}
$gameId = $_SESSION['game_id'];
// $current_player_id = $_SESSION['player_id'];
$current_player_id = 1;
$location = isset($_GET['location']) ? (int)$_GET['location'] : -1;

include_once './database_connect.php';
if (!isset($mysqli) || $mysqli->connect_errno) {
    echo "<p style='color: red;'>Błąd połączenia z bazą danych: " . ($mysqli->connect_error ?? 'Brak szczegółów błędu.') . "</p>";
    exit();
}

$mysqli->set_charset("utf8");


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
        }
        $result_player->free();
    } else {
        error_log("Błąd wykonania zapytania SQL dla graczy (gry ID: " . $gameId . "): " . $stmt_player->error);
    }
    $stmt_player->close();
}

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

if (
    $location === 2 || $location === 13 || $location === 17 ||
    $location === 26 || $location === 34 || $location === 41
) {
    // pojedynek
    $players_in_game = [];
    $sql_player_duel = "SELECT id as id_player, name as name_player FROM `players` WHERE game_id = ? AND NOT id= ?;"; 
    $stmt_player_duel = $mysqli->prepare($sql_player_duel);

    if ($stmt_player_duel) {
        $stmt_player_duel->bind_param('ii', $gameId, $current_player_id); 
        if ($stmt_player_duel->execute()) {
            $result_player_duel = $stmt_player_duel->get_result();
            while($row = $result_player_duel->fetch_assoc()) {
                $players_in_game[] = $row;
            }
            $result_player_duel->free();
        } else {
            error_log("Błąd wykonania zapytania SQL dla graczy w pojedynku (gry ID: " . $gameId . "): " . $stmt_player_duel->error);
            $output_html .= "<p'>Błąd pobierania danych graczy.</p>";
        }
        $stmt_player_duel->close();
    } else {
        error_log("Błąd przygotowania zapytania SQL dla graczy w pojedynku (gry ID: " . $gameId . "): " . $mysqli->error);
        $output_html .= "<p>Błąd przygotowania zapytania SQL dla graczy.</p>";
    }

    if (!empty($players_in_game)) {
        $output_html .= '<p>Wybierz rywala do pojedynku:</p>';
        foreach ($players_in_game as $player_data) {
            $output_html .= '<button class="action-button duel-player-button" data-action-type="duel" data-target-player-id="' . htmlspecialchars($player_data['id_player']) . '">' . htmlspecialchars($player_data['name_player']) . '</button>';
        }
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

} elseif (
    $location === 0 || $location === 7 || $location === 15 || 
    $location === 22 || $location === 29 || $location === 37 
) {
    // WEJŚĆ DO KONTYNENTÓW
    $output_html .= '<button class="action-button accept">Super</button>';
}

echo $output_html;
if (isset($mysqli) && $mysqli instanceof mysqli && !$mysqli->connect_errno) {
    $mysqli->close();
}
?>