<?php


function getValidActions()
{
    return [
        "enterCommand",
        "editScroll",
        "closeScroll",
        "deleteMap",
        "loadMap",
        "newMap",
        "uploadProfilePic",
        "logoutUser"
    ];
}
function enterCommand($dbHelper)
{
    $_POST["command"] = trim($_POST["command"], " ");
    $_SESSION["inputCommand"] = $_POST["command"];
    $_SESSION["backUpMap"] = clone $_SESSION["map"];
    
    if ($_POST["command"] == "") return;

    Controller::startTerminalProcess();

    if (isset($_SESSION["isLoggedIn"]))
    {
        $dbHelper->updateUserMap();
    }
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
    $dbHelper->createGameState($_POST["newMapName"], $_POST["rank"]);
    header("Location: /");
    exit;
}
function uploadProfilePic($dbHelper)
{
    $path = handleProfilePicUpload();
    if ($path !== null && !empty($_SESSION["isLoggedIn"]))
    {
        $dbHelper->setUserProfilePic($_SESSION["user"]["id"], $path);
    }
    header("Location: /profile");
    exit;
}

function logoutUser()
{
    session_unset();
    header("Location: /");
    exit;
}
