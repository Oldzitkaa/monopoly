<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Potęga Smaku - Wyniki</title>
    <link rel="stylesheet" href="../css/end_game.css"> 
    <link rel="icon" href="../zdj/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../zdj/favicon.ico" type="image/x-icon">
    </head>
<body>
    <div class="logo_div">
        <p class="step-title">MONOPOLY</p> <img src="../zdj/logo.png" alt="Potega Smakow" class="logo_zdj">
        <button class="end-btn" id="endGameButton">OK</button>
    </div>
    <img src="../zdj/tlo3.png" alt="Tło gry" class="backg">

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const endGameButton = document.getElementById('endGameButton');
            if (endGameButton) {
                endGameButton.addEventListener('click', () => {
                    fetch('end_session.php')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                window.location.href = './index.php';
                            } else {
                                console.error('Błąd podczas kończenia sesji:', data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Wystąpił błąd sieciowy:', error);
                        });
                });
            }
        });
    </script>
</body>
</html>