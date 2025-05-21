<?php
header('Content-Type: application/json');
require_once './database_connect.php';

$response = [
    'success' => false,
    'message' => 'Wystąpił nieznany błąd.',
    'roll_result' => null,
    'new_location' => null,
    'new_coins' => null 
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

// Generate the dice roll result
$rollResult = rand(1, 6);
// You might roll two dice in Monopoly, so you'd do:
// $rollResult = rand(1, 6) + rand(1, 6);
// The current code rolls one die, assigns it, then rolls another and assigns it again.
// Let's assume you want a single roll for now, or adjust if you need two dice.
// For a single die:
$rollResult = rand(1, 6); // Keep only one of these lines if it's a single die game

$response['roll_result'] = $rollResult;

// --- Fetch current player location and coins ---
// Prepare statement for security
$sql_get_pos_coins = "SELECT location, coins FROM players WHERE id = ? AND game_id = ? LIMIT 1";
$stmt_get_pos_coins = $mysqli->prepare($sql_get_pos_coins);

if ($stmt_get_pos_coins) {
    $stmt_get_pos_coins->bind_param('ii', $playerId, $gameId);
    $stmt_get_pos_coins->execute();
    $result_get_pos_coins = $stmt_get_pos_coins->get_result();

    if ($result_get_pos_coins && $result_get_pos_coins->num_rows === 1) {
        $row = $result_get_pos_coins->fetch_assoc();
        $currentLocation = (int)$row['location'];
        // $currentCoins = (int)$row['coins']; // You have current coins here if needed for transactions

        // Calculate new location (using % 40 for a standard 40-space board)
        $newLocation = ($currentLocation + $rollResult) % 40; // Corrected modulo

        // --- Update player's location in the database ---
        // Prepare statement for security
        $sql_update_location = "UPDATE players SET location = ? WHERE id = ? AND game_id = ?";
        $stmt_update_location = $mysqli->prepare($sql_update_location);

        if ($stmt_update_location) {
            $stmt_update_location->bind_param('iii', $newLocation, $playerId, $gameId);
            $stmt_update_location->execute();
            // Check if update was successful (optional but good practice)
            // if ($stmt_update_location->affected_rows > 0) { ... }

            $stmt_update_location->close();

            // --- **FETCH UPDATED COINS AFTER LOCATION CHANGE AND ANY TRANSACTIONS** ---
            // IMPORTANT: If moving to the new location triggers events that change coins
            // (like collecting salary for passing GO, paying rent, drawing cards),
            // that logic should happen *here* before refetching coins.
            // For now, we'll just refetch the coins assuming no immediate transaction logic here.
            $sql_get_updated_coins = "SELECT coins FROM players WHERE id = ? AND game_id = ? LIMIT 1";
            $stmt_get_updated_coins = $mysqli->prepare($sql_get_updated_coins);

            if ($stmt_get_updated_coins) {
                $stmt_get_updated_coins->bind_param('ii', $playerId, $gameId);
                $stmt_get_updated_coins->execute();
                $result_get_updated_coins = $stmt_get_updated_coins->get_result();

                if ($result_get_updated_coins && $result_get_updated_coins->num_rows === 1) {
                    $updatedRow = $result_get_updated_coins->fetch_assoc();
                    $newCoins = (int)$updatedRow['coins'];

                    // --- Add new_location and new_coins to the response ---
                    $response['success'] = true; // Set success to true here after all operations
                    $response['message'] = 'Rzut kostką wykonany i pozycja zaktualizowana.';
                    $response['new_location'] = $newLocation;
                    $response['new_coins'] = $newCoins; // <-- ADDED THIS LINE
                } else {
                     // Error fetching updated coins - this is less critical than location, but good to log
                    error_log("Failed to refetch coins for player ID: " . $playerId . " after move in game ID: " . $gameId);
                    // Continue with success=true but new_coins might be null or old value depending on logic
                    $response['success'] = true; // Still a successful roll and move
                    $response['message'] = 'Rzut kostką wykonany, pozycja zaktualizowana, ale nie udało się pobrać aktualnych monet.';
                    $response['new_location'] = $newLocation;
                    // $response['new_coins'] remains null or previous value
                }
                $stmt_get_updated_coins->close();

            } else {
                error_log("Error preparing updated coins query: " . $mysqli->error);
                 // Continue with success=true but new_coins might be null
                $response['success'] = true; // Still a successful roll and move
                $response['message'] = 'Rzut kostką wykonany, pozycja zaktualizowana, ale błąd przygotowania zapytania o monety.';
                $response['new_location'] = $newLocation;
                 // $response['new_coins'] remains null
            }

        } else {
            $response['message'] = "Błąd przygotowania zapytania o aktualizację pozycji: " . $mysqli->error;
            http_response_code(500); // Internal Server Error
        }

    } else {
        $response['message'] = "Nie znaleziono pozycji gracza w bazie.";
        http_response_code(404); // Not Found
    }
    $stmt_get_pos_coins->close();

} else {
    $response['message'] = "Błąd przygotowania zapytania o pobranie pozycji: " . $mysqli->error;
    http_response_code(500); // Internal Server Error
}


if (isset($mysqli) && $mysqli instanceof mysqli && !$mysqli->connect_errno) {
    $mysqli->close();
}

// Send the final JSON response
echo json_encode($response);
?>
