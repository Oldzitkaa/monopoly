@use "./_color" as c;

// Zmienne dla spójności z styl.scss
$spacing-xs: 5px;
$spacing-sm: 10px;
$spacing-md: 15px;
$spacing-lg: 20px;
$spacing-xl: 30px;
$spacing-xxl: 50px; // Zwiększony spacing dla większych odstępów

$border-radius-sm: 8px;
$border-radius-md: 10px;
$border-radius-lg: 15px;
$border-radius-round: 50%;
// $border-radius-logo-div: 40%; // Nie potrzebne w tym pliku

$shadow-sm: 0 2px 5px rgba(0, 0, 0, 0.3);
$shadow-md: 0 4px 8px rgba(0, 0, 0, 0.4);
$shadow-lg: 0 8px 15px rgba(0, 0, 0, 0.5);
// $shadow-logo-div: 0 0 50px c.$red1; // Nie potrzebne w tym pliku
$shadow-button: 0px 0px 10px c.$mint; // Użyjemy tej zmiennej dla przycisku

$transition-duration: 0.5s;
$transition-ease: ease-in-out;
$transition-fast: 0.3s;


// --- Wspólne style strony ---
body {
    margin: 0;
    padding: $spacing-md; // Dodaj padding wokół całej zawartości
    font-family: 'Poppins', Arial, sans-serif; // Użyj tej samej czcionki co w styl.scss
    min-height: 100vh; // Minimalna wysokość na całą wysokość okna
    background-color: c.$white1; // Tło strony (solidny kolor)
    overflow: auto; // Zezwól na przewijanie, jeśli zawartość jest zbyt duża, szczególnie w układzie kolumnowym
    color: black; // Kolor tekstu na czarny dla lepszego kontrastu z planszą

    // Układ flexbox dla umieszczenia głównych elementów (plansza, panel sterowania, przycisk) jeden pod drugim i wyśrodkowania
    display: flex;
    flex-direction: column; // Elementy (plansza, panel, przycisk) ułożone w kolumnie
    justify-content: flex-start; // Elementy wyrównane do góry kontenera flex (body)
    align-items: center; // Elementy wyśrodkowane poziomo w kontenerze flex (body)

    box-sizing: border-box; // Padding wewnątrz elementu
}

html {
    height: 100%; // HTML również na 100% wysokości, aby body mogło użyć min-height: 100vh
}

a {
    text-decoration: none; // Usuń podkreślenie linków
}


// --- Stylizacja Planszy ---
.monopoly-board {
    display: grid;
    gap: 0;
    background-color: c.$white1; // Tło planszy
    box-sizing: border-box;
    position: relative;
    margin-bottom: $spacing-lg; // Dodaj odstęp pod planszą

    // --- Domyślne style (dla ekranów > 1200px) ---
    // Plansza ma stały rozmiar (max-width) i siatkę opartą o rem
    max-width: 1200px; // Maksymalna szerokość planszy
    width: 100%; // Plansza zajmuje 100% dostępnej przestrzeni do momentu osiągnięcia max-width
    // Przykładowe rozmiary siatki oparte o rem (można dostosować do dokładnych proporcji przy 1200px)
    grid-template-columns: minmax(7.5rem, auto) repeat(10, minmax(5rem, auto)) minmax(7.5rem, auto);
    grid-template-rows: minmax(7.5rem, auto) repeat(10, minmax(5rem, auto)) minmax(7.5rem, auto);
    border: 0.3vw solid black; // Ramka, skaluje się z viewportem
    padding: 0.3rem; // Padding
    font-size: 1rem; // Bazowy rozmiar czcionki

    // --- Style dla ekranów <= 1200px (nadpisują domyślne) ---
    @media (max-width: 1200px) {
        width: 98vw; // Plansza skaluje się z viewportem poniżej 1200px
        aspect-ratio: 1 / 1; // Zachowaj proporcje kwadratu
        // Responsywna siatka oparta o jednostki fr
        grid-template-columns: 1.5fr repeat(10, 1fr) 1.5fr;
        grid-template-rows: 1.5fr repeat(10, 1fr) 1.5fr;
        // Max height calculation dla mniejszych ekranów, uwzględniaj\u0105c marginesy pod plansz\u0105
        max-height: calc(100vh - 2 * $spacing-md - $spacing-xxl - $spacing-lg);
        // Ramka dla mniejszych ekranów, skaluje się z viewportem
        border-width: 0.4vw;
        border-style: solid;
        border-color: black;
        font-size: clamp(1rem, 5vw, 2rem); // Responsywny rozmiar czcionki
    }
    // Dodatkowe dostosowanie max-height dla bardzo niskich ekran\u00f3w
    @media (max-height: 800px) {
         max-height: calc(100vh - 2 * $spacing-md - $spacing-xxl - $spacing-lg);
    }
}

