<?php
ini_set('display_errors', 0); // Set to 1 for development, 0 for production
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    require_once './database_connect.php';

    $response = [
        'success' => false,
        'message' => 'Wystąpił nieznany błąd serwera.',
        'gameId' => null,
        'debugInfo' => []
    ];

    $logFile = 'game_creation_log.txt';

    if (!isset($mysqli) || $mysqli->connect_errno) {
        $response['message'] = 'Błąd połączenia z bazą danych. Sprawdź konfigurację bazy.';
        $response['debugInfo']['db_connect_error'] = $mysqli->connect_error ?? 'Brak obiektu $mysqli lub nieznany błąd połączenia.';
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - KRYTYCZNY BŁĄD: Błąd połączenia z bazą danych: " . ($mysqli->connect_error ?? 'Nieznany błąd') . PHP_EOL, FILE_APPEND);
        http_response_code(500);
        echo json_encode($response);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Nieprawidłowa metoda żądania. Wymagana metoda POST.';
        http_response_code(405);
        echo json_encode($response);
        exit;
    }

    $inputData = file_get_contents('php://input');
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Otrzymane dane: " . $inputData . PHP_EOL, FILE_APPEND);

    $data = json_decode($inputData, true);

    if (empty($data)) {
        $response['message'] = 'Nie otrzymano danych JSON lub dane są puste. Error: ' . json_last_error_msg();
        $response['debugInfo']['input_data'] = $inputData;
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
        if (!isset($player['characterId']) || !is_numeric($player['characterId']) || (int)$player['characterId'] <= 0) {
            $response['message'] = 'Brak lub nieprawidłowe ID postaci dla gracza ' . ($index + 1) . '.';
            http_response_code(400);
            echo json_encode($response);
            exit;
        }
    }

    $mysqli->begin_transaction();

    try {
        $currentDate = date("Y-m-d H:i:s");
        // Initialize game with current_turn = 1
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
                            name,
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
                            is_current_turn,
                            turns_to_miss
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $stmtPlayer = $mysqli->prepare($sqlPlayer);
        if (!$stmtPlayer) {
            throw new Exception("Błąd przygotowania zapytania gracza: " . $mysqli->error);
        }

        $firstPlayerId = null;
        $playerIds = [];
        $playersForTurnQueue = [];

        foreach ($playersData as $index => $player) {
            $characterId = (int)$player['characterId'];
            $nickname = trim($player['nickname']);
            $turnOrder = $index + 1;
            $isCurrentTurn = ($index === 0) ? 1 : 0;

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
                $isCurrentTurn,
                $initialTurnsToMiss
            );
            if (!$stmtPlayer->execute()) {
                throw new Exception("Błąd wykonania zapytania wstawiania gracza '$nickname': " . $stmtPlayer->error);
            }
            $playerId = $mysqli->insert_id;
            $playerIds[] = $playerId;
            if ($isCurrentTurn) {
                $firstPlayerId = $playerId;
            }
            $response['debugInfo']['players_created'][] = [
                'id' => $playerId,
                'name' => $nickname,
                'characterId' => $characterId,
                'characterName' => $charStats['name'],
                'turnOrder' => $turnOrder,
                'isTurn' => (bool)$isCurrentTurn
            ];

            // Store player data for turn_queue insertion
            $playersForTurnQueue[] = [
                'player_id' => $playerId,
                'queue_position' => $turnOrder
            ];
        }

        // Set the current_player_id in the games table
        $sqlUpdateGame = "UPDATE games SET current_player_id = ? WHERE id = ?";
        $stmtUpdateGame = $mysqli->prepare($sqlUpdateGame);
        if (!$stmtUpdateGame) {
            throw new Exception("Błąd przygotowania zapytania aktualizacji gry: " . $mysqli->error);
        }
        $stmtUpdateGame->bind_param('ii', $firstPlayerId, $gameId);
        if (!$stmtUpdateGame->execute()) {
            throw new Exception("Błąd wykonania zapytania aktualizacji gry: " . $stmtUpdateGame->error);
        }

        // Initialize game_tiles for restaurant tiles
        $sqlInitTiles = "INSERT INTO game_tiles (game_id, tile_id, current_level, is_mortgaged)
                            SELECT ?, id, 0, 0 FROM tiles WHERE type = 'restaurant'";
        $stmtInitTiles = $mysqli->prepare($sqlInitTiles);
        if (!$stmtInitTiles) {
            throw new Exception("Błąd przygotowania zapytania inicjalizacji pól gry: " . $mysqli->error);
        }
        $stmtInitTiles->bind_param('i', $gameId);
        if (!$stmtInitTiles->execute()) {
            throw new Exception("Błąd wykonania zapytania inicjalizacji pól gry: " . $stmtInitTiles->error);
        }

        // NEW: Initialize turn_queue for the first round (turn_number = 1)
        $sqlInitTurnQueue = "INSERT INTO turn_queue (game_id, player_id, turn_number, queue_position, has_played, is_skipped) VALUES (?, ?, 1, ?, 0, 0)";
        $stmtInitTurnQueue = $mysqli->prepare($sqlInitTurnQueue);
        if (!$stmtInitTurnQueue) {
            throw new Exception("Błąd przygotowania zapytania inicjalizacji kolejki tur: " . $mysqli->error);
        }

        foreach ($playersForTurnQueue as $playerData) {
            $playerId = $playerData['player_id'];
            $queuePosition = $playerData['queue_position'];
            $stmtInitTurnQueue->bind_param('iii', $gameId, $playerId, $queuePosition);
            if (!$stmtInitTurnQueue->execute()) {
                throw new Exception("Błąd wykonania zapytania wstawiania gracza do kolejki tur: " . $stmtInitTurnQueue->error);
            }
        }

        $mysqli->commit();

        $response['success'] = true;
        $response['message'] = 'Gra i gracze zostali pomyślnie utworzeni. Kolejka tur zainicjalizowana.';
        $response['gameId'] = $gameId;
        $response['players'] = $playerIds;

        // --- WAŻNA ZMIANA TUTAJ ---
        // Upewnij się, że sesja jest uruchomiona ZANIM ustawisz zmienne sesji
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['game_id'] = $gameId;
        // DODANA LINIA: Ustawiamy ID gracza w sesji, aby gameboard.php wiedział, który gracz ogląda
        $_SESSION['player_id'] = $firstPlayerId; 
        // --- KONIEC ZMIANY ---

    } catch (Exception $e) {
        $mysqli->rollback();
        error_log('Błąd tworzenia gry w create_game.php: ' . $e->getMessage());
        $response['debugInfo']['error'] = $e->getMessage();
        $response['debugInfo']['trace'] = $e->getTraceAsString();
        $response['message'] = 'Wystąpił błąd podczas tworzenia gry: ' . $e->getMessage();
        http_response_code(500);
    } finally {
        if (isset($stmtGame) && $stmtGame instanceof mysqli_stmt) $stmtGame->close();
        if (isset($stmtCharData) && $stmtCharData instanceof mysqli_stmt) $stmtCharData->close();
        if (isset($stmtPlayer) && $stmtPlayer instanceof mysqli_stmt) $stmtPlayer->close();
        if (isset($stmtUpdateGame) && $stmtUpdateGame instanceof mysqli_stmt) $stmtUpdateGame->close();
        if (isset($stmtInitTiles) && $stmtInitTiles instanceof mysqli_stmt) $stmtInitTiles->close();
        if (isset($stmtInitTurnQueue) && $stmtInitTurnQueue instanceof mysqli_stmt) $stmtInitTurnQueue->close(); // Close new statement

        if (isset($mysqli) && $mysqli instanceof mysqli && !$mysqli->connect_errno) {
            $mysqli->close();
        }
    }

    echo json_encode($response);

} catch (Throwable $e) {
    $errorResponse = [
        'success' => false,
        'message' => 'Krytyczny błąd serwera: ' . $e->getMessage(),
        'debugInfo' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ];
    error_log('Krytyczny błąd w create_game.php: ' . $e->getMessage() . ' w ' . $e->getFile() . ' na linii ' . $e->getLine());
    echo json_encode($errorResponse);
}
?>