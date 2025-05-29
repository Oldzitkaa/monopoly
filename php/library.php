<?php
include_once './database_connect.php';

if (!isset($mysqli) || $mysqli->connect_errno) {
    die("<p style='color: red;'>Błąd połączenia z bazą danych: " . ($mysqli->connect_error ?? 'Brak szczegółów błędu.') . "</p>");
} else {
    $mysqli->set_charset("utf8");
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Potęga Smaku</title>
    <link rel="stylesheet" href="../css/rule.css"> 
    <link rel="icon" href="../zdj/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../zdj/favicon.ico" type="image/x-icon">
    </head>
<body>
    <img src="../zdj/tlo3.png" alt="Tło gry" class="backg"> 
    <div class="logo-div">
        <img src="../zdj/logo.png" alt="Potega Smakow" class="logo-zdj" onclick="logoPrompt()">
        <h2 class="step-title">MONOPOLY - AUTORZY</h2>
        <div class="rule-text">
            <table class="cards-table">
                <thead class="cards-table__header">
                    <tr>
                        <th>★</th>
                        <th>Opis Niespodzianki</th>
                    </tr>
                </thead>
                <tbody class="cards-table__body">
                <?php
                $sql_action = "SELECT id, name, description FROM `action_cards`";
                $stmt_action = $mysqli->prepare($sql_action);

                if ($stmt_action) {
                    if ($stmt_action->execute()) {
                        $result_action = $stmt_action->get_result();
                        if ($result_action->num_rows > 0) {
                            while ($row_action = $result_action->fetch_object()) {
                                echo "<tr class='cards-table__row2'>";
                                echo "<td class='number-td'>" . htmlspecialchars($row_action->id) ."</td>";
                                echo "<td class='number-td'>" . htmlspecialchars($row_action->description) ."  ". htmlspecialchars($row_action->name) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr class='cards-table__row'><td colspan='2'>Brak kart niespodzianek w bazie danych.</td></tr>";
                        }
                        $result_action->free();
                    } else {
                        echo "<tr class='cards-table__row'><td colspan='3' style='color: red;'>Błąd wykonania zapytania dla kart niespodzianek: " . htmlspecialchars($stmt_action->error) . "</td></tr>";
                        error_log("Błąd wykonania zapytania dla kart niespodzianek: " . $stmt_action->error);
                    }
                    $stmt_action->close();
                } else {
                    echo "<tr class='cards-table__row'><td colspan='3' style='color: red;'>Błąd przygotowania zapytania dla kart niespodrusek: " . htmlspecialchars($mysqli->error) . "</td></tr>";
                    error_log("Błąd przygotowania zapytania dla kart niespodzianek: " . $mysqli->error);
                }
                ?>
                </tbody>
            </table>

            <br><br>
            <table class="cards-table">
                <thead class="cards-table__header">
                    <tr>
                        <th>★</th>
                        <th>Opis Pojedynku</th>
                    </tr>
                </thead>
                <tbody class="cards-table__body">
                <?php
                $sql_duel = "SELECT id, description FROM `duel_cards`";
                $stmt_duel = $mysqli->prepare($sql_duel);

                if ($stmt_duel) {
                    if ($stmt_duel->execute()) {
                        $result_duel = $stmt_duel->get_result();
                        if ($result_duel->num_rows > 0) {
                            while ($row_duel = $result_duel->fetch_object()) {
                                echo "<tr class='cards-table__row'>";
                                echo "<td>" . htmlspecialchars($row_duel->id) . "</td>";
                                echo "<td'>" . htmlspecialchars($row_duel->description) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr class='cards-table__row'><td colspan='2'>Brak kart pojedynków w bazie danych.</td></tr>";
                        }
                        $result_duel->free();
                    } else {
                        echo "<tr class='cards-table__row'><td colspan='2' style='color: red;'>Błąd wykonania zapytania dla kart pojedynków: " . htmlspecialchars($stmt_duel->error) . "</td></tr>";
                        error_log("Błąd wykonania zapytania dla kart pojedynków: " . $stmt_duel->error);
                    }
                    $stmt_duel->close();
                } else {
                    echo "<tr class='cards-table__row'><td colspan='2' style='color: red;'>Błąd przygotowania zapytania dla kart pojedynków: " . htmlspecialchars($mysqli->error) . "</td></tr>";
                    error_log("Błąd przygotowania zapytania dla kart pojedynków: " . $mysqli->error);
                }
                ?>
                </tbody>
            </table>
            <div class="xd">
                <p class="easter-egg">
                    ░░░░░░░░▄██▄░░░░░░▄▄░░ <br>
                    ░░░░░░░▐███▀░░░░░▄███▌ <br>
                    ░░▄▀░░▄█▀▀░░░░░░░░▀██░ <br>
                    ░█░░░██░░░░░░░░░░░░░░░ <br>
                    █▌░░▐██░░▄██▌░░▄▄▄░░░▄ <br>
                    ██░░▐██▄░▀█▀░░░▀██░░▐▌ <br>
                    ██▄░▐███▄▄░░▄▄▄░▀▀░▄██ <br>
                    ▐███▄██████▄░▀░▄█████▌ <br>
                    ▐████████████▀▀██████░ <br>
                    ░▐████▀██████░░█████░░ <br>
                    ░░░▀▀▀░░█████▌░████▀░░ <br>
                    ░░░░░░░░░▀▀███░▀▀▀░░░░ <br> 
            </p>
            </div>
        </div>
        <a href="./end_game.php"><div class="info-div back" style="z-index: 120"><p class="info">Powrót</p></div></a>
        <a href="./rule.php"><div class="info-div author" style="z-index: 120"><p class="info">Zasady</p></div></a>
        <script src="../js/main_foto.js"></script>
    </body>
    </html>