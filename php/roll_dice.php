<?php

header('Content-Type: application/json');

require_once './database_connect.php';

$response = [
    'success' => false,
    'message' => 'Wystąpił nieznany błąd.',
    'roll_result' => null,
];

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

$data = json_decode($inputData, true);

if (empty($data) || !isset($data['game_id']) || !isset($data['player_id'])) {
    $response['message'] = 'Brak lub nieprawidłowe dane wejściowe (wymagane game_id, player_id).';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

$gameId = (int) $data['game_id'];
$playerId = (int) $data['player_id'];

$sql_get_pos = "SELECT location FROM players WHERE id = ? AND game_id = ? LIMIT 1";
$stmt_get_pos = $mysqli->prepare($sql_get_pos);

if (!$stmt_get_pos) {
    error_log("Błąd przygotowania zapytania SELECT w roll_dice.php: " . $mysqli->error);
    $response['message'] = 'Wystąpił wewnętrzny błąd serwera (DB Prepare Select).';
    http_response_code(500);
    echo json_encode($response);
    exit;
}

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


    $rollResult = rand(1, 6);

    $newLocation = ($currentLocation + $rollResult) % $board_size;


    $sql_update = "UPDATE players SET location = ? WHERE id = ? AND game_id = ?";
    $stmt_update = $mysqli->prepare($sql_update);

     if (!$stmt_update) {
        error_log("Błąd przygotowania zapytania UPDATE w roll_dice.php: " . $mysqli->error);
        $response['message'] = 'Wystąpił wewnętrzny błąd serwera (DB Prepare Update).';
        http_response_code(500);
        echo json_encode($response);
        exit;
    }

    if (!$stmt_update->execute()) {
        error_log("Błąd wykonania zapytania UPDATE w roll_dice.php: " . $stmt_update->error);
        $response['message'] = 'Wystąpił wewnętrzny błąd serwera (DB Execute Update).';
        http_response_code(500);
        echo json_encode($response);
        exit;
    }


    $response['success'] = true;
    $response['message'] = 'Rzut kostką wykonany i pozycja zaktualizowana.';
    $response['roll_result'] = $rollResult;
    $response['new_location'] = $newLocation;

} else {
    $response['success'] = false;
    $response['message'] = "Nie znaleziono pozycji gracza w bazie dla podanych ID.";
}


if (isset($mysqli) && $mysqli instanceof mysqli && !$mysqli->connect_errno) {
    $mysqli->close();
}

echo json_encode($response);
?>
