<?php
header('Content-Type: application/json');
require_once './database_connect.php'; 
$response = [
    'success' => false,
    'message' => 'Wystąpił nieznany błąd.', 
    'roll_result' => null, 
];
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Nieprawidłowa metoda żądania. Wymagana metoda POST.';
    http_response_code(405); 
    echo json_encode($response);
    exit;
}
$inputData = file_get_contents('php://input');
$data = json_decode($inputData, true);
if (empty($data) || !isset($data['game_id']) || !isset($data['player_id'])) {
    $response['message'] = 'Brak lub nieprawidłowe dane wejściowe (wymagane game_id, player_id).';
    http_response_code(400); 
    echo json_encode($response);
    exit;
}
$gameId = (int) $data['game_id'];
$playerId = (int) $data['player_id'];
$rollResult = rand(1, 6);
$response['success'] = true;
$response['message'] = 'Rzut kostką wykonany.';
$response['roll_result'] = $rollResult; 
if (isset($mysqli) && $mysqli instanceof mysqli && !$mysqli->connect_errno) {
    $mysqli->close();
}
echo json_encode($response);
?>