.tile {
    border: 0.10rem solid #ccc;
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    background-color: transparent;
    text-align: center;
    overflow: visible;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    align-items: center;
    word-wrap: break-word;
    hyphens: auto;
    line-height: 1.2;
    position: relative;
    font-size: clamp(0.6rem, 0.8vw + 0.3rem, 0.9rem);
    background-size: cover;
    background-position: center;
    background-image: none;
    background-repeat: no-repeat;
    background-blend-mode: overlay;


    &::before {
        content: "";
        position: absolute;
        inset: 0;
        background-image: var(--tile-bg);
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        opacity: 0.5;
        z-index: 0;
        pointer-events: none;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }

    .tile-id,
    .tile-name,
    .tile-name-text,
    .tile-price,
    .tile-rent,
    .tile-cost,
    .tile-description,
    .tile-tile,
    .tile-color-bar {
        position: relative;
        z-index: 2;
    }

    .tile-name {
        position: relative;
        z-index: 1;
        font-weight: bold;
        font-size: clamp(0.4rem, 0.8vw + 0.3rem, 0.7rem);
        flex-grow: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        text-align: center;
        padding: 2px;
        word-break: break-word;
        hyphens: auto;
    }

    .tile-name-text {
        // Użyj półprzezroczystego tła z koloru białego z pliku kolorów (dla czytelności tekstu na tle/obrazku)
        background: rgba(c.$white1, 0.3);
        padding: 2px;
    }


    .tile-tile {
        width: 100%;
        height: 0.6rem;
        margin-top: auto;
        border-top: 1px solid #000;
        position: absolute;
        bottom: 0;
        left: 0;
        z-index: 3;
    }

    .title-id {
        transform: rotate(-30deg);
        transform-origin: center;
        display: inline-block;
    }

    .tile-color-bar {
        position: relative;
        z-index: 2;
    }

    // Przypisanie kolorów do pasków na podstawie typu/regionu pola
    &.azja_restaurant .tile-tile     { background-color: c.$azja; }
    &.afryka_restaurant .tile-tile   { background-color: c.$afr; }
    &.australia_restaurant .tile-tile { background-color: c.$aus; }
    &.amerykan_restaurant .tile-tile { background-color: c.$amen; } // Sprawdź nazwę klasy dla regionu Ameryki Północnej
    &.amerykas_restaurant .tile-tile { background-color: c.$ames; } // Sprawdź nazwę klasy dla regionu Ameryki Południowej
    &.europa_restaurant .tile-tile   { background-color: c.$euro; }

    &.region_entrance .tile-tile    {background-color: c.$start;} // Kolor dla wejścia do regionu
    &.special .tile-tile    {background-color: c.$start;} // Kolor dla pól specjalnych (np. Szansa, Kasa Społeczna, Podatek)

    &.duel .tile-tile      { background-color: c.$duel; }
    &.event .tile-tile     { background-color: c.$action; }
    &.start .tile-tile,
    &.training .tile-tile,
    &.vacation .tile-tile  { background-color: c.$start; } // Kolor dla pól startowych/specjalnych

    .tile-price,
    .tile-rent,
    .tile-cost,
    .tile-description {
        display: block;
        font-size: 0.8em;
        font-weight: normal;
        margin-top: 0.2em;
        text-align: center;
        width: 100%;
        color: black; // Upewnij się, że tekst na polach jest czarny
    }
}

