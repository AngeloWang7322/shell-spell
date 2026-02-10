<?php

declare(strict_types=1);


function closeScroll()
{
    unset($_SESSION["openedScroll"]);
}
function editScroll()
{
    $tempScroll = &getItem($_SESSION["openedScroll"]["path"]);
    $userRank = $_SESSION["user"]["Rank"];
    if ($userRank->isLowerThan($tempScroll->requiredRank))
    {
        Controller::editLastHistory("unable to change scroll, required Rank: " . colorizeString($tempScroll->requiredRank->value));
    }
    else
    {
        $tempScroll->content = strip_tags($_POST["newFileContent"]);
    }
    closeScroll();
}
function exitIfLoggedIn()
{
    if (isset($_SESSION["isLoggedIn"]))
    {
        header(header: "Location: " . "/");
        exit;
    }
}
function exitIfNotLoggedIn()
{
    if (!isset($_SESSION["isLoggedIn"]))
    {
        header(header: "Location: " . "/");
        exit;
    }
}

function canDisplay($room)
{
    return !$room->isHidden || isset($_SESSION["displayAll"]);
}

function importCss($cssFileName)
{
    echo "<link rel='stylesheet' href='/../assets/css/" . $cssFileName . "'>";
}
function importScript($scriptName)
{
    echo "<script src='/../scripts/" . $scriptName . "' defer></script>";
}
function splitString($baseString, &$beforeSeperator, &$afterSeperator, $seperator)
{
    $needlePos = strrpos($baseString, $seperator);
    $beforeSeperator = trim(substr($baseString, 0, $needlePos));
    $afterSeperator = trim(substr($baseString, $needlePos + strlen($seperator) + 1));
}
