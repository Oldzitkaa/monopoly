<?php
session_start();

if (!isset($_SESSION['game_id'])) {
    echo '<script>sessionStorage.removeItem("gameIdSet");</script>';
} else {
    echo '<script>sessionStorage.setItem("gameIdSet", "true");</script>';
}

include_once './database_connect.php'; 
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Potęga Smaku</title>
    <link rel="stylesheet" href="../css/styl.css"> 
    <link rel="stylesheet" href="../css/rule.css"> 
    <link rel="stylesheet" href="../css/main.css"> 
    <link rel="icon" href="../zdj/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../zdj/favicon.ico" type="image/x-icon">
    </head>
<body>
    <div class="logo_div step1">
        <p class="step-title">MONOPOLY</p>
        <img src="../zdj/logo.png" alt="Potega Smakow" class="logo_zdj" onclick="logoPrompt()">
        <div class="buttons-container">
            <?php
            if (isset($_SESSION['game_id']) && is_numeric($_SESSION['game_id']) && !is_null($_SESSION['game_id'])) {
                echo "<button class='back-button' id='brake-session-button' onclick='goToStepTwoExtra()'>NOWA GRA</button>";
                echo "<a href= './gameboard.php'><button class='start-button is'>WZNÓW</button></a>";
            } else {
                echo "<button class='back-button' onclick='goToStepTwo()'>NOWA GRA</button>";
                echo "<button class='start-button notis' onclick=''>WZNÓW</button>";
            }
            ?>
        </div>
    </div>
    <div class="logo_div step2" style="display: none; opacity: 0;">
        <p class="step-title">Podaj liczbę graczy</p> <div class="player-selection"> <div class="quantity-container"> <label for="quantity">Liczba graczy:</label>
        <span id="playerCount">2</span>
        <input type="range" class="rangeplayer" name="quantity" id="quantity" min="2" max="4" value="2">
    </div>
    <div class="buttons-container"> 
        <button class="back-button" onclick='(function(){ window.location.href="./index.php"; goBackToStepOne(); })()'>&larr; WSTECZ</button>
        <button class="next-button" onclick="goToNicknameStep()">DALEJ &rarr;</button>
    </div>
</div>
</div>
<div class="logo_div step3 nick" style="display: none; opacity: 0;">
    <p class="step-title">Utwórz graczy: <br> Podaj nicki</p> <div id="nicknameInputsContainer">
        </div>
        <div class="buttons-container"> 
            <button class="back-button" onclick="goBackToStepTwo()">&larr; WSTECZ</button>
            <button class="next-button" onclick="goToCharacterStep()">DALEJ &rarr;</button>
        </div>
    </div>
    <div class="logo_div step3 characters" style="display: none; opacity: 0; overflow: auto; max-height: 300px;">
        <p class="step-title" id="characterSelectionHeader">Wybierz postacie</p> 
        <div class="player-setup-container">
            <div class="player-list" id="playerSetupList">
                </div>
                <div class="character-details-panel">
                    <img id="selectedCharacterImage" src="../zdj/postacie/placeholder.png" alt="Wybierz postać" class="character-preview">
                    <h3 id="selectedCharacterName">Wybierz postać</h3>
                    <p id="selectedCharacterDescription">Kliknij na postać, aby zobaczyć jej opis i statystyki.</p>
                    <div class="character-stats-details">
                        <table>
                            </table>
                        </div>
                        <button id="confirmCharacterButton" class="next-button" style="width: 300px; margin-top: 10px;">Zatwierdź</button>
                    </div>
                </div>
                <div class="character-carousel">
                    <div id="characterCardsContainer" class="character-cards-container">
                        </div>
                    </div>
                    <div class="buttons-container"> <button class="back-button" onclick="goBackToNicknameStep()">&larr; WSTECZ</button>
                    <button class="start-button" id="startGameButton" onclick="submitGameSetup()" style="width: 300px;">ROZPOCZNIJ GRĘ!</button>
                </div>
            </div>
            <img src="../zdj/tlo3.png" alt="Tło gry" class="backg"> 
            <a href="./rule.php"><div class="info-div rule" style="z-index: 120"><p class="info">Zasady</p></div></a>
            <a href="./author.php"><div class="info-div author" style="z-index: 121"><p class="info">U・ᴥ・U</p></div></a>
    <script src="../js/script_main.js"></script>
    <script src="../js/main_foto.js"></script>
</body>
</html>