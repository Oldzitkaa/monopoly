<?php
/**
 * API do tworzenia nowej gry
 *
 * Skrypt odbiera dane JSON z informacjami o graczach i tworzy nową grę w bazie danych.
 * Każdy gracz otrzymuje początkowe statystyki na podstawie wybranej postaci.
 * Wykorzystuje transakcje dla zapewnienia spójności danych.
 */

// Ustawienie nagłówka odpowiedzi jako JSON
header('Content-Type: application/json');

// Dołączenie pliku z połączeniem do bazy danych
// Zakładamy, że database_connect.php obsługuje błędy połączenia
require_once './database_connect.php';

// Inicjalizacja odpowiedzi - domyślnie status błędu
$response = [
    'success' => false,
    'message' => 'Wystąpił nieznany błąd serwera.', // Domyślna generyczna wiadomość
    'gameId' => null,
    'debugInfo' => [] // Pole na dodatkowe informacje debugowe w trybie deweloperskim
];

// Używamy zmiennych na przygotowane zapytania, aby upewnić się, że są dostępne w finally
$stmtGame = null;
$stmtCharData = null;
$stmtPlayer = null;
$stmtUpdateGame = null;

// Weryfikacja metody żądania
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Nieprawidłowa metoda żądania. Wymagana metoda POST.';
    http_response_code(405); // Method Not Allowed
    echo json_encode($response);
    exit;
}

// Pobranie i dekodowanie danych JSON
$inputData = file_get_contents('php://input');
$data = json_decode($inputData, true);

// Logowanie otrzymanych danych (opcjonalnie, przydatne do debugowania na serwerze)
$logFile = 'game_creation_log.txt'; // Upewnij się, że serwer ma prawa zapisu do tego pliku
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Otrzymane dane: " . $inputData . PHP_EOL, FILE_APPEND);


// Walidacja podstawowych danych JSON
if (empty($data)) {
    $response['message'] = 'Nie otrzymano danych JSON.';
    http_response_code(400); // Bad Request
    echo json_encode($response);
    exit;
}

// Walidacja liczby graczy
if (!isset($data['numPlayers']) || !is_numeric($data['numPlayers'])) {
    $response['message'] = 'Brak lub nieprawidłowa liczba graczy.';
    http_response_code(400); // Bad Request
    echo json_encode($response);
    exit;
}
$numPlayers = (int)$data['numPlayers'];
if ($numPlayers < 2 || $numPlayers > 4) {
    $response['message'] = 'Nieprawidłowa liczba graczy. Dozwolona liczba: 2-4.';
    http_response_code(400); // Bad Request
    echo json_encode($response);
    exit;
}

// Walidacja danych graczy (struktura i zgodność liczby)
if (!isset($data['players']) || !is_array($data['players'])) {
    $response['message'] = 'Brak lub nieprawidłowe dane graczy.';
    http_response_code(400); // Bad Request
    echo json_encode($response);
    exit;
}
$playersData = $data['players'];
if (count($playersData) !== $numPlayers) { // Użyj !== dla ścisłego porównania typów
    $response['message'] = 'Niezgodna liczba graczy. Podano: ' . count($playersData) . ', oczekiwano: ' . $numPlayers;
    http_response_code(400); // Bad Request
    echo json_encode($response);
    exit;
}

// Walidacja danych każdego gracza (nick i ID postaci)
foreach ($playersData as $index => $player) {
    if (!isset($player['nickname']) || trim($player['nickname']) === '') {
        $response['message'] = 'Brak nicku dla gracza ' . ($index + 1) . '.';
        http_response_code(400); // Bad Request
        echo json_encode($response);
        exit;
    }

    if (!isset($player['characterId']) || !is_numeric($player['characterId'])) {
        $response['message'] = 'Brak lub nieprawidłowe ID postaci dla gracza ' . ($index + 1) . '.';
        http_response_code(400); // Bad Request
        echo json_encode($response);
        exit;
    }
    // Opcjonalna walidacja czy characterId > 0 jeśli ID startują od 1
    if ((int)$player['characterId'] <= 0) {
        $response['message'] = 'Nieprawidłowe ID postaci dla gracza ' . ($index + 1) . '.';
        http_response_code(400); // Bad Request
        echo json_encode($response);
        exit;
    }
}

