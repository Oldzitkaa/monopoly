<?php

header('Content-Type: application/json');

require_once './database_connect.php';

$response = [
    'success' => false,
    'message' => 'Wystąpił nieznany błąd.',
    'roll_result' => null,
    'new_location' => null,
    'new_coins' => null // Ensure this is initialized if you plan to return it
];

// Check if database connection is established
if (!isset($mysqli) || $mysqli->connect_errno) {
    error_log("Błąd połączenia z bazą danych w roll_dice.php: " . $mysqli->connect_error);
    $response['message'] = 'Wystąpił wewnętrzny błąd serwera (DB Init).';
    http_response_code(500);
    echo json_encode($response);
    exit;
}

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Nieprawidłowa metoda żądania. Wymagana metoda POST.';
    http_response_code(405);
    echo json_encode($response);
    exit;
}

// Get and decode the JSON input
$inputJson = file_get_contents('php://input'); // Corrected variable name
$data = json_decode($inputJson, true);

// Validate input data
if (empty($data) || !isset($data['game_id']) || !isset($data['player_id'])) {
    $response['message'] = 'Brak lub nieprawidłowe dane wejściowe (wymagane game_id, player_id).';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

$gameId = (int) $data['game_id'];
$playerId = (int) $data['player_id'];

// Define board size (assuming a standard Monopoly board of 40 spaces)
$board_size = 40; // <-- ADDED THIS LINE

// --- Fetch current player location and coins (coins implicitly, though not used here) ---
$sql_get_pos = "SELECT location, coins FROM players WHERE id = ? AND game_id = ? LIMIT 1";
$stmt_get_pos = $mysqli->prepare($sql_get_pos);

if (!$stmt_get_pos) {
    error_log("Błąd przygotowania zapytania SELECT w roll_dice.php: " . $mysqli->error);
    $response['message'] = 'Wystąpił wewnętrzny błąd serwera (DB Prepare Select).';
    http_response_code(500);
    echo json_encode($response);
    exit;
}

$stmt_get_pos->bind_param('ii', $playerId, $gameId);
if (!$stmt_get_pos->execute()) {
    error_log("Błąd wykonania zapytania SELECT w roll_dice.php: " . $stmt_get_pos->error);
    $response['message'] = 'Wystąpił wewnętrzny błąd serwera (DB Execute Select).';
    http_response_code(500);
    echo json_encode($response);
    exit;
}

$result_pos = $stmt_get_pos->get_result();

if ($result_pos->num_rows === 1) {
    $row = $result_pos->fetch_assoc();
    $currentLocation = (int)$row['location'];
    $currentCoins = (int)$row['coins']; // Fetch current coins, useful for future logic

    // Generate the dice roll result (assuming one die for now, as in original)
    $rollResult = rand(1, 6);

    // Calculate new location (with proper modulo)
    $newLocation = ($currentLocation + $rollResult) % $board_size; // Corrected $board_size usage

    // --- Update player's location in the database ---
    $sql_update = "UPDATE players SET location = ? WHERE id = ? AND game_id = ?";
    $stmt_update = $mysqli->prepare($sql_update);

    if (!$stmt_update) {
        error_log("Błąd przygotowania zapytania UPDATE w roll_dice.php: " . $mysqli->error);
        $response['message'] = 'Wystąpił wewnętrzny błąd serwera (DB Prepare Update).';
        http_response_code(500);
        echo json_encode($response);
        exit;
    }

    $stmt_update->bind_param('iii', $newLocation, $playerId, $gameId);
    if (!$stmt_update->execute()) {
        error_log("Błąd wykonania zapytania UPDATE w roll_dice.php: " . $stmt_update->error);
        $response['message'] = 'Wystąpił wewnętrzny błąd serwera (DB Execute Update).';
        http_response_code(500);
        echo json_encode($response);
        exit;
    }

    // --- You might want to re-fetch coins here if moving changes them (e.g., passing GO) ---
    // For now, we'll just send back the current coins fetched initially,
    // assuming no immediate coin changes upon simple movement.
    // If you add "pass GO" logic, you'd update coins *before* this point and fetch the new value.
    $newCoins = $currentCoins; // Placeholder, update if logic changes coins

    // Set success and return results
    $response['success'] = true;
    $response['message'] = 'Rzut kostką wykonany i pozycja zaktualizowana.';
    $response['roll_result'] = $rollResult;
    $response['new_location'] = $newLocation;
    $response['new_coins'] = $newCoins; // Include new_coins in the response

    $stmt_update->close(); // Close update statement
    $stmt_get_pos->close(); // Close select statement

} else {
    $response['message'] = "Nie znaleziono pozycji gracza w bazie dla podanych ID.";
    http_response_code(404); // Not Found
}

// Close database connection
if (isset($mysqli) && $mysqli instanceof mysqli && !$mysqli->connect_errno) {
    $mysqli->close();
}

echo json_encode($response);

?>