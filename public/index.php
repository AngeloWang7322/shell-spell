<?php

declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/model/room.php';
require __DIR__ . '/../src/model/user.php';
require __DIR__ . '/../src/model/item.php';
session_start();
// $x = 1;
// $y =& $x;
// $z =& $y;
// $z++;

// echo "x: $x <br>y: $y<br>z: $z <br>";

// session_unset();
if (!isset($_SESSION["history"])) {
    $_SESSION["history"] = [];
    $_SESSION["map"] = new Room("hall");
    $_SESSION["curRoom"] = &$_SESSION["map"];
    $_SESSION["map"]->path = ["hall"];
    $_SESSION["map"]->doors[] = new Room("library");
    $_SESSION["map"]->doors[] = new Room("armory");
    $_SESSION["map"]->items[] = new Item(
        "manaPotion",
        ItemType::SPELL,
        ActionType::MANA,
        Rarity::COMMON
    );
    $_SESSION["user"] = new User();
}

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    switch ($_POST["action"]) {
        case "enterCommand": {
                require __DIR__ . "/../src/logic/terminal.php";
                break;
            }
    }
}

$routes = [
    '' => 'templates/main.php',
    'login' => 'templates/login.php',
    'profile' => 'templates/profile.php',
    'notfound' => 'templates/notfound.php'
];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($path, '/');
?>
<?php
if (isset($routes[$path])) {
    require __DIR__ . '/' . $routes[$path];
} else {
    require __DIR__ . '/' . $routes['notfound'];
}

require __DIR__ . '/assets/layout.php';
require __DIR__ . '/assets/footer.php';

?>