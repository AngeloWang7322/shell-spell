<?php

declare(strict_types=1);

require __DIR__ . '/../src/model/room.php';
require __DIR__ . '/../src/model/items.php';
require __DIR__ . '/../src/model/exceptions.php';
require __DIR__ . '/../src/model/command.php';
require __DIR__ . '/../src/model/enums.php';
require __DIR__ . "/../src/db/db.php";
require __DIR__ . '/../src/logic/upload.php';
require __DIR__ . "/../src/logic/gameUtils.php";
require __DIR__ . "/../src/logic/api.php";
require __DIR__ . "/../src/logic/terminal.php";
require __DIR__ . "/../src/logic/terminalController.php";
require __DIR__ . "/../src/logic/terminalUtils.php";
require __DIR__ . "/../src/logic/gameController.php";

session_start();
// session_unset();
$test = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request =  $_SERVER["REQUEST_URI"];

if (
    !isset($_SESSION["map"])
    && $_SERVER['REQUEST_URI'] != "/newgame"
)
{
    header("Location: /newgame");
    exit;
}

if (!isset($_SESSION["map"]))
{
    DBHelper::loadDefaultSession();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]))
{
    if (in_array($_POST["action"], getValidActions()))
    {
        ($_POST["action"])($dbHelper);
    }
    // header(header: "Location: " . $_SERVER["REQUEST_URI"]);
    // exit;
}

$routes = [
    '' => 'main.php',
    'login' => 'login.php',
    'register' => 'authentication.php',
    'profile' => 'profile.php',
    'menu' => 'gameStateSelection.php',
    'notfound' => 'notfound.php',
    'newgame' => 'createNewGame.php',
];

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($path, '/');

if (isset($routes[$path]))
{
    require __DIR__ . "/assets/header.php";
    require __DIR__ . '/templates//' . $routes[$path];
}
else
{
    require __DIR__ . '/templates//' . $routes['notfound'];
}

require __DIR__ . '/assets/layout.php';
