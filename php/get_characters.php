<?php

header('Content-Type: application/json');

require_once './database_connect.php';

$response = [];

try {
    $sql = "SELECT
                id,
                name,
                description,
                plik,
                base_cook_skill,
                base_tolerance,
                base_business_acumen,
                base_belly_capacity,
                base_spice_sense,
                base_prep_time,
                base_tradition_affinity,
                special_ability_description
            FROM
                characters
            ORDER BY
                id ASC";

    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        throw new Exception("Błąd przygotowania zapytania: " . $mysqli->error);
    }

    if (!$stmt->execute()) {
        throw new Exception("Błąd wykonania zapytania: " . $stmt->error);
    }

    $result = $stmt->get_result();

    $characters = [];
    $baseCharacterImagePath = '../zdj/postacie/';

    $mainPlaceholderPath = '../zdj/placeholder.png';


    while ($row = $result->fetch_assoc()) {
        $characterData = $row;

        if (!empty($characterData['plik'])) {
            $characterData['image_path'] = $baseCharacterImagePath . $characterData['plik'];
        } else {
            $characterData['image_path'] = $mainPlaceholderPath;
        }

        unset($characterData['plik']);

        $characters[] = $characterData;
    }


    $response = $characters;

} catch (Exception $e) {
    http_response_code(500);
    $response = [
        'error' => true,
    ];

    error_log('Błąd w get_characters.php: ' . $e->getMessage());

} finally {
    if ($stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
    if (isset($mysqli) && $mysqli instanceof mysqli && !$mysqli->connect_errno) {
        $mysqli->close();
    }
}

if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
    unset($response['debug_message']);
}
echo json_encode($response);
?>


