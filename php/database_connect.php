<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "monopoly";
$mysqli = new mysqli($host, $user, $password, $database);
if ($mysqli->connect_errno) {
    error_log("Błąd połączenia z bazą danych: " . $mysqli->connect_error);
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Wystąpił wewnętrzny błąd serwera (DB).', 
    ]);
    exit();
}
?>
