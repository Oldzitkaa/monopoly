<?php
session_start(); // Upewnij się, że sesja jest startowana, jeśli potrzebujesz dostępu do sesji (choć endpoint może działać bez niej, jeśli dane przychodzą w POST)

header('Content-Type: application/json');

require_once './database_connect.php';

$response = [
    'success' => false,
    'message' => 'Wystąpił nieznany błąd.',
    'roll_result' => null,
    'new_location' => null, // Dodajemy new_location do domyślnej odpowiedzi
];

// Sprawdź, czy połączenie z bazą danych jest aktywne po dołączeniu pliku
if (!isset($mysqli) || $mysqli->connect_errno) {
    error_log("Błąd połączenia z bazą danych w roll_dice.php: " . $mysqli->connect_error);
    $response['message'] = 'Wystąpił wewnętrzny błąd serwera (DB Init).';
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
$data = json_decode($inputData, true);

// Walidacja danych wejściowych
if (empty($data) || !isset($data['game_id']) || !isset($data['player_id'])) {
    $response['message'] = 'Brak lub nieprawidłowe dane wejściowe (wymagane game_id, player_id).';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

// Rzutuj na int dla pewności, chociaż prepared statements i tak to zrobią bezpiecznie
$gameId = (int) $data['game_id'];
$playerId = (int) $data['player_id'];

// --- Pobierz aktualną pozycję gracza ---
// Używamy przygotowanej instrukcji
$sql_get_pos = "SELECT location FROM players WHERE id = ? AND game_id = ? LIMIT 1";
$stmt_get_pos = $mysqli->prepare($sql_get_pos);

if (!$stmt_get_pos) {
    error_log("Błąd przygotowania zapytania SELECT w roll_dice.php: " . $mysqli->error);
    $response['message'] = 'Wystąpił wewnętrzny błąd serwera (DB Prepare Select).';
    http_response_code(500);
    echo json_encode($response);
    exit;
}

// Bindowanie parametrów i wykonanie zapytania
$stmt_get_pos->bind_param('ii', $playerId, $gameId); // 'ii' oznacza dwa parametry integer
if (!$stmt_get_pos->execute()) {
    error_log("Błąd wykonania zapytania SELECT w roll_dice.php: " . $stmt_get_pos->error);
    $response['message'] = 'Wystąpił wewnętrzny błąd serwera (DB Execute Select).';
    http_response_code(500);
    $stmt_get_pos->close(); // Zamknij instrukcję przed wyjściem
    echo json_encode($response);
    exit;
}

$result_pos = $stmt_get_pos->get_result();

if ($result_pos->num_rows === 1) {
    $row = $result_pos->fetch_assoc();
    $currentLocation = (int)$row['location'];

    $stmt_get_pos->close(); // Zamknij instrukcję po pobraniu wyniku

    // --- Wykonaj rzut kostką (TERAZ TYLKO JEDEN RAZ) ---
    $rollResult = rand(1, 6);

    // Zakładamy, że plansza ma 40 pól (0-39) jak w standardowym Monopoly
    // Modulo 40 oznacza, że po polu 39 wracamy na pole 0.
    // Jeśli masz 41 pól (0-40), użyj % 41. Sprawdź dokładnie liczbę pól.
    $board_size = 40; // Popraw, jeśli Twoja plansza ma inną liczbę pól
    $newLocation = ($currentLocation + $rollResult) % $board_size;


    // --- Zaktualizuj pozycję gracza w bazie ---
    // Używamy przygotowanej instrukcji
    $sql_update = "UPDATE players SET location = ? WHERE id = ? AND game_id = ?";
    $stmt_update = $mysqli->prepare($sql_update);

     if (!$stmt_update) {
        error_log("Błąd przygotowania zapytania UPDATE w roll_dice.php: " . $mysqli->error);
        $response['message'] = 'Wystąpił wewnętrzny błąd serwera (DB Prepare Update).';
        http_response_code(500);
        echo json_encode($response);
        exit;
    }

    // Bindowanie parametrów i wykonanie zapytania
    $stmt_update->bind_param('iii', $newLocation, $playerId, $gameId); // 'iii' oznacza trzy parametry integer
    if (!$stmt_update->execute()) {
        error_log("Błąd wykonania zapytania UPDATE w roll_dice.php: " . $stmt_update->error);
        $response['message'] = 'Wystąpił wewnętrzny błąd serwera (DB Execute Update).';
        http_response_code(500);
        $stmt_update->close(); // Zamknij instrukcję przed wyjściem
        echo json_encode($response);
        exit;
    }

    $stmt_update->close(); // Zamknij instrukcję

    // Przygotowanie odpowiedzi sukcesu
    $response['success'] = true;
    $response['message'] = 'Rzut kostką wykonany i pozycja zaktualizowana.';
    $response['roll_result'] = $rollResult;
    $response['current_location'] = $currentLocation; // Opcjonalnie możesz zwrócić starą pozycję
    $response['new_location'] = $newLocation;

} else {
    // Nie znaleziono gracza dla podanych ID gry i gracza
    $response['success'] = false;
    $response['message'] = "Nie znaleziono pozycji gracza w bazie dla podanych ID.";
    http_response_code(404); // Resource Not Found
}


// Zamknij połączenie z bazą danych
if (isset($mysqli) && $mysqli instanceof mysqli && !$mysqli->connect_errno) {
    $mysqli->close();
}

// Wysłanie odpowiedzi JSON
echo json_encode($response);
?>