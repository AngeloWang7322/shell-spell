<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();


$routes = [
    '/' => 'pages/game.php',
    '/login' => 'pages/login.php',
    '/profile' => 'pages/profile.php',
    '/notfound' => 'pages/notfound.php'
];

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($path, '/');

if (isset($routes[$path])) 
{
    require __DIR__ . '/' . $routes[$path];
}
else
{
    require __DIR__ . '/' . $routes['/notfound'];
}