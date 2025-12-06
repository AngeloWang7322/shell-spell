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
    $pdo = new PDO($dsn, $dbuser, $dbpass, $options);
} catch (PDOException $e) {
    echo "Datenbank-Verbindung fehlgeschlagen: " . $e->getMessage();
    
//     if (!isset($_SESSION["history"])) {
//     $_SESSION["history"] = [];
//     $_SESSION["map"] = new Room("hall");
//     $_SESSION["curRoom"] = &$_SESSION["map"];
//     $_SESSION["map"]->path = ["hall"];
//     $_SESSION["map"]->doors["library"] = new Room( "library", requiredRole: ROLE::APPRENTICE);
//     $_SESSION["map"]->doors["armory"] = new Room( "armory", requiredRole: ROLE::ARCHIVIST);
//     $_SESSION["map"]->doors["passage"] = new Room("passage", requiredRole: ROLE::WANDERER);
//     $_SESSION["map"]->doors["passage"]->doors["staircase"] = new Room(name: "staircase", path: $_SESSION["map"]-> doors["passage"]-> path, requiredRole: ROLE::ROOT);
    
//     $_SESSION["map"]->items["manaPotion.exe"] = new Item(
//         "manaPotion",
//         ItemType::SPELL,
//         ActionType::MANA,
//         Rarity::COMMON
//     );
//       $_SESSION["map"]->items["grimoire.txt"] = new Item(
//         "grimoire",
//         ItemType::SCROLL,
//         ActionType::OPEN_SCROLL,
//         Rarity::COMMON,
//         "OPEN SCROLL: <br>'cat [scroll name]'<br>"
//     );
//     $_SESSION["map"]->items["testScroll.txt"] = new Item(
//         "testScroll",
//         ItemType::SCROLL,
//         ActionType::OPEN_SCROLL,
//         Rarity::COMMON,
//         "This is a test scroll content. It is used to demonstrate the scroll functionality in the" .
//             " game. You can read this scroll to gain knowledge and power."
//     );
//     $_SESSION["maxMana"] = 100;
//     $_SESSION["curMana"] = 100;
//     $_SESSION["openedScroll"] = new Scroll("", "");
//     $_SESSION["user"]["role"] = ROLE::WANDERER;
// }
}
