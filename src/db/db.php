<?php

$host   = "localhost";
$dbname = "cligame_db"; 
$dbuser = "root";       
$dbpass = "";               
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $_SESSION["hasDbConnection"]= true;
    $pdo = new PDO($dsn, $dbuser, $dbpass, $options);
} catch (PDOException $e) {
    $_SESSION["hasDbConnection"]= false;
    echo "Datenbank-Verbindung fehlgeschlagen: " . $e->getMessage();
}