// Rozpoczęcie transakcji bazodanowej - kluczowe dla spójności danych
$mysqli->begin_transaction();

try {
    // 1. Utworzenie rekordu gry
    $currentDate = date("Y-m-d H:i:s");
    // current_turn zaczyna od 1, status na 'active', created_at na teraz, current_player_id będzie ustawione później
    $sqlGame = 'INSERT INTO games (current_turn, status, created_at) VALUES (1, "active", ?)';
    $stmtGame = $mysqli->prepare($sqlGame);

    if (!$stmtGame) {
        // Jeśli przygotowanie się nie powiedzie, rzuć wyjątek
        throw new Exception("Błąd przygotowania zapytania gry: " . $mysqli->error);
    }

    $stmtGame->bind_param('s', $currentDate);

    if (!$stmtGame->execute()) {
        // Jeśli wykonanie się nie powiedzie, rzuć wyjątek
        throw new Exception("Błąd wykonania zapytania gry: " . $stmtGame->error);
    }

    $gameId = $mysqli->insert_id; // Pobierz ID nowo utworzonej gry

    // Upewnij się, że ID gry zostało poprawnie pobrane
    if (!$gameId) {
        throw new Exception("Nie udało się uzyskać ID nowej gry po wstawieniu.");
    }

    // --- USUNIĘTO: $stmtGame->close(); - Zamykanie przeniesione do finally ---

    // 2. Pobranie danych bazowych postaci wybranych przez graczy
    // Potrzebujemy statystyk bazowych z tabeli 'characters'
    $characterIds = array_map(function($p) {
        return (int)$p['characterId']; // Upewnij się, że ID są integerami
    }, $playersData);

    // Przygotowanie placeholderów '?' dla klauzuli IN w zapytaniu SQL
    $placeholders = implode(',', array_fill(0, count($characterIds), '?'));

    // Zapytanie wybierające statystyki bazowe dla podanych ID postaci
    // Zwraca tylko kolumny statystyk, które będą kopiowane do tabeli 'players'
    $sqlCharData = "SELECT
                        id,
                        name, -- Dodano name, przydatne w logach debugowych
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

    // Dynamiczne bindowanie parametrów (wszystkie ID postaci są integerami 'i')
    $types = str_repeat('i', count($characterIds));
    // bind_param oczekuje referencji, operator ... rozpakowuje tablicę na argumenty
    $stmtCharData->bind_param($types, ...$characterIds);

    if (!$stmtCharData->execute()) {
        throw new Exception("Błąd wykonania zapytania danych postaci: " . $stmtCharData->error);
    }

    $resultCharData = $stmtCharData->get_result();
    $charactersBaseStats = [];

    // Przechowaj statystyki bazowe w tablicy asocjacyjnej, gdzie kluczem jest ID postaci
    while ($row = $resultCharData->fetch_assoc()) {
        $charactersBaseStats[$row['id']] = $row;
        // Dodaj do debug info, aby zobaczyć, które postacie znaleziono
        $response['debugInfo']['characters_fetched'][] = ['id' => $row['id'], 'name' => $row['name']];
    }

    // Zamknij wynik zapytania
    $resultCharData->close(); // Można zamknąć wynik zaraz po pobraniu danych

    // --- USUNIĘTO: $stmtCharData->close(); - Zamykanie przeniesione do finally ---


    // 3. Walidacja czy wszystkie postacie zostały znalezione
    // Sprawdź, czy liczba znalezionych postaci odpowiada liczbie graczy.
    // Zapobiega to sytuacji, gdy gracz wybierze postać o ID, które nie istnieje w bazie.
    if (count($charactersBaseStats) !== count($characterIds)) {
        // Znajdź brakujące ID postaci dla dokładniejszego komunikatu debugowego
        $missingIds = array_diff($characterIds, array_keys($charactersBaseStats));
        throw new Exception("Nie znaleziono danych dla wszystkich wybranych postaci. Brakujące ID: " . implode(', ', $missingIds));
    }


    // 4. Utworzenie rekordów graczy i przypisanie im statystyk
    // Użyjemy Multi Insert lub przygotujemy zapytanie do wykonania w pętli
    // Zapytanie w pętli jest często prostsze w zarządzaniu parametrami
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
                        is_turn,
                        turns_to_miss
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

    $stmtPlayer = $mysqli->prepare($sqlPlayer);

    if (!$stmtPlayer) {
        throw new Exception("Błąd przygotowania zapytania gracza: " . $mysqli->error);
    }

    $firstPlayerId = null; // Zmienna do przechowania ID pierwszego gracza
    $playerIds = []; // Tablica na ID wszystkich utworzonych graczy

    // Dodaj każdego gracza do bazy
    foreach ($playersData as $index => $player) {
        $characterId = (int)$player['characterId']; // Upewnij się, że to integer
        $nickname = trim($player['nickname']); // Usuń białe znaki z nicku
        $turnOrder = $index + 1; // Kolejność tur (1-based)
        $isTurn = ($index === 0) ? 1 : 0; // Pierwszy gracz (o indeksie 0) zaczyna turę

        // Pobierz statystyki bazowe dla wybranej postaci
        // Mamy pewność, że postać istnieje dzięki wcześniejszej walidacji
        $charStats = $charactersBaseStats[$characterId];

        // Początkowe wartości stanu gry dla gracza
        $initialCoins = 1500; // Domyślny kapitał startowy (można przenieść do konfiguracji)
        $initialPopularity = 0;
        $initialLocation = 0; // ID pola startowego (zakładamy, że ID=0 to start)
        $initialTurnsToMiss = 0;

        // Bindowanie parametrów zapytania INSERT INTO players
        // Typy parametrów: i (int), s (string)
        $stmtPlayer->bind_param(
            'iisiiiiiiiiiiiii', // 16 parametrów: game_id, character_id, name, coins, pop, loc, stats..., turn_order, is_turn, turns_to_miss
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
            $isTurn,
            $initialTurnsToMiss
        );

        // Wykonaj zapytanie dla bieżącego gracza
        if (!$stmtPlayer->execute()) {
            // Jeśli wstawienie gracza się nie powiedzie, rzuć wyjątek z informacją o nicku
            throw new Exception("Błąd wykonania zapytania wstawiania gracza '$nickname': " . $stmtPlayer->error);
        }

        $playerId = $mysqli->insert_id; // Pobierz ID nowo utworzonego gracza
        $playerIds[] = $playerId; // Dodaj ID gracza do tablicy

        // Zapisz ID pierwszego gracza (który zaczyna grę)
        if ($isTurn) {
            $firstPlayerId = $playerId;
        }

        // Dodaj informacje o utworzonym graczu do logów debugowania odpowiedzi
        $response['debugInfo']['players_created'][] = [
            'id' => $playerId,
            'name' => $nickname,
            'characterId' => $characterId,
            'characterName' => $charStats['name'], // Użyj nazwy postaci pobranej wcześniej
            'turnOrder' => $turnOrder,
            'isTurn' => (bool)$isTurn // Konwertuj na boolean dla JSON
        ];
    }

    // --- USUNIĘTO: $stmtPlayer->close(); - Zamykanie przeniesione do finally ---


    // 5. Aktualizacja rekordu gry - ustawienie ID pierwszego gracza
    // current_player_id w tabeli games wskazuje, który gracz aktualnie ma turę
    if ($firstPlayerId) {
        $sqlUpdateGame = "UPDATE games SET current_player_id = ? WHERE id = ?";
        $stmtUpdateGame = $mysqli->prepare($sqlUpdateGame);

        if (!$stmtUpdateGame) {
             throw new Exception("Błąd przygotowania zapytania aktualizacji gry: " . $mysqli->error);
        }

        $stmtUpdateGame->bind_param('ii', $firstPlayerId, $gameId); // ID gracza i ID gry są integerami

        if (!$stmtUpdateGame->execute()) {
             throw new Exception("Błąd wykonania zapytania aktualizacji gry: " . $stmtUpdateGame->error);
        }

        // --- USUNIĘTO: $stmtUpdateGame->close(); - Zamykanie przeniesione do finally ---

    } else {
        // To nie powinno się zdarzyć, jeśli numPlayers > 0, ale jest dobrym zabezpieczeniem
        throw new Exception("Nie udało się ustalić pierwszego gracza. Brak graczy do utworzenia?");
    }

    // 6. Inicjalizacja stanu gry - ustawienie początkowych zasobów, zdarzeń, itp.
    // W tym miejscu można by dodać kod inicjalizujący stan planszy, kart, itp.
    // Np. Wylosowanie kart początkowych dla graczy, ustawienie właścicieli pól na null, itp.
    // W obecnej strukturze tabeli 'tiles' pole 'owner_id' jest domyślnie NULL, więc inicjalizacja planszy
    // nie jest tu konieczna, ale może być potrzebna dla innych elementów gry.


    // Jeśli wszystkie operacje na bazie danych powiodły się - zatwierdzamy transakcję
    $mysqli->commit();

    // Ustaw sukces w odpowiedzi
    $response['success'] = true;
    $response['message'] = 'Gra i gracze zostali pomyślnie utworzeni.';
    $response['gameId'] = $gameId; // Zwróć ID nowej gry
    $response['players'] = $playerIds; // Zwróć ID utworzonych graczy (opcjonalnie, ale może być przydatne)
    // Możesz także zwrócić więcej danych o graczach, jeśli są potrzebne na front-endzie zaraz po utworzeniu
    // $response['playersData'] = $response['debugInfo']['players_created']; // Przykład

} catch (Exception $e) {
    // Coś poszło nie tak w bloku try - wycofujemy transakcję, aby anulować wszystkie zmiany
    $mysqli->rollback();

    // Zapisz szczegółowy błąd do logu serwera (nie dla klienta)
    error_log('Błąd tworzenia gry w create_game.php: ' . $e->getMessage());

    // Dodaj informacje o błędzie do odpowiedzi w trybie debugowania
    $response['debugInfo']['error'] = $e->getMessage();
    $response['debugInfo']['trace'] = $e->getTraceAsString(); // Może być bardzo szczegółowy, używać ostrożnie

    // Ustaw generyczną wiadomość błędu dla klienta, aby uniknąć ujawniania szczegółów bazy danych
    $response['message'] = 'Wystąpił błąd podczas tworzenia gry. Spróbuj ponownie.'; // Wiadomość dla użytkownika
    // Możesz dodać bardziej specyficzną wiadomość, jeśli błąd jest np. walidacyjny i nie pochodzi z bazy
    // Ale w przypadku błędów z bloku try (np. błędy zapytań), generyczna wiadomość jest bezpieczniejsza.
    http_response_code(500); // Internal Server Error
} finally {
    // Upewnij się, że wszystkie przygotowane zapytania zostaną zamknięte, niezależnie od wyniku transakcji
    // Używamy 'instanceof mysqli_stmt' na wypadek, gdyby prepare() się nie powiodło i zmienna $stmt... nadal była nullem
    if ($stmtGame instanceof mysqli_stmt) $stmtGame->close();
    if ($stmtCharData instanceof mysqli_stmt) $stmtCharData->close();
    if ($stmtPlayer instanceof mysqli_stmt) $stmtPlayer->close();
    if ($stmtUpdateGame instanceof mysqli_stmt) $stmtUpdateGame->close();

    // Upewnij się, że połączenie z bazą danych zostanie zamknięte
    // Sprawdź, czy zmienna $mysqli istnieje i jest obiektem połączenia
    if (isset($mysqli) && $mysqli instanceof mysqli && !$mysqli->connect_errno) {
        $mysqli->close();
    }
}

// W trybie produkcyjnym usuń szczegóły debugowania z odpowiedzi wysyłanej do klienta
// Zdefiniuj stałą ENVIRONMENT np. w pliku konfiguracyjnym lub w database_connect.php
// define('ENVIRONMENT', 'development'); // lub 'production'
if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
    unset($response['debugInfo']);
}


// Zwróć odpowiedź JSON do klienta
echo json_encode($response);
?>