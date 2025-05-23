<?php
// session_start();

// include_once './database_connect.php';
function getRandomDuelCard($mysqli) {
    if ($mysqli->connect_errno) {
        echo "Nie udało sie połączyć z MySQL: " . $mysqli->connect_error;
        exit();
    }

    if (!isset($_SESSION['drawn_duel_card_ids'])) {
        $_SESSION['drawn_duel_card_ids'] = [];
    }

    $max_drawn_cards = 5;

    if (count($_SESSION['drawn_duel_card_ids']) >= $max_drawn_cards) {
        $removed_card_id = array_shift($_SESSION['drawn_duel_card_ids']);

    // karta wracajaca 
        // echo "Karta pojedynku o ID: " . $removed_card_id . " wróciła do puli.<br>";
    }

    $drawnIds = $_SESSION['drawn_duel_card_ids'];

    $sql_count = 'SELECT COUNT(id) AS many FROM duel_cards';
    if (!empty($drawnIds)) {
        $placeholders = implode(',', array_fill(0, count($drawnIds), '?'));
        $sql_count .= ' WHERE id NOT IN (' . $placeholders . ')';
    }

    $stmt_count = $mysqli->prepare($sql_count);

    if (!$stmt_count) {
        echo "Błąd przygotowania zapytania zliczającego: (" . $mysqli->errno . ") " . $mysqli->error;
        exit();
    }

    if (!empty($drawnIds)) {
        $types = str_repeat('i', count($drawnIds));
        $stmt_count->bind_param($types, ...$drawnIds);
    }

    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $row_count = $result_count->fetch_object();
    $total_available_cards = $row_count->many;
    $stmt_count->close();

    if ($total_available_cards === 0) {
        echo "Nie znaleziono żadnych nowych kart pojedynku do wylosowania.";
        $mysqli->close();
        exit();
    }

    $random_offset = mt_rand(0, $total_available_cards - 1);

    $sql_duel = 'SELECT id, description, related_stat, effect_json FROM duel_cards';
    if (!empty($drawnIds)) {
        $sql_duel .= ' WHERE id NOT IN (' . $placeholders . ')';
    }
    $sql_duel .= ' LIMIT 1 OFFSET ?';

    $stmt_duel = $mysqli->prepare($sql_duel);

    if (!$stmt_duel) {
        echo "Błąd przygotowania zapytania pojedynku: (" . $mysqli->errno . ") " . $mysqli->error;
        exit();
    }

    $param_types = '';
    $param_values = [];

    if (!empty($drawnIds)) {
        $param_types .= str_repeat('i', count($drawnIds));
        $param_values = array_merge($param_values, $drawnIds);
    }

    $param_types .= 'i';
    $param_values[] = $random_offset;

    $stmt_duel->bind_param($param_types, ...$param_values);

    $stmt_duel->execute();
    $result_duel = $stmt_duel->get_result();
    $row_duel = $result_duel->fetch_object();


    // potem to bedzie out
    // if ($row_duel) {
    //     echo $row_duel->description . "<br>";
    //     $_SESSION['drawn_duel_card_ids'][] = $row_duel->id;
    //     echo "Obecnie wyciągnięte karty pojedynku (ID): " . implode(', ', $_SESSION['drawn_duel_card_ids']) . "<br>";
    //     echo "Liczba wyciągniętych kart pojedynku: " . count($_SESSION['drawn_duel_card_ids']) . "<br>";
    // } else {
    //     echo "Nie znaleziono karty pojedynku.";
    // }

    if ($row_duel) {
        return $row_duel;
    } else {
        error_log("Nie znaleziono karty pojedynku o wylosowanym offsetcie.");
        return null;
    }

    $stmt_duel->close();
    $mysqli->close();
}
?>