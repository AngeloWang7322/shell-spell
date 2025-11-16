<?php

declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/model/room.php';
require __DIR__ . '/../src/model/user.php';
require __DIR__ . '/../src/model/item.php';
require __DIR__ . '/../src/model/scroll.php';

session_start();
// $x = 1;
// $y = &$x;
// $z = $y;
// $x = 2;
// echo ("x: $x, y: $y, z: $z<br>");
// session_unset();      
if (!isset($_SESSION["history"])) {
    $_SESSION["history"] = [];
    $_SESSION["map"] = new Room("hall");
    $_SESSION["curRoom"] = &$_SESSION["map"];
    $_SESSION["map"]->path = ["hall"];
    $_SESSION["map"]->doors["library"] = new Room("library");
    $_SESSION["map"]->doors["armory"] = new Room("armory");
    $_SESSION["map"]->doors["passage"] = new Room("passage");
    $_SESSION["map"]->doors["passage"]->doors["staircase"] = new Room(name: "staircase", path: $_SESSION["map"]-> doors["passage"]-> path);

    $_SESSION["map"]->items["manaPotion.exe"] = new Item(
        "manaPotion",
        ItemType::SPELL,
        ActionType::MANA,
        Rarity::COMMON
    );
    $_SESSION["map"]->items["testScroll.txt"] = new Item(
        "testScroll",
        ItemType::SCROLL,
        ActionType::OPEN_SCROLL,
        Rarity::COMMON,
        "This is a test scroll content. It is used to demonstrate the scroll functionality in the" .
            " game. You can read this scroll to gain knowledge and power."
    );
    $_SESSION["user"] = new User();
    $_SESSION["openedScroll"] = new Scroll("", "");
}

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    switch ($_POST["action"]) {
        case "enterCommand": {
                require __DIR__ . "/../src/logic/terminal.php";
                break;
            }
        case "closeScroll": {
                require __DIR__ . "/../src/logic/game.php";
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