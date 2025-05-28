<?php
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

    // gracze
    $players = [];
    $stmt = $mysqli->prepare("
        SELECT 
            p.id, 
            p.name, 
            p.coins, 
            p.location, 
            p.is_current_turn,
            p.turn_order,
            p.cook_skill,
            p.tolerance,
            p.business_acumen,
            p.belly_capacity,
            p.spice_sense,
            p.prep_time,
            p.tradition_affinity,
            p.turns_to_miss,
            p.color as player_color,
            c.name as character_name
        FROM players p
        LEFT JOIN characters c ON p.character_id = c.id
        WHERE p.game_id = ? 
        ORDER BY p.turn_order ASC
    ");
    
    if (!$stmt) {
        throw new Exception("Błąd przygotowania zapytania dla graczy: " . $mysqli->error);
    }
    
    $stmt->bind_param('i', $gameId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $row['id'] = (int)$row['id'];
        $row['coins'] = (int)$row['coins'];
        $row['location'] = (int)$row['location'];
        $row['is_current_turn'] = (int)$row['is_current_turn'];
        $row['turn_order'] = (int)$row['turn_order'];
        $row['cook_skill'] = (int)$row['cook_skill'];
        $row['tolerance'] = (int)$row['tolerance'];
        $row['business_acumen'] = (int)$row['business_acumen'];
        $row['belly_capacity'] = (int)$row['belly_capacity'];
        $row['spice_sense'] = (int)$row['spice_sense'];
        $row['prep_time'] = (int)$row['prep_time'];
        $row['tradition_affinity'] = (int)$row['tradition_affinity'];
        $row['turns_to_miss'] = (int)$row['turns_to_miss'];
        
        $players[] = $row;
    }
    $stmt->close();

    $currentTurnPlayerId = null;
    foreach ($players as $player) {
        if ($player['is_current_turn'] == 1) {
            $currentTurnPlayerId = $player['id'];
            break;
        }
    }

    if ($currentTurnPlayerId === null) {
        $stmt = $mysqli->prepare("SELECT current_player_id FROM games WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $gameId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $currentTurnPlayerId = (int)$row['current_player_id'];
            }
            $stmt->close();
        }
    }

    $response['success'] = true;
    $response['message'] = 'Stan gry pobrany pomyślnie.';
    $response['players'] = $players;
    $response['current_turn_player_id'] = $currentTurnPlayerId;

    error_log('get_game_state.php: Zwrócono ' . count($players) . ' graczy dla gry ' . $gameId);
    error_log('get_game_state.php: current_turn_player_id = ' . ($currentTurnPlayerId ?? 'null'));

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