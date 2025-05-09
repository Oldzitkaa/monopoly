<?php include_once './database_connect.php';

if (!isset($mysqli) || $mysqli->connect_errno) {
die("Brak aktywnego połączenia z bazą danych po dołączeniu pliku.");
}

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Potęga Smaku</title>
    <link rel="stylesheet" href="../css/styl.css">
</head>
<body>
<div class="logo_div step1">
        <p class="monopoly size1">MONOPOLY</p>
        <img src="../zdj/logo.png" alt="Potega Smakow" class="logo_zdj">
        <button class="next nextright" onclick="goToStepTwo()"><p class="nextstep size3">DALEJ &rarr;</p></button>
    </div>
    
<div class="logo_div step2" style="display: none; opacity: 0;">
        <p class="monopoly size1">Podaj liczbę <br> graczy</p><br>
        <p id="playerCount" class="size2">2</p>
        <label for="quantity">
            <input type="range" class="rangeplayer" name="quantity" id="quantity" min="2" max="4" value="2">
        </label>
        <div class="navigation-buttons">
            <button class="next nextleft" onclick="goBackToStepOne()"><p class="nextstep size3">&larr; WSTECZ</p></button>
            <button class="next nextright" onclick="goToNicknameStep()"><p class="nextstep size3">DALEJ &rarr;</p></button>
        </div>
    </div>
    
<?php 
    ?>

<div class="logo_div step3 nick" style="display: none; opacity: 0;">
        <p class="monopoly size1">Podaj nicki <br> graczy</p>
        <div id="nicknameInputsContainer">
            </div>
        <div class="navigation-buttons">
            <button class="next nextleft" onclick="goBackToStepTwo()"><p class="nextstep size3">&larr; WSTECZ</p></button>
            <button class="next nextright" onclick="goToCharacterStep()"><p class="nextstep size3">DALEJ &rarr;</p></button>
        </div>
    </div>

<div class="logo_div step3 characters" style="display: none; opacity: 0;">
        <p id="characterSelectionHeader" class="monopoly size1">Gracz 1, wybierz postać</p>
        <div id="characterCardsContainer" class="character-cards-grid">
            <?php
                if (isset($mysqli)) {
                    $sql2 = 'SELECT id, name, plik FROM characters'; // Dodano ID postaci
                    $query2 = $mysqli -> prepare($sql2);
                    if ($query2) {
                        $query2->execute();
                        $result2 = $query2->get_result();
                        while ($row = $result2->fetch_object()) {
                            echo "<div class='character-card' data-character-id='" . $row->id . "' data-character-name='" . htmlspecialchars($row->name) . "'>";
                            echo "<img src='../zdj/postacie/" . htmlspecialchars($row->plik) . "' alt='" . htmlspecialchars($row->name) . "'>";
                            echo "<p>" . htmlspecialchars($row->name) . "</p></div>";
                        }
                        $query2->close();
                    } else {
                        echo "<p>Błąd przygotowania zapytania postaci: " . $mysqli->error . "</p>";
                    }
                } else {
                    echo "<p>Brak połączenia z bazą danych do załadowania postaci.</p>";
                }
            ?>
        </div>
        <div id="selectedCharactersInfo" style="margin-top: 10px; color: white; font-size: 0.8em;">
            </div>
        <div class="navigation-buttons">
             <button class="next nextleft" onclick="goBackToNicknameStep()"><p class="nextstep size3">&larr; WSTECZ</p></button>
             <button class="next nextright" id="startGameButton" onclick="submitGameSetup()" style="display:none;"><p class="nextstep size3">ROZPOCZNIJ GRĘ!</p></button>
        </div>
    </div>
    
<img src="../zdj/tlo3.png" alt="" class="backg">
    
    <script src="../js/script_main.js"></script>
</body>
</html>