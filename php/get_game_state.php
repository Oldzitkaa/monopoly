<?php
// get_game_state.php
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => 'Nieznany błąd serwera podczas pobierania stanu gry.'
];

try {
    $dbConnectPath = __DIR__ . '/database_connect.php';
    if (!file_exists($dbConnectPath)) {
        throw new Exception('Plik database_connect.php nie został znaleziony.');
    }
    require_once $dbConnectPath;

    if (!isset($mysqli) || $mysqli->connect_error) {
        throw new Exception('Błąd połączenia z bazą danych: ' . ($mysqli->connect_error ?? 'Nieznany błąd'));
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Nieprawidłowa metoda żądania. Wymagana metoda POST.';
        http_response_code(405);
        echo json_encode($response);
        exit;
    }

    $inputData = file_get_contents('php://input');
    if ($inputData === false) {
        throw new Exception('Nie można odczytać danych wejściowych.');
    }
    $data = json_decode($inputData, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $response['message'] = 'Nieprawidłowy format danych JSON: ' . json_last_error_msg();
        http_response_code(400);
        echo json_encode($response);
        exit;
    }

    $gameId = $data['game_id'] ?? null;

    if ($gameId === null) {
        $response['message'] = 'Brak wymaganego parametru game_id.';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }

    // Pobierz wszystkich graczy w grze
    $players = [];
    $stmt = $mysqli->prepare("SELECT id, name, coins, location, is_current_turn FROM players WHERE game_id = ? ORDER BY turn_order ASC");
    if (!$stmt) {
        throw new Exception("Błąd przygotowania zapytania dla graczy: " . $mysqli->error);
    }
    $stmt->bind_param('i', $gameId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $players[] = $row;
    }
    $stmt->close();

    // Pobierz ID gracza, którego jest aktualnie tura
    $currentTurnPlayerId = null;
    foreach ($players as $player) {
        if ($player['is_current_turn'] == 1) {
            $currentTurnPlayerId = $player['id'];
            break;
        }
    }

    $response['success'] = true;
    $response['message'] = 'Stan gry pobrany pomyślnie.';
    $response['players'] = $players;
    $response['current_turn_player_id'] = $currentTurnPlayerId;

} catch (Exception $e) {
    error_log('Błąd w get_game_state.php: ' . $e->getMessage());
    $response['success'] = false;
    $response['message'] = 'Wystąpił błąd serwera: ' . $e->getMessage();
    http_response_code(500);
} finally {
    if (isset($mysqli) && $mysqli instanceof mysqli && !$mysqli->connect_errno) {
        $mysqli->close();
    }
}

echo json_encode($response);
?>
