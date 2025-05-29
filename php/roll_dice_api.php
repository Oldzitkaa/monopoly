<?php
global $mysqli;
header('Content-Type: application/json');
require_once './database_connect.php';

$response = [
    'success' => false,
    'message' => 'Wystąpił nieznany błąd.',
    'roll_result' => null,
    'new_location' => null,
    'new_coins' => null,
    'current_player_id' => null,
    'turn_info' => null
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

$mysqli->begin_transaction();

function getCurrentPlayerId($gameId, $mysqli) {
    $stmt = $mysqli->prepare("SELECT current_player_id FROM games WHERE id = ?");
    $stmt->bind_param("i", $gameId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? (int)$row['current_player_id'] : null;
}

function handleAllPlayersWithTurnsToMiss($gameId, $mysqli) {
    // Sprawdź czy wszyscy gracze mają tury do przegapienia (turns_to_miss > 0)
    $sql_check_all_skip = "SELECT COUNT(*) as total_players, 
                          COUNT(CASE WHEN turns_to_miss > 0 THEN 1 END) as players_with_skip
                          FROM players WHERE game_id = ?";
    $stmt_check = $mysqli->prepare($sql_check_all_skip);
    if (!$stmt_check) {
        throw new Exception("Błąd przygotowania zapytania sprawdzania tur do przegapienia: " . $mysqli->error);
    }
    
    $stmt_check->bind_param('i', $gameId);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $check_row = $result_check->fetch_assoc();
    $stmt_check->close();
    
    $totalPlayers = (int)$check_row['total_players'];
    $playersWithSkip = (int)$check_row['players_with_skip'];
    
    // Jeśli wszyscy gracze mają tury do przegapienia
    if ($totalPlayers > 0 && $totalPlayers === $playersWithSkip) {
        // Odejmij po jednej turze do przegapienia wszystkim graczom
        $sql_reduce_skip = "UPDATE players SET turns_to_miss = turns_to_miss - 1 
                           WHERE game_id = ? AND turns_to_miss > 0";
        $stmt_reduce = $mysqli->prepare($sql_reduce_skip);
        if (!$stmt_reduce) {
            throw new Exception("Błąd przygotowania zapytania redukcji tur do przegapienia: " . $mysqli->error);
        }
        
        $stmt_reduce->bind_param('i', $gameId);
        $stmt_reduce->execute();
        $affected_rows = $stmt_reduce->affected_rows;
        $stmt_reduce->close();
        
        if ($affected_rows > 0) {
            error_log("Zredukowano tury do przegapienia dla $affected_rows graczy w grze $gameId");
            return true; // Oznacza, że dokonano redukcji
        }
    }
    
    return false; // Nie dokonano redukcji
}

function getAvailablePlayers($gameId, $mysqli) {
    $sql_get_players = "SELECT id, turn_order FROM players WHERE game_id = ? AND turns_to_miss <= 0 ORDER BY turn_order ASC";
    $stmt_get_players = $mysqli->prepare($sql_get_players);
    if (!$stmt_get_players) {
        throw new Exception("Błąd przygotowania zapytania o graczy: " . $mysqli->error);
    }
    $stmt_get_players->bind_param('i', $gameId);
    $stmt_get_players->execute();
    $result_get_players = $stmt_get_players->get_result();
    
    $players = [];
    while ($player_row = $result_get_players->fetch_assoc()) {
        $players[] = $player_row;
    }
    $stmt_get_players->close();
    
    return $players;
}

function createTurnQueue($gameId, $players, $turnNumber, $mysqli) {
    if (empty($players)) {
        return false;
    }
    
    $sql_create_queue = "INSERT INTO turn_queue (game_id, player_id, turn_number, queue_position, has_played, is_skipped) VALUES (?, ?, ?, ?, 0, 0)";
    $stmt_create_queue = $mysqli->prepare($sql_create_queue);
    if (!$stmt_create_queue) {
        throw new Exception("Błąd przygotowania zapytania tworzenia kolejki: " . $mysqli->error);
    }
    
    $queuePosition = 1;
    foreach ($players as $player) {
        $stmt_create_queue->bind_param('iiii', $gameId, $player['id'], $turnNumber, $queuePosition);
        $stmt_create_queue->execute();
        $queuePosition++;
    }
    $stmt_create_queue->close();
    
    return true;
}

try {
    $sql_get_turn = "SELECT current_turn FROM games WHERE id = ? LIMIT 1";
    $stmt_get_turn = $mysqli->prepare($sql_get_turn);
    if (!$stmt_get_turn) {
        throw new Exception("Błąd przygotowania zapytania pobierania numeru tury: " . $mysqli->error);
    }
    $stmt_get_turn->bind_param('i', $gameId);
    $stmt_get_turn->execute();
    $result_turn = $stmt_get_turn->get_result();
    $turn_row = $result_turn->fetch_assoc();
    $stmt_get_turn->close();

    if (!$turn_row) {
        throw new Exception("Nie znaleziono gry o podanym ID.");
    }
    $currentTurnNumber = (int)$turn_row['current_turn'];
    
    $sql_check_queue_exists = "SELECT COUNT(*) as queue_count FROM turn_queue WHERE game_id = ? AND turn_number = ?";
    $stmt_check_queue = $mysqli->prepare($sql_check_queue_exists);
    if (!$stmt_check_queue) {
        throw new Exception("Błąd przygotowania zapytania sprawdzania kolejki: " . $mysqli->error);
    }
    $stmt_check_queue->bind_param('ii', $gameId, $currentTurnNumber);
    $stmt_check_queue->execute();
    $result_check_queue = $stmt_check_queue->get_result();
    $queue_row = $result_check_queue->fetch_assoc();
    $stmt_check_queue->close();

    if ($queue_row['queue_count'] == 0) {
        // Najpierw sprawdź i obsłuż sytuację gdy wszyscy mają tury do przegapienia
        $reducedTurns = handleAllPlayersWithTurnsToMiss($gameId, $mysqli);
        
        // Pobierz dostępnych graczy
        $players = getAvailablePlayers($gameId, $mysqli);
        
        // Jeśli nadal brak graczy po redukcji, ale redukcja została wykonana, spróbuj ponownie
        if (empty($players) && $reducedTurns) {
            $players = getAvailablePlayers($gameId, $mysqli);
        }
        
        // Jeśli nadal brak graczy, to oznacza problem z logiką gry
        if (empty($players)) {
            // Sprawdź czy są w ogóle jacyś gracze w grze
            $sql_check_any_players = "SELECT COUNT(*) as player_count FROM players WHERE game_id = ?";
            $stmt_check_any = $mysqli->prepare($sql_check_any_players);
            $stmt_check_any->bind_param('i', $gameId);
            $stmt_check_any->execute();
            $result_any = $stmt_check_any->get_result();
            $any_row = $result_any->fetch_assoc();
            $stmt_check_any->close();
            
            if ($any_row['player_count'] == 0) {
                throw new Exception("Brak graczy w grze.");
            } else {
                // Jeśli są gracze, ale wszyscy mają turns_to_miss, wymuś reset
                $sql_force_reset = "UPDATE players SET turns_to_miss = 0 WHERE game_id = ?";
                $stmt_force_reset = $mysqli->prepare($sql_force_reset);
                $stmt_force_reset->bind_param('i', $gameId);
                $stmt_force_reset->execute();
                $stmt_force_reset->close();
                
                // Ponownie pobierz graczy
                $players = getAvailablePlayers($gameId, $mysqli);
                
                if (empty($players)) {
                    throw new Exception("Nie można znaleźć dostępnych graczy nawet po resecie turns_to_miss.");
                }
                
                error_log("Wymuszono reset turns_to_miss dla wszystkich graczy w grze $gameId");
            }
        }
        
        // Utwórz kolejkę
        if (!createTurnQueue($gameId, $players, $currentTurnNumber, $mysqli)) {
            throw new Exception("Nie udało się utworzyć kolejki graczy.");
        }
        
        // Ustaw pierwszego gracza jako aktualnego
        if (!empty($players)) {
            $firstPlayerId = $players[0]['id'];
            $sql_set_current_player = "UPDATE games SET current_player_id = ? WHERE id = ?";
            $stmt_set_current_player = $mysqli->prepare($sql_set_current_player);
            if ($stmt_set_current_player) {
                $stmt_set_current_player->bind_param('ii', $firstPlayerId, $gameId);
                $stmt_set_current_player->execute();
                $stmt_set_current_player->close();
            }
        }
    }
    
    $sql_ensure_player_in_queue = "INSERT IGNORE INTO turn_queue (game_id, player_id, turn_number, queue_position, has_played, is_skipped) 
                                   SELECT ?, ?, ?, 
                                          COALESCE((SELECT MAX(queue_position) FROM turn_queue tq WHERE tq.game_id = ? AND tq.turn_number = ?), 0) + 1,
                                          0, 0 
                                   WHERE NOT EXISTS (SELECT 1 FROM turn_queue WHERE game_id = ? AND player_id = ? AND turn_number = ?)";
    $stmt_ensure_player = $mysqli->prepare($sql_ensure_player_in_queue);
    if (!$stmt_ensure_player) {
        throw new Exception("Błąd przygotowania zapytania dodawania gracza do kolejki: " . $mysqli->error);
    }
    $stmt_ensure_player->bind_param('iiiiiiii', $gameId, $playerId, $currentTurnNumber, $gameId, $currentTurnNumber, $gameId, $playerId, $currentTurnNumber);
    $stmt_ensure_player->execute();
    $stmt_ensure_player->close();
    
    $sql_check_played = "SELECT has_played FROM turn_queue WHERE game_id = ? AND player_id = ? AND turn_number = ? LIMIT 1";
    $stmt_check_played = $mysqli->prepare($sql_check_played);
    if (!$stmt_check_played) {
        throw new Exception("Błąd przygotowania zapytania sprawdzania czy gracz już grał: " . $mysqli->error);
    }
    $stmt_check_played->bind_param('iii', $gameId, $playerId, $currentTurnNumber);
    $stmt_check_played->execute();
    $result_check_played = $stmt_check_played->get_result();
    $check_row = $result_check_played->fetch_assoc();
    $stmt_check_played->close();

    if ($check_row && $check_row['has_played'] == 1) {
        $response['success'] = false;
        $response['message'] = "Ten gracz już wykonał swój ruch w tej turze.";
        echo json_encode($response);
        exit;
    }

    $rollResult = rand(1, 6);
    $response['roll_result'] = $rollResult;

    $sql_get_pos_coins = "SELECT location, coins FROM players WHERE id = ? AND game_id = ? LIMIT 1";
    $stmt_get_pos_coins = $mysqli->prepare($sql_get_pos_coins);

    if (!$stmt_get_pos_coins) {
        throw new Exception("Błąd przygotowania zapytania o pobranie pozycji: " . $mysqli->error);
    }

    $stmt_get_pos_coins->bind_param('ii', $playerId, $gameId);
    $stmt_get_pos_coins->execute();
    $result_get_pos_coins = $stmt_get_pos_coins->get_result();

    if ($result_get_pos_coins && $result_get_pos_coins->num_rows === 1) {
        $row = $result_get_pos_coins->fetch_assoc();
        $currentLocation = (int)$row['location'];
        $currentCoins = (int)$row['coins'];

        $newLocation = ($currentLocation + $rollResult);
        $boardSize = 44;
        $passGoBonus = 200;
        if ($newLocation >= $boardSize) {
            $newLocation = $newLocation % $boardSize;
            $currentCoins += $passGoBonus;
            $passedGo = true;
        }

        if (isset($passedGo) && $passedGo) {
            $passGoMessage = "Gracz przeszedł całą planszę i otrzymał bonus $passGoBonus monet!";
        }

        $sql_update_location = "UPDATE players SET location = ?, coins = ? WHERE id = ? AND game_id = ?";
        $stmt_update_location = $mysqli->prepare($sql_update_location);

        if (!$stmt_update_location) {
            throw new Exception("Błąd przygotowania zapytania o aktualizację pozycji: " . $mysqli->error);
        }

        $stmt_update_location->bind_param('iiii', $newLocation, $currentCoins, $playerId, $gameId);
        $stmt_update_location->execute();
        $stmt_update_location->close();
        
        $sql_mark_played = "UPDATE turn_queue SET has_played = 1, last_roll = ? WHERE game_id = ? AND player_id = ? AND turn_number = ? AND has_played = 0";
        $stmt_mark_played = $mysqli->prepare($sql_mark_played);

        if (!$stmt_mark_played) {
            throw new Exception("Błąd przygotowania zapytania oznaczania gracza jako zagranego i zapisu rzutu: " . $mysqli->error);
        }

        $stmt_mark_played->bind_param('iiii', $rollResult, $gameId, $playerId, $currentTurnNumber);
        $stmt_mark_played->execute();
        $affected_rows = $stmt_mark_played->affected_rows;
        $stmt_mark_played->close();
        
        if ($affected_rows === 0) {
            error_log("WARNING: No rows affected when marking player $playerId as played in game $gameId, turn $currentTurnNumber");
        }
        
        $sql_next_player = "SELECT player_id FROM turn_queue 
                           WHERE game_id = ? AND turn_number = ? AND has_played = 0 AND is_skipped = 0 
                           ORDER BY queue_position ASC LIMIT 1";
        $stmt_next_player = $mysqli->prepare($sql_next_player);
        if (!$stmt_next_player) {
            throw new Exception("Błąd przygotowania zapytania o następnego gracza: " . $mysqli->error);
        }
        $stmt_next_player->bind_param('ii', $gameId, $currentTurnNumber);
        $stmt_next_player->execute();
        $result_next_player = $stmt_next_player->get_result();
        $next_player_row = $result_next_player->fetch_assoc();
        $stmt_next_player->close();

        if ($next_player_row) {
            $nextPlayerId = (int)$next_player_row['player_id'];
            $sql_update_current_player = "UPDATE games SET current_player_id = ? WHERE id = ?";
            $stmt_update_current_player = $mysqli->prepare($sql_update_current_player);
            if ($stmt_update_current_player) {
                $stmt_update_current_player->bind_param('ii', $nextPlayerId, $gameId);
                $stmt_update_current_player->execute();
                $stmt_update_current_player->close();
            }
            
            $response['current_player_id'] = $nextPlayerId;
            $response['turn_info'] = "Następny gracz w kolejce: " . $nextPlayerId;
        } else {
            $nextTurnNumber = $currentTurnNumber + 1;
            
            // Sprawdź i obsłuż sytuację gdy wszyscy mają tury do przegapienia dla nowej tury
            $reducedTurns = handleAllPlayersWithTurnsToMiss($gameId, $mysqli);
            
            // Pobierz dostępnych graczy dla nowej tury
            $players = getAvailablePlayers($gameId, $mysqli);
            
            // Jeśli nadal brak graczy po redukcji, ale redukcja została wykonana
            if (empty($players) && $reducedTurns) {
                $players = getAvailablePlayers($gameId, $mysqli);
            }
            
            // Jeśli nadal brak graczy, wymuś reset
            if (empty($players)) {
                $sql_force_reset = "UPDATE players SET turns_to_miss = 0 WHERE game_id = ?";
                $stmt_force_reset = $mysqli->prepare($sql_force_reset);
                $stmt_force_reset->bind_param('i', $gameId);
                $stmt_force_reset->execute();
                $stmt_force_reset->close();
                
                $players = getAvailablePlayers($gameId, $mysqli);
                error_log("Wymuszono reset turns_to_miss dla nowej tury w grze $gameId");
            }
            
            if (!empty($players)) {
                if (!createTurnQueue($gameId, $players, $nextTurnNumber, $mysqli)) {
                    throw new Exception("Nie udało się utworzyć kolejki dla nowej tury.");
                }
                
                $firstPlayerId = $players[0]['id'];
                $sql_update_game = "UPDATE games SET current_turn = ?, current_player_id = ? WHERE id = ?";
                $stmt_update_game = $mysqli->prepare($sql_update_game);
                if ($stmt_update_game) {
                    $stmt_update_game->bind_param('iii', $nextTurnNumber, $firstPlayerId, $gameId);
                    $stmt_update_game->execute();
                    $stmt_update_game->close();
                }
                
                $response['current_player_id'] = $firstPlayerId;
                $response['turn_info'] = "Nowa tura " . $nextTurnNumber . " rozpoczęta. Pierwszy gracz: " . $firstPlayerId;
                $response['new_round_started'] = true;
            } else {
                throw new Exception("Nie można utworzyć nowej tury - brak dostępnych graczy.");
            }
        }
        
        $sql_get_updated_coins = "SELECT coins FROM players WHERE id = ? AND game_id = ? LIMIT 1";
        $stmt_get_updated_coins = $mysqli->prepare($sql_get_updated_coins);

        if ($stmt_get_updated_coins) {
            $stmt_get_updated_coins->bind_param('ii', $playerId, $gameId);
            $stmt_get_updated_coins->execute();
            $result_get_updated_coins = $stmt_get_updated_coins->get_result();

            if ($result_get_updated_coins && $result_get_updated_coins->num_rows === 1) {
                $updatedRow = $result_get_updated_coins->fetch_assoc();
                $newCoins = (int)$updatedRow['coins'];

                $response['success'] = true;
                $response['message'] = 'Rzut kostką wykonany, pozycja zaktualizowana i kolejka zarządzana.';
                $response['new_location'] = $newLocation;
                $response['new_coins'] = $newCoins;
            } else {
                error_log("Failed to refetch coins for player ID: " . $playerId . " after move in game ID: " . $gameId);
                $response['success'] = true;
                $response['message'] = 'Rzut kostką wykonany, pozycja zaktualizowana, ale nie udało się pobrać aktualnych monet.';
                $response['new_location'] = $newLocation;
            }
            $stmt_get_updated_coins->close();
        } else {
            error_log("Error preparing updated coins query: " . $mysqli->error);
            $response['success'] = true;
            $response['message'] = 'Rzut kostką wykonany, pozycja zaktualizowana, ale błąd przygotowania zapytania o monety.';
            $response['new_location'] = $newLocation;
        }

    } else {
        throw new Exception("Nie znaleziono pozycji gracza w bazie.");
    }
    $stmt_get_pos_coins->close();

    $mysqli->commit();

    $response['current_player_id_db'] = getCurrentPlayerId($gameId, $mysqli);

} catch (Exception $e) {
    $mysqli->rollback();
    error_log("Błąd w roll_dice.php: " . $e->getMessage());
    $response['message'] = 'Wystąpił błąd podczas rzutu kostką: ' . $e->getMessage();
    http_response_code(500);
}

if (isset($mysqli) && $mysqli instanceof mysqli && !$mysqli->connect_errno) {
    $mysqli->close();
}
echo json_encode($response);
?>