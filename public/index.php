<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/model/map.php';
session_start();

$_SESSION["currentDirectory"] = "hall";
$_SESSION["map"] = [new Room("hall", "root")];


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