// --- Stylizacja Centralnego Obszaru Planszy ---
.board-center-placeholder {
    grid-area: 2 / 2 / 12 / 12; // Umieszcza w środku siatki 12x12
    z-index: 1;
    // Skomentowane lub usunięte wewn\u0119trzne style display/flex/color -
    // Ostateczny wygl\u0105d tego obszaru jest definiowany przez elementy wewn\u0105trz
    // (np. .player-info ze styl\u00f3w w gameboard_inner.css)
}


// --- Stylizacja Pionk\u00f3w Graczy ---
.player-token {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    margin: 2px;
    display: inline-block;
    border: 2px solid white; // Bia\u0142a ramka wok\u00f3\u0142 pionka
    // Kolor t\u0142a pionka jest ustawiany przez klasy .player-1, .player-2, etc.
}

// Kolory pionk\u00f3w (zgodne z kolorami z gameboard_inner.css/pawns.css)
.player-1 { background-color: red; }
.player-2 { background-color: blue; }
.player-3 { background-color: green; }
.player-4 { background-color: yellow; }


.players-on-tile {
    position: absolute; // Absolutne pozycjonowanie wewn\u0105trz pola (.tile jest relative)
    bottom: 5px; // Umieszczenie na dole pola
    width: 100%; // Na ca\u0142\u0105 szeroko\u015B\u0107 pola
    display: flex; // Uk\u0142ad flexbox
    justify-content: center; // Centrowanie pionk\u0142w poziomo wewn\u0105trz kontenera
    flex-wrap: wrap; // Zawijanie pionk\u0142w w rz\u0119dy, je\u015Bli jest ich za du\u017Co w jednej linii
}

// --- G\u0142\u00f3wny kontener dla planszy i kontrolek gry ---
// Ten kontener centruje plansz\u0119 i panel sterowania w poziomie i uk\u0142ada je w kolumnie
.game-main-container {
    display: flex;
    flex-direction: column; // Uk\u0142ada dzieci (plansza, wrapper kostki) w kolumnie
    width: 100%; // Zajmuje pe\u0142n\u0105 szeroko\u015B\u0107 rodzica (body)
    max-width: 1200px; // Ogranicza maksymaln\u0105 szeroko\u015B\u0107, aby plansza i kontrolki nie by\u0142y za szerokie
    align-items: center; // Centruje dzieci (plansz\u0119, wrapper kostki) w poziomie wewn\u0105trz tego kontenera
    margin: 0 auto; // Centruje sam kontener game-main-container w body
}

// --- Kontener dla kostki i panelu sterowania ---
// Ten wrapper zawiera panel sterowania i pomaga w jego pozycjonowaniu pod plansz\u0105
.game-controls-wrapper {
    display: flex;
    flex-direction: column; // Uk\u0142ada dzieci (panel kostki, przycisk ko\u0144ca gry?) w kolumnie
    align-items: center; // Centruje dzieci w poziomie
    width: 100%; // Zajmuje pe\u0142n\u0105 szeroko\u015B\u0107 rodzica (game-main-container)
    margin-top: $spacing-xxl; // ZWI\u0118KSZONY margines z g\u00f3ry, aby zawsze by\u0142o wi\u0119ksze oddzielenie od planszy
    padding-top: $spacing-lg; // Dodatkowy padding na gorze wewn\u0105trz wrappera
}

