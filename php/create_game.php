<?php
// Important: This must be the very first line, before any other output
// to prevent PHP errors from being output as HTML
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Ustawienie nagłówka dla odpowiedzi JSON
header('Content-Type: application/json');

try {
    // Dołączanie pliku z połączeniem do bazy danych
    require_once './database_connect.php';

    // Inicjalizacja tablicy odpowiedzi, która zostanie zwrócona jako JSON
    $response = [
        'success' => false,
        'message' => 'Wystąpił nieznany błąd serwera.',
        'gameId' => null,
        'debugInfo' => [] // Informacje debugowe, które będą usuwane w trybie produkcyjnym
    ];

    // Inicjalizacja zmiennych dla prepared statements
    $stmtGame = null;
    $stmtCharData = null;
    $stmtPlayer = null;
    $stmtUpdateGame = null;
    $stmtInitTiles = null; // Zmienna dla statementu inicjalizującego pola gry

    // Ścieżka do pliku logów, pomocnego w debugowaniu
    $logFile = 'game_creation_log.txt';

    // --- KLUCZOWY BLOK OBSŁUGI BŁĘDÓW POŁĄCZENIA Z BAZĄ DANYCH ---
    // Sprawdzamy, czy połączenie z bazą danych zostało nawiązane poprawnie
    if (!isset($mysqli) || $mysqli->connect_errno) {
        // Jeśli jest błąd, ustawiamy odpowiedni komunikat i informacje debugowe
        $response['message'] = 'Błąd połączenia z bazą danych. Sprawdź konfigurację bazy.';
        $response['debugInfo']['db_connect_error'] = $mysqli->connect_error ?? 'Brak obiektu $mysqli lub nieznany błąd połączenia.';
        
        // Logujemy błąd do pliku logów
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - KRYTYCZNY BŁĄD: Błąd połączenia z bazą danych: " . ($mysqli->connect_error ?? 'Nieznany błąd') . PHP_EOL, FILE_APPEND);
        
        // Ustawiamy kod odpowiedzi HTTP na 500 (Internal Server Error)
        http_response_code(500);
        // Zwracamy odpowiedź JSON i kończymy działanie skryptu
        echo json_encode($response);
        exit;
    }
    // --- KONIEC KLUCZOWEGO BLOKU ---

    // Sprawdzenie, czy żądanie jest typu POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Nieprawidłowa metoda żądania. Wymagana metoda POST.';
        http_response_code(405); // Metoda niedozwolona
        echo json_encode($response);
        exit;
    }

    // Odczytanie danych JSON z ciała żądania POST
    $inputData = file_get_contents('php://input');
    // Logowanie otrzymanych danych do celów diagnostycznych
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Otrzymane dane: " . $inputData . PHP_EOL, FILE_APPEND);

    // Dekodowanie danych JSON
    $data = json_decode($inputData, true);

    // Walidacja, czy dane JSON zostały poprawnie odebrane
    if (empty($data)) {
        $jsonError = json_last_error_msg();
        $response['message'] = 'Nie otrzymano danych JSON lub dane są puste. Error: ' . $jsonError;
        $response['debugInfo']['input_data'] = $inputData;
        http_response_code(400); // Błąd złego żądania
        echo json_encode($response);
        exit;
    }

    // Walidacja liczby graczy
    if (!isset($data['numPlayers']) || !is_numeric($data['numPlayers'])) {
        $response['message'] = 'Brak lub nieprawidłowa liczba graczy.';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
    $numPlayers = (int)$data['numPlayers'];

    if ($numPlayers < 2 || $numPlayers > 4) {
        $response['message'] = 'Nieprawidłowa liczba graczy. Dozwolona liczba: 2-4.';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }

    // Walidacja danych o graczach
    if (!isset($data['players']) || !is_array($data['players'])) {
        $response['message'] = 'Brak lub nieprawidłowe dane graczy.';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
    $playersData = $data['players'];

    if (count($playersData) !== $numPlayers) {
        $response['message'] = 'Niezgodna liczba graczy. Podano: ' . count($playersData) . ', oczekiwano: ' . $numPlayers;
        http_response_code(400);
        echo json_encode($response);
        exit;
    }

    // Walidacja danych każdego gracza (nickname i ID postaci)
    foreach ($playersData as $index => $player) {
        if (!isset($player['nickname']) || trim($player['nickname']) === '') {
            $response['message'] = 'Brak nicku dla gracza ' . ($index + 1) . '.';
            http_response_code(400);
            echo json_encode($response);
            exit;
        }
        if (!isset($player['characterId']) || !is_numeric($player['characterId'])) {
            $response['message'] = 'Brak lub nieprawidłowe ID postaci dla gracza ' . ($index + 1) . '.';
            http_response_code(400);
            echo json_encode($response);
            exit;
        }
        if ((int)$player['characterId'] <= 0) {
            $response['message'] = 'Nieprawidłowe ID postaci dla gracza ' . ($index + 1) . '.';
            http_response_code(400);
            echo json_encode($response);
            exit;
        }
    }

    // Rozpoczęcie transakcji bazy danych
    $mysqli->begin_transaction();

    try {
        // Wstawienie nowej gry do tabeli 'games'
        $currentDate = date("Y-m-d H:i:s");
        $sqlGame = 'INSERT INTO games (current_turn, status, created_at) VALUES (1, "active", ?)';
        $stmtGame = $mysqli->prepare($sqlGame);
        if (!$stmtGame) {
            throw new Exception("Błąd przygotowania zapytania gry: " . $mysqli->error);
        }
        $stmtGame->bind_param('s', $currentDate);
        if (!$stmtGame->execute()) {
            throw new Exception("Błąd wykonania zapytania gry: " . $stmtGame->error);
        }
        $gameId = $mysqli->insert_id; // Pobranie ID nowo utworzonej gry
        if (!$gameId) {
            throw new Exception("Nie udało się uzyskać ID nowej gry po wstawieniu.");
        }

        // Pobranie danych bazowych postaci dla wybranych ID
        $characterIds = array_map(function($p) {
            return (int)$p['characterId'];
        }, $playersData);

        // Tworzenie placeholderów dla zapytania IN (...)
        $placeholders = implode(',', array_fill(0, count($characterIds), '?'));
        $sqlCharData = "SELECT
                            id,
                            name,
                            base_cook_skill,
                            base_tolerance,
                            base_business_acumen,
                            base_belly_capacity,
                            base_spice_sense,
                            base_prep_time,
                            base_tradition_affinity
                        FROM characters
                        WHERE id IN ($placeholders)";
        $stmtCharData = $mysqli->prepare($sqlCharData);
        if (!$stmtCharData) {
            throw new Exception("Błąd przygotowania zapytania danych postaci: " . $mysqli->error);
        }
        // Wiązanie parametrów (wszystkie to liczby całkowite 'i')
        $types = str_repeat('i', count($characterIds));
        $stmtCharData->bind_param($types, ...$characterIds); // Użycie operatora rozpakowania
        if (!$stmtCharData->execute()) {
            throw new Exception("Błąd wykonania zapytania danych postaci: " . $stmtCharData->error);
        }
        $resultCharData = $stmtCharData->get_result();
        $charactersBaseStats = [];
        while ($row = $resultCharData->fetch_assoc()) {
            $charactersBaseStats[$row['id']] = $row;
            $response['debugInfo']['characters_fetched'][] = ['id' => $row['id'], 'name' => $row['name']];
        }
        $resultCharData->close();

        // Sprawdzenie, czy pobrano dane dla wszystkich postaci
        if (count($charactersBaseStats) !== count($characterIds)) {
            $missingIds = array_diff($characterIds, array_keys($charactersBaseStats));
            throw new Exception("Nie znaleziono danych dla wszystkich wybranych postaci. Brakujące ID: " . implode(', ', $missingIds));
        }

        // Wstawienie graczy do tabeli 'players'
        $sqlPlayer = 'INSERT INTO players (
                            game_id,
                            character_id,
                            name,
                            coins,
                            popularity,
                            location,
                            cook_skill,
                            tolerance,
                            business_acumen,
                            belly_capacity,
                            spice_sense,
                            prep_time,
                            tradition_affinity,
                            turn_order,
                            is_current_turn, 
                            turns_to_miss
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'; // 16 placeholderów
        $stmtPlayer = $mysqli->prepare($sqlPlayer);
        if (!$stmtPlayer) {
            throw new Exception("Błąd przygotowania zapytania gracza: " . $mysqli->error);
        }

        $firstPlayerId = null;
        $playerIds = [];
        foreach ($playersData as $index => $player) {
            $characterId = (int)$player['characterId'];
            $nickname = trim($player['nickname']);
            $turnOrder = $index + 1; // Ustawienie kolejności tur
            $isCurrentTurn = ($index === 0) ? 1 : 0; // Pierwszy gracz rozpoczyna turę
            $charStats = $charactersBaseStats[$characterId]; // Pobranie statystyk postaci

            // Domyślne wartości początkowe dla gracza
            $initialCoins = 1500;
            $initialPopularity = 0;
            $initialLocation = 0; // Początkowa pozycja na planszy (pole START)
            $initialTurnsToMiss = 0;

            // Wiązanie parametrów dla zapytania gracza
            $stmtPlayer->bind_param(
                'iisiiiiiiiiiiiii',
                $gameId,
                $characterId,
                $nickname,
                $initialCoins,
                $initialPopularity,
                $initialLocation,
                $charStats['base_cook_skill'],
                $charStats['base_tolerance'],
                $charStats['base_business_acumen'],
                $charStats['base_belly_capacity'],
                $charStats['base_spice_sense'],
                $charStats['base_prep_time'],
                $charStats['base_tradition_affinity'],
                $turnOrder,
                $isCurrentTurn,
                $initialTurnsToMiss
            );
            if (!$stmtPlayer->execute()) {
                throw new Exception("Błąd wykonania zapytania wstawiania gracza '$nickname': " . $stmtPlayer->error);
            }
            $playerId = $mysqli->insert_id; // Pobranie ID nowo utworzonego gracza
            $playerIds[] = $playerId; // Zapisanie ID gracza
            if ($isCurrentTurn) {
                $firstPlayerId = $playerId; // Ustawienie ID pierwszego gracza
            }
            $response['debugInfo']['players_created'][] = [
                'id' => $playerId,
                'name' => $nickname,
                'characterId' => $characterId,
                'characterName' => $charStats['name'],
                'turnOrder' => $turnOrder,
                'isTurn' => (bool)$isCurrentTurn
            ];
        } // KONIEC PĘTLI FOREACH

        // Aktualizacja gry o ID pierwszego gracza
        $sqlUpdateGame = "UPDATE games SET current_player_id = ? WHERE id = ?";
        $stmtUpdateGame = $mysqli->prepare($sqlUpdateGame);
        if (!$stmtUpdateGame) {
            throw new Exception("Błąd przygotowania zapytania aktualizacji gry: " . $mysqli->error);
        }
        $stmtUpdateGame->bind_param('ii', $firstPlayerId, $gameId);
        if (!$stmtUpdateGame->execute()) {
            throw new Exception("Błąd wykonania zapytania aktualizacji gry: " . $stmtUpdateGame->error);
        }

        // Inicjalizacja pól planszy (nieruchomości) w tabeli `game_tiles`
        $sqlInitTiles = "INSERT INTO game_tiles (game_id, tile_id, current_level, is_mortgaged)
                         SELECT ?, id, 0, 0 FROM tiles WHERE type = 'restaurant'";
        $stmtInitTiles = $mysqli->prepare($sqlInitTiles);
        if (!$stmtInitTiles) {
            throw new Exception("Błąd przygotowania zapytania inicjalizacji pól gry: " . $mysqli->error);
        }
        $stmtInitTiles->bind_param('i', $gameId);
        if (!$stmtInitTiles->execute()) {
            throw new Exception("Błąd wykonania zapytania inicjalizacji pól gry: " . $stmtInitTiles->error);
        }

        // Zatwierdzenie wszystkich operacji w transakcji
        $mysqli->commit();

        // Ustawienie odpowiedzi o sukcesie
        $response['success'] = true;
        $response['message'] = 'Gra i gracze zostali pomyślnie utworzeni.';
        $response['gameId'] = $gameId;
        $response['players'] = $playerIds;

        // Rozpoczęcie sesji i zapisanie ID gry (dla przekierowania)
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['game_id'] = $gameId;

    } catch (Exception $e) {
        // W przypadku błędu, wycofujemy wszystkie zmiany w transakcji
        $mysqli->rollback();
        // Logowanie błędu do pliku logów
        error_log('Błąd tworzenia gry w create_game.php: ' . $e->getMessage());
        // Dodanie informacji o błędzie do odpowiedzi debugowej
        $response['debugInfo']['error'] = $e->getMessage();
        $response['debugInfo']['trace'] = $e->getTraceAsString(); // Ślad stosu dla dokładniejszej diagnostyki
        $response['message'] = 'Wystąpił błąd podczas tworzenia gry: ' . $e->getMessage();
        http_response_code(500); // Ustawienie kodu błędu serwera
    } finally {
        // Zamykanie wszystkich prepared statements
        if ($stmtGame instanceof mysqli_stmt) $stmtGame->close();
        if ($stmtCharData instanceof mysqli_stmt) $stmtCharData->close();
        if ($stmtPlayer instanceof mysqli_stmt) $stmtPlayer->close();
        if ($stmtUpdateGame instanceof mysqli_stmt) $stmtUpdateGame->close();
        if ($stmtInitTiles instanceof mysqli_stmt) $stmtInitTiles->close();

        // Zamykanie połączenia z bazą danych
        if (isset($mysqli) && $mysqli instanceof mysqli && !$mysqli->connect_errno) {
            $mysqli->close();
        }
    }

    // Zwracanie odpowiedzi JSON
    echo json_encode($response);

} catch (Throwable $e) {
    // This is a catch-all for any errors that might occur before the regular error handling
    $errorResponse = [
        'success' => false,
        'message' => 'Krytyczny błąd serwera: ' . $e->getMessage(),
        'debugInfo' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ];
    error_log('Krytyczny błąd w create_game.php: ' . $e->getMessage() . ' w ' . $e->getFile() . ' na linii ' . $e->getLine());
    echo json_encode($errorResponse);
}
?>