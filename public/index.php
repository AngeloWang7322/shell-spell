<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/model/room.php';
require __DIR__ . '/../src/model/user.php';
require __DIR__ . '/../src/model/item.php';
require __DIR__ . '/../src/model/scroll.php';
require_once  __DIR__ . '/../src/db/db.php';
require_once __DIR__ . '/../src/db/dbhelper.php';
require __DIR__ . '/../src/model/enums.php';

$dbHelper = new DBHelper($pdo);
session_start();
// session_unset();    

if (!isset($_SESSION["history"])) {
    DBHelper::loadDefaultSession();
}
// echo json_encode($_SESSION["map"]);

$_SESSION["curRoom"];
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
    'selection' => 'gameStateSelection',
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