<?php

declare(strict_types=1);

require __DIR__ . "/terminalHelper.php";

function closeScroll()
{

    unset($_SESSION["openedScroll"]);
}
function editScroll()
{
    $tempScroll = &getItem($_SESSION["openedScroll"]["path"]);
    $userRole = $_SESSION["user"]["role"];
    if ($userRole->isLowerThan($tempScroll->requiredRole))
    {
        editLastHistory("unable to change scroll, required role: " . colorizeString($tempScroll->requiredRole->value));
    }
    else
    {
        $tempScroll->content = $_POST["newFileContent"];
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
