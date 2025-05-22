<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "monopoly";

// Tymczasowo włącz raportowanie błędów MySQLi jako wyjątki
// Pomoże to wychwycić błędy w zapytaniach SQL, które inaczej mogłyby być pominięte
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Usuń operator tłumienia błędów '@'
// Teraz błędy połączenia będą widoczne i spowodują przerwanie skryptu
$mysqli = new mysqli($host, $user, $password, $database);

// Sprawdzenie połączenia jest nadal ważne, ale teraz błąd połączenia
// zostanie automatycznie zgłoszony przez PHP jeśli 'display_errors' jest na 1
if ($mysqli->connect_errno) {
    error_log("Database connection error: " . $mysqli->connect_error);
    // W normalnej sytuacji tutaj można by rzucić wyjątek, ale dla debugowania
    // wystarczy, że PHP wyświetli błąd, jeśli display_errors jest włączone.
}

?>