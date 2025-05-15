<?php
session_start();
if (!isset($_SESSION['game_id'])) {
    header("Location: index.php");
    // echo $_SESSION['game_id'];
    exit();
}
$gameId = $_SESSION['game_id'];

include_once './database_connect.php';

if (!isset($mysqli) || $mysqli->connect_errno) {
    die("Brak aktywnego połączenia z bazą danych po dołączeniu pliku.");
}
$wynik = rand(1, 6);

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Rzut Kostką</title>
<link rel="stylesheet" href="../css/roll_dice.css">
</head>
<body>

<h1>Rzut Kostką</h1>

<img id="kostka" src="../zdj/kostki/<?= $wynik ?>.png" alt="Wynik: <?= $wynik ?>" class="kostka">

<p>Wyrzucono: <strong id="wynikTekst"><?= $wynik ?></strong></p>

<form id="rzutForm" method="post">
    <button type="submit" onclick="animacja()">Rzuć kostką</button>
</form>

<script>
    function animacja() {
        const kostka = document.getElementById('kostka');
        kostka.classList.add('animacja');
        setTimeout(() => {
            kostka.classList.remove('animacja');
        }, 1000);
    }
</script>

</body>
</html>
