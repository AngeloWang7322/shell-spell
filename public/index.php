<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/model/room.php';
require __DIR__ . '/../src/model/user.php';
require __DIR__ . '/../src/model/item.php';
require __DIR__ . '/../src/model/scroll.php';
require_once  __DIR__ . '/../src/db/db.php';
require __DIR__ . '/../src/model/role.php';

session_start();
// session_unset();

if (!isset($_SESSION["history"])) {
    $_SESSION["history"] = [];
    $_SESSION["map"] = new Room("hall");
    $_SESSION["curRoom"] = &$_SESSION["map"];
    $_SESSION["map"]->path = ["hall"];
    $_SESSION["map"]->doors["library"] = new Room("library", requiredRole: ROLE::APPRENTICE);
    $_SESSION["map"]->doors["armory"] = new Room("armory", requiredRole: ROLE::ARCHIVIST);
    $_SESSION["map"]->doors["passage"] = new Room("passage", requiredRole: ROLE::WANDERER);
    $_SESSION["map"]->doors["passage"]->doors["staircase"] = new Room(name: "staircase", path: $_SESSION["map"]->doors["passage"]->path, requiredRole: ROLE::ROOT);

    $_SESSION["map"]->items["manaRune.exe"] = new Item(
        "manaRune",
        ItemType::SPELL,
        ActionType::MANA,
        Rarity::COMMON
    );
    $_SESSION["map"]->items["grimoire.txt"] = new Item(
        "grimoire",
        ItemType::SCROLL,
        ActionType::OPEN_SCROLL,
        Rarity::COMMON,
        "OPEN SCROLL: <br>'cat [scroll name]'<br>"
    );
    $_SESSION["map"]->items["testScroll.txt"] = new Item(
        "testScroll",
        ItemType::SCROLL,
        ActionType::OPEN_SCROLL,
        Rarity::COMMON,
        "This is a test scroll content. It is used to demonstrate the scroll functionality in the" .
        " game. You can read this scroll to gain knowledge and power."
    );
    $_SESSION["map"]->items["oldDiary.txt"] = new Item(
        "oldDiary",
        ItemType::SCROLL,
        ActionType::OPEN_SCROLL,
        Rarity::RARE,
        "some old diary text about hunting boar"
    );
    $_SESSION["maxMana"] = 100;
    $_SESSION["curMana"] = 100;
    $_SESSION["openedScroll"] = new Scroll("", "");
    $_SESSION["user"]["role"] = ROLE::WANDERER;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"])) {
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
    header("Location: " . $_SERVER["REQUEST_URI"]);
    exit;
}

$routes = [
    '' => 'main.php',
    'login' => 'login.php',
    'register' => 'authentication.php',
    'profile' => 'profile.php',
    'notfound' => 'notfound.php'
];

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($path, '/');

if (isset($routes[$path])) {
    require __DIR__ . '/templates//' . $routes[$path];
} else {
    require __DIR__ . '/templates//' . $routes['notfound'];
}

require __DIR__ . '/assets/footer.php';
require __DIR__ . '/assets/layout.php';
?>