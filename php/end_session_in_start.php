<?php
session_start();
include_once './database_connect.php';

$response = ['success' => false, 'message' => 'Nie udało się zakończyć sesji.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_SESSION['game_id'])) {
        $gameId = $_SESSION['game_id'];
        $sql = "UPDATE `games` SET `status` = 'ended' WHERE `games`.`id` = ?";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $gameId);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Gra zakończona i baza danych zaktualizowana.';
            } else {
                $response['message'] = 'Błąd podczas aktualizacji bazy danych: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $response['message'] = 'Błąd przygotowania zapytania SQL: ' . $mysqli->error;
        }
    } else {
        $response['success'] = true;
        $response['message'] = 'Brak ID gry w sesji.';
    }

    session_destroy();
} else {
    $response['message'] = 'Nieprawidłowe żądanie.';
}

header('Content-Type: application/json');
echo json_encode($response);
?>