<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['game_id'])) {
    echo json_encode(['success' => false, 'message' => 'Game ID not set in session.']);
    exit();
}

$gameId = $_SESSION['game_id'];

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['player_id'])) {
    echo json_encode(['success' => false, 'message' => 'Player ID not provided.']);
    exit();
}

$playerId = $data['player_id'];

include_once './database_connect.php';

if (!isset($mysqli) || $mysqli->connect_errno) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

$mysqli->set_charset("utf8mb4");

$properties = [];

$sql_properties = "
    SELECT
        t.name AS property_name,
        t.cost AS property_cost,
        t.base_rent AS property_base_rent,
        t.type AS property_type,
        t.upgrade_cost AS property_upgrade_cost,
        tg.name AS property_group_name,
        tg.color_code AS property_group_color,
        gt.current_level AS current_level,
        gt.is_mortgaged AS is_mortgaged
    FROM tiles t
    LEFT JOIN tile_groups tg ON t.group_id = tg.id
    JOIN game_tiles gt ON t.id = gt.tile_id
    WHERE gt.current_owner_id = ? AND gt.game_id = ?
    ORDER BY tg.name, t.name";

if ($stmt_properties = $mysqli->prepare($sql_properties)) {
    $stmt_properties->bind_param('ii', $playerId, $gameId);
    if ($stmt_properties->execute()) {
        $result_properties = $stmt_properties->get_result();
        while ($row = $result_properties->fetch_assoc()) {
            $base_rent = $row['property_base_rent'];
            $level = $row['current_level'];
            $calculated_rent = $base_rent;

            // OGRANICZENIE MAKSYMALNEGO POZIOMU DO 5
            // Jeśli poziom jest wyższy niż 5, traktujemy go jako poziom 5 do celów obliczeniowych czynszu.
            $effective_level = min($level, 5);

            switch ($effective_level) {
                case 0:
                    $calculated_rent = $base_rent;
                    break;
                case 1:
                    $calculated_rent = floor(($base_rent * 5) / 10) * 10;
                    break;
                case 2:
                    $rent_level_1 = floor(($base_rent * 5) / 10) * 10;
                    $calculated_rent = $rent_level_1 * 3;
                    break;
                case 3:
                case 4:
                case 5:
                    $current_iterative_rent = $base_rent;
                    for ($i = 1; $i <= $effective_level; $i++) {
                        if ($i === 1) {
                            $current_iterative_rent = floor(($base_rent * 5) / 10) * 10;
                        } elseif ($i === 2) {
                            $rent_level_1 = floor(($base_rent * 5) / 10) * 10;
                            $current_iterative_rent = $rent_level_1 * 3;
                        } else { // i >= 3
                            $current_iterative_rent += 300;
                        }
                    }
                    $calculated_rent = $current_iterative_rent;
                    break;
                default:
                    // Jeśli jakimś cudem effective_level jest > 5 (co nie powinno się zdarzyć dzięki min())
                    // lub inna nieprzewidziana sytuacja, możemy zastosować domyślną logikę.
                    // Obecnie min($level, 5) zapobiega temu, więc ten 'default' jest bardziej zabezpieczeniem.
                    $calculated_rent = $base_rent; // Domyślnie, jeśli coś pójdzie nie tak.
                    break;
            }

            $properties[] = [
                'name' => $row['property_name'],
                'cost' => $row['property_cost'],
                'base_rent' => $base_rent,
                'calculated_rent' => (int)$calculated_rent,
                'type' => $row['property_type'],
                'region' => $row['property_group_name'] ? $row['property_group_name'] : 'Specjalne',
                'color' => $row['property_group_color'],
                'level' => $level, // Zwracamy rzeczywisty poziom z bazy
                'is_mortgaged' => (bool)$row['is_mortgaged'],
                'upgrade_cost' => $row['property_upgrade_cost']
            ];
        }
        $result_properties->free();
    } else {
        error_log("Error executing property query for player ID: " . $playerId . ", game ID: " . $gameId . " - " . $stmt_properties->error);
        echo json_encode(['success' => false, 'message' => 'Error fetching player properties.']);
        $stmt_properties->close();
        $mysqli->close();
        exit();
    }
    $stmt_properties->close();
} else {
    error_log("Error preparing property query: " . $mysqli->error);
    echo json_encode(['success' => false, 'message' => 'Error preparing property query.']);
    $mysqli->close();
    exit();
}

$sql_player_stats = "
    SELECT
        p.cook_skill,
        p.tolerance,
        p.business_acumen,
        p.belly_capacity,
        p.spice_sense,
        p.prep_time,
        p.tradition_affinity,
        p.coins
    FROM players p
    WHERE p.id = ? AND p.game_id = ?";

$playerStats = [];
if ($stmt_stats = $mysqli->prepare($sql_player_stats)) {
    $stmt_stats->bind_param('ii', $playerId, $gameId);
    if ($stmt_stats->execute()) {
        $result_stats = $stmt_stats->get_result();
        if ($row_stats = $result_stats->fetch_assoc()) {
            $playerStats = $row_stats;
        }
        $result_stats->free();
    } else {
        error_log("Error executing player stats query for player ID: " . $playerId . ", game ID: " . $gameId . " - " . $stmt_stats->error);
    }
    $stmt_stats->close();
} else {
    error_log("Error preparing player stats query: " . $mysqli->error);
}

$mysqli->close();

echo json_encode(['success' => true, 'properties' => $properties, 'player_stats' => $playerStats]);