<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => 'Nieznany błąd serwera podczas aktualizacji tury.'
];

try {
    $inputData = file_get_contents('php://input');
    if ($inputData === false) {
        throw new Exception('Nie można odczytać danych wejściowych.');
    }

    $data = json_decode($inputData, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Nieprawidłowy format danych JSON: ' . json_last_error_msg());
    }

    $gameId = $data['game_id'] ?? null;
    $newTurnPlayerId = $data['new_turn_player_id'] ?? null;

    if (!$gameId || !$newTurnPlayerId) {
        throw new Exception('Brak wymaganych danych: game_id lub new_turn_player_id.');
    }

    require_once __DIR__ . '/database_connect.php';

    if (!isset($mysqli) || $mysqli->connect_error) {
        throw new Exception('Błąd połączenia z bazą danych: ' . ($mysqli->connect_error ?? 'Nieznany błąd'));
    }

    $mysqli->begin_transaction();

    $resetStmt = $mysqli->prepare("
    UPDATE players 
    SET is_current_turn = 0, turns_to_miss = IF(turns_to_miss > 0, turns_to_miss - 1, 0) 
    WHERE game_id = ? ");
    $resetStmt->bind_param("i", $gameId);
    $resetStmt->execute();
    $resetStmt->close();

    $setStmt = $mysqli->prepare("UPDATE players SET is_current_turn = 1 WHERE id = ? AND game_id = ?");
    $setStmt->bind_param("ii", $newTurnPlayerId, $gameId);
    $setStmt->execute();
    $setStmt->close();

    $updateGameStmt = $mysqli->prepare("UPDATE games SET current_player_id = ? WHERE id = ?");
    $updateGameStmt->bind_param("ii", $newTurnPlayerId, $gameId);
    $updateGameStmt->execute();
    $updateGameStmt->close();

    $mysqli->commit();

    $response['success'] = true;
    $response['message'] = 'Tura została zaktualizowana pomyślnie.';
    $response['current_turn_player_id'] = $newTurnPlayerId;

    error_log("update_turn.php: Zaktualizowano turę na gracza ID: $newTurnPlayerId w grze ID: $gameId");

} catch (Exception $e) {
    if (isset($mysqli) && $mysqli->errno) {
        $mysqli->rollback();
    }
    error_log("Błąd w update_turn.php: " . $e->getMessage());
    $response['message'] = 'Błąd serwera: ' . $e->getMessage();
    http_response_code(500);
} finally {
    if (isset($mysqli) && $mysqli instanceof mysqli && !$mysqli->connect_errno) {
        $mysqli->close();
    }
}

error_log("Tura ustawiona na gracza ID: $newTurnPlayerId w grze ID: $gameId");

echo json_encode($response);
?>
