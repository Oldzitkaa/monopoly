<?php
header('Content-Type: text/plain; charset=utf-8');

if (isset($_GET['location'])) {
    $location = (int)$_GET['location'];

    if ($location === 0) {
        echo "Start wejście do Azji";
    } elseif (
        $location == 1 || $location == 3 || $location == 5 || $location == 6 ||
        $location == 8 || $location == 9 || $location == 12 || $location == 14 ||
        $location == 16 || $location == 18 || $location == 20 || $location == 21 ||
        $location == 24 || $location == 25 || $location == 27 || $location == 28 ||
        $location == 30 || $location == 31 || $location == 32 || $location == 36 ||
        $location == 39 || $location == 40 || $location == 42 || $location == 43
    ) {
        $restaurant = $location;
        echo "Restauracja " . $location;
    } elseif (
        $location === 2 || $location === 13 || $location === 17 ||
        $location === 26 || $location === 34 || $location === 41
    ) {
        echo "Stajesz do pojedynku kulinarnego! Wybierz swojego rywala!" . $location;
    } elseif (
        $location === 4 || $location === 10 || $location === 19 ||
        $location === 23 || $location === 35 || $location === 38
    ) {
        echo "Niespodzianka " . $location;
    } elseif (
        $location === 7
    ) {
        echo "Wejście do Afryki " . $location;
    } elseif ( 
        $location === 15
    ) {
        echo "Wejście do Australi " . $location;
    } elseif ( 
        $location === 22
    ) {
        echo "Wejście do Ameryki Północnej " . $location;
    } elseif ( 
        $location === 29
    ) {
        echo "Wejście do Ameryki Południowej " . $location;
    } elseif (
        $location === 37
    ) {
        echo "Wejście do Europy " . $location;
    } elseif ( 
        $location === 11
    ) {
        echo "Czas na doskonalenie umiejętności! Stoisz 2 kolejki, ale zyskujesz dodatkowe punkty do losowej statystyki." . $location;
    } elseif ( 
        $location === 33
    ) {
        echo "Czas na zasłużony odpoczynek! Stoisz 1 kolejkę, ciesząc się wolnym dniem. " . $location;
    } else { 
        echo "Wylądowałeś na polu numer " . $location . ".";
    }
} else { 
    echo "Brak parametru 'location'.";
}
?>