<?php

ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');
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
    if (json_last_error() !== JSON_ERROR_NONE) {
        $response['message'] = 'Nieprawidłowy format danych JSON: ' . json_last_error_msg();
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
    $actionType = $data['action_type'] ?? null;
    $playerId = $data['player_id'] ?? null;
    $gameId = $data['game_id'] ?? null;
    $location = $data['location'] ?? null;
    if (!$actionType || $playerId === null || $gameId === null || $location === null) {
        $response['message'] = 'Brak wymaganych parametrów (action_type, player_id, game_id, location).';
        $response['received_data'] = $data;
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
    $mysqli->begin_transaction();
    $nextPlayerId = null;
    $newRoundStarted = false;
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
        case 'buy_restaurant':
        case 'buy_hotel':
            $propertyId = $data['property_id'] ?? $location;
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
            $tileType = $tileData['type'];
            if ($actionType === 'buy_restaurant' && $tileType !== 'restaurant') {
                throw new Exception("Nie można kupić restauracji na tym polu - to nie jest restauracja.");
            }
            if ($actionType === 'buy_hotel' && $tileType !== 'hotel') {
                throw new Exception("Nie można kupić hotelu na tym polu - to nie jest hotel.");
            }
            if ($playerCoins < $purchasePrice) {
                throw new Exception("Masz za mało pieniędzy na zakup tej nieruchomości. Potrzebujesz {$purchasePrice} $.");
            }
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
            $response['message'] = "Kupiłeś {$propertyTypeName} \"{$tileData['name']}\" za {$purchasePrice} $.";
            $response['new_coins'] = $newPlayerCoins;
            $nextPlayerId = getNextPlayerAndAdvanceTurn($mysqli, $gameId, $playerId, $newRoundStarted);
            $response['next_player_id'] = $nextPlayerId;
            $response['new_round_started'] = $newRoundStarted;
            break;
        case 'pay_rent':
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
            $rentAmount = $tileInfo['base_rent'] * ($level + 1);
            if ($playerCoins < $rentAmount) {
                throw new Exception("Masz za mało pieniędzy na opłacenie czynszu ({$rentAmount} $)! Obecne saldo: {$playerCoins} $.");
            }
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
            $response['message'] = "Zapłacono czynsz w wysokości {$rentAmount} $ za \"{$tileInfo['name']}\".";
            $response['new_coins'] = $newPlayerCoins;
            $response['affected_player_id'] = $ownerId;
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
        case 'skip_action':
            $nextPlayerId = getNextPlayerAndAdvanceTurn($mysqli, $gameId, $playerId, $newRoundStarted);
            $response['success'] = true;
            $response['message'] = 'Tura zakończona pomyślnie.';
            $response['new_coins'] = $playerCoins;
            $response['next_player_id'] = $nextPlayerId;
            $response['new_round_started'] = $newRoundStarted;
            break;
        case 'upgrade_property':
            $propertyId = $data['property_id'] ?? $location;
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
                throw new Exception("Masz za mało pieniędzy na ulepszenie ({$upgradeCost} $).");
            }
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
            $response['message'] = "Ulepszono \"{$upgradeInfo['name']}\" do poziomu {$newLevel} za {$upgradeCost} $.";
            $response['new_coins'] = $newPlayerCoins;
            $nextPlayerId = getNextPlayerAndAdvanceTurn($mysqli, $gameId, $playerId, $newRoundStarted);
            $response['next_player_id'] = $nextPlayerId;
            $response['new_round_started'] = $newRoundStarted;
            break;
        case 'mortgage_property':
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
            $mortgageValue = floor($mortgageInfo['cost'] / 2);

            $stmt = $mysqli->prepare("UPDATE game_tiles SET is_mortgaged = 1 WHERE game_id = ? AND tile_id = ?");
            if (!$stmt) {
                throw new Exception("Błąd zastawiania nieruchomości: " . $mysqli->error);
            }
            $stmt->bind_param('ii', $gameId, $propertyId);
            if (!$stmt->execute()) {
                throw new Exception("Błąd wykonania zastawu: " . $stmt->error);
            }
            $stmt->close();
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
            $response['message'] = "Zastawiono \"{$mortgageInfo['name']}\" za {$mortgageValue} $.";
            $response['new_coins'] = $newPlayerCoins;
            break;
       case 'accept_surprise':
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $actionCardHandlerPath = __DIR__ . '/random_action.php';
    if (!file_exists($actionCardHandlerPath)) {
        throw new Exception('Plik action_card_handler.php nie został znaleziony.');
    }
    require_once $actionCardHandlerPath;

    $actionHandler = new ActionCardHandler($mysqli);
    $result = $actionHandler->handleSurpriseField($playerId);

    if (!$result['success']) {
        throw new Exception($result['message']);
    }

    $cardInfo = $result['card'];
    // $actionMessage = "🎴 Karta akcji: \"{$cardInfo['name']}\"\n📜 {$cardInfo['description']}\n\n";
    $actionMessage = "{$cardInfo['name']}\"{$cardInfo['description']}\n\n";
    $newPlayerCoins = $playerCoins;
    $newPlayerLocation = $playerLocation;
    $affectedPlayerId = null;
    $affectedPlayerNewCoins = null;

    foreach ($result['effects'] as $effect) {
        switch ($effect['type']) {
            case 'move':
                $moveValue = $effect['value'];
                $direction = $effect['direction'] ?? 'forward';

                if ($direction === 'back') {
                    $newPlayerLocation = max(0, $playerLocation - $moveValue);
                    $actionMessage .= "⬅️ Cofasz się o {$moveValue} pól (z pola {$playerLocation} na pole {$newPlayerLocation}).";
                } else {
                    $newPlayerLocation = $playerLocation + $moveValue;
                    $actionMessage .= "➡️ Przesuwasz się o {$moveValue} pól do przodu (z pola {$playerLocation} na pole {$newPlayerLocation}).";
                }

                $stmt = $mysqli->prepare("UPDATE players SET location = ? WHERE id = ? AND game_id = ?");
                if (!$stmt) {
                    throw new Exception("Błąd aktualizacji pozycji gracza: " . $mysqli->error);
                }
                $stmt->bind_param('iii', $newPlayerLocation, $playerId, $gameId);
                if (!$stmt->execute()) {
                    throw new Exception("Błąd wykonania aktualizacji pozycji: " . $stmt->error);
                }
                $stmt->close();
                break;

            case 'coin_change':
                $moneyValue = $effect['value'];

                $newPlayerCoins = $playerCoins + $moneyValue;
                if ($newPlayerCoins < 0) {
                    throw new Exception("Nie masz wystarczająco pieniędzy na opłacenie kary (" . abs($moneyValue) . " $)!");
                }

                $stmt = $mysqli->prepare("UPDATE players SET coins = ? WHERE id = ? AND game_id = ?");
                if (!$stmt) {
                    throw new Exception("Błąd aktualizacji pieniędzy gracza: " . $mysqli->error);
                }
                $stmt->bind_param('iii', $newPlayerCoins, $playerId, $gameId);
                if (!$stmt->execute()) {
                    throw new Exception("Błąd wykonania aktualizacji pieniędzy: " . $stmt->error);
                }
                $stmt->close();

                if ($moneyValue > 0) {
                    $actionMessage .= "💰 Otrzymujesz {$moneyValue} $!";
                } else {
                    $actionMessage .= "💸 Płacisz " . abs($moneyValue) . " $!";
                }
                break;

            case 'stat_change':
                $statName = $effect['stat'];
                $statValue = $effect['value'];

                $validStats = ['cook_skill', 'tolerance', 'business_acumen', 'belly_capacity', 'spice_sense', 'prep_time', 'tradition_affinity'];

                if (!in_array($statName, $validStats)) {
                    throw new Exception("Nieznana statystyka: {$statName}");
                }

                $stmt = $mysqli->prepare("UPDATE players SET {$statName} = {$statName} + ? WHERE id = ? AND game_id = ?");
                if (!$stmt) {
                    throw new Exception("Błąd aktualizacji statystyki gracza: " . $mysqli->error);
                }
                $stmt->bind_param('iii', $statValue, $playerId, $gameId);
                if (!$stmt->execute()) {
                    throw new Exception("Błąd wykonania aktualizacji statystyki: " . $stmt->error);
                }
                $stmt->close();

                $statDisplayNames = [
                    'cook_skill' => 'Umiejętności gotowania',
                    'tolerance' => 'Tolerancja ostrości',
                    'business_acumen' => 'Łeb do biznesu',
                    'belly_capacity' => 'Pojemność brzucha',
                    'spice_sense' => 'Zmysł do przypraw',
                    'prep_time' => 'Czas przygotowania',
                    'tradition_affinity' => 'Tradycyjne powiązania'
                ];

                $statDisplayName = $statDisplayNames[$statName] ?? $statName;

                if ($statValue > 0) {
                    $actionMessage .= "📈 Twoja statystyka '{$statDisplayName}' wzrosła o {$statValue}!";
                } else {
                    $actionMessage .= "📉 Twoja statystyka '{$statDisplayName}' spadła o " . abs($statValue) . "!";
                }
                break;

            case 'skip_turns':
                $turnsValue = $effect['value'];

                $stmt = $mysqli->prepare("SELECT turns_to_miss FROM players WHERE id = ? AND game_id = ?");
                if (!$stmt) {
                    throw new Exception("Błąd pobierania aktualnych tur do przegapienia: " . $mysqli->error);
                }
                $stmt->bind_param('ii', $playerId, $gameId);
                $stmt->execute();
                $result_turns = $stmt->get_result();
                $currentTurnsToMiss = $result_turns->fetch_assoc()['turns_to_miss'];
                $stmt->close();

                $newTurnsToMiss = max(0, $currentTurnsToMiss + $turnsValue);

                $stmt = $mysqli->prepare("UPDATE players SET turns_to_miss = ? WHERE id = ? AND game_id = ?");
                if (!$stmt) {
                    throw new Exception("Błąd aktualizacji tur do przegapienia: " . $mysqli->error);
                }
                $stmt->bind_param('iii', $newTurnsToMiss, $playerId, $gameId);
                if (!$stmt->execute()) {
                    throw new Exception("Błąd wykonania aktualizacji tur do przegapienia: " . $stmt->error);
                }
                $stmt->close();

                if ($turnsValue > 0) {
                    $turnText = $turnsValue == 1 ? 'turę' : ($turnsValue <= 4 ? 'tury' : 'tur');
                    $actionMessage .= "⏸️ Będziesz musiał przegapić następne {$turnsValue} {$turnText}! (Łącznie: {$newTurnsToMiss})";
                } else if ($turnsValue < 0) {
                    $turnText = abs($turnsValue) == 1 ? 'turę' : (abs($turnsValue) <= 4 ? 'tury' : 'tur');
                    if ($currentTurnsToMiss > 0) {
                        $actionMessage .= "⏩ Odzyskujesz " . abs($turnsValue) . " {$turnText} do gry! (Pozostało do przegapienia: {$newTurnsToMiss})";
                    } else {
                        $actionMessage .= "⏩ Karta chciała odzyskać Ci " . abs($turnsValue) . " {$turnText}, ale nie masz żadnych kar!";
                    }
                } else {
                    $actionMessage .= "🔄 Brak zmian w turach do przegapienia.";
                }
                break;

            case 'teleport':
                $targetLocation = $effect['location'];
                $newPlayerLocation = $targetLocation;

                $stmt = $mysqli->prepare("UPDATE players SET location = ? WHERE id = ? AND game_id = ?");
                if (!$stmt) {
                    throw new Exception("Błąd teleportacji gracza: " . $mysqli->error);
                }
                $stmt->bind_param('iii', $newPlayerLocation, $playerId, $gameId);
                if (!$stmt->execute()) {
                    throw new Exception("Błąd wykonania teleportacji: " . $stmt->error);
                }
                $stmt->close();

                $actionMessage .= "🌀 Teleportujesz się na pole {$targetLocation}!";
                break;

            default:
                throw new Exception("Nieznany typ efektu karty akcji: " . $effect['type']);
        }
    }

    $response['success'] = true;
    $response['message'] = $actionMessage;
    $response['new_coins'] = $newPlayerCoins;
    $response['new_location'] = $newPlayerLocation;
    $response['affected_player_id'] = $affectedPlayerId;
    $response['affected_player_new_coins'] = $affectedPlayerNewCoins;
    $response['action_card'] = [
        'id' => $cardInfo['id'],
        'name' => $cardInfo['name'],
        'description' => $cardInfo['description']
    ];

    $nextPlayerId = getNextPlayerAndAdvanceTurn($mysqli, $gameId, $playerId, $newRoundStarted);
    $response['next_player_id'] = $nextPlayerId;
    $response['new_round_started'] = $newRoundStarted;
    break;
     case 'duel':
    include_once 'random_duel.php';

    $targetPlayerId = $data['target_player_id'] ?? null;
    if ($targetPlayerId === null) {
        throw new Exception("Nie wybrano rywala do pojedynku.");
    }
    if ($targetPlayerId == $playerId) {
        throw new Exception("Nie możesz pojedynkować się sam ze sobą!");
    }
    $stmt = $mysqli->prepare("
        SELECT 
            id, name, coins, cook_skill, tolerance, business_acumen, 
            belly_capacity, spice_sense, prep_time, tradition_affinity 
        FROM players 
        WHERE id IN (?, ?) AND game_id = ?
    ");
    if (!$stmt) {
        throw new Exception("Błąd przygotowania zapytania dla graczy: " . $mysqli->error);
    }
    $stmt->bind_param('iii', $playerId, $targetPlayerId, $gameId);
    $stmt->execute();
    $result = $stmt->get_result();
    $playersData = [];
    while ($row = $result->fetch_assoc()) {
        $playersData[$row['id']] = $row;
    }
    $stmt->close();

    if (!isset($playersData[$playerId]) || !isset($playersData[$targetPlayerId])) {
        throw new Exception("Jeden z graczy nie istnieje w tej grze.");
    }

    $currentPlayer = $playersData[$playerId];
    $targetPlayer = $playersData[$targetPlayerId];
    $duelCardObject = getRandomDuelCard($mysqli);

    if (!$duelCardObject) {
        throw new Exception("Brak dostępnych kart pojedynków w bazie danych.");
    }
    $duelCard = [
        'id' => $duelCardObject->id,
        'description' => $duelCardObject->description,
        'related_stat' => $duelCardObject->related_stat,
        'effect_json' => $duelCardObject->effect_json ?? null
    ];

    $relatedStat = $duelCard['related_stat'];
    $cardDescription = $duelCard['description'];

    $playerStatValue = $currentPlayer[$relatedStat] ?? 0;
    $targetStatValue = $targetPlayer[$relatedStat] ?? 0;

    $playerWins = false;
    $targetWins = false;
    if ($relatedStat === 'prep_time') {
        $playerWins = $playerStatValue < $targetStatValue;
        $targetWins = $playerStatValue > $targetStatValue;
    } else {
        $playerWins = $playerStatValue > $targetStatValue;
        $targetWins = $playerStatValue < $targetStatValue;
    }

    $duelAmount = 100;
    $affectedPlayerId = null;
    $affectedPlayerNewCoins = null;
    $duelMessage = $cardDescription . "\n\n";

    if ($playerWins) {
        $newPlayerCoins = $currentPlayer['coins'] + $duelAmount;
        $newTargetPlayerCoins = $targetPlayer['coins'] - $duelAmount;
        $affectedPlayerId = $targetPlayerId;
        $affectedPlayerNewCoins = $newTargetPlayerCoins;
        $duelMessage .= "Wygrywa {$currentPlayer['name']}";
    } elseif ($targetWins) {
        $newPlayerCoins = $currentPlayer['coins'] - $duelAmount;
        $newTargetPlayerCoins = $targetPlayer['coins'] + $duelAmount;
        $affectedPlayerId = $targetPlayerId;
        $affectedPlayerNewCoins = $newTargetPlayerCoins;
        $duelMessage .= "Wygrywa {$targetPlayer['name']}";
    } else {
        $duelMessage .= "Remis! Dodatkowy rzut kostką:\n";
        $playerRoll = rand(1, 6);
        $targetRoll = rand(1, 6);
        $duelMessage .= "Twój rzut: {$playerRoll}, rzut {$targetPlayer['name']}: {$targetRoll}\n";
        
        if ($playerRoll > $targetRoll) {
            $newPlayerCoins = $currentPlayer['coins'] + $duelAmount;
            $newTargetPlayerCoins = $targetPlayer['coins'] - $duelAmount;
            $affectedPlayerId = $targetPlayerId;
            $affectedPlayerNewCoins = $newTargetPlayerCoins;
            $duelMessage .= "Wygrałeś rzut kostką!";
        } elseif ($playerRoll < $targetRoll) {
            $newPlayerCoins = $currentPlayer['coins'] - $duelAmount;
            $newTargetPlayerCoins = $targetPlayer['coins'] + $duelAmount;
            $affectedPlayerId = $targetPlayerId;
            $affectedPlayerNewCoins = $newTargetPlayerCoins;
            $duelMessage .= "Przegrałeś rzut kostką!";
        } else {
            $newPlayerCoins = $currentPlayer['coins'];
            $newTargetPlayerCoins = $targetPlayer['coins'];
            $duelMessage .= "Całkowity remis! Nikt nie płaci.";
        }
    }
    if ($newPlayerCoins < 0) {
        throw new Exception("Masz za mało pieniędzy na opłacenie pojedynku ({$duelAmount} $)! Obecne saldo: {$currentPlayer['coins']} $.");
    }
    if ($newTargetPlayerCoins < 0) {
        throw new Exception("{$targetPlayer['name']} ma za mało pieniędzy na opłacenie pojedynku ({$duelAmount} $)! Obecne saldo: {$targetPlayer['coins']} $.");
    }
    $stmt = $mysqli->prepare("UPDATE players SET coins = ? WHERE id = ? AND game_id = ?");
    if (!$stmt) {
        throw new Exception("Błąd przygotowania zapytania aktualizacji monet gracza: " . $mysqli->error);
    }
    $stmt->bind_param('iii', $newPlayerCoins, $playerId, $gameId);
    if (!$stmt->execute()) {
        throw new Exception("Błąd aktualizacji monet gracza: " . $stmt->error);
    }
    $stmt->close();
    if ($newTargetPlayerCoins !== $targetPlayer['coins']) {
        $stmt = $mysqli->prepare("UPDATE players SET coins = ? WHERE id = ? AND game_id = ?");
        if (!$stmt) {
            throw new Exception("Błąd przygotowania zapytania aktualizacji monet rywala: " . $mysqli->error);
        }
        $stmt->bind_param('iii', $newTargetPlayerCoins, $targetPlayerId, $gameId);
        if (!$stmt->execute()) {
            throw new Exception("Błąd aktualizacji monet rywala: " . $stmt->error);
        }
        $stmt->close();
    }
    $response['success'] = true;
    $response['message'] = $duelMessage;
    $response['new_coins'] = $newPlayerCoins;
    $response['affected_player_id'] = $affectedPlayerId;
    $response['affected_player_new_coins'] = $affectedPlayerNewCoins;
    $response['duel_card'] = [
        'id' => $duelCard['id'],
        'description' => $cardDescription,
        'related_stat' => $relatedStat,
        'effect_json' => $duelCard['effect_json']
    ];

    $nextPlayerId = getNextPlayerAndAdvanceTurn($mysqli, $gameId, $playerId, $newRoundStarted);
    $response['next_player_id'] = $nextPlayerId;
    $response['new_round_started'] = $newRoundStarted;
    break;
        case 'not_interested':
            $nextPlayerId = getNextPlayerAndAdvanceTurn($mysqli, $gameId, $playerId, $newRoundStarted);
            $response['success'] = true;
            $response['message'] = 'Rezygnacja z zakupu!';
            $response['new_coins'] = $playerCoins;
            $response['next_player_id'] = $nextPlayerId;
            $response['new_round_started'] = $newRoundStarted;
            break;

        case 'accept_surprise':
            $nextPlayerId = getNextPlayerAndAdvanceTurn($mysqli, $gameId, $playerId, $newRoundStarted);
            $response['success'] = true;
            $response['message'] = 'Niespodzianka zaakceptowana!';
            $response['new_coins'] = $playerCoins;
            $response['next_player_id'] = $nextPlayerId;
            $response['new_round_started'] = $newRoundStarted;
            break;

        case 'accept_training':
            $nextPlayerId = getNextPlayerAndAdvanceTurn($mysqli, $gameId, $playerId, $newRoundStarted);
            $response['success'] = true;
            $response['message'] = 'Szkolenie rozpoczęte!';
            $response['new_coins'] = $playerCoins;
            $response['next_player_id'] = $nextPlayerId;
            $response['new_round_started'] = $newRoundStarted;
            break;

        case 'accept_vacation':
            $nextPlayerId = getNextPlayerAndAdvanceTurn($mysqli, $gameId, $playerId, $newRoundStarted);
            $response['success'] = true;
            $response['message'] = 'Miłego urlopu!';
            $response['new_coins'] = $playerCoins;
            $response['next_player_id'] = $nextPlayerId;
            $response['new_round_started'] = $newRoundStarted;
            break;

        case 'accept_start_tile':
            $nextPlayerId = getNextPlayerAndAdvanceTurn($mysqli, $gameId, $playerId, $newRoundStarted);
            $response['success'] = true;
            $response['message'] = 'Wróciłeś na pole start.';
            $response['new_coins'] = $playerCoins;
            $response['next_player_id'] = $nextPlayerId;
            $response['new_round_started'] = $newRoundStarted;
            break;

        case 'accept_special_tile':
            $nextPlayerId = getNextPlayerAndAdvanceTurn($mysqli, $gameId, $playerId, $newRoundStarted);
            $response['success'] = true;
            $response['message'] = 'Witaj na nowym kontynencie!';
            $response['new_coins'] = $playerCoins;
            $response['next_player_id'] = $nextPlayerId;
            $response['new_round_started'] = $newRoundStarted;
            break;

        case 'accept_continent_entry':
            $nextPlayerId = getNextPlayerAndAdvanceTurn($mysqli, $gameId, $playerId, $newRoundStarted);
            $response['success'] = true;
            $response['message'] = 'Witaj na nowym kontynencie!';
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
    $stmt = $mysqli->prepare("UPDATE players SET is_current_turn = 0 WHERE game_id = ? AND id = ?");
    if ($stmt) {
        $stmt->bind_param('ii', $gameId, $currentPlayingPlayerId);
        $stmt->execute();
        $stmt->close();
    }
    $stmt = $mysqli->prepare("UPDATE players SET is_current_turn = 1 WHERE game_id = ? AND id = ?");
    if ($stmt) {
        $stmt->bind_param('ii', $gameId, $nextPlayerId);
        $stmt->execute();
        $stmt->close();
    }
    $stmt = $mysqli->prepare("UPDATE games SET current_player_id = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('ii', $nextPlayerId, $gameId);
        $stmt->execute();
        $stmt->close();
    }
    return $nextPlayerId;
}
?>