<!-- <?php
include_once './database_connect.php';
if (!isset($mysqli) || $mysqli->connect_errno) {
    die("Brak aktywnego połączenia z bazą danych po dołączeniu pliku.");
}

$mysqli->set_charset("utf8");

// narazie po tem trzeba w sesji z indexu
$game_id = 1; 

// wyciaganie gry
$sql_game = "SELECT * FROM games WHERE id = ?";
$stmt_game = $mysqli->prepare($sql_game);
$stmt_game->bind_param("i", $game_id);
$stmt_game->execute();
$game = $stmt_game->get_result()->fetch_assoc();
$stmt_game->close();

// pola
$sql_tiles = "SELECT id, name, type, region, cost, base_rent, description FROM tiles ORDER BY id";
$result_tiles = $mysqli->query($sql_tiles);

$tiles = []; 
if ($result_tiles) { 
    if ($result_tiles->num_rows > 0) {
        while($row = $result_tiles->fetch_assoc()) {
            $tiles[] = $row;
        }
    }
} else {
    echo "Błąd zapytania SQL (tiles): " . $mysqli->error;
}

//gracze
$sql_players = "SELECT p.*, c.color, c.name as character_name 
                FROM players p 
                JOIN characters c ON p.character_id = c.id 
                WHERE p.game_id = ? AND p.is_active = 1 
                ORDER BY p.turn_order";
$stmt_players = $mysqli->prepare($sql_players);
$stmt_players->bind_param("i", $game_id);
$stmt_players->execute();
$players = $stmt_players->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_players->close();


$mysqli->close();

function get_space_classes($tile) {
    $classes = ['tile'];
    $type_class = strtolower(str_replace([' ', '_', '/'], '_', $tile['type']));
    if ($tile['type'] === 'restaurant' && !empty($tile['region'])) {
         $region_part = strtolower(str_replace([' ', '/'], '_', $tile['region']));
         $classes[] = $region_part . '_restaurant';
    } else {
         $classes[] = $type_class;
    }
    return implode(' ', $classes);
}

function get_space_content($tile) {
    //  $displayed_id = $tile['id'] + 1; 
    //  $content = '<div class="tile-id">' . $displayed_id . '</div>';
    //  $content .= '<div class="tile-name">' . htmlspecialchars($tile['name']) . '</div>'; 
    $content = '<div class="tile-name tile-' . htmlspecialchars($tile['type']) . ' '. htmlspecialchars($tile['region']).'">' . htmlspecialchars($tile['name']) . '</div>'; 
    $content .= '<div class="tile-tile"></div>'; 
    
    return $content;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MONOPOLY</title>
    <link rel="stylesheet" href="../css/style_gameboard.css">
</head>
<body>
    <div class="monopoly-board" id="monopoly-board">
        <div class="board-center-placeholder">
            MONOPOLY
            <span>Custom Edition</span>
        </div>
        <?php
        $tile_counter = 0; 
        if (!empty($tiles)) {
            foreach ($tiles as $tile) {
                $tile_counter++;
                echo '<div class="' . get_space_classes($tile) . '" id="space-' . $tile['id'] . '">';
                echo get_space_content($tile); 
                echo '</div>';
            }
        } else {
             echo "<p>Nie udało się pobrać danych pól z bazy danych lub brak pól do wyświetlenia.</p>";
        }
        ?>
    </div>
</body>
</html> -->

<?php
session_start();
require_once "config.php";

if ($mysqli->connect_errno) {
    die("Brak aktywnego połączenia z bazą danych po dołączeniu pliku.");
}

$mysqli->set_charset("utf8");

// Get game ID from session or default to 1
$game_id = $_SESSION['game_id'] ?? 1;

// Get game data
$sql_game = "SELECT * FROM games WHERE id = ?";
$stmt_game = $mysqli->prepare($sql_game);
$stmt_game->bind_param("i", $game_id);
$stmt_game->execute();
$game = $stmt_game->get_result()->fetch_assoc();
$stmt_game->close();

// Get tiles
$sql_tiles = "SELECT id, name, type, region, cost, base_rent, description FROM tiles ORDER BY id";
$result_tiles = $mysqli->query($sql_tiles);
$tiles = [];
if ($result_tiles) {
    if ($result_tiles->num_rows > 0) {
        while($row = $result_tiles->fetch_assoc()) {
            $tiles[] = $row;
        }
    }
}

// Get players with their positions
$sql_players = "SELECT p.*, c.color, c.name as character_name 
                FROM players p 
                JOIN characters c ON p.character_id = c.id 
                WHERE p.game_id = ? AND p.is_active = 1 
                ORDER BY p.turn_order";
$stmt_players = $mysqli->prepare($sql_players);
$stmt_players->bind_param("i", $game_id);
$stmt_players->execute();
$players = $stmt_players->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_players->close();

$mysqli->close();

function get_space_classes($tile) {
    $classes = ['tile'];
    $type_class = strtolower(str_replace([' ', '_', '/'], '_', $tile['type']));
    if ($tile['type'] === 'restaurant' && !empty($tile['region'])) {
        $region_part = strtolower(str_replace([' ', '/'], '_', $tile['region']));
        $classes[] = $region_part . '_restaurant';
    } else {
        $classes[] = $type_class;
    }
    return implode(' ', $classes);
}

function get_space_content($tile, $players_on_tile) {
    $content = '<div class="tile-name">' . htmlspecialchars($tile['name']) . '</div>';
    
    // Show players on this tile
    if (!empty($players_on_tile)) {
        $content .= '<div class="players-on-tile">';
        foreach ($players_on_tile as $player) {
            $content .= '<div class="player-marker" style="background-color: ' . $player['color'] . '"></div>';
        }
        $content .= '</div>';
    }
    
    return $content;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Monopoly - Custom Edition</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="monopoly-board">
        <?php
        // Group players by their location
        $players_by_location = [];
        foreach ($players as $player) {
            $players_by_location[$player['location']][] = $player;
        }
        
        foreach ($tiles as $tile) {
            $players_on_tile = $players_by_location[$tile['id']] ?? [];
            echo '<div class="' . get_space_classes($tile) . '">';
            echo get_space_content($tile, $players_on_tile);
            echo '</div>';
        }
        ?>
        
        <div class="board-center-placeholder">
            MONOPOLY
            <span>Custom Edition</span>
            
            <!-- Dice roll button -->
            <div class="dice-roll-container">
                <a href="dice.php" class="dice-roll-button">Rzuć kostką</a>
            </div>
        </div>
    </div>
</body>
</html>