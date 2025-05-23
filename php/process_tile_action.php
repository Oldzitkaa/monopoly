<?php
// process_tile_action.php
// WYŁĄCZ wyświetlanie błędów w odpowiedzi HTTP
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1); // Zapisuj błędy do loga, ale nie wyświetlaj
ini_set('error_log', __DIR__ . '/php_errors.log'); // Ścieżka do pliku logów
header('Content-Type: application/json');
$response = [
    'success' => false,
    'message' => 'Nieznany błąd serwera podczas akcji na polu.'
];
try {
    $dbConnectPath = __DIR__ . '/database_connect.php';
    if (!file_exists($dbConnectPath)) {
        throw new Exception('Plik database_connect.php nie został znaleziony.');
    }
    require_once $dbConnectPath;
    if (!isset($mysqli) || $mysqli->connect_error) {
        throw new Exception('Błąd połączenia z bazą danych: ' . ($mysqli->connect_error ?? 'Nieznany błąd'));
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Nieprawidłowa metoda żądania. Wymagana metoda POST.';
        http_response_code(405);
        echo json_encode($response);
        exit;
    }
    $inputData = file_get_contents('php://input');
    if ($inputData === false) {
        throw new Exception('Nie można odczytać danych wejściowych.');
    }
    $data = json_decode($inputData, true);
    // Sprawdź błędy JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        $response['message'] = 'Nieprawidłowy format danych JSON: ' . json_last_error_msg();
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
    // Walidacja podstawowych parametrów
    $actionType = $data['action_type'] ?? null;
    $playerId = $data['player_id'] ?? null;
    $gameId = $data['game_id'] ?? null;
    $location = $data['location'] ?? null; // To jest tile_id
    if (!$actionType || $playerId === null || $gameId === null || $location === null) {
        $response['message'] = 'Brak wymaganych parametrów (action_type, player_id, game_id, location).';
        $response['received_data'] = $data; // Dodaj to do debugowania
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
    // Rozpocznij transakcję bazy danych
    $mysqli->begin_transaction();
    $nextPlayerId = null; // Zmienna do przechowywania ID kolejnego gracza
    $newRoundStarted = false; // Zmienna informująca o rozpoczęciu nowej rundy
    // Pobierz aktualne dane gracza (monety, pozycja)
    $stmt = $mysqli->prepare("SELECT coins, location FROM players WHERE id = ? AND game_id = ?");
    if (!$stmt) {
        throw new Exception("Błąd przygotowania zapytania: " . $mysqli->error);
    }
    $stmt->bind_param('ii', $playerId, $gameId);
    $stmt->execute();
    $result = $stmt->get_result();
    $playerData = $result->fetch_assoc();
    $stmt->close();
    if (!$playerData) {
        throw new Exception("Gracz o ID {$playerId} lub gra o ID {$gameId} nie istnieje.");
    }
    $playerCoins = $playerData['coins'];
    $playerLocation = $playerData['location'];

    $actionsNotStrictlyBoundToLocation = [
        'duel', 'not_interested', 'skip_action',
        'accept_surprise', 'accept_training', 'accept_vacation',
        'accept_start_tile', 'accept_special_tile', 'accept_continent_entry'
    ];
    if ((int)$playerLocation !== (int)$location && !in_array($actionType, $actionsNotStrictlyBoundToLocation)) {
        throw new Exception("Gracz o ID {$playerId} nie znajduje się na polu {$location}. Znajduje się na polu {$playerLocation}.");
    }
    switch ($actionType) {
        case 'buy_property':
        case 'buy_restaurant': // Dodaj obsługę kupna restauracji
        case 'buy_hotel': // Dodaj obsługę kupna hotelu
            // Wymagany property_id, który będzie ID pola z tabeli 'tiles'
            $propertyId = $data['property_id'] ?? $location; // Użyj location jako fallback
            // 1. Pobierz dane pola z 'tiles' (koszt) i 'game_tiles' (current_owner_id)
            $stmt = $mysqli->prepare("SELECT t.cost, t.type, t.name, gt.current_owner_id FROM tiles t LEFT JOIN game_tiles gt ON t.id = gt.tile_id AND gt.game_id = ? WHERE t.id = ?");
            if (!$stmt) {
                throw new Exception("Błąd przygotowania zapytania: " . $mysqli->error);
            }
            $stmt->bind_param('ii', $gameId, $propertyId);
            $stmt->execute();
            $result = $stmt->get_result();
            $tileData = $result->fetch_assoc();
            $stmt->close();
            if (!$tileData) {
                throw new Exception("Pole o ID {$propertyId} nie istnieje.");
            }
            if ($tileData['current_owner_id'] !== null) {
                throw new Exception("Nieruchomość na polu {$propertyId} ma już właściciela.");
            }
            $purchasePrice = $tileData['cost'];
            if ($purchasePrice === null || $purchasePrice <= 0) {
                throw new Exception("To pole nie może być kupione.");
            }
            // Sprawdź zgodność typu akcji z typem pola
            $tileType = $tileData['type'];
            if ($actionType === 'buy_restaurant' && $tileType !== 'restaurant') {
                throw new Exception("Nie można kupić restauracji na tym polu - to nie jest restauracja.");
            }
            if ($actionType === 'buy_hotel' && $tileType !== 'hotel') {
                throw new Exception("Nie można kupić hotelu na tym polu - to nie jest hotel.");
            }
            // 2. Sprawdź, czy gracz ma wystarczająco pieniędzy
            if ($playerCoins < $purchasePrice) {
                throw new Exception("Masz za mało pieniędzy na zakup tej nieruchomości. Potrzebujesz {$purchasePrice} zł.");
            }
            // 3. Zaktualizuj tabelę 'game_tiles'
            $stmt = $mysqli->prepare("SELECT COUNT(*) FROM game_tiles WHERE game_id = ? AND tile_id = ?");
            if (!$stmt) {
                throw new Exception("Błąd przygotowania zapytania: " . $mysqli->error);
            }
            $stmt->bind_param('ii', $gameId, $propertyId);
            $stmt->execute();
            $exists = $stmt->get_result()->fetch_row()[0];
            $stmt->close();
            if ($exists > 0) {
                $stmt = $mysqli->prepare("UPDATE game_tiles SET current_owner_id = ?, is_mortgaged = 0, current_level = 0 WHERE game_id = ? AND tile_id = ?");
                if (!$stmt) {
                    throw new Exception("Błąd przygotowania zapytania UPDATE: " . $mysqli->error);
                }
                $stmt->bind_param('iii', $playerId, $gameId, $propertyId);
            } else {
                $stmt = $mysqli->prepare("INSERT INTO game_tiles (game_id, tile_id, current_owner_id, is_mortgaged, current_level) VALUES (?, ?, ?, 0, 0)");
                if (!$stmt) {
                    throw new Exception("Błąd przygotowania zapytania INSERT: " . $mysqli->error);
                }
                $stmt->bind_param('iii', $gameId, $propertyId, $playerId);
            }
            if (!$stmt->execute()) {
                throw new Exception("Błąd wykonania zapytania game_tiles: " . $stmt->error);
            }
            $stmt->close();
            // 4. Zaktualizuj saldo gracza
            $newPlayerCoins = $playerCoins - $purchasePrice;
            $stmt = $mysqli->prepare("UPDATE players SET coins = ? WHERE id = ? AND game_id = ?");
            if (!$stmt) {
                throw new Exception("Błąd przygotowania zapytania UPDATE players: " . $mysqli->error);
            }
            $stmt->bind_param('iii', $newPlayerCoins, $playerId, $gameId);
            if (!$stmt->execute()) {
                throw new Exception("Błąd aktualizacji salda gracza: " . $stmt->error);
            }
            $stmt->close();
            $propertyTypeName = ($tileType === 'restaurant') ? 'restaurację' : (($tileType === 'hotel') ? 'hotel' : 'nieruchomość');
            $response['success'] = true;
            $response['message'] = "Kupiłeś {$propertyTypeName} \"{$tileData['name']}\" za {$purchasePrice} zł.";
            $response['new_coins'] = $newPlayerCoins;
            // Po zakupie, tura zazwyczaj się kończ
            $nextPlayerId = getNextPlayerAndAdvanceTurn($mysqli, $gameId, $playerId, $newRoundStarted);
            $response['next_player_id'] = $nextPlayerId;
            $response['new_round_started'] = $newRoundStarted;
            break;
        case 'pay_rent':
            // Implementacja pay_rent
            $propertyId = $data['property_id'] ?? $location;
            $stmt = $mysqli->prepare("
                SELECT
                    gt.current_owner_id,
                    gt.current_level,
                    gt.is_mortgaged,
                    t.base_rent,
                    t.name
                FROM
                    game_tiles gt
                JOIN
                    tiles t ON gt.tile_id = t.id
                WHERE
                    gt.game_id = ? AND gt.tile_id = ?
            ");
            if (!$stmt) {
                throw new Exception("Błąd przygotowania zapytania rent: " . $mysqli->error);
            }
            $stmt->bind_param('ii', $gameId, $propertyId);
            $stmt->execute();
            $result = $stmt->get_result();
            $tileInfo = $result->fetch_assoc();
            $stmt->close();
            if (!$tileInfo) {
                throw new Exception("Informacje o polu dla ID {$propertyId} w grze {$gameId} nie zostały znalezione.");
            }
            $ownerId = $tileInfo['current_owner_id'];
            $level = $tileInfo['current_level'];
            $isMortgaged = $tileInfo['is_mortgaged'];
            if ($ownerId === null) {
                $response['success'] = true;
                $response['message'] = 'Pole nie ma właściciela, nie trzeba płacić czynszu.';
                $response['new_coins'] = $playerCoins;
                break;
            }
            if ($playerId == $ownerId) {
                $response['success'] = true;
                $response['message'] = 'Jesteś właścicielem tego pola, nie musisz płacić czynszu.';
                $response['new_coins'] = $playerCoins;
                break;
            }
            if ($isMortgaged) {
                $response['success'] = true;
                $response['message'] = 'Nieruchomość jest zastawiona, nie trzeba płacić czynszu.';
                $response['new_coins'] = $playerCoins;
                break;
            }
            // Oblicz czynsz (uproszczona wersja - tylko base_rent)
            $rentAmount = $tileInfo['base_rent'] * ($level + 1); // Proste mnożenie przez poziom
            if ($playerCoins < $rentAmount) {
                throw new Exception("Masz za mało pieniędzy na opłacenie czynszu ({$rentAmount} zł)! Obecne saldo: {$playerCoins} zł.");
            }
            // Odejmij czynsz od gracza
            $newPlayerCoins = $playerCoins - $rentAmount;
            $stmt = $mysqli->prepare("UPDATE players SET coins = ? WHERE id = ? AND game_id = ?");
            if (!$stmt) {
                throw new Exception("Błąd przygotowania zapytania UPDATE player coins: " . $mysqli->error);
            }
            $stmt->bind_param('iii', $newPlayerCoins, $playerId, $gameId);
            if (!$stmt->execute()) {
                throw new Exception("Błąd odejmowania czynszu: " . $stmt->error);
            }
            $stmt->close();
            // Dodaj czynsz do właściciela
            $stmt = $mysqli->prepare("UPDATE players SET coins = coins + ? WHERE id = ? AND game_id = ?");
            if (!$stmt) {
                throw new Exception("Błąd przygotowania zapytania UPDATE owner coins: " . $mysqli->error);
            }
            $stmt->bind_param('iii', $rentAmount, $ownerId, $gameId);
            if (!$stmt->execute()) {
                throw new Exception("Błąd dodawania czynszu do właściciela: " . $stmt->error);
            }
            $stmt->close();
            $response['success'] = true;
            $response['message'] = "Zapłacono czynsz w wysokości {$rentAmount} zł za \"{$tileInfo['name']}\".";
            $response['new_coins'] = $newPlayerCoins;
            $response['affected_player_id'] = $ownerId;
            // Pobierz nowe saldo właściciela
            $stmt = $mysqli->prepare("SELECT coins FROM players WHERE id = ? AND game_id = ?");
            if ($stmt) {
                $stmt->bind_param('ii', $ownerId, $gameId);
                $stmt->execute();
                $result = $stmt->get_result();
                $ownerData = $result->fetch_assoc();
                $response['affected_player_new_coins'] = $ownerData['coins'] ?? null;
                $stmt->close();
            }
            $nextPlayerId = getNextPlayerAndAdvanceTurn($mysqli, $gameId, $playerId, $newRoundStarted);
            $response['next_player_id'] = $nextPlayerId;
            $response['new_round_started'] = $newRoundStarted;
            break;
        case 'pass_turn':
        case 'skip_action': // Dodaj obsługę pomijania akcji
            $nextPlayerId = getNextPlayerAndAdvanceTurn($mysqli, $gameId, $playerId, $newRoundStarted);
            $response['success'] = true;
            $response['message'] = 'Tura zakończona pomyślnie.';
            $response['new_coins'] = $playerCoins;
            $response['next_player_id'] = $nextPlayerId;
            $response['new_round_started'] = $newRoundStarted;
            break;
        case 'upgrade_property':
            // Implementacja upgrade nieruchomości
            $propertyId = $data['property_id'] ?? $location;
            // Pobierz informacje o nieruchomości
            $stmt = $mysqli->prepare("
                SELECT
                    gt.current_owner_id,
                    gt.current_level,
                    gt.is_mortgaged,
                    t.upgrade_cost,
                    t.name
                FROM
                    game_tiles gt
                JOIN
                    tiles t ON gt.tile_id = t.id
                WHERE
                    gt.game_id = ? AND gt.tile_id = ?
            ");
            if (!$stmt) {
                throw new Exception("Błąd przygotowania zapytania upgrade: " . $mysqli->error);
            }
            $stmt->bind_param('ii', $gameId, $propertyId);
            $stmt->execute();
            $result = $stmt->get_result();
            $upgradeInfo = $result->fetch_assoc();
            $stmt->close();
            if (!$upgradeInfo) {
                throw new Exception("Informacje o nieruchomości nie zostały znalezione.");
            }
            if ($upgradeInfo['current_owner_id'] != $playerId) {
                throw new Exception("Nie jesteś właścicielem tej nieruchomości.");
            }
            if ($upgradeInfo['is_mortgaged']) {
                throw new Exception("Nie można ulepszać zastawionej nieruchomości.");
            }
            if ($upgradeInfo['current_level'] >= 5) {
                throw new Exception("Nieruchomość osiągnęła już maksymalny poziom.");
            }
            $upgradeCost = $upgradeInfo['upgrade_cost'];
            if ($upgradeCost === null || $upgradeCost <= 0) {
                throw new Exception("Ta nieruchomość nie może być ulepszona.");
            }
            if ($playerCoins < $upgradeCost) {
                throw new Exception("Masz za mało pieniędzy na ulepszenie ({$upgradeCost} zł).");
            }
            // Wykonaj ulepszenie
            $newLevel = $upgradeInfo['current_level'] + 1;
            $stmt = $mysqli->prepare("UPDATE game_tiles SET current_level = ? WHERE game_id = ? AND tile_id = ?");
            if (!$stmt) {
                throw new Exception("Błąd aktualizacji poziomu nieruchomości: " . $mysqli->error);
            }
            $stmt->bind_param('iii', $newLevel, $gameId, $propertyId);
            if (!$stmt->execute()) {
                throw new Exception("Błąd wykonania aktualizacji poziomu: " . $stmt->error);
            }
            $stmt->close();
            // Zaktualizuj saldo gracza
            $newPlayerCoins = $playerCoins - $upgradeCost;
            $stmt = $mysqli->prepare("UPDATE players SET coins = ? WHERE id = ? AND game_id = ?");
            if (!$stmt) {
                throw new Exception("Błąd aktualizacji salda gracza: " . $mysqli->error);
            }
            $stmt->bind_param('iii', $newPlayerCoins, $playerId, $gameId);
            if (!$stmt->execute()) {
                throw new Exception("Błąd wykonania aktualizacji salda: " . $stmt->error);
            }
            $stmt->close();
            $response['success'] = true;
            $response['message'] = "Ulepszono \"{$upgradeInfo['name']}\" do poziomu {$newLevel} za {$upgradeCost} zł.";
            $response['new_coins'] = $newPlayerCoins;
            // Po ulepszeniu zazwyczaj kończy się tura
            $nextPlayerId = getNextPlayerAndAdvanceTurn($mysqli, $gameId, $playerId, $newRoundStarted);
            $response['next_player_id'] = $nextPlayerId;
            $response['new_round_started'] = $newRoundStarted;
            break;
        case 'mortgage_property':
            // Implementacja zastawiania nieruchomości
            $propertyId = $data['property_id'] ?? $location;
            $stmt = $mysqli->prepare("
                SELECT
                    gt.current_owner_id,
                    gt.current_level,
                    gt.is_mortgaged,
                    t.cost,
                    t.name
                FROM
                    game_tiles gt
                JOIN
                    tiles t ON gt.tile_id = t.id
                WHERE
                    gt.game_id = ? AND gt.tile_id = ?
            ");
            if (!$stmt) {
                throw new Exception("Błąd przygotowania zapytania mortgage: " . $mysqli->error);
            }
            $stmt->bind_param('ii', $gameId, $propertyId);
            $stmt->execute();
            $result = $stmt->get_result();
            $mortgageInfo = $result->fetch_assoc();
            $stmt->close();
            if (!$mortgageInfo) {
                throw new Exception("Informacje o nieruchomości nie zostały znalezione.");
            }
            if ($mortgageInfo['current_owner_id'] != $playerId) {
                throw new Exception("Nie jesteś właścicielem tej nieruchomości.");
            }
            if ($mortgageInfo['is_mortgaged']) {
                throw new Exception("Ta nieruchomość jest już zastawiona.");
            }
            // Wartość zastawu to zazwyczaj połowa kosztu zakupu
            $mortgageValue = floor($mortgageInfo['cost'] / 2);
            // Zastaw nieruchomość
            $stmt = $mysqli->prepare("UPDATE game_tiles SET is_mortgaged = 1 WHERE game_id = ? AND tile_id = ?");
            if (!$stmt) {
                throw new Exception("Błąd zastawiania nieruchomości: " . $mysqli->error);
            }
            $stmt->bind_param('ii', $gameId, $propertyId);
            if (!$stmt->execute()) {
                throw new Exception("Błąd wykonania zastawu: " . $stmt->error);
            }
            $stmt->close();
            // Dodaj pieniądze graczowi
            $newPlayerCoins = $playerCoins + $mortgageValue;
            $stmt = $mysqli->prepare("UPDATE players SET coins = ? WHERE id = ? AND game_id = ?");
            if (!$stmt) {
                throw new Exception("Błąd aktualizacji salda gracza: " . $mysqli->error);
            }
            $stmt->bind_param('iii', $newPlayerCoins, $playerId, $gameId);
            if (!$stmt->execute()) {
                throw new Exception("Błąd wykonania aktualizacji salda: " . $stmt->error);
            }
            $stmt->close();
            $response['success'] = true;
            $response['message'] = "Zastawiono \"{$mortgageInfo['name']}\" za {$mortgageValue} zł.";
            $response['new_coins'] = $newPlayerCoins;
            break;
        case 'duel':
            // ZMIANA: Poprawne pobieranie targetPlayerId z 'target_player_id'
            $targetPlayerId = $data['target_player_id'] ?? null;
            if ($targetPlayerId === null) {
                throw new Exception("Nie wybrano rywala do pojedynku.");
            }
            if ($targetPlayerId == $playerId) {
                throw new Exception("Nie możesz pojedynkować się sam ze sobą!");
            }
            // Get target player's coins and name
            $stmt = $mysqli->prepare("SELECT coins, name FROM players WHERE id = ? AND game_id = ?");
            if (!$stmt) {
                throw new Exception("Błąd przygotowania zapytania dla rywala: " . $mysqli->error);
            }
            $stmt->bind_param('ii', $targetPlayerId, $gameId);
            $stmt->execute();
            $result = $stmt->get_result();
            $targetPlayerData = $result->fetch_assoc();
            $stmt->close();
            if (!$targetPlayerData) {
                throw new Exception("Rywal o ID {$targetPlayerId} nie istnieje w tej grze.");
            }
            $targetPlayerCoins = $targetPlayerData['coins'];
            $targetPlayerName = $targetPlayerData['name'];
            // Simple duel logic: each player "rolls a dice" (random number)
            $playerRoll = rand(1, 6);
            $targetRoll = rand(1, 6);
            $duelMessage = "Pojedynek między Tobą a {$targetPlayerName}! Twój rzut: {$playerRoll}, rzut {$targetPlayerName}: {$targetRoll}. ";
            $duelAmount = 100; // Amount to win/lose in duel
            $affectedPlayerId = null;
            $affectedPlayerNewCoins = null;
            if ($playerRoll > $targetRoll) {
                // Player wins
                $newPlayerCoins = $playerCoins + $duelAmount;
                $newTargetPlayerCoins = $targetPlayerCoins - $duelAmount;
                $duelMessage .= "Wygrałeś! Otrzymujesz {$duelAmount} zł od {$targetPlayerName}.";
            } elseif ($playerRoll < $targetRoll) {
                // Player loses
                $newPlayerCoins = $playerCoins - $duelAmount;
                $newTargetPlayerCoins = $targetPlayerCoins + $duelAmount;
                $duelMessage .= "Przegrałeś! Płacisz {$duelAmount} zł dla {$targetPlayerName}.";
            } else {
                // Tie
                $newPlayerCoins = $playerCoins;
                $newTargetPlayerCoins = $targetPlayerCoins;
                $duelMessage .= "Remis! Nikt nie płaci.";
            }
            // Update player's coins
            $stmt = $mysqli->prepare("UPDATE players SET coins = ? WHERE id = ? AND game_id = ?");
            if (!$stmt) {
                throw new Exception("Błąd przygotowania zapytania aktualizacji monet gracza: " . $mysqli->error);
            }
            $stmt->bind_param('iii', $newPlayerCoins, $playerId, $gameId);
            if (!$stmt->execute()) {
                throw new Exception("Błąd aktualizacji monet gracza: " . $stmt->error);
            }
            $stmt->close();
            // Update target player's coins (if not a tie)
            if ($playerRoll !== $targetRoll) {
                $stmt = $mysqli->prepare("UPDATE players SET coins = ? WHERE id = ? AND game_id = ?");
                if (!$stmt) {
                    throw new Exception("Błąd przygotowania zapytania aktualizacji monet rywala: " . $mysqli->error);
                }
                $stmt->bind_param('iii', $newTargetPlayerCoins, $targetPlayerId, $gameId);
                if (!$stmt->execute()) {
                    throw new Exception("Błąd aktualizacji monet rywala: " . $stmt->error);
                }
                $stmt->close();
                $affectedPlayerId = $targetPlayerId;
                $affectedPlayerNewCoins = $newTargetPlayerCoins;
            }
            $response['success'] = true;
            $response['message'] = $duelMessage;
            $response['new_coins'] = $newPlayerCoins;
            $response['affected_player_id'] = $affectedPlayerId;
            $response['affected_player_new_coins'] = $affectedPlayerNewCoins;
            $nextPlayerId = getNextPlayerAndAdvanceTurn($mysqli, $gameId, $playerId, $newRoundStarted);
            $response['next_player_id'] = $nextPlayerId;
            $response['new_round_started'] = $newRoundStarted;
            break;
        case 'not_interested':
        case 'accept_surprise':
        case 'accept_training':
        case 'accept_vacation':
        case 'accept_start_tile':
        case 'accept_special_tile':
        case 'accept_continent_entry':
            $nextPlayerId = getNextPlayerAndAdvanceTurn($mysqli, $gameId, $playerId, $newRoundStarted);
            $response['success'] = true;
            $response['message'] = 'Akcja zakończona pomyślnie. Tura zakończona.';
            $response['new_coins'] = $playerCoins;
            $response['next_player_id'] = $nextPlayerId;
            $response['new_round_started'] = $newRoundStarted;
            break;
        default:
            throw new Exception('Nieznany typ akcji: ' . $actionType . '. Dostępne akcje: buy_property, buy_restaurant, buy_hotel, pay_rent, pass_turn, skip_action, upgrade_property, mortgage_property, duel, not_interested, accept_surprise, accept_training, accept_vacation, accept_start_tile, accept_special_tile, accept_continent_entry');
    }
    $mysqli->commit();
} catch (Exception $e) {
    if (isset($mysqli) && $mysqli instanceof mysqli) {
        $mysqli->rollback();
    }
    error_log('Błąd w process_tile_action.php: ' . $e->getMessage());
    $response['success'] = false;
    $response['message'] = 'Wystąpił błąd serwera: ' . $e->getMessage();

    if (ini_get('display_errors')) {
        $response['debug_info'] = [
            'error_message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
    }
    http_response_code(500);
} finally {
    if (isset($mysqli) && $mysqli instanceof mysqli && !$mysqli->connect_errno) {
        $mysqli->close();
    }
}
echo json_encode($response);
/**
 * Funkcja pomocnicza do określania kolejnego gracza w turze
 */
function getNextPlayerAndAdvanceTurn(mysqli $mysqli, int $gameId, int $currentPlayingPlayerId, bool &$newRoundStarted): ?int {
    $newRoundStarted = false;
    $stmt = $mysqli->prepare("SELECT id, turn_order FROM players WHERE game_id = ? ORDER BY turn_order ASC");
    if (!$stmt) {
        throw new Exception("Błąd przygotowania zapytania dla kolejnego gracza: " . $mysqli->error);
    }
    $stmt->bind_param('i', $gameId);
    $stmt->execute();
    $result = $stmt->get_result();
    $allPlayers = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    if (empty($allPlayers)) {
        throw new Exception("Brak graczy w grze o ID {$gameId}.");
    }
    $playerIds = array_column($allPlayers, 'id');
    $currentPlayingPlayerIndex = array_search($currentPlayingPlayerId, $playerIds);
    if ($currentPlayingPlayerIndex === false) {
        throw new Exception("Gracz o ID {$currentPlayingPlayerId} nie jest częścią gry.");
    }
    $nextPlayerIndex = ($currentPlayingPlayerIndex + 1) % count($allPlayers);
    $nextPlayerId = $allPlayers[$nextPlayerIndex]['id'];
    if ($nextPlayerIndex < $currentPlayingPlayerIndex) {
        $newRoundStarted = true;
        $stmt = $mysqli->prepare("UPDATE games SET current_turn = current_turn + 1 WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $gameId);
            $stmt->execute();
            $stmt->close();
        }
    }
    // Wyłącz turę dla obecnego gracza
    $stmt = $mysqli->prepare("UPDATE players SET is_current_turn = 0 WHERE game_id = ? AND id = ?");
    if ($stmt) {
        $stmt->bind_param('ii', $gameId, $currentPlayingPlayerId);
        $stmt->execute();
        $stmt->close();
    }
    // Włącz turę dla następnego gracza
    $stmt = $mysqli->prepare("UPDATE players SET is_current_turn = 1 WHERE game_id = ? AND id = ?");
    if ($stmt) {
        $stmt->bind_param('ii', $gameId, $nextPlayerId);
        $stmt->execute();
        $stmt->close();
    }
    // Zaktualizuj current_player_id w tabeli games
    $stmt = $mysqli->prepare("UPDATE games SET current_player_id = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('ii', $nextPlayerId, $gameId);
        $stmt->execute();
        $stmt->close();
    }
    return $nextPlayerId;
}
?>
