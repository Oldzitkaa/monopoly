<?php
header('Content-Type: application/json');
include_once './database_connect.php'; 

$response = ['success' => false, 'message' => '', 'gameId' => null];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Nieprawidłowa metoda żądania.';
    echo json_encode($response);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data) || !isset($data['numPlayers']) || !isset($data['players']) || count($data['players']) != $data['numPlayers']) {
    $response['message'] = 'Niekompletne lub nieprawidłowe dane.';
    echo json_encode($response);
    exit;
}

$numPlayers = (int)$data['numPlayers'];
$playersData = $data['players'];

// Walidacja danych wejściowych
if ($numPlayers < 2 || $numPlayers > 4) {
    $response['message'] = 'Nieprawidłowa liczba graczy.';
    echo json_encode($response);
    exit;
}

foreach ($playersData as $player) {
    if (empty(trim($player['nickname'])) || empty($player['characterId'])) {
        $response['message'] = 'Nick i ID postaci są wymagane dla każdego gracza.';
        echo json_encode($response);
        exit;
    }
}


$mysqli->begin_transaction();

try {
    // 1. Utwórz nową grę
    $currentDate = date("Y-m-d H:i:s");
    // Status 'lobby' lub 'active' w zależności od logiki gry (np. czy od razu zaczyna się tura pierwszego gracza)
    // current_turn może być ID pierwszego gracza lub 1 (numer tury)
    $sqlGame = 'INSERT INTO games (current_turn, status, created_at) VALUES (1, "active", ?)';
    $stmtGame = $mysqli->prepare($sqlGame);
    if (!$stmtGame) {
        throw new Exception("Błąd przygotowania zapytania (gra): " . $mysqli->error);
    }
    $stmtGame->bind_param('s', $currentDate);
    if (!$stmtGame->execute()) {
         throw new Exception("Błąd wykonania zapytania (gra): " . $stmtGame->error);
    }
    $gameId = $mysqli->insert_id;
    $stmtGame->close();

    if (!$gameId) {
        throw new Exception("Nie udało się uzyskać ID nowej gry.");
    }

    // 2. Dodaj graczy do gry
    // Najpierw pobierz dane postaci, aby ustawić bazowe statystyki
    $characterIds = array_map(function($p) { return (int)$p['characterId']; }, $playersData);
    $placeholders = implode(',', array_fill(0, count($characterIds), '?'));
    $sqlCharData = "SELECT id, base_cook_skill, base_tolerance, base_business_acumen, base_belly_capacity, base_spice_sense, base_prep_time, base_tradition_affinity FROM characters WHERE id IN ($placeholders)";
    $stmtCharData = $mysqli->prepare($sqlCharData);
    
    if (!$stmtCharData) {
        throw new Exception("Błąd przygotowania zapytania (dane postaci): " . $mysqli->error);
    }
    // Dynamiczne bindowanie parametrów dla IN clause
    $types = str_repeat('i', count($characterIds));
    $stmtCharData->bind_param($types, ...$characterIds);
    $stmtCharData->execute();
    $resultCharData = $stmtCharData->get_result();
    $charactersBaseStats = [];
    while ($row = $resultCharData->fetch_assoc()) {
        $charactersBaseStats[$row['id']] = $row;
    }
    $stmtCharData->close();


    $sqlPlayer = 'INSERT INTO players (game_id, character_id, name, coins, popularity, location, cook_skill, tolerance, business_acumen, belly_capacity, spice_sense, prep_time, tradition_affinity, turn_order, is_turn, turns_to_miss) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
    $stmtPlayer = $mysqli->prepare($sqlPlayer);
    if (!$stmtPlayer) {
        throw new Exception("Błąd przygotowania zapytania (gracz): " . $mysqli->error);
    }

    $firstPlayerId = null;

    foreach ($playersData as $index => $player) {
        $characterId = (int)$player['characterId'];
        $nickname = trim($player['nickname']);
        $turnOrder = $index + 1;
        $isTurn = ($index === 0) ? 1 : 0; // Pierwszy gracz zaczyna
        
        $charStats = $charactersBaseStats[$characterId] ?? null;
        if (!$charStats) {
            throw new Exception("Nie znaleziono statystyk dla postaci ID: $characterId");
        }

        // Domyślne wartości
        $coins = 1500; // Przykładowa wartość startowa
        $popularity = 0;
        $location = 0; // ID kafelka startowego (zazwyczaj 0 lub 1)
        $turnsToMiss = 0;

        $stmtPlayer->bind_param(
            'iisiiisiiiiiiiii', // i - integer, s - string
            $gameId,
            $characterId,
            $nickname,
            $coins,
            $popularity,
            $location,
            $charStats['base_cook_skill'],
            $charStats['base_tolerance'],
            $charStats['base_business_acumen'],
            $charStats['base_belly_capacity'],
            $charStats['base_spice_sense'],
            $charStats['base_prep_time'],
            $charStats['base_tradition_affinity'],
            $turnOrder,
            $isTurn,
            $turnsToMiss
        );
        if (!$stmtPlayer->execute()) {
            throw new Exception("Błąd wykonania zapytania (gracz $nickname): " . $stmtPlayer->error);
        }
        if ($isTurn) {
            $firstPlayerId = $mysqli->insert_id; // ID pierwszego gracza do ustawienia w tabeli games
        }
    }
    $stmtPlayer->close();

    // Zaktualizuj current_player_id w tabeli games
    if ($firstPlayerId) {
        $sqlUpdateGame = "UPDATE games SET current_player_id = ? WHERE id = ?";
        $stmtUpdateGame = $mysqli->prepare($sqlUpdateGame);
        if (!$stmtUpdateGame) {
            throw new Exception("Błąd przygotowania zapytania (aktualizacja gry): " . $mysqli->error);
        }
        $stmtUpdateGame->bind_param('ii', $firstPlayerId, $gameId);
        if (!$stmtUpdateGame->execute()) {
            throw new Exception("Błąd wykonania zapytania (aktualizacja gry): " . $stmtUpdateGame->error);
        }
        $stmtUpdateGame->close();
    }


    $mysqli->commit();
    $response['success'] = true;
    $response['message'] = 'Gra i gracze zostali pomyślnie utworzeni.';
    $response['gameId'] = $gameId;

} catch (Exception $e) {
    $mysqli->rollback();
    $response['message'] = 'Błąd transakcji: ' . $e->getMessage();
     error_log('Błąd tworzenia gry: ' . $e->getMessage()); // Logowanie błędów serwera
} finally {
    if (isset($stmtGame)) $stmtGame->close();
    if (isset($stmtPlayer)) $stmtPlayer->close();
    if (isset($stmtCharData)) $stmtCharData->close();
    if (isset($stmtUpdateGame)) $stmtUpdateGame->close();
    $mysqli->close();
}

echo json_encode($response);
?>