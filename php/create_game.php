<?php
header('Content-Type: application/json');
require_once './database_connect.php';

$response = [
    'success' => false,
    'message' => 'Wystąpił nieznany błąd serwera.',
    'gameId' => null,
    'debugInfo' => []
];
$stmtGame = null;
$stmtCharData = null;
$stmtPlayer = null;
$stmtUpdateGame = null;
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Nieprawidłowa metoda żądania. Wymagana metoda POST.';
    http_response_code(405); 
    echo json_encode($response);
    exit;
}
$inputData = file_get_contents('php://input');
$data = json_decode($inputData, true);
$logFile = 'game_creation_log.txt'; 
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Otrzymane dane: " . $inputData . PHP_EOL, FILE_APPEND);
if (empty($data)) {
    $response['message'] = 'Nie otrzymano danych JSON.';
    http_response_code(400); 
    echo json_encode($response);
    exit;
}
if (!isset($data['numPlayers']) || !is_numeric($data['numPlayers'])) {
    $response['message'] = 'Brak lub nieprawidłowa liczba graczy.';
    http_response_code(400); 
    echo json_encode($response);
    exit;
}
$numPlayers = (int)$data['numPlayers'];
if ($numPlayers < 2 || $numPlayers > 4) {
    $response['message'] = 'Nieprawidłowa liczba graczy. Dozwolona liczba: 2-4.';
    http_response_code(400); 
    echo json_encode($response);
    exit;
}
if (!isset($data['players']) || !is_array($data['players'])) {
    $response['message'] = 'Brak lub nieprawidłowe dane graczy.';
    http_response_code(400); 
    echo json_encode($response);
    exit;
}
$playersData = $data['players'];
if (count($playersData) !== $numPlayers) { 
    $response['message'] = 'Niezgodna liczba graczy. Podano: ' . count($playersData) . ', oczekiwano: ' . $numPlayers;
    http_response_code(400); 
    echo json_encode($response);
    exit;
}
foreach ($playersData as $index => $player) {
    if (!isset($player['nickname']) || trim($player['nickname']) === '') {
        $response['message'] = 'Brak nicku dla gracza ' . ($index + 1) . '.';
        http_response_code(400); 
        echo json_encode($response);
        exit;
    }
    if (!isset($player['characterId']) || !is_numeric($player['characterId'])) {
        $response['message'] = 'Brak lub nieprawidłowe ID postaci dla gracza ' . ($index + 1) . '.';
        http_response_code(400); 
        echo json_encode($response);
        exit;
    }
    if ((int)$player['characterId'] <= 0) {
        $response['message'] = 'Nieprawidłowe ID postaci dla gracza ' . ($index + 1) . '.';
        http_response_code(400); 
        echo json_encode($response);
        exit;
    }
}
$mysqli->begin_transaction();
try {
    $currentDate = date("Y-m-d H:i:s");
    $sqlGame = 'INSERT INTO games (current_turn, status, created_at) VALUES (1, "active", ?)';
    $stmtGame = $mysqli->prepare($sqlGame);
    if (!$stmtGame) {
        throw new Exception("Błąd przygotowania zapytania gry: " . $mysqli->error);
    }
    $stmtGame->bind_param('s', $currentDate);
    if (!$stmtGame->execute()) {
        throw new Exception("Błąd wykonania zapytania gry: " . $stmtGame->error);
    }
    $gameId = $mysqli->insert_id; 
    if (!$gameId) {
        throw new Exception("Nie udało się uzyskać ID nowej gry po wstawieniu.");
    }
    $characterIds = array_map(function($p) {
        return (int)$p['characterId']; 
    }, $playersData);
    $placeholders = implode(',', array_fill(0, count($characterIds), '?'));
    $sqlCharData = "SELECT
                        id,
                        name, -- Dodano name, przydatne w logach debugowych
                        base_cook_skill,
                        base_tolerance,
                        base_business_acumen,
                        base_belly_capacity,
                        base_spice_sense,
                        base_prep_time,
                        base_tradition_affinity
                    FROM characters
                    WHERE id IN ($placeholders)";
    $stmtCharData = $mysqli->prepare($sqlCharData);
    if (!$stmtCharData) {
        throw new Exception("Błąd przygotowania zapytania danych postaci: " . $mysqli->error);
    }
    $types = str_repeat('i', count($characterIds));
    $stmtCharData->bind_param($types, ...$characterIds);
    if (!$stmtCharData->execute()) {
        throw new Exception("Błąd wykonania zapytania danych postaci: " . $stmtCharData->error);
    }
    $resultCharData = $stmtCharData->get_result();
    $charactersBaseStats = [];
    while ($row = $resultCharData->fetch_assoc()) {
        $charactersBaseStats[$row['id']] = $row;
        $response['debugInfo']['characters_fetched'][] = ['id' => $row['id'], 'name' => $row['name']];
    }
    $resultCharData->close(); 
    if (count($charactersBaseStats) !== count($characterIds)) {
        $missingIds = array_diff($characterIds, array_keys($charactersBaseStats));
        throw new Exception("Nie znaleziono danych dla wszystkich wybranych postaci. Brakujące ID: " . implode(', ', $missingIds));
    }
    $sqlPlayer = 'INSERT INTO players (
                        game_id,
                        character_id,
                        name,
                        coins,
                        popularity,
                        location,
                        cook_skill,
                        tolerance,
                        business_acumen,
                        belly_capacity,
                        spice_sense,
                        prep_time,
                        tradition_affinity,
                        turn_order,
                        is_turn,
                        turns_to_miss
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
    $stmtPlayer = $mysqli->prepare($sqlPlayer);
    if (!$stmtPlayer) {
        throw new Exception("Błąd przygotowania zapytania gracza: " . $mysqli->error);
    }
    $firstPlayerId = null; 
    $playerIds = []; 
    foreach ($playersData as $index => $player) {
        $characterId = (int)$player['characterId']; 
        $nickname = trim($player['nickname']); 
        $turnOrder = $index + 1; 
        $isTurn = ($index === 0) ? 1 : 0; 
        $charStats = $charactersBaseStats[$characterId];
        $initialCoins = 1500; 
        $initialPopularity = 0;
        $initialLocation = 0; 
        $initialTurnsToMiss = 0;
        $stmtPlayer->bind_param(
            'iisiiiiiiiiiiiii', 
            $gameId,
            $characterId,
            $nickname,
            $initialCoins,
            $initialPopularity,
            $initialLocation,
            $charStats['base_cook_skill'],
            $charStats['base_tolerance'],
            $charStats['base_business_acumen'],
            $charStats['base_belly_capacity'],
            $charStats['base_spice_sense'],
            $charStats['base_prep_time'],
            $charStats['base_tradition_affinity'],
            $turnOrder,
            $isTurn,
            $initialTurnsToMiss
        );
        if (!$stmtPlayer->execute()) {
            throw new Exception("Błąd wykonania zapytania wstawiania gracza '$nickname': " . $stmtPlayer->error);
        }
        $playerId = $mysqli->insert_id; 
        $playerIds[] = $playerId; 
        if ($isTurn) {
            $firstPlayerId = $playerId;
        }
        $response['debugInfo']['players_created'][] = [
            'id' => $playerId,
            'name' => $nickname,
            'characterId' => $characterId,
            'characterName' => $charStats['name'], 
            'turnOrder' => $turnOrder,
            'isTurn' => (bool)$isTurn 
        ];
    }
    if ($firstPlayerId) {
        $sqlUpdateGame = "UPDATE games SET current_player_id = ? WHERE id = ?";
        $stmtUpdateGame = $mysqli->prepare($sqlUpdateGame);
        if (!$stmtUpdateGame) {
             throw new Exception("Błąd przygotowania zapytania aktualizacji gry: " . $mysqli->error);
        }
        $stmtUpdateGame->bind_param('ii', $firstPlayerId, $gameId); 
        if (!$stmtUpdateGame->execute()) {
             throw new Exception("Błąd wykonania zapytania aktualizacji gry: " . $stmtUpdateGame->error);
        }
    } else {
        throw new Exception("Nie udało się ustalić pierwszego gracza. Brak graczy do utworzenia?");
    }
    $mysqli->commit();
    $response['success'] = true;
    $response['message'] = 'Gra i gracze zostali pomyślnie utworzeni.';
    $response['gameId'] = $gameId; 
    $response['players'] = $playerIds; 

// sesja
    session_start();
    $_SESSION['game_id'] = $gameId;

} catch (Exception $e) {
    $mysqli->rollback();
    error_log('Błąd tworzenia gry w create_game.php: ' . $e->getMessage());
    $response['debugInfo']['error'] = $e->getMessage();
    $response['debugInfo']['trace'] = $e->getTraceAsString(); 
    $response['message'] = 'Wystąpił błąd podczas tworzenia gry. Spróbuj ponownie.'; 
    http_response_code(500); 
} finally {
    if ($stmtGame instanceof mysqli_stmt) $stmtGame->close();
    if ($stmtCharData instanceof mysqli_stmt) $stmtCharData->close();
    if ($stmtPlayer instanceof mysqli_stmt) $stmtPlayer->close();
    if ($stmtUpdateGame instanceof mysqli_stmt) $stmtUpdateGame->close();
    if (isset($mysqli) && $mysqli instanceof mysqli && !$mysqli->connect_errno) {
        $mysqli->close();
    }
}
if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
    unset($response['debugInfo']);
}
echo json_encode($response);
?>