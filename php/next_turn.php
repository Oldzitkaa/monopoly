<?php
session_start();
header('Content-Type: application/json'); // Ustawiamy nagłówek na JSON
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sprawdź, czy sesja gry jest ustawiona
if (!isset($_SESSION['game_id'])) {
    echo json_encode(['success' => false, 'message' => 'Brak aktywnej sesji gry.']);
    exit();
}
$gameId = $_SESSION['game_id'];

// Upewnij się, że żądanie jest typu POST i zawiera niezbędne dane
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowa metoda żądania.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

// Walidacja danych wejściowych
if (!isset($data['currentPlayerId']) || !isset($data['gameId'])) {
    echo json_encode(['success' => false, 'message' => 'Brak wymaganych danych (currentPlayerId lub gameId).']);
    exit();
}

$currentPlayerId = $data['currentPlayerId'];
$requestedGameId = $data['gameId'];

// Dodatkowa walidacja, czy gameId z żądania zgadza się z gameId z sesji
if ($requestedGameId != $gameId) {
    echo json_encode(['success' => false, 'message' => 'Niezgodność identyfikatora gry.']);
    exit();
}

include_once './database_connect.php'; // Upewnij się, że ścieżka jest prawidłowa

if (!isset($mysqli) || $mysqli->connect_errno) {
    echo json_encode(['success' => false, 'message' => 'Brak aktywnego połączenia z bazą danych.']);
    exit();
}

$mysqli->set_charset("utf8");

$mysqli->begin_transaction(); // Rozpocznij transakcję dla bezpieczeństwa danych

try {
    // 1. Pobierz wszystkich graczy dla danej gry, posortowanych według turn_order
    $sql_players_order = "SELECT id, turn_order, turns_to_miss FROM players WHERE game_id = ? ORDER BY turn_order ASC";
    $stmt_players_order = $mysqli->prepare($sql_players_order);
    if (!$stmt_players_order) {
        throw new Exception("Błąd przygotowania zapytania SQL do pobrania kolejności graczy: " . $mysqli->error);
    }
    $stmt_players_order->bind_param('i', $gameId);
    if (!$stmt_players_order->execute()) {
        throw new Exception("Błąd wykonania zapytania SQL do pobrania kolejności graczy: " . $stmt_players_order->error);
    }
    $result_players_order = $stmt_players_order->get_result();
    $players_in_order = [];
    while ($row = $result_players_order->fetch_assoc()) {
        $players_in_order[] = $row;
    }
    $stmt_players_order->close();

    if (empty($players_in_order)) {
        throw new Exception("Brak graczy w grze o ID: " . $gameId);
    }

    // Znajdź indeks obecnego gracza
    $current_player_index = -1;
    foreach ($players_in_order as $index => $p) {
        if ($p['id'] == $currentPlayerId) {
            $current_player_index = $index;
            break;
        }
    }

    if ($current_player_index === -1) {
        throw new Exception("Obecny gracz (" . $currentPlayerId . ") nie znaleziony w tej grze.");
    }

    $nextPlayerId = null;
    $foundNextPlayer = false;

    // Iteruj, aby znaleźć następnego gracza, który nie ma tur do pominięcia
    for ($i = 1; $i <= count($players_in_order); $i++) {
        $next_index = ($current_player_index + $i) % count($players_in_order);
        $potentialNextPlayer = $players_in_order[$next_index];

        // Obsługa tur do pominięcia
        if ($potentialNextPlayer['turns_to_miss'] > 0) {
            // Zmniejsz liczbę tur do pominięcia dla tego gracza
            $sql_update_missed_turn = "UPDATE players SET turns_to_miss = turns_to_miss - 1 WHERE id = ?";
            $stmt_update_missed_turn = $mysqli->prepare($sql_update_missed_turn);
            if (!$stmt_update_missed_turn) {
                throw new Exception("Błąd przygotowania zapytania SQL do aktualizacji turns_to_miss: " . $mysqli->error);
            }
            $stmt_update_missed_turn->bind_param('i', $potentialNextPlayer['id']);
            if (!$stmt_update_missed_turn->execute()) {
                throw new Exception("Błąd wykonania zapytania SQL do aktualizacji turns_to_miss: " . $stmt_update_missed_turn->error);
            }
            $stmt_update_missed_turn->close();
            // Ten gracz pomija turę, więc przechodzimy do następnego
            continue;
        } else {
            // Ten gracz nie ma tur do pominięcia, więc to jest następny aktywny gracz
            $nextPlayerId = $potentialNextPlayer['id'];
            $foundNextPlayer = true;
            break;
        }
    }

    if (!$foundNextPlayer) {
         // To może się zdarzyć, jeśli wszyscy gracze mają tury do pominięcia.
         // W takim przypadku należy zdecydować, co zrobić (np. zrestartować szukanie, albo zaimplementować inną logikę).
         // Na razie dla uproszczenia po prostu zwrócimy błąd lub wyznaczymy obecnego gracza.
         throw new Exception("Nie znaleziono następnego aktywnego gracza. Wszyscy gracze mogą mieć tury do pominięcia.");
    }

    // 2. Ustaw is_turn dla obecnego gracza na 0
    $sql_update_current = "UPDATE players SET is_turn = 0 WHERE id = ? AND game_id = ?";
    $stmt_update_current = $mysqli->prepare($sql_update_current);
    if (!$stmt_update_current) {
        throw new Exception("Błąd przygotowania zapytania SQL do aktualizacji obecnego gracza: " . $mysqli->error);
    }
    $stmt_update_current->bind_param('ii', $currentPlayerId, $gameId);
    if (!$stmt_update_current->execute()) {
        throw new Exception("Błąd wykonania zapytania SQL do aktualizacji obecnego gracza: " . $stmt_update_current->error);
    }
    $stmt_update_current->close();

    // 3. Ustaw is_turn dla następnego gracza na 1
    $sql_update_next = "UPDATE players SET is_turn = 1 WHERE id = ? AND game_id = ?";
    $stmt_update_next = $mysqli->prepare($sql_update_next);
    if (!$stmt_update_next) {
        throw new Exception("Błąd przygotowania zapytania SQL do aktualizacji następnego gracza: " . $mysqli->error);
    }
    $stmt_update_next->bind_param('ii', $nextPlayerId, $gameId);
    if (!$stmt_update_next->execute()) {
        throw new Exception("Błąd wykonania zapytania SQL do aktualizacji następnego gracza: " . $stmt_update_next->error);
    }
    $stmt_update_next->close();

    $mysqli->commit(); // Zatwierdź transakcję
    echo json_encode(['success' => true, 'nextPlayerId' => $nextPlayerId, 'message' => 'Tura zmieniona pomyślnie.']);

} catch (Exception $e) {
    $mysqli->rollback(); // Wycofaj transakcję w przypadku błędu
    error_log("Błąd zmiany tury: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Wystąpił błąd podczas zmiany tury: ' . $e->getMessage()]);
} finally {
    if (isset($mysqli) && $mysqli instanceof mysqli && !$mysqli->connect_errno) {
        $mysqli->close();
    }
}
?>