<?php
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