// --- Stylizacja Kontenera Sterowania Gr\u0105 (Kostka, Wynik, Przycisk) ---
// Kontener dla kostki i przycisk\u00f3w (panel pod plansz\u0105) - znajduje si\u0119 wewn\u0105trz game-controls-wrapper
.game-controls-container {
    display: flex;
    flex-direction: column; // Elementy (sekcja kostki) u\u0142o\u017Cone w kolumnie
    align-items: center; // Elementy wy\u015Brodkowane poziomo
    background-color: c.$white1; // T\u0142o kontenera (bia\u0142y z palety kolor\u00f3w)
    padding: $spacing-md; // Wewn\u0119trzne odst\u0119py
    border-radius: $border-radius-md; // Zaokr\u0105glone rogi
    margin-left: 0; // Brak marginesu z lewej strony w uk\u0142adzie kolumnowym body (lub game-main-container)
    margin-top: $spacing-lg; // Margines z g\u00f3ry wewn\u0105trz game-controls-wrapper
    box-shadow: $shadow-md; // \u015Aredni cie\u0144
    width: 100%; // Domy\u015Blnie pe\u0142na szeroko\u015B\u0107 w kontenerze flexbox (game-controls-wrapper)
    max-width: 300px; // Ograniczenie maksymalnej szeroko\u015Bci
    box-sizing: border-box; // Padding wewn\u0105trz elementu
}

// Styl dla sekcji zawieraj\u0105cej kostk\u0119 i wynik
.dice-section {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    width: 100%;
}

// Style dla obrazka kostki
.dice-image {
    display: block;
    width: clamp(60px, 15vw, 100px); // Responsywny rozmiar
    height: clamp(60px, 15vw, 100px); // Responsywny rozmiar
    margin: 10px auto; // Centrowanie poziome
    transition: transform .5s ease-out; // P\u0142ynne przej\u015Bcia transformacji
    cursor: pointer; // Kursor wskazuj\u0105cy na klikalno\u015B\u0107

    &.animacja { // Gdy element ma dodatkowo klas\u0119 .animacja
        animation: roll-animation 1s ease-out forwards; // Uruchom animacj\u0119 rzutu
    }
    // Usunięto media query, clamp obsłuży skalowanie na wszystkich szerokościach
}

// Definicja animacji rzutu kostk\u0105
@keyframes roll-animation {
    0% { transform: rotateX(0deg) rotateY(0deg) scale(1); }
    25% { transform: rotateX(180deg) rotateY(90deg) scale(1.1); }
    50% { transform: rotateX(360deg) rotateY(180deg) scale(1.2); }
    75% { transform: rotateX(540deg) rotateY(270deg) scale(1.1); }
    100% { transform: rotateX(720deg) rotateY(360deg) scale(1); }
}

// Style dla tekstu wy\u015Bwietlaj\u0105cego wynik rzutu
.roll-result-text {
    text-align: center;
    font-size: clamp(1em, 4vw, 1.5em); // Responsywny rozmiar czcionki
    font-weight: bold;
    color: c.$red1; // Kolor tekstu (czerwony z palety)
    margin-top: 5px;
    margin-bottom: 10px;
    min-height: 1.8em; // Zapewnia sta\u0142\u0105 wysoko\u015B\u0107, zapobiegaj\u0105c przesuni\u0119ciom layoutu
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3); // Cie\u0144 tekstu
    // Usunięto media query, clamp obsłuży skalowanie na wszystkich szerokościach
}

