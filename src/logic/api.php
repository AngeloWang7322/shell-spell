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
function enterCommand($dbHelper)
{
    $start = hrtime(as_number: true);
    $_POST["command"] = trim($_POST["command"], " ");
    $_SESSION["inputCommand"] = $_POST["command"];

    if ($_POST["command"] == "") return;


    startTerminalProcess();
    $end = hrtime(true);
    echo "<br>Terminal: " . (($end - $start) / 1000000) . "ms";

    if (isset($_SESSION["isLoggedIn"]))
    {
        $start = hrtime(as_number: true);
        $dbHelper->updateUserMap();
        $end = hrtime(true);
        echo "<br>Query: " . (($end - $start) / 1000000) . "ms";
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
    $dbHelper->createGameState($_POST["newMapName"]);
    header("Location: /");
    exit;
}
