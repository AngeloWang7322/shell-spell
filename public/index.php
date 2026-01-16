<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/model/room.php';
require __DIR__ . '/../src/model/user.php';
require __DIR__ . '/../src/model/items.php';
require __DIR__ . '/../src/model/exceptions.php';
require __DIR__ . '/../src/model/command.php';
require_once __DIR__ . '/../src/db/db.php';
require_once __DIR__ . '/../src/db/dbhelper.php';
require __DIR__ . '/../src/model/enums.php';
require_once __DIR__ . "/../src/logic/terminal.php";
require_once __DIR__ . "/../src/logic/terminalHelper.php";

if ($_SESSION["hasDbConnection"]) {
    $dbHelper = new DBHelper($pdo);
}
session_start();
session_unset();        

if (!isset($_SESSION["history"])) {
    DBHelper::loadDefaultSession();
}

$_SESSION["curRoom"];
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"])) {
    switch ($_POST["action"]) {
        case "enterCommand": {
                startTerminalProcess();
                break;
            }
        case "loadMap": {
                $dbHelper->loadGameState($_POST["mapId"]);
                header("Location: /");
                exit;
            }
        case "closeScroll": {
                require __DIR__ . "/../src/logic/game.php";
                break;
            }
        case "newMap": {
                $dbHelper->createGameState($_POST["newMapName"]);
                header("Location: /");
                exit;
            }
        case "deleteMap": {
                $dbHelper->deleteGameState($_POST["mapId"]);
                break;
            }   
    }
    // header("Location: " . $_SERVER["REQUEST_URI"]);
    // exit;
}
$routes = [
    '' => 'main.php',
    'login' => 'login.php',
    'register' => 'authentication.php',
    'profile' => 'profile.php',
    'selection' => 'gameStateSelection.php',
    'notfound' => 'notfound.php',
    'newgame' => 'createNewGame.php',
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