// Style dla przycisku rzutu kostk\u0105 (pasuj\u0105ce do reszty przycisk\u0142w, ale z innymi kolorami)
.roll-dice-button {
    padding: 8px 16px; // Wewn\u0119trzne odst\u0119py
    font-size: clamp(0.8em, 3vw, 1em); // Responsywny rozmiar czcionki
    cursor: pointer; // Kursor wskazuj\u0105cy na klikalno\u015B\u0107
    background-color: c.$mint; // T\u0142o przycisku (mi\u0119towy)
    color: c.$red1; // Kolor tekstu (czerwony)
    border: 2px solid c.$red1; // Ramka (czerwona)
    border-radius: 8px; // Zaokr\u0105glone rogi
    transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.1s ease; // P\u0142ynne przej\u015Bcia
    text-transform: uppercase; // Tekst du\u017Cymi literami
    font-weight: bold; // Gruba czcionka
    margin-top: 15px; // Margines z g\u00f3ry, oddzielaj\u0105cy od wyniku rzutu
    box-shadow: 0 2px 5px rgba(0,0,0,0.2); // Lekki cie\u0144

    &:hover { // Styl po najechaniu mysz\u0105
        background-color: darken(c.$mint, 10%); // Lekko przyciemnij t\u0142o
        border-color: darken(c.$red1, 10%); // Lekko przyciemnij ramk\u0119
        box-shadow: 0 4px 8px rgba(0,0,0,0.3); // Zwi\u0119ksz cie\u0144
    }

    &:active { // Styl po klikni\u0119ciu (stan aktywny)
        transform: translateY(1px); // Lekkie przesuni\u0119cie w d\u00f3\u0142
        box-shadow: 0 1px 3px rgba(0,0,0,0.2); // Zmniejsz cie\u0144
    }

    &:disabled { // Styl dla wy\u0142\u0105czonego przycisku
        opacity: 0.6; // Zmniejsz przezroczysto\u015B\u0107
        cursor: not-allowed; // Kursor wskazuj\u0105cy na brak mo\u017Cliwo\u015Bci klikni\u0119cia
        transform: none; // Usu\u0144 transformacje
        box-shadow: none; // Usu\u0144 cie\u0144
    }
    // Usunięto media query, clamp obsłuży skalowanie na wszystkich szerokościach
}

// --- Stylizacja Przycisku Zakończ Gre ---
// Kontener dla przycisku końca gry - znajduje się wewnątrz game-controls-wrapper
.end-game-div {
    display: flex; // Użyj flexbox, aby wyśrodkować przycisk
    justify-content: center; // Centruj zawartość (przycisk) poziomo
    margin-top: $spacing-lg; // Margines z góry, oddzielający od panelu sterowania
    width: 100%; // Div zajmuje pełną szerokość, co pomaga w centrowaniu
    margin-bottom: $spacing-lg; // Dodaj margines na dole strony
}

.btn-end-game {
    // Style skopiowane i zaadoptowane z przycisków w styl.scss (start-button, back-button, next-button)
    position: relative; // Jeśli potrzebne do wewnętrznych elementów pozycjonowanych
    background-color: c.$red1; // Tło przycisku (czerwony)
    border: 2px solid c.$mint; // Ramka (miętowa)
    border-radius: 40%; // Mocno zaokrąglone rogi (jak w styl.scss)
    color: c.$white1; // Kolor tekstu (biały)
    font-size: clamp(1rem, 2vw, 1.2rem); // Responsywny rozmiar czcionki (przywr\u00f3cono clamp)
    display: inline-flex; // Wyświetlanie inline-flex
    justify-content: center; // Centrowanie zawartości w pionie i poziomie
    align-items: center;
    box-shadow: $shadow-button; // Cień (miętowy)
    box-sizing: border-box; // Padding wewnątrz elementu
    padding: $spacing-md $spacing-xl; // Wewnętrzne odstępy (przywr\u00f3cono szerszy padding)
    text-align: center; // Wyrównanie tekstu do środka
    white-space: normal; // Normalne zawijanie tekstu
    line-height: 1.2; // Wysokość linii

    cursor: pointer; // Kursor wskazujący na klikalność
    transition: all $transition-fast $transition-ease; // Płynne przejścia przy najechaniu/kliknięciu
    font-weight: 600; // Gruba czcionka
    text-transform: uppercase; // Tekst dużymi literami
    letter-spacing: 1px; // Odstępy między literami
    text-decoration: none; // Brak podkreślenia (dla <a> elementu)

    &:hover { // Styl po najechaniu myszą
        background-color: c.$red2; // Lekko przyciemnij tło
        box-shadow: 0px 0px 15px c.$mint; // Zwiększ cień
        transform: translateY(-2px); // Lekkie przesunięcie w górę
    }

    &:active { // Styl po kliknięciu (stan aktywny)
        transform: translateY(0); // Powrót do pozycji
        box-shadow: $shadow-button; // Przywróć cień
    }

    &:disabled { // Styl dla wyłączonego przycisku (mniej prawdopodobne dla tego przycisku, ale dobra praktyka)
        opacity: 0.5; // Zmniejsz przezroczystość
        cursor: not-allowed; // Kursor wskazujący na brak możliwości kliknięcia
        transform: none; // Usuń transformacje
        box-shadow: none; // Usuń cień
    }

    // Opcjonalne dostosowanie rozmiaru na mniejszych ekranach
    @media (max-width: 767px) { // Zmieniono breakpoint z 1200px na 767px dla mniejszych telefon\u00f3w
         padding: $spacing-sm $spacing-md;
         font-size: 1rem;
    }
}


