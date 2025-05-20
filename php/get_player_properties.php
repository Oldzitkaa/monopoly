<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['game_id'])) {
    echo json_encode(['success' => false, 'message' => 'Game ID not set in session.']);
    exit();
}

$gameId = $_SESSION['game_id'];

// Get the POST data
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

// Fetch properties owned by the player (zmodyfikowane zapytanie)
$sql_properties = "
    SELECT
        t.name AS property_name,
        t.cost AS property_cost,
        t.base_rent AS property_rent,
        t.type AS property_type,
        tg.name AS property_group_name,
        tg.color AS property_group_color
    FROM tiles t
    LEFT JOIN tile_groups tg ON t.group_id = tg.id
    WHERE t.owner_id = ? AND t.game_id = ?
    ORDER BY tg.name, t.name";

if ($stmt_properties = $mysqli->prepare($sql_properties)) {
    $stmt_properties->bind_param('ii', $playerId, $gameId);
    if ($stmt_properties->execute()) {
        $result_properties = $stmt_properties->get_result();
        while ($row = $result_properties->fetch_assoc()) {
            // Zmieniono strukturę zwracanych danych dla łatwiejszego renderowania w JS
            $properties[] = [
                'name' => $row['property_name'],
                'cost' => $row['property_cost'],
                'rent' => $row['property_rent'],
                'type' => $row['property_type'],
                'region' => $row['property_group_name'] ? $row['property_group_name'] : 'Specjalne',
                'color' => $row['property_group_color']
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

// Fetch player's current stats (for the "Umiejętności" section) and coins
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