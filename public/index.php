<?php

declare(strict_types=1);

// require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/model/room.php';
require __DIR__ . '/../src/model/user.php';
require __DIR__ . '/../src/model/items.php';
require __DIR__ . '/../src/model/exceptions.php';
require __DIR__ . '/../src/model/command.php';
require __DIR__ . '/../src/model/enums.php';
require __DIR__ . "/../src/logic/game.php";
require __DIR__ . "/../src/logic/api.php";
require __DIR__ . "/../src/db/db.php";
require_once __DIR__ . "/../src/logic/terminal.php";
require_once __DIR__ . "/../src/logic/terminalHelper.php";

session_start();
// session_unset();           

if (!isset($_SESSION["history"]))
{
    DBHelper::loadDefaultSession();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]))
{
    if (in_array($_POST["action"], getValidActions()))
    {
        $start = hrtime(as_number: true);
        ($_POST["action"])($dbHelper);
        $end = hrtime(true);
        echo "<br>executed in: " . (($end - $start) / 1000000) . "ms";
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

if (isset($routes[$path]))
{
    require __DIR__ . '/templates//' . $routes[$path];
}
else
{
    require __DIR__ . '/templates//' . $routes['notfound'];
}

// require __DIR__ . '/assets/footer.php';
require __DIR__ . '/assets/layout.php';