// --- Reguły dla rozmieszczenia pól na siatce (automatycznie generowane przez pętlę @for) ---
// NIE EDYTUJ TEGO BLOKU RĘCZNIE - JEST ON GENEROWANY PRZEZ SCSS
@for $i from 1 through 44 { // Pętla dla każdego pola (zakładając maksymalnie 44 pola)
  .tile:nth-child(#{$i}) { // Wybiera n-ty element z klasą .tile

    @if $i >= 2 and $i <= 12 { // Pola na dolnej krawędzi (bez narożników, licząc od lewej)
      $col: 12 - ($i - 2); // Oblicza kolumnę siatki (malejąco od 11 do 1)
      grid-area: 12 / #{$col}; // Przypisuje obszar siatki (wiersz 12, obliczona kolumna)

      // Obrót nazw i ID dla lepszej czytelności na krawędzi
      .tile-name,
      .tile-id {
        transform: rotate(-45deg);
        transform-origin: center;
      }
    } @else if $i >= 13 and $i <= 23 { // Pola na lewej krawędzi (bez narożników, licząc od dołu)
      $row: 12 - ($i - 13); // Oblicza wiersz siatki (malejąco od 11 do 1)
      grid-area: #{$row} / 1; // Przypisuje obszar siatki (obliczony wiersz, kolumna 1)
        // Brak obrotu - tekst w pionie jest czytelny

    } @else if $i >= 25 and $i <= 34 { // Pola na górnej krawędzi (bez narożników, licząc od prawej)
      $col: 1 + ($i - 24); // Oblicza kolumnę siatki (rosnąco od 2 do 11)
      grid-area: 1 / #{$col}; // Przypisuje obszar siatki (wiersz 1, obliczona kolumna)

      // Obrót nazw i ID dla lepszej czytelności na krawędzi
      .tile-name,
      .tile-id {
        transform: rotate(45deg);
        transform-origin: center;
      }
    } @else if $i >= 35 and $i <= 44 { // Pola na prawej krawędzi (bez narożników, licząc od góry)
      $row: 1 + ($i - 35); // Oblicza wiersz siatki (rosnąco od 2 do 11)
      grid-area: #{$row} / 12; // Przypisuje obszar siatki (obliczony wiersz, kolumna 12)
        // Brak obrotu - tekst w pionie jest czytelny
    }
    // Uwaga: Pola narożne (ID 0, 10, 20, 30 w układzie 0-39) maj\u0105 swoje grid-area
    // zdefiniowane w PHP lub przez domyślne zachowanie siatki, nie w tej pętli @for.
    // Sprawdź, czy Twoje narożniki mają poprawne grid-area (np. grid-area: 12 / 12; dla pola 0)
  }
}