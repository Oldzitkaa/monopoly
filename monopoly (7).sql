-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Maj 20, 2025 at 01:02 PM
-- Wersja serwera: 10.4.32-MariaDB
-- Wersja PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `monopoly`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `action_cards`
--

CREATE TABLE `action_cards` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `effect_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`effect_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `action_cards`
--

INSERT INTO `action_cards` (`id`, `name`, `description`, `effect_json`) VALUES
(1, 'Zgubiłeś paragon!', 'Cofnij się o 3 pola i wyjaśnij sprawę w urzędzie.', '{\"type\": \"move\", \"direction\": \"back\", \"value\": 3}'),
(2, 'Sekretna przyprawa od babci!', '+1 do zmysłu do przypraw.', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"spice_sense\", \"value\": 1}'),
(3, 'Zostałeś jurorem w festiwalu curry', 'Wyczuwasz wszystko! +2 do zmysłu do przypraw.', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"spice_sense\", \"value\": 2}'),
(4, 'Nowy nóż szefa kuchni', 'Siekasz dwa razy szybciej! Zmniejsza Czas Przygotowania o 1.', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"prep_time\", \"value\": -1}'),
(5, 'Robot kuchenny', 'Dania lecą jak z taśmy! Zmniejsza Czas Przygotowania o 2.', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"prep_time\", \"value\": -2}'),
(6, 'Wspomnienia z dzieciństwa', 'Przypomniałeś sobie smak oryginału! +1 do przywiązania do tradycji.', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"tradition_affinity\", \"value\": 1}'),
(7, 'Rękopis przodków', 'Z dawną recepturą! +2 do przywiązania do tradycji.', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"tradition_affinity\", \"value\": 2}'),
(8, 'Nowa certyfikacja kucharska!', '+1 do umiejętności gotowania.', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"cook_skill\", \"value\": 1}'),
(9, 'Książka z przepisami Babci!', '+2 do umiejętności gotowania.', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"cook_skill\", \"value\": 2}'),
(10, 'Trening z papryczką chili!', '+1 do tolerancji ostrości.', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"tolerance\", \"value\": 1}'),
(11, 'Ekstremalne wyzwanie kapsaicynowe', 'Dałeś radę! +2 do tolerancji ostrości.', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"tolerance\", \"value\": 2}'),
(12, 'Weekendowe żarcie z babcią', 'Jesteś napakowany! +1 do pojemności brzucha.', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"belly_capacity\", \"value\": 1}'),
(13, 'Rekord w konkursie jedzenia pierogów!', '+2 do pojemności brzucha.', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"belly_capacity\", \"value\": 2}'),
(14, 'Kurs online: Jak prowadzić restaurację i nie zbankrutować', '+1 do łba do biznesu.', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"business_acumen\", \"value\": 1}'),
(15, 'Networking z top szefami', 'Masz nowe kontakty! +2 do łba do biznesu.', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"business_acumen\", \"value\": 2}'),
(16, 'Bogaty klient zostawił napiwek!', 'Otrzymujesz 150 monet.', '{\"type\": \"change_coins\", \"target\": \"self\", \"value\": 150}'),
(17, 'Kontrola Sanepidu', 'Twoja kuchnia nie przeszła inspekcji. Płać 200 monet!', '{\"type\": \"change_coins\", \"target\": \"self\", \"value\": -200}'),
(18, 'Wyjazd na szkolenie kulinarnie do Tokio', 'Stoisz 2 kolejki.', '{\"type\": \"change_turns_to_miss\", \"target\": \"self\", \"value\": 2}'),
(19, 'Gotowanie w telewizji śniadaniowej!', 'Dostajesz 100 monet.', '{\"type\": \"change_coins\", \"target\": \"self\", \"value\": 100}'),
(20, 'Sabotaż kuchenny!', 'Ktoś działał na twoją niekorzyść – tracisz 1 od łba biznesu.', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"business_acumen\", \"value\": -1}');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `characters`
--

CREATE TABLE `characters` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `region_affinity` varchar(50) DEFAULT NULL,
  `base_cook_skill` int(11) DEFAULT NULL,
  `base_tolerance` int(11) DEFAULT NULL,
  `base_business_acumen` int(11) DEFAULT NULL,
  `base_belly_capacity` int(11) DEFAULT NULL,
  `base_spice_sense` int(11) DEFAULT NULL,
  `base_prep_time` int(11) DEFAULT NULL,
  `base_tradition_affinity` int(11) DEFAULT NULL,
  `special_ability_description` text DEFAULT NULL,
  `plik` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `characters`
--

INSERT INTO `characters` (`id`, `name`, `description`, `region_affinity`, `base_cook_skill`, `base_tolerance`, `base_business_acumen`, `base_belly_capacity`, `base_spice_sense`, `base_prep_time`, `base_tradition_affinity`, `special_ability_description`, `plik`) VALUES
(1, 'Wiesław Parówa', 'Postawny, z wąsem jak u ministra, zawsze proponuje dokładkę. Wierzy, że \"bez mięsa nie ma obiadu\", a jeśli nie ma kiełbasy, to nie ma zabawy. „Kiełbasa to podstawa każdej uczty!” – mawia, trzymając piwo w jednej ręce, a parówkę w drugiej. Przebojowy, głośny i zawsze gotowy do rzucenia żartu lub kawałka mięsa na ruszt. Na grillu zawsze \"wyczaruje\" coś, co pachnie jak dom – i nie ma tam miejsca na wegetariańskie eksperymenty.', 'Europa', 2, 3, 7, 8, 3, 3, 9, NULL, 'polak-removebg-preview.png'),
(2, 'Nonna Antonella', 'Mądra i cierpliwa, jej makaron smakuje jak wspomnienia z dzieciństwa. Gdy mówi \"spaghetti\", to znaczy, że w kuchni dzieje się magia. „Gotuję z sercem, a serce zawsze ma miejsce dla oliwy” – mówi, nalewając ją do garnka, jakby była złotem. Wzdycha nad każdym daniem, jakby przygotowywała je dla całej rodziny – i każdą potrawą udowadnia, że gotowanie to więcej niż tylko jedzenie – to sposób na życie.', 'Europa', 9, 1, 5, 3, 7, 1, 9, NULL, 'babuszka-removebg-preview-removebg-preview.png'),
(3, 'Mistrz Wei', 'Z wąsem, jak wstążka, i rękami, które kroją szybciej niż błyskawica. Jego pho jest tak czyste i delikatne, że aż łzy wzruszenia same przychodzą. \"Zupa ma być jak życie – przezroczysta, z nutą smaku\" – mówi z powagą, a każdy, kto spróbuje, nie chce już innej. Mistrz w równowadze: przyprawy w odpowiednich proporcjach, a tempo gotowania... błyskawiczne.', 'Azja', 2, 8, 5, 2, 3, 9, 6, NULL, 'wietnam-removebg-preview.png'),
(4, 'Tańcząca Sharma', 'Zawsze z uśmiechem i w tańcu, nawet przy garnkach. Jej dahl jest bardziej aromatyczny niż każda modlitwa, a przyprawy tańczą w potrawach jak bollywoodzki taniec. \"Każdy kęs to historia\" – mówi, a jej curry jest tak gęste, że można je jeść łyżką. Zawsze ciepła, zawsze gościnna, nie zapomina o tym, że w kuchni chodzi o miłość i harmonię smaków.', 'Azja', 6, 7, 1, 6, 9, 4, 2, NULL, 'indie-removebg-preview-removebg-preview.png'),
(5, 'DJ Cassava', 'Na co dzień miksuje bity, w weekendy – maniok. Łączy stare przepisy babci z uliczną energią – street food z rytmem afrobeat. Je jego dania, zanim staną się viralem.', 'Afryka', 4, 7, 3, 7, 4, 4, 6, NULL, 'niger-removebg-preview.png'),
(6, 'Ciocia MBassa', 'Nosi kolorowe chusty i zawsze pachnie imbirem. Jej zupa z fufu leczy wszystko – od złamanego serca po kaca. Gotuje intuicją i nie uznaje przepisu.', 'Afryka', 8, 5, 3, 5, 7, 3, 4, NULL, 'gabon-removebg-preview.png'),
(7, 'Coral Mae', 'Zawsze z muszlą we włosach i solą na skórze. Jej kuchnia to poezja rafy koralowej — grillowane małże, pieczone banany i sos z limonki, który „budzi więcej niż kawa”. Czasem milczy, ale gdy mówi, brzmi jak ocean przed burzą. Mówi, że gotowanie to rozmowa z morzem.', 'Australia/Oceania', 3, 3, 5, 1, 7, 7, 9, NULL, 'fidzi-removebg-preview-removebg-preview.png'),
(8, 'Mako Zed', 'Były surfer, obecnie samozwańczy filozof ogniska. Smaży ryby zawinięte w liście i dorzuca do nich sentencje o życiu. Zawsze w klapkach, nawet na weselach. Uważa, że każda potrawa powinna „pachnieć wiatrem i smakować wolnością”.', 'Australia/Oceania', 2, 7, 8, 3, 7, 6, 2, NULL, 'nowa-zelandia-removebg-preview.png'),
(9, 'Lupita Fuego', 'Z warkoczem jak lasso i papryczką habanero za uchem. Jej taco to dzieło sztuki – kolorowe, chrupiące, z ukrytą mocą. Twierdzi, że nachos to tylko pretekst do rozlania sera i dramatu. Gdy gotuje, słychać mariachich. Gdy się śmieje – ogień w kuchni.', 'Ameryka Północna', 7, 10, 3, 1, 4, 2, 8, NULL, 'meksyk-removebg-preview-removebg-preview.png'),
(10, 'Big Jimmy Smoke', 'Rozmiar XXL, serce jeszcze większe. Mistrz grilla i dymu – jego brisket rozkłada ludzi emocjonalnie. Zawsze ma fartuch z keczupem i kufel coli z lodem. Mawia: „Nie ufam ludziom, co nie jedzą tłusto.” Mistrz BBQ i gadki – na weselu i na pogrzebie będzie grillował.', 'Ameryka Północna', 1, 6, 9, 10, 1, 7, 1, NULL, 'usa-removebg-preview.png'),
(11, 'Ceviche Rosa', 'Woda z oceanu płynie w jej żyłach, a limonka to jej drugie imię. Mistrzyni ceviche, której ryby są zawsze świeże, a papryczki nigdy nie brakuje. Zawsze ma przy sobie cytrusy, jakby miała je w genach. \"Kto nie potrafi zrobić dobrego ceviche, ten nie potrafi żyć!\" – mówi z uśmiechem, a po spróbowaniu jej dania zapominasz, czym w ogóle jest zmarnowany dzień. Każdy kęs to podróż do Limy – ciepło, słońce, i trochę ostrości w życiu.', 'Ameryka Południowa', 7, 7, 3, 2, 7, 2, 7, NULL, 'peru-removebg-preview.png'),
(12, 'Don Chimichurri', 'Ciało jak ruszt, serce jak argentyńska pampas. Jego grill to prawdziwa świątynia, a każdy kawałek mięsa przechodzi tam rytuał, który rozgrzewa serca i podniebienia. \"Asado to nie tylko jedzenie, to religia!\" – mówi, wkładając mięso na ruszt, a potem dorzucając kolejny kawałek, bo w końcu kto liczy? Zawsze z kieliszkiem wina, bo \"nic nie smakuje lepiej niż stek i zioła\". W jego świecie grilla nie ma miejsca na szybkie dania, tu czas ma smak.', 'Ameryka Południowa', 6, 7, 1, 8, 7, 2, 4, NULL, 'argentyna-removebg-preview.png');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `duel_cards`
--

CREATE TABLE `duel_cards` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `related_stat` varchar(50) DEFAULT NULL,
  `effect_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`effect_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `duel_cards`
--

INSERT INTO `duel_cards` (`id`, `name`, `description`, `related_stat`, `effect_json`) VALUES
(1, 'Ostry Pojedynek 1', 'To wyzwanie \"ślepej próby\". Przygotowaliście swoje najostrzejsze sosy, ale nie wiecie, który jest który. Podajecie je do identycznych tacos i próbujecie zidentyfikować swój sos, oceniając jego moc. Wygrywa ten, kto poprawnie rozpozna swój sos i najlepiej oceni jego \"ekstremalność\" w porównaniu z sosem rywala - ten z wyższą \"tolerancją ostrości\".', 'tolerance', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"tolerance\", \"value\": 2}'),
(2, 'Ostry Pojedynek 2', 'Zostaliście zaproszeni na legendarny festiwal ostrej kuchni. Stajecie naprzeciwko siebie z talerzem \"Nagłych Śmierci Jalapeño\". Wygrywa ten, kto zje je bez popijania - ten z wyższą \"tolerancją ostrości\".', 'tolerance', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"tolerance\", \"value\": 2}'),
(3, 'Ostry Pojedynek 3', 'Lokalne radio organizuje konkurs \"Ogniste Usta\". Zadaniem jest jak najdłuższe trzymanie w ustach papryczki habanero. Wygrywa ten, kto wytrzyma najdłużej, nie wypluwając i nie krzycząc - ten z wyższą \"tolerancją ostrości\".', 'tolerance', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"tolerance\", \"value\": 2}'),
(4, 'Ostry Pojedynek 4', 'Wyzwaliście się na pojedynek jedzenia \"Skrzydełek Armagedonu\" polanych najostrzejszymi dostępnymi sosami na świecie. Wygrywa ten, kto zje więcej skrzydełek do końca czasu, nie poddając się płaczowi i drgawkom - ten z wyższą \"tolerancją ostrości\".', 'tolerance', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"tolerance\", \"value\": 2}'),
(5, 'Ostry Pojedynek 5', 'Stajecie przed talerzem pełnym marynowanych papryczek ghost pepper. Zadaniem jest zjedzenie ich jak najwięcej w ciągu 5 minut. Wygrywa ten, kto opróżni talerz - ten z wyższą \"tolerancją ostrości\".', 'tolerance', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"tolerance\", \"value\": 2}'),
(6, 'Biznesowy Pojedynek 1', 'Otwieracie food trucki z różnymi kuchniami na tym samym popularnym placu. Po miesiącu sprawdzamy, który z was zarobił więcej i ma lepsze recenzje online - ten z wyższym \"łeb do biznesu\".', 'business_acumen', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"business_acumen\", \"value\": 2}'),
(7, 'Biznesowy Pojedynek 2', 'Burmistrz oferuje do wynajęcia dwa identyczne lokale w atrakcyjnej lokalizacji. Wygrywa ten, kto przedstawi bardziej przekonujący biznesplan z prognozami zysków i strategią marketingową - ten z wyższym \"łeb do biznesu\".', 'business_acumen', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"business_acumen\", \"value\": 2}'),
(8, 'Biznesowy Pojedynek 3', 'Wasze restauracje biorą udział w lokalnym festiwalu kulinarnym. Wygrywa ten, którego stoisko przyciągnie więcej klientów i sprzeda więcej porcji - ten z lepszym \"łebem do biznesu\".', 'business_acumen', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"business_acumen\", \"value\": 2}'),
(9, 'Biznesowy Pojedynek 4', 'Inwestor oferuje wsparcie finansowe tylko jednej z waszych restauracji. Decyzję podejmie na podstawie analizy waszych finansów, strategii rozwoju i umiejętności zarządzania zespołem - ten z lepszym \"łebem do biznesu\".', 'business_acumen', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"business_acumen\", \"value\": 2}'),
(10, 'Biznesowy Pojedynek 5', 'Na rynku pojawia się nowa, silna konkurencja. Wygrywa ten, kto szybciej i skuteczniej dostosuje swoją ofertę i strategię, utrzymując lub zwiększając swoją bazę klientów - ten z wyższym \"łebem do biznesu\".', 'business_acumen', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"business_acumen\", \"value\": 2}'),
(11, 'Obżartuch Pojedynek 1', 'Stajecie do legendarnego konkursu \"Królewski Obżartuch\" w jedzeniu hamburgerów. Wygrywa ten, kto w ciągu 10 minut zdoła pochłonąć ich najwięcej - ten z większą \"brzucha pojemnością\".', 'belly_capacity', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"belly_capacity\", \"value\": 2}'),
(12, 'Obżartuch Pojedynek 2', 'Lokalne delikatesy organizują zawody w jedzeniu pączków na czas. Wygrywa ten, kto w 3 minuty zje ich najwięcej, nie krztusząc się i nie wymiotując - ten z większą \"brzucha pojemnością\".', 'belly_capacity', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"belly_capacity\", \"value\": 2}'),
(13, 'Obżartuch Pojedynek 3', 'Wyzwaliście się na prywatny pojedynek w jedzeniu pizzy na kawałki. Wygrywa ten, kto zje najwięcej standardowych kawałków pizzy w pół godziny - ten z większą \"brzucha pojemnością\".', 'belly_capacity', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"belly_capacity\", \"value\": 2}'),
(14, 'Obżartuch Pojedynek 4', 'Na stole ląduje gigantyczny talerz spaghetti z klopsikami. Wygrywa ten, kto pierwszy zje wszystko (lub zje najwięcej w wyznaczonym czasie) - ten z większą \"brzucha pojemnością\".', 'belly_capacity', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"belly_capacity\", \"value\": 2}'),
(15, 'Obżartuch Pojedynek 5', 'To noc jedzenia bez ograniczeń w lokalnej restauracji \"All You Can Eat\". Po dwóch godzinach sprawdzamy, kto zamówił i zjadł najwięcej dań - ten z większą \"brzucha pojemnością\".', 'belly_capacity', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"belly_capacity\", \"value\": 2}'),
(16, 'Gotowanie Pojedynek 1', 'Zostajecie zaproszeni do elitarnego konkursu kulinarnego. W pierwszej rundzie musicie przygotować wyrafinowane danie z czarnej skrzynki pełnej nieznanych składników. Jury ocenia smak, technikę i kreatywność - ten z wyższymi \"umiejętnościami gotowania\".', 'cook_skill', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"cook_skill\", \"value\": 2}'),
(17, 'Gotowanie Pojedynek 2', 'Tematem tygodnia w waszych restauracjach jest klasyczne danie kuchni francuskiej - Boeuf Bourguignon. Klienci w tajnym głosowaniu wybierają, które danie smakuje bardziej autentycznie i wykwintnie - ten z lepszymi \"umiejętnościami gotowania\".', 'cook_skill', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"cook_skill\", \"value\": 2}'),
(18, 'Gotowanie Pojedynek 3', 'Musicie przygotować trzydaniowy posiłek dla wybrednego krytyka kulinarnego. Ocenia on smak, prezentację i harmonię całego menu - ten z wyższymi \"umiejętnościami gotowania\".', 'cook_skill', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"cook_skill\", \"value\": 2}'),
(19, 'Gotowanie Pojedynek 4', 'Zostajecie poproszeni o ugotowanie popisowego dania z wykorzystaniem tylko lokalnych, sezonowych składników. Jury ocenia waszą umiejętność wydobycia najlepszych smaków z prostych produktów - ten z wyższymi \"umiejętnościami gotowania\".', 'cook_skill', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"cook_skill\", \"value\": 2}'),
(20, 'Gotowanie Pojedynek 5', 'Waszym zadaniem jest odtworzenie skomplikowanego, wieloskładnikowego dania z legendarnej, starej książki kucharskiej. Jury ocenia wierność recepturze, technikę wykonania i ostateczny smak - ten z wyższymi \"umiejętnościami gotowania\".', 'cook_skill', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"cook_skill\", \"value\": 2}'),
(21, 'Przyprawowy Pojedynek 1', 'Otrzymujecie identyczny, prosty bulion i zestaw kilkunastu różnych przypraw. Zadaniem jest doprawienie go tak, aby stworzyć unikalny i wyważony smak. Jury ocenia harmonię i kreatywność waszych mieszanek - ten z lepszym \"zmysłem do przypraw\" wygrywa.', 'spice_sense', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"spice_sense\", \"value\": 2}'),
(22, 'Przyprawowy Pojedynek 2', 'Stajecie do \"ślepej próby przypraw\". Prowadzący podaje wam zmielone przyprawy, a wy musicie je zidentyfikować po zapachu i smaku. Wygrywa ten, kto rozpozna więcej przypraw i opisze ich potencjalne zastosowania - ten z lepszym \"zmysłem do przypraw\".', 'spice_stat', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"spice_sense\", \"value\": 2}'),
(23, 'Przyprawowy Pojedynek 3', 'Waszym zadaniem jest stworzenie unikalnej mieszanki przypraw do klasycznego dania. Jury ocenia aromat przed gotowaniem i smak gotowej potrawy, zwracając uwagę na to, jak dobrze przyprawy się komponują - ten z lepszym \"zmysłem do przypraw\" wygrywa.', 'spice_sense', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"spice_sense\", \"value\": 2}'),
(24, 'Przyprawowy Pojedynek 4', 'Otrzymujecie danie, które jest \"płaskie\" w smaku. Waszym zadaniem jest uratowanie go poprzez dodanie odpowiednich przypraw, tak aby wydobyć jego potencjał. Jury ocenia, które danie po doprawieniu smakuje lepiej i bardziej interesująco - ten z lepszym \"zmysłem do przypraw\".', 'spice_sense', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"spice_sense\", \"value\": 2}'),
(25, 'Przyprawowy Pojedynek 5', 'Wyzwaliście się na \"pojedynek smaków świata\". Każdy z was przygotowuje danie charakterystyczne dla wybranego regionu, gdzie kluczową rolę odgrywają przyprawy. Jury ocenia autentyczność smaku i umiejętność wykorzystania lokalnych przypraw - ten z lepszym \"zmysłem do przypraw\" wygrywa.', 'spice_sense', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"spice_sense\", \"value\": 2}'),
(26, 'Szybki Pojedynek 1', 'Ogłoszono \"Ekspresowy Wyzwanie\". Musicie przygotować prosty, ale smaczny posiłek w jak najkrótszym czasie. Jury ocenia zarówno czas, jak i smak końcowego dania - ten z lepszym \"czasem przygotowania\" wygrywa.', 'prep_time', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"prep_time\", \"value\": -2}'),
(27, 'Szybki Pojedynek 2', 'Stajecie do \"Minutowego Mistrza\". Prowadzący ogłasza danie, które musicie przygotować w ciągu jednej minuty. Liczy się każdy składnik i technika. Jury ocenia, kto w tak krótkim czasie stworzy najbardziej zjadliwe i przypominające oryginał danie - ten z lepszym \"czasem przygotowania\".', 'prep_time', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"prep_time\", \"value\": -2}'),
(28, 'Szybki Pojedynek 3', 'To \"sztafeta kuchenna\". Każdy z was ma ograniczony czas na wykonanie określonego etapu skomplikowanego dania. Wygrywa ten z was, który jako pierwszy ukończy smaczne i dobrze przygotowane danie - ten z lepszym \"czasem przygotowania\" (w kontekście sekwencji zadań).', 'prep_time', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"prep_time\", \"value\": -2}'),
(29, 'Szybki Pojedynek 4', 'Waszym zadaniem jest przygotowanie identycznego dania, ale z ograniczeniem czasowym. Jury mierzy czas przygotowania i ocenia, czy pośpiech nie wpłynął negatywnie na smak i jakość potrawy. Wygrywa ten, kto będzie szybszy, nie tracąc na jakości - ten z lepszym \"czasem przygotowania\".', 'prep_time', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"prep_time\", \"value\": -2}'),
(30, 'Szybki Pojedynek 5', 'Organizujecie \"Fast Food Battle\". Każdy z was musi przygotować swoją wersję popularnego dania typu fast food w jak najkrótszym czasie, starając się zachować smak i estetykę. Jury ocenia szybkość i ostateczny efekt - ten z lepszym \"czasem przygotowania\".', 'prep_time', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"prep_time\", \"value\": -2}'),
(31, 'Tradycyjny Pojedynek 1', 'Waszym zadaniem jest przygotowanie klasycznego dania regionalnego z waszego \"pochodzenia\" zgodnie z najbardziej autentyczną, przekazywaną z pokolenia na pokolenie recepturą. Jury ocenia wierność tradycji i smak - ten z większym \"przywiązaniem do tradycji\" wygrywa.', 'tradition_affinity', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"tradition_affinity\", \"value\": 2}'),
(32, 'Tradycyjny Pojedynek 2', 'Stajecie do \"Bitwy o Przepis Babci\". Musicie odtworzyć danie na podstawie starego, często niekompletnego przepisu babci. Jury ocenia, jak blisko udało wam się odtworzyć smak i charakter dania z przeszłości - ten z większym \"przywiązaniem do tradycji\" wygrywa.', 'tradition_affinity', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"tradition_affinity\", \"value\": 2}'),
(33, 'Tradycyjny Pojedynek 3', 'Waszym zadaniem jest przygotowanie dania z wykorzystaniem tradycyjnych technik kulinarnych i lokalnych składników, charakterystycznych dla danego regionu. Jury ocenia, czy wasze podejście jest zgodne z duchem tradycji - ten z większym \"przywiązaniem do tradycji\" wygrywa.', 'tradition_affinity', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"tradition_affinity\", \"value\": 2}'),
(34, 'Tradycyjny Pojedynek 4', 'Otrzymujecie zadanie \"nowe w starym stylu\". Musicie zaadaptować nowoczesny przepis, wykorzystując tradycyjne metody gotowania i składniki. Jury ocenia, jak dobrze udało wam się połączyć nowoczesność z tradycją - ten z większym \"przywiązaniem do tradycji\" wygrywa.', 'tradition_affinity', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"tradition_affinity\", \"value\": 2}'),
(35, 'Tradycyjny Pojedynek 5', 'Wasze restauracje organizują \"Tydzień Kuchni Tradycyjnej\". Klienci głosują, które z waszych dań najlepiej oddaje smak i ducha tradycyjnej kuchni, a wy opowiadacie o historii i znaczeniu waszych potraw - ten z większym \"przywiązaniem do tradycji\" wygrywa.', 'tradition_affinity', '{\"type\": \"change_stat\", \"target\": \"self\", \"stat\": \"tradition_affinity\", \"value\": 2}');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `games`
--

CREATE TABLE `games` (
  `id` int(11) NOT NULL,
  `current_turn` int(11) DEFAULT 1,
  `status` enum('lobby','active','ended') DEFAULT 'lobby',
  `created_at` datetime DEFAULT current_timestamp(),
  `current_player_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `games`
--

INSERT INTO `games` (`id`, `current_turn`, `status`, `created_at`, `current_player_id`) VALUES
(9, 1, 'ended', '2025-05-20 10:57:19', 1),
(10, 1, 'ended', '2025-05-20 10:59:17', 3),
(11, 1, 'ended', '2025-05-20 11:04:36', 5),
(12, 1, 'ended', '2025-05-20 11:08:57', 7),
(13, 1, 'ended', '2025-05-20 11:09:33', 9),
(14, 1, 'active', '2025-05-20 11:34:10', 11),
(15, 1, 'ended', '2025-05-20 11:52:52', 13),
(16, 1, 'ended', '2025-05-20 11:53:10', 15),
(17, 1, 'ended', '2025-05-20 11:59:17', 17),
(18, 1, 'ended', '2025-05-20 12:00:17', 19),
(19, 1, 'ended', '2025-05-20 12:09:13', 21),
(20, 1, 'active', '2025-05-20 12:11:02', 23),
(21, 1, 'active', '2025-05-20 12:15:45', 25);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `game_tiles`
--

CREATE TABLE `game_tiles` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `tile_id` int(11) NOT NULL,
  `current_owner_id` int(11) DEFAULT NULL,
  `current_level` int(11) DEFAULT 0,
  `is_mortgaged` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `game_tiles`
--

INSERT INTO `game_tiles` (`id`, `game_id`, `tile_id`, `current_owner_id`, `current_level`, `is_mortgaged`) VALUES
(1, 9, 1, NULL, 0, 0),
(2, 9, 3, NULL, 0, 0),
(3, 9, 5, NULL, 0, 0),
(4, 9, 6, NULL, 0, 0),
(5, 9, 8, NULL, 0, 0),
(6, 9, 9, NULL, 0, 0),
(7, 9, 12, NULL, 0, 0),
(8, 9, 14, NULL, 0, 0),
(9, 9, 16, NULL, 0, 0),
(10, 9, 18, NULL, 0, 0),
(11, 9, 20, NULL, 0, 0),
(12, 9, 21, NULL, 0, 0),
(13, 9, 24, NULL, 0, 0),
(14, 9, 25, NULL, 0, 0),
(15, 9, 27, NULL, 0, 0),
(16, 9, 28, NULL, 0, 0),
(17, 9, 30, NULL, 0, 0),
(18, 9, 31, NULL, 0, 0),
(19, 9, 32, NULL, 0, 0),
(20, 9, 36, NULL, 0, 0),
(21, 9, 39, NULL, 0, 0),
(22, 9, 40, NULL, 0, 0),
(23, 9, 42, NULL, 0, 0),
(24, 9, 43, NULL, 0, 0),
(32, 10, 1, NULL, 0, 0),
(33, 10, 3, NULL, 0, 0),
(34, 10, 5, NULL, 0, 0),
(35, 10, 6, NULL, 0, 0),
(36, 10, 8, NULL, 0, 0),
(37, 10, 9, NULL, 0, 0),
(38, 10, 12, NULL, 0, 0),
(39, 10, 14, NULL, 0, 0),
(40, 10, 16, NULL, 0, 0),
(41, 10, 18, NULL, 0, 0),
(42, 10, 20, NULL, 0, 0),
(43, 10, 21, NULL, 0, 0),
(44, 10, 24, NULL, 0, 0),
(45, 10, 25, NULL, 0, 0),
(46, 10, 27, NULL, 0, 0),
(47, 10, 28, NULL, 0, 0),
(48, 10, 30, NULL, 0, 0),
(49, 10, 31, NULL, 0, 0),
(50, 10, 32, NULL, 0, 0),
(51, 10, 36, NULL, 0, 0),
(52, 10, 39, NULL, 0, 0),
(53, 10, 40, NULL, 0, 0),
(54, 10, 42, NULL, 0, 0),
(55, 10, 43, NULL, 0, 0),
(63, 11, 1, NULL, 0, 0),
(64, 11, 3, NULL, 0, 0),
(65, 11, 5, NULL, 0, 0),
(66, 11, 6, NULL, 0, 0),
(67, 11, 8, NULL, 0, 0),
(68, 11, 9, NULL, 0, 0),
(69, 11, 12, NULL, 0, 0),
(70, 11, 14, NULL, 0, 0),
(71, 11, 16, NULL, 0, 0),
(72, 11, 18, NULL, 0, 0),
(73, 11, 20, NULL, 0, 0),
(74, 11, 21, NULL, 0, 0),
(75, 11, 24, NULL, 0, 0),
(76, 11, 25, NULL, 0, 0),
(77, 11, 27, NULL, 0, 0),
(78, 11, 28, NULL, 0, 0),
(79, 11, 30, NULL, 0, 0),
(80, 11, 31, NULL, 0, 0),
(81, 11, 32, NULL, 0, 0),
(82, 11, 36, NULL, 0, 0),
(83, 11, 39, NULL, 0, 0),
(84, 11, 40, NULL, 0, 0),
(85, 11, 42, NULL, 0, 0),
(86, 11, 43, NULL, 0, 0),
(94, 12, 1, NULL, 0, 0),
(95, 12, 3, NULL, 0, 0),
(96, 12, 5, NULL, 0, 0),
(97, 12, 6, NULL, 0, 0),
(98, 12, 8, NULL, 0, 0),
(99, 12, 9, NULL, 0, 0),
(100, 12, 12, NULL, 0, 0),
(101, 12, 14, NULL, 0, 0),
(102, 12, 16, NULL, 0, 0),
(103, 12, 18, NULL, 0, 0),
(104, 12, 20, NULL, 0, 0),
(105, 12, 21, NULL, 0, 0),
(106, 12, 24, NULL, 0, 0),
(107, 12, 25, NULL, 0, 0),
(108, 12, 27, NULL, 0, 0),
(109, 12, 28, NULL, 0, 0),
(110, 12, 30, NULL, 0, 0),
(111, 12, 31, NULL, 0, 0),
(112, 12, 32, NULL, 0, 0),
(113, 12, 36, NULL, 0, 0),
(114, 12, 39, NULL, 0, 0),
(115, 12, 40, NULL, 0, 0),
(116, 12, 42, NULL, 0, 0),
(117, 12, 43, NULL, 0, 0),
(125, 13, 1, NULL, 0, 0),
(126, 13, 3, NULL, 0, 0),
(127, 13, 5, NULL, 0, 0),
(128, 13, 6, NULL, 0, 0),
(129, 13, 8, NULL, 0, 0),
(130, 13, 9, NULL, 0, 0),
(131, 13, 12, NULL, 0, 0),
(132, 13, 14, NULL, 0, 0),
(133, 13, 16, NULL, 0, 0),
(134, 13, 18, NULL, 0, 0),
(135, 13, 20, NULL, 0, 0),
(136, 13, 21, NULL, 0, 0),
(137, 13, 24, NULL, 0, 0),
(138, 13, 25, NULL, 0, 0),
(139, 13, 27, NULL, 0, 0),
(140, 13, 28, NULL, 0, 0),
(141, 13, 30, NULL, 0, 0),
(142, 13, 31, NULL, 0, 0),
(143, 13, 32, NULL, 0, 0),
(144, 13, 36, NULL, 0, 0),
(145, 13, 39, NULL, 0, 0),
(146, 13, 40, NULL, 0, 0),
(147, 13, 42, NULL, 0, 0),
(148, 13, 43, NULL, 0, 0),
(156, 14, 1, NULL, 0, 0),
(157, 14, 3, NULL, 0, 0),
(158, 14, 5, NULL, 0, 0),
(159, 14, 6, NULL, 0, 0),
(160, 14, 8, NULL, 0, 0),
(161, 14, 9, NULL, 0, 0),
(162, 14, 12, NULL, 0, 0),
(163, 14, 14, NULL, 0, 0),
(164, 14, 16, NULL, 0, 0),
(165, 14, 18, NULL, 0, 0),
(166, 14, 20, NULL, 0, 0),
(167, 14, 21, NULL, 0, 0),
(168, 14, 24, NULL, 0, 0),
(169, 14, 25, NULL, 0, 0),
(170, 14, 27, NULL, 0, 0),
(171, 14, 28, NULL, 0, 0),
(172, 14, 30, NULL, 0, 0),
(173, 14, 31, NULL, 0, 0),
(174, 14, 32, NULL, 0, 0),
(175, 14, 36, NULL, 0, 0),
(176, 14, 39, NULL, 0, 0),
(177, 14, 40, NULL, 0, 0),
(178, 14, 42, NULL, 0, 0),
(179, 14, 43, NULL, 0, 0),
(187, 15, 1, NULL, 0, 0),
(188, 15, 3, NULL, 0, 0),
(189, 15, 5, NULL, 0, 0),
(190, 15, 6, NULL, 0, 0),
(191, 15, 8, NULL, 0, 0),
(192, 15, 9, NULL, 0, 0),
(193, 15, 12, NULL, 0, 0),
(194, 15, 14, NULL, 0, 0),
(195, 15, 16, NULL, 0, 0),
(196, 15, 18, NULL, 0, 0),
(197, 15, 20, NULL, 0, 0),
(198, 15, 21, NULL, 0, 0),
(199, 15, 24, NULL, 0, 0),
(200, 15, 25, NULL, 0, 0),
(201, 15, 27, NULL, 0, 0),
(202, 15, 28, NULL, 0, 0),
(203, 15, 30, NULL, 0, 0),
(204, 15, 31, NULL, 0, 0),
(205, 15, 32, NULL, 0, 0),
(206, 15, 36, NULL, 0, 0),
(207, 15, 39, NULL, 0, 0),
(208, 15, 40, NULL, 0, 0),
(209, 15, 42, NULL, 0, 0),
(210, 15, 43, NULL, 0, 0),
(218, 16, 1, NULL, 0, 0),
(219, 16, 3, NULL, 0, 0),
(220, 16, 5, NULL, 0, 0),
(221, 16, 6, NULL, 0, 0),
(222, 16, 8, NULL, 0, 0),
(223, 16, 9, NULL, 0, 0),
(224, 16, 12, NULL, 0, 0),
(225, 16, 14, NULL, 0, 0),
(226, 16, 16, NULL, 0, 0),
(227, 16, 18, NULL, 0, 0),
(228, 16, 20, NULL, 0, 0),
(229, 16, 21, NULL, 0, 0),
(230, 16, 24, NULL, 0, 0),
(231, 16, 25, NULL, 0, 0),
(232, 16, 27, NULL, 0, 0),
(233, 16, 28, NULL, 0, 0),
(234, 16, 30, NULL, 0, 0),
(235, 16, 31, NULL, 0, 0),
(236, 16, 32, NULL, 0, 0),
(237, 16, 36, NULL, 0, 0),
(238, 16, 39, NULL, 0, 0),
(239, 16, 40, NULL, 0, 0),
(240, 16, 42, NULL, 0, 0),
(241, 16, 43, NULL, 0, 0),
(249, 17, 1, NULL, 0, 0),
(250, 17, 3, NULL, 0, 0),
(251, 17, 5, NULL, 0, 0),
(252, 17, 6, NULL, 0, 0),
(253, 17, 8, NULL, 0, 0),
(254, 17, 9, NULL, 0, 0),
(255, 17, 12, NULL, 0, 0),
(256, 17, 14, NULL, 0, 0),
(257, 17, 16, NULL, 0, 0),
(258, 17, 18, NULL, 0, 0),
(259, 17, 20, NULL, 0, 0),
(260, 17, 21, NULL, 0, 0),
(261, 17, 24, NULL, 0, 0),
(262, 17, 25, NULL, 0, 0),
(263, 17, 27, NULL, 0, 0),
(264, 17, 28, NULL, 0, 0),
(265, 17, 30, NULL, 0, 0),
(266, 17, 31, NULL, 0, 0),
(267, 17, 32, NULL, 0, 0),
(268, 17, 36, NULL, 0, 0),
(269, 17, 39, NULL, 0, 0),
(270, 17, 40, NULL, 0, 0),
(271, 17, 42, NULL, 0, 0),
(272, 17, 43, NULL, 0, 0),
(280, 18, 1, NULL, 0, 0),
(281, 18, 3, NULL, 0, 0),
(282, 18, 5, NULL, 0, 0),
(283, 18, 6, NULL, 0, 0),
(284, 18, 8, NULL, 0, 0),
(285, 18, 9, NULL, 0, 0),
(286, 18, 12, NULL, 0, 0),
(287, 18, 14, NULL, 0, 0),
(288, 18, 16, NULL, 0, 0),
(289, 18, 18, NULL, 0, 0),
(290, 18, 20, NULL, 0, 0),
(291, 18, 21, NULL, 0, 0),
(292, 18, 24, NULL, 0, 0),
(293, 18, 25, NULL, 0, 0),
(294, 18, 27, NULL, 0, 0),
(295, 18, 28, NULL, 0, 0),
(296, 18, 30, NULL, 0, 0),
(297, 18, 31, NULL, 0, 0),
(298, 18, 32, NULL, 0, 0),
(299, 18, 36, NULL, 0, 0),
(300, 18, 39, NULL, 0, 0),
(301, 18, 40, NULL, 0, 0),
(302, 18, 42, NULL, 0, 0),
(303, 18, 43, NULL, 0, 0),
(311, 19, 1, NULL, 0, 0),
(312, 19, 3, NULL, 0, 0),
(313, 19, 5, NULL, 0, 0),
(314, 19, 6, NULL, 0, 0),
(315, 19, 8, NULL, 0, 0),
(316, 19, 9, NULL, 0, 0),
(317, 19, 12, NULL, 0, 0),
(318, 19, 14, NULL, 0, 0),
(319, 19, 16, NULL, 0, 0),
(320, 19, 18, NULL, 0, 0),
(321, 19, 20, NULL, 0, 0),
(322, 19, 21, NULL, 0, 0),
(323, 19, 24, NULL, 0, 0),
(324, 19, 25, NULL, 0, 0),
(325, 19, 27, NULL, 0, 0),
(326, 19, 28, NULL, 0, 0),
(327, 19, 30, NULL, 0, 0),
(328, 19, 31, NULL, 0, 0),
(329, 19, 32, NULL, 0, 0),
(330, 19, 36, NULL, 0, 0),
(331, 19, 39, NULL, 0, 0),
(332, 19, 40, NULL, 0, 0),
(333, 19, 42, NULL, 0, 0),
(334, 19, 43, NULL, 0, 0),
(342, 20, 1, NULL, 0, 0),
(343, 20, 3, NULL, 0, 0),
(344, 20, 5, NULL, 0, 0),
(345, 20, 6, NULL, 0, 0),
(346, 20, 8, NULL, 0, 0),
(347, 20, 9, NULL, 0, 0),
(348, 20, 12, NULL, 0, 0),
(349, 20, 14, NULL, 0, 0),
(350, 20, 16, NULL, 0, 0),
(351, 20, 18, NULL, 0, 0),
(352, 20, 20, NULL, 0, 0),
(353, 20, 21, NULL, 0, 0),
(354, 20, 24, NULL, 0, 0),
(355, 20, 25, NULL, 0, 0),
(356, 20, 27, NULL, 0, 0),
(357, 20, 28, NULL, 0, 0),
(358, 20, 30, NULL, 0, 0),
(359, 20, 31, NULL, 0, 0),
(360, 20, 32, NULL, 0, 0),
(361, 20, 36, NULL, 0, 0),
(362, 20, 39, NULL, 0, 0),
(363, 20, 40, NULL, 0, 0),
(364, 20, 42, NULL, 0, 0),
(365, 20, 43, NULL, 0, 0),
(373, 21, 1, NULL, 0, 0),
(374, 21, 3, NULL, 0, 0),
(375, 21, 5, NULL, 0, 0),
(376, 21, 6, NULL, 0, 0),
(377, 21, 8, NULL, 0, 0),
(378, 21, 9, NULL, 0, 0),
(379, 21, 12, NULL, 0, 0),
(380, 21, 14, NULL, 0, 0),
(381, 21, 16, NULL, 0, 0),
(382, 21, 18, NULL, 0, 0),
(383, 21, 20, NULL, 0, 0),
(384, 21, 21, NULL, 0, 0),
(385, 21, 24, NULL, 0, 0),
(386, 21, 25, NULL, 0, 0),
(387, 21, 27, NULL, 0, 0),
(388, 21, 28, NULL, 0, 0),
(389, 21, 30, NULL, 0, 0),
(390, 21, 31, NULL, 0, 0),
(391, 21, 32, NULL, 0, 0),
(392, 21, 36, NULL, 0, 0),
(393, 21, 39, NULL, 0, 0),
(394, 21, 40, NULL, 0, 0),
(395, 21, 42, NULL, 0, 0),
(396, 21, 43, NULL, 0, 0);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `players`
--

CREATE TABLE `players` (
  `id` int(11) NOT NULL,
  `game_id` int(11) DEFAULT NULL,
  `character_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `coins` int(11) DEFAULT 0,
  `popularity` int(11) DEFAULT 0,
  `location` int(11) DEFAULT 0,
  `cook_skill` int(11) DEFAULT NULL,
  `tolerance` int(11) DEFAULT NULL,
  `business_acumen` int(11) DEFAULT NULL,
  `belly_capacity` int(11) DEFAULT NULL,
  `spice_sense` int(11) DEFAULT NULL,
  `prep_time` int(11) DEFAULT NULL,
  `tradition_affinity` int(11) DEFAULT NULL,
  `turn_order` int(11) DEFAULT NULL,
  `turns_to_miss` int(11) DEFAULT 0,
  `color` varchar(7) DEFAULT '#000000',
  `is_current_turn` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `players`
--

INSERT INTO `players` (`id`, `game_id`, `character_id`, `name`, `coins`, `popularity`, `location`, `cook_skill`, `tolerance`, `business_acumen`, `belly_capacity`, `spice_sense`, `prep_time`, `tradition_affinity`, `turn_order`, `turns_to_miss`, `color`, `is_current_turn`) VALUES
(1, 9, 3, 'ssa', 1500, 0, 0, 2, 8, 5, 2, 3, 9, 6, 1, 0, '#000000', 1),
(2, 9, 4, 'ddd', 1500, 0, 0, 6, 7, 1, 6, 9, 4, 2, 2, 0, '#000000', 0),
(3, 10, 3, 'dsa', 1500, 0, 0, 2, 8, 5, 2, 3, 9, 6, 1, 0, '#000000', 1),
(4, 10, 4, 'aa', 1500, 0, 0, 6, 7, 1, 6, 9, 4, 2, 2, 0, '#000000', 0),
(5, 11, 3, 'sss', 1500, 0, 0, 2, 8, 5, 2, 3, 9, 6, 1, 0, '#000000', 1),
(6, 11, 4, 'ddd', 1500, 0, 0, 6, 7, 1, 6, 9, 4, 2, 2, 0, '#000000', 0),
(7, 12, 3, 'aaa', 1500, 0, 0, 2, 8, 5, 2, 3, 9, 6, 1, 0, '#000000', 1),
(8, 12, 4, 'ddd', 1500, 0, 0, 6, 7, 1, 6, 9, 4, 2, 2, 0, '#000000', 0),
(9, 13, 2, 'dd', 1500, 0, 0, 9, 1, 5, 3, 7, 1, 9, 1, 0, '#000000', 1),
(10, 13, 3, 'aa', 1500, 0, 0, 2, 8, 5, 2, 3, 9, 6, 2, 0, '#000000', 0),
(11, 14, 3, 'ooo', 1500, 0, 0, 2, 8, 5, 2, 3, 9, 6, 1, 0, '#000000', 1),
(12, 14, 4, 'joojo', 1500, 0, 0, 6, 7, 1, 6, 9, 4, 2, 2, 0, '#000000', 0),
(13, 15, 3, 'ooo', 1500, 0, 0, 2, 8, 5, 2, 3, 9, 6, 1, 0, '#000000', 1),
(14, 15, 4, 'joojo', 1500, 0, 0, 6, 7, 1, 6, 9, 4, 2, 2, 0, '#000000', 0),
(15, 16, 3, 'dsaa', 1500, 0, 0, 2, 8, 5, 2, 3, 9, 6, 1, 0, '#000000', 1),
(16, 16, 2, 'd', 1500, 0, 0, 9, 1, 5, 3, 7, 1, 9, 2, 0, '#000000', 0),
(17, 17, 3, 'saa', 1500, 0, 0, 2, 8, 5, 2, 3, 9, 6, 1, 0, '#000000', 1),
(18, 17, 4, 'sss', 1500, 0, 0, 6, 7, 1, 6, 9, 4, 2, 2, 0, '#000000', 0),
(19, 18, 3, 'aa', 1500, 0, 0, 2, 8, 5, 2, 3, 9, 6, 1, 0, '#000000', 1),
(20, 18, 4, 'sdd', 1500, 0, 0, 6, 7, 1, 6, 9, 4, 2, 2, 0, '#000000', 0),
(21, 19, 3, 'sa', 1500, 0, 0, 2, 8, 5, 2, 3, 9, 6, 1, 0, '#000000', 1),
(22, 19, 4, 'ss', 1500, 0, 0, 6, 7, 1, 6, 9, 4, 2, 2, 0, '#000000', 0),
(23, 20, 3, 'ss', 1500, 0, 0, 2, 8, 5, 2, 3, 9, 6, 1, 0, '#000000', 1),
(24, 20, 4, 'aa', 1500, 0, 0, 6, 7, 1, 6, 9, 4, 2, 2, 0, '#000000', 0),
(25, 21, 3, 'dd', 1500, 0, 26, 2, 8, 5, 2, 3, 9, 6, 1, 0, '#000000', 1),
(26, 21, 4, 'as', 1500, 0, 0, 6, 7, 1, 6, 9, 4, 2, 2, 0, '#000000', 0);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `tiles`
--

CREATE TABLE `tiles` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `type` enum('start','restaurant','duel','event','region_entrance','training','vacation') DEFAULT NULL,
  `description` text DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `cost` int(11) DEFAULT NULL,
  `base_rent` int(11) DEFAULT NULL,
  `upgrade_cost` int(11) DEFAULT NULL,
  `file` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tiles`
--

INSERT INTO `tiles` (`id`, `name`, `type`, `description`, `group_id`, `cost`, `base_rent`, `upgrade_cost`, `file`) VALUES
(0, 'START - Azja', 'start', 'Początek gry i brama do regionu Azja. Otrzymujesz 800 monet za przejście przez START.', 1, NULL, NULL, NULL, 'start.png'),
(1, 'Sushi Bar', 'restaurant', 'Specjalizacja: Sushi w każdej postaci – od klasycznego nigiri po rolki tak duże, że trzeba je jeść oburęcz. Opis: W tym miejscu ryż jest ściskany z miłością, a ryba ma honor trafić na twój talerz tylko wtedy, gdy jest wystarczająco fotogeniczna. Kelnerzy podają wasabi z ostrzeżeniem: \"To nie jest zwykły chrzan, to duchowe doświadczenie.\" A jeśli zamówisz \"chef\'s special\", istnieje szansa, że dostaniesz coś, czego nawet on nie potrafi wymówić.', 2, 150, 15, 75, 'sushi-Photoroom.png'),
(2, 'Pojedynek', 'duel', 'Stajesz do pojedynku kulinarnego! Sprawdź swoje statystyki przeciwko rywalowi!', 8, NULL, NULL, NULL, 'walka-Photoroom.png'),
(3, 'Pekińska Kaczka', 'restaurant', 'Specjalizacja: Kaczka po pekińsku, której skórka jest tak idealna, że mogłaby być reklamówką luksusowych kosmetyków. Opis: Tutaj kaczka spędza więcej czasu w piecu niż przeciętny student na nauce przed sesją. Efekt? Mięso, które rozpływa się w ustach, i skórka, która chrupie głośniej niż twoje życiowe decyzje. Do tego podają ją z naleśnikami tak cienkimi, że możesz przez nie czytać menu – gdybyś jeszcze miał na to siłę po zjedzeniu pół kilograma tłuszczu.', 2, 180, 18, 90, 'chińska-Photoroom.png'),
(4, 'Niespodzianka', 'event', 'Losujesz kartę niespodzianki! Może przynieść szczęście lub pecha w Twojej kulinarnej karierze!', 9, NULL, NULL, NULL, 'niespodzianka-Photoroom.png'),
(5, 'Thai Spice', 'restaurant', 'Specjalizacja: Pad thai, zielone curry i zupy, które albo cię uleczą, albo zmuszą do podpisania testamentu. Opis: W tej kuchni ostrzegają: \"Thai spicy znaczy THAI SPICY, nie \'dodaj pieprzu\'.\' Ich tom yum jest na tyle intensywny, że możesz go użyć jako odtrutki po imprezie. A pad thai? Tak dobry, że nawet twoja dieta powie \"okej, dziś sobie odpuszczę\". Tylko nie pytaj, co jest w ich tajnym sosie – szef kuchni tylko się uśmiecha i mówi \"magia… i MSG.\"', 2, 210, 21, 105, 'tajska-Photoroom.png'),
(6, 'Kimchi & Grill', 'restaurant', 'Specjalizacja: Kimchi, bibimbap i mięso grillowane na twoich oczach (żebyś wiedział, że nie ucieknie). Opis: Tutaj kimchi ma więcej osobowości niż twój ex. Podają je do wszystkiego, nawet gdy zamówisz wodę. Ich bibimbap wygląda jak tęcza, którą niszczysz mieszając, ale smakuje jak coś, za co twoje podniebienie podziękuje ci w snach. A grill? Mięso jest tak soczyste, że możesz się zastanawiać, czy przypadkiem nie jesz… no, nie pytaj. Tylko uważaj na ich gochujang – to nie pasta, to broń masowego rażenia (dla kubków smakowych).', 2, 240, 24, 120, 'koreańska-Photoroom.png'),
(7, 'Afryka', 'region_entrance', 'Witaj w Afryce! Aby wejść do regionu, musisz opłacić wizę kulinarną (100 monet).', 12, 100, NULL, NULL, 'afryka-Photoroom.png'),
(8, 'Couscous Kingdom', 'restaurant', 'Specjalizacja: Tajine i couscous – dania, w których mięso rozpływa się, a warzywa udają, że są zdrowsze niż są. Opis: Tutaj tajine gotuje się tak długo, że mięso samo prosi, żeby je zjeść. Ich couscous jest tak puszysty, że mógłby posłużyć za poduszkę, ale szkoda go – lepiej wchłonąć go z górą duszonego masła i rodzynek. A jeśli zamówisz \"przyprawy szefa\", kelner przyniesie ci coś między \"pikantne\" a \"czy potrzebujesz pogotowia?\".', 3, 300, 30, 150, 'maroko-Photoroom.png'),
(9, 'Nyama Choma HQ', 'restaurant', 'Specjalizacja: Nyama choma (grillowane mięso) i ugali – czyli węgiel drzewny w duecie z czymś, co przypomina plastelinę, ale smakuje jak dom. Opis: W tym miejscu mięso wędzone jest tak wolno, że możesz obejść je trzy razy, zanim będzie gotowe. Ich ugali jest tak gęste, że gdybyś rzucił je na ziemię, odbiłoby się. A sos Kachumbari? Na tyle ostry, że możesz go użyć jako testu na prawdziwego Kenijczyka – jeśli zjadasz go bez mrugnięcia okiem, dostajesz obywatelstwo.', 3, 350, 35, 175, 'kenijska-Photoroom.png'),
(10, 'Niespodzianka', 'event', 'Losujesz kartę niespodzianki! Może przynieść szczęście lub pecha w Twojej kulinarnej karierze!', 9, NULL, NULL, NULL, 'niespodzianka-Photoroom.png'),
(11, 'Szkolenie', 'training', 'Czas na doskonalenie umiejętności! Stoisz 2 kolejki, ale zyskujesz dodatkowe punkty do losowej statystyki.', 10, NULL, NULL, NULL, 'szkolenie-Photoroom.png'),
(12, 'Jollof Wars', 'restaurant', 'Specjalizacja: Jollof rice – danie, o które toczą się wojny między krajami, bo każdy twierdzi, że robi je najlepiej. Opis: Ich Jollof rice jest tak dobry, że Ghanijczycy potajemnie tu przychodzą. Ryż ma kolor zachodu słońca i smak, który sprawia, że chcesz zadzwonić do mamy i powiedzieć \"dlaczego tak nie gotowałaś?\". Do tego obowiązkowo grillowana kurczak – tak soczysty, że gdy go kroją, sąsiedzi się oblizują. Uwaga: jeśli powiesz, że jest \"za ostry\", kelner tylko wzruszy ramionami i powie \"no więc?\".', 3, 400, 40, 200, 'nigeryjska-Photoroom.png'),
(13, 'Pojedynek', 'duel', 'Stajesz do pojedynku kulinarnego! Sprawdź swoje statystyki przeciwko rywalowi!', 8, NULL, NULL, NULL, 'walka-Photoroom.png'),
(14, 'Braai & Vibe', 'restaurant', 'Specjalizacja: Braai (afrykański grill) i boerewors (kiełbasa, która ma własny fanclub). Opis: Tutaj mięso wędzone jest na ogniu tak dużym, że mógłby ogrzać całe Johannesburg. Ich boerewors jest tak długi, że możesz go użyć jako liny do wspinaczki. A sos chakalaka? Na tyle wyrazisty, że nawet najtwardszy kotlet się przed nim ukorzy. Jeśli zamówisz \"everything\", dostaniesz talerz wielkości koła od traktora – i będziesz szczęśliwy, że tak się stało.', 3, 450, 45, 225, 'RPA-Photoroom.png'),
(15, 'Australia', 'region_entrance', 'Witaj w Australii i Oceanii! Aby wejść do regionu, musisz opłacić wizę kulinarną (100 monet).', 12, 100, NULL, NULL, 'australia-Photoroom (1).png'),
(16, 'Shrimp on the Barbie', 'restaurant', 'Specjalizacja: Grillowane krewetki, stek z kangura i pieczona wegańska... a nie, czekaj, tu wszystko ma oczy i byłoby smutne na Instagramie. Opis: Tutaj każdy posiłek zaczyna się od rzucenia mięsa na żar z siłą godną rugbysty. Ich krewetki są tak duże, że mogłyby nosić kapelusze i mówić \"G\'day mate\". A stek z kangura? Na tyle niskotłuszczowy, że po zjedzeniu czujesz się winny, że nie biegasz tak szybko jak on. Do tego obowiązkowo Vegemite – smarowidło tak intensywne, że albo je kochasz, albo podejrzewasz, że to spisek przeciwko turystom.', 4, 500, 50, 250, 'Australia-Photoroom.png'),
(17, 'Pojedynek', 'duel', 'Stajesz do pojedynku kulinarnego! Sprawdź swoje statystyki przeciwko rywalowi!', 8, NULL, NULL, NULL, 'walka-Photoroom.png'),
(18, 'Hangi Pit Stop', 'restaurant', 'Specjalizacja: Hangi – tradycyjne maoryskie danie z mięsa i warzyw pieczonych w ziemnym piecu. Opis: Tutaj jedzenie gotuje się tak wolno, że możesz w międzyczasie obejść całą Wyspę Północną. Mięso jest tak miękkie, że rozpada się na widok widelca, a ziemniaki mają więcej aromatu dymu niż twoje ubrania po ognisku. Jeśli zapytasz, co jest w środku, usłyszysz \"no, wszystko, co było pod ręką\". Efekt? Smak, który sprawia, że chcesz się przywitać z każdym napotkanym drzewem (bo to też mogło być w hangi).', 4, 550, 55, 275, 'nowa zela-Photoroom.png'),
(19, 'Niespodzianka', 'event', 'Losujesz kartę niespododzianki! Może przynieść szczęście lub pecha w Twojej kulinarnej karierze!', 9, NULL, NULL, NULL, 'niespodzianka-Photoroom.png'),
(20, 'Fiji Kokoda Club', 'restaurant', 'Specjalizacja: Kokoda – lokalna wersja ceviche, gdzie ryba marynuje się w mleku kokosowym aż zapomni, że pływała w oceanie. Opis: Ich kokoda jest tak świeża, że ryba jeszcze próbuje uciekać z talerza. Mleko kokosowe jest na tyle gęste, że mógłbyś w nim utopić swoje smutki, a chili dodaje się tu według zasady \"jeden dla smaku, trzy dla odwagi\". Do tego obowiązkowo kasava – bo każdy posiłek potrzebuje czegoś, co przypomina drewno, ale smakuje jak niebo.', 4, 600, 60, 300, 'fidzi-Photoroom.png'),
(21, 'Poi & Pork Palace', 'restaurant', 'Specjalizacja: Kalua pig (wieprzowina z ziemnego pieca) i poi – fioletowa papka, która smakuje jak... no właśnie, jak co? Opis: Ich kalua pig jest tak delikatna, że możesz ją jeść łyżką (ale nie rób tego, bo lokalni się wkurzą). Poi podają z napisem \"dla wtajemniczonych\" – bo pierwsze wrażenie to \"czy to klej do tapet?\", ale po trzecim łyku zaczynasz rozumieć jego urok. A jeśli zamówisz Loco Moco, dostaniesz górę ryżu, burgera, jajka i sosu – czyli idealne danie po nocy, gdy nie wiesz, czy to śniadanie, obiad, czy terapia.', 4, 650, 65, 325, 'hawaje-Photoroom.png'),
(22, 'Ameryka Północna', 'region_entrance', 'Otrzymujesz bonus związany z Ameryką Północną! +1 do pojemności brzucha.', 12, 100, NULL, NULL, 'AmerykaN-Photoroom.png'),
(23, 'Niespodzianka', 'event', 'Losujesz kartę niespodzianki! Może przynieść szczęście lub pecha w Twojej kulinarnej karierze!', 9, NULL, NULL, NULL, 'niespodzianka-Photoroom.png'),
(24, 'Burger & Freedom', 'restaurant', 'Specjalizacja: Burgery wielkości twojej głowy i frytki nasączone patriotyzmem (czytaj: olejem). Opis: Tutaj wołowina jest tak soczysta, że płaczesz – raz ze wzruszenia, raz bo kapie ci na koszulę. Ich \"podwójny bacon cheeseburger\" ma więcej kalorii niż twoje dzienne zapotrzebowanie... na tydzień. Frytki są tak chrupiące, że słychać je w sąsiednim stanie, a ich \"secret sauce\" to prawdopodobnie jedyna rzecz, której rząd nie może się domyślić. Dodatkowo: napoje gazowane w kubkach tak dużych, że mógłbyś w nich pływać.', 5, 900, 90, 450, 'ameryka-Photoroom.png'),
(25, 'Taco Trauma', 'restaurant', 'Specjalizacja: Tacos al pastor – mięso z rożna, które spada prosto do twoich ust (i marzeń). Opis: W tym miejscu tortille robi się na twoich oczach, mięso kroi z gigantycznego rożna, a salsa verde jest tak ostra, że możesz zacząć mówić w nieznanym języku. Ich \"tacos de carnitas\" są tak dobre, że nawet świnia by się uśmiechnęła. A jeśli zamówisz \"habanero challenge\", dostaniesz tacos z sosem tak piekielnym, że kelner podaje go w rękawiczkach ochronnych.', 5, 950, 95, 475, 'meksykanska-Photoroom.png'),
(26, 'Pojedynek', 'duel', 'Stajesz do pojedynku kulinarnego! Sprawdź swoje statystyki przeciwko rywalowi!', 8, NULL, NULL, NULL, 'walka-Photoroom.png'),
(27, 'Karaibski Pożar', 'restaurant', 'Specjalizacja: Jerk chicken – kurczak marynowany w przyprawach, które mogą cię teleportować na Montego Bay. Opis: Tutaj mięso wędzone jest na drewnie pimentowym, a przyprawy są tak intensywne, że możesz zacząć słyszeć reggae w tle. Ich jerk chicken jest tak soczysty, że gdy go gryziesz, sąsiedzi dostają zazdrości. Do tego obowiązkowo \"festival\" – słodkie chlebki, które są jedynym ratunkiem przed ogniem jerk sosu. Uwaga: jeśli zamówisz \"extra spicy\", podpisują ci najpierw zrzeczenie się odpowiedzialności.', 5, 1000, 100, 500, 'jamajka-Photoroom.png'),
(28, 'Poutine Palace', 'restaurant', 'Specjalizacja: Poutine – frytki, ser w grudkach i sos tak tłusty, że mógłby być walutą. Opis: Tutaj frytki to tylko pretekst, żeby zalać je sosem i serem, który ciągnie się jak kanadyjski zimowy poranek. Ich \"klasyczna poutine\" to danie tak kaloryczne, że po zjedzeniu możesz legalnie ubiegać się o status niedźwiedzia przed hibernacją. Wersja \"smoke meat poutine\" zawiera mięso wędzone tak długo, że pamięta jeszcze czasy, gdy Quebec był tanim miastem.', 5, 1050, 105, 525, 'kanadyjska-Photoroom.png'),
(29, 'Ameryka Południowa', 'region_entrance', 'Witaj w Ameryce Południowej! Aby wejść do regionu, musisz opłacić wizę kulinarną (100 monet).', 12, 100, NULL, NULL, 'AmerykaW-Photoroom.png'),
(30, 'La Parrilla Loca', 'restaurant', 'Specjalizacja: Asado - grilowane mięso w ilościach, które mogą nakarmić małe miasteczko. Opis: Tutaj wołowina spędza więcej czasu na grillu niż turyści na zwiedzaniu Buenos Aires. Ich słynne \"asado\" to właściwie 6-godzinna sesja terapeutyczna z udziałem żeberek, kiełbas i mięsa, które rozpada się na widok noża. Kelnerzy podają wino Malbec w takich ilościach, że po obiedzie możesz zacząć mówić po hiszpańsku... nawet jeśli nigdy się nie uczyłeś. Uwaga: wegetarianie wchodzą na własne ryzyko - sałatka to tu plasterek pomidora na gigantycznej górze mięsa.', 6, 700, 70, 350, 'argentynska-Photoroom.png'),
(31, 'Feijoada Funk', 'restaurant', 'Specjalizacja: Feijoada - gulasz z fasoli i wszelkich możliwych części świni. Opis: Ich feijoada jest tak bogata, że po zjedzeniu możesz ubiegać się o obywatelstwo. Gotowana przez babcię, która twierdzi, że sekretem jest \"tylko 12 godzin i miłość\" (oraz prawdopodobnie kawałki świni, o których lepiej nie wiedzieć). Podają z farofą - czyli smażoną mąką maniokową, bo każdy porządny posiłek potrzebuje czegoś, co wygląda jak piasek, a smakuje jak grzech. Do tego obowiązkowo caipirinha - koktajl tak mocny, że po dwóch możesz zacząć rozumieć sens samby.', 6, 750, 75, 375, 'brazylia-Photoroom.png'),
(32, 'Ceviche Inc.', 'restaurant', 'Specjalizacja: Ceviche - ryba marynowana w limonce aż do duchowego przebudzenia. Opis: Tutaj ryba trafia z oceanu prosto na twój talerz, z krótką przerwą na kąpiel w limonce. Ich ceviche jest tak świeże, że czasem jeszcze się porusza. Podają z kukurydzą tak wielką, że mogłaby być bronią, i słodkimi ziemniakami, które sprawiają, że czujesz się trochę lepiej z tym całym jedzeniem surowej ryby. Pisco sour obowiązkowe - bo po tym koktajlu nawet alpaka będzie ci się wydawała dobrym pomysłem na selfie.', 6, 800, 80, 400, 'perru-Photoroom.png'),
(33, 'Urlop', 'vacation', 'Czas na zasłużony odpoczynek! Stoisz 1 kolejkę, ciesząc się wolnym dniem.', 11, NULL, NULL, NULL, 'urlop-Photoroom.png'),
(34, 'Pojedynek', 'duel', 'Stajesz do pojedynku kulinarnego! Sprawdź swoje statystyki przeciwko rywalowi!', 8, NULL, NULL, NULL, 'walka-Photoroom.png'),
(35, 'Niespodzianka', 'event', 'Losujesz kartę niespodzianki! Może przynieść szczęście lub pecha w Twojej kulinarnej karierze!', 9, NULL, NULL, NULL, 'niespodzianka-Photoroom.png'),
(36, 'Arepa Libre', 'restaurant', 'Specjalizacja: Arepy - placuszki kukurydziane, które mogą zawierać wszystko, od sera po twoje marzenia. Opis: Ich arepy są tak uniwersalne, że mogłyby rozwiązać problem głodu na świecie. Nadziewane serem tak ciągnącym, że możesz zagrać w nim w gumę, albo mięsem, które sprawia, że zapominasz o wszystkich swoich problemach. Do tego tajny sos \"guasacaca\" - czyli awokado w wersji \"nie mogę przestać\". Uwaga: po zjedzeniu trzech możesz potrzebować pomocy przy wstawaniu od stołu.', 6, 850, 85, 425, 'wenezuela-Photoroom.png'),
(37, 'Europa', 'region_entrance', 'Witaj w Europie! Aby wejść do regionu, musisz opłacić wizę kulinarną (100 monet).', 12, 100, NULL, NULL, 'Europa-Photoroom.png'),
(38, 'Niespodzianka', 'event', 'Losujesz kartę niespodzianki! Może przynieść szczęście lub pecha w Twojej kulinarnej karierze!', 9, NULL, NULL, NULL, 'niespodzianka-Photoroom.png'),
(39, 'Włoska Wariatka', 'restaurant', 'Specjalizacja: Pasta w 152 odmianach (w tym 151, których nie znajdziesz nigdzie indziej). Opis: Tutaj każdy makaron ma swoją dramatyczną historię dłuższą niż opera. Ich carbonara jest tak autentyczna, że gdybyś dodał śmietanę, szef kuchni wyjdzie z kuchni i zacznie gestykulować jak przy meczu Napoli. Pizza ma cienki spód nie dlatego, że tak wypada, ale bo Włosi nie lubią marnować czasu na zbędnych węglowodanach. A tiramisu? Tak lekkie, że po zjedzeniu możesz udawać, że to tylko kawa... z deserem na dnie.', 7, 1100, 110, 550, 'włoska-Photoroom.png'),
(40, 'Croissant & Complaints', 'restaurant', 'Specjalizacja: Bagietki twarde jak francuski urzędnik i sery śmierdzące jak ich metro. Opis: W tej restauracji podają croissanty tak maślane, że po zjedzeniu musisz iść na detoks. Ich ratatouille wygląda jak obrazek z podręcznika do sztuki, ale i tak kelner spojrzy na ciebie z politowaniem, bo zamówiłeś danie warzywne we Francji. A wino? Podają je w takich ilościach, że po drugim kieliszku nawet ich arogancja zaczyna być urocza.', 7, 1150, 115, 575, 'francuska-Photoroom.png'),
(41, 'Pojedynek', 'duel', 'Stajesz do pojedynku kulinarnego! Sprawdź swoje statystyki przeciwko rywalowi!', 8, NULL, NULL, NULL, 'walka-Photoroom.png'),
(42, 'Frytkowy Raj', 'restaurant', 'Specjalizacja: Frytki z majonezem (bo keczup to herezja) i gofry grubsze od brukselskiego dywanu kwiatowego. Opis: Tutaj frytki smaży się na łoju wołowym, bo Belgowie wiedzą, że życie jest za krótkie na olej roślinny. Ich stoisko z goframi to jedyne miejsce, gdzie możesz legalnie zjeść deser wielkości poduszki przed obiadem. A piwo? Podają je w tak wielu odmianach, że wybór jest trudniejszy niż znalezienie sensu w surrealizmie.', 7, 1200, 120, 600, 'belgijska-Photoroom.png'),
(43, 'Pierogi Power', 'restaurant', 'Specjalizacja: Pierogi w ilościach, które mogłyby wykarmić armię husarii. Opis: W tej restauracji ciasto na pierogi wałkuje się z taką siłą, że można by tym napędzać elektrownię. Ich pierogi ruskie są tak dobre, że nawet Ukraińcy i Rosjanie przestają się kłócić przy stole. Bigos gotuje się tu według starej zasady: \"im dłużej stoi, tym lepszy\" - niektóre garnki pamiętają jeszcze czasy PRL-u. A vodka? Podawana jest w ilościach leczniczych, bo przecież \"do picia i do polania\" to nasze narodowe motto.', 7, 1250, 125, 625, 'polskaaa-Photoroom.png');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `tile_groups`
--

CREATE TABLE `tile_groups` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `color_code` varchar(7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tile_groups`
--

INSERT INTO `tile_groups` (`id`, `name`, `color_code`) VALUES
(1, 'Start', '#ffcf87'),
(2, 'Azja', '#f8fdb5'),
(3, 'Afryka', '#81dba8'),
(4, 'Australia', '#89d2f6'),
(5, 'Amerykapln', '#937661'),
(6, 'Amerykapld', '#ebacab'),
(7, 'Europa', '#FFFF00'),
(8, 'Pojedynek', '#bd95fe'),
(9, 'Niespodzianka', '#e8b2fa'),
(10, 'Szkolenie', '#ffcf87'),
(11, 'Urlop', '#ffcf87'),
(12, 'regionenter', '#ffcf87');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `turn_log`
--

CREATE TABLE `turn_log` (
  `log_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `turn_number` int(11) NOT NULL,
  `dice_roll_1` int(11) DEFAULT NULL,
  `dice_roll_2` int(11) DEFAULT NULL,
  `action_description` text DEFAULT NULL,
  `money_change` int(11) DEFAULT NULL,
  `current_money` int(11) DEFAULT NULL,
  `properties_count` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indeksy dla zrzutów tabel
--

--
-- Indeksy dla tabeli `action_cards`
--
ALTER TABLE `action_cards`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `characters`
--
ALTER TABLE `characters`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `duel_cards`
--
ALTER TABLE `duel_cards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `related_stat_idx` (`related_stat`);

--
-- Indeksy dla tabeli `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_games_current_player` (`current_player_id`);

--
-- Indeksy dla tabeli `game_tiles`
--
ALTER TABLE `game_tiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_game_tile` (`game_id`,`tile_id`),
  ADD KEY `tile_id` (`tile_id`),
  ADD KEY `current_owner_id` (`current_owner_id`);

--
-- Indeksy dla tabeli `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id_idx` (`game_id`),
  ADD KEY `character_id_idx` (`character_id`),
  ADD KEY `location_idx` (`location`);

--
-- Indeksy dla tabeli `tiles`
--
ALTER TABLE `tiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tiles_group` (`group_id`);

--
-- Indeksy dla tabeli `tile_groups`
--
ALTER TABLE `tile_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indeksy dla tabeli `turn_log`
--
ALTER TABLE `turn_log`
  ADD PRIMARY KEY (`log_id`),
  ADD UNIQUE KEY `game_id` (`game_id`,`player_id`,`turn_number`),
  ADD KEY `player_id` (`player_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `action_cards`
--
ALTER TABLE `action_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `characters`
--
ALTER TABLE `characters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `duel_cards`
--
ALTER TABLE `duel_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `game_tiles`
--
ALTER TABLE `game_tiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=404;

--
-- AUTO_INCREMENT for table `players`
--
ALTER TABLE `players`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `tile_groups`
--
ALTER TABLE `tile_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `turn_log`
--
ALTER TABLE `turn_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `games`
--
ALTER TABLE `games`
  ADD CONSTRAINT `fk_games_current_player` FOREIGN KEY (`current_player_id`) REFERENCES `players` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `game_tiles`
--
ALTER TABLE `game_tiles`
  ADD CONSTRAINT `game_tiles_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `game_tiles_ibfk_2` FOREIGN KEY (`tile_id`) REFERENCES `tiles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `game_tiles_ibfk_3` FOREIGN KEY (`current_owner_id`) REFERENCES `players` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `players`
--
ALTER TABLE `players`
  ADD CONSTRAINT `fk_players_characters` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_players_games` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_players_location` FOREIGN KEY (`location`) REFERENCES `tiles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `tiles`
--
ALTER TABLE `tiles`
  ADD CONSTRAINT `fk_tiles_group` FOREIGN KEY (`group_id`) REFERENCES `tile_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `turn_log`
--
ALTER TABLE `turn_log`
  ADD CONSTRAINT `turn_log_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `turn_log_ibfk_2` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
