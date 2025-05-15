<?php
session_start();

$response = ['success' => false, 'message' => 'Nie udało się zakończyć sesji.'];

if (session_destroy()) {
    $response['success'] = true;
    $response['message'] = 'Sesja została pomyślnie zakończona.';
}

header('Content-Type: application/json');
echo json_encode($response);
?>