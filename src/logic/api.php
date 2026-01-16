<?php


function getValidActions()
{
    return [
        "enterCommand",
        "editScroll",
        "closeScroll",
        "deleteMap",
        "loadMap",
        "newMap"
    ];
}
function enterCommand()
{
    $_SESSION["inputCommand"]= $_POST["command"];
    startTerminalProcess();
}

function deleteMap($dbHelper)
{
    $dbHelper->deleteGameState($_POST["mapId"]);
}

function loadMap($dbHelper)
{
    $dbHelper->loadGameState($_POST["mapId"]);
    header("Location: /");
    exit;
}

function newMap($dbHelper)
{
    $dbHelper->createGameState($_POST["newMapName"]);
    header("Location: /");
    exit;
}
