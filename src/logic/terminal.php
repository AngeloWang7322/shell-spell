<?php

declare(strict_types=1);
require __DIR__ . "/terminalHelper.php";

function initiateTerminalLogic()
{
    $_SESSION["context"]["response"] = "";
    $_SESSION["context"]["inputArgs"] = [];

    if (empty($_POST["command"]))
    {
        return;
    }

    try
    {
        $_SESSION["context"]["inputArgs"] = organizeInput(explode(" ", $_POST["command"]));
        $commandFunction = "execute" . $_SESSION["context"]["inputArgs"]["command"];
        $commandFunction();
    }
    catch (Exception $e)
    {
        editMana(amount: 10);
        $_SESSION["context"]["response"] = $e->getMessage();
    }

    $_SESSION["history"][] = [
        "directory" => $_POST["baseString"],
        "command" => $_POST["command"],
        "response" => $_SESSION["context"]["response"]
    ];
}

function executeCd()
{
    switch ($_POST["command"][3])
    {
        case "/":
            {
                $_SESSION["curRoom"] = &$_SESSION["map"];
                pushNewLastPath($_SESSION["curRoom"]->path);
                break;
            }
        case "-":
            {
                $_SESSION["curRoom"] = &getRoom(array_pop($_SESSION["lastPath"]));
                break;
            }
        default:
            {
                if (count($_SESSION["context"]["inputArgs"]["path"][0]) == 0)
                {
                    throw new Exception("no path provided");
                }
                pushNewLastPath($_SESSION["curRoom"]->path);

                $_SESSION["curMana"] -= (count($_SESSION["context"]["inputArgs"]["path"][0]) - 1) * 2;
                $_SESSION["curRoom"] = &getRoom($_SESSION["context"]["inputArgs"]["path"][0], true);
                break;
            }
    }
}
function executeMkdir()
{
    if (empty($_SESSION["context"]["inputArgs"]["path"][0]))
    {
        throw new Exception("no directory name provided");
    }
    $roomName = end($_SESSION["context"]["inputArgs"]["path"][0]);
    $tempRoom = &getRoom(array_slice($_SESSION["context"]["inputArgs"]["path"][0], 0, -1));
    $tempRoom->doors[$roomName] = new Room(
        name: $roomName,
        path: $tempRoom->path,
        requiredRole: $_SESSION["user"]["role"]
    );
}
function executeLs()
{
    $tempRoom = getRoom($_SESSION["context"]["inputArgs"]["path"][0], true);
    $lsArray = array_merge(array_keys($tempRoom->doors), array_keys($tempRoom->items));
    $_SESSION["context"]["response"] = "- " . implode(", ", $lsArray);
}

function executePwd()
{
    $_SESSION["context"]["response"] = implode("/", $_SESSION["curRoom"]->path);
}

function executeRm()
{
    deleteElement($_SESSION["context"]["inputArgs"]["path"][0]);
}

function executeCp(){
        $destinationRoom = &getRoom($_SESSION["context"]["inputArgs"]["path"][1]);

    if (empty($_SESSION["context"]["inputArgs"]["path"][1]))
    {
        throw new Exception("no source path provided");
    }
    else if ($_SESSION["context"]["inputArgs"]["path"][0][0] == $_SESSION["context"]["inputArgs"]["path"][1][0])
    {
        throw new Exception("cannot move room into itsself");
    }

    if (stristr(end($_SESSION["context"]["inputArgs"]["path"][0]), '.'))
    {
        $tempItem = &getItem($_SESSION["context"]["inputArgs"]["path"][0]);
        $destinationRoom->items[$tempItem->name] = $tempItem;
        updateItemPaths($destinationRoom);
    }
    else
    {
        $tempRoom = &getRoom($_SESSION["context"]["inputArgs"]["path"][0]);
        $destinationRoom->doors[$tempRoom->name] = $tempRoom;
        updatePathsAfterMv($destinationRoom);
    }
}

function executeMv()
{
    executeCp();

    deleteElement($_SESSION["context"]["inputArgs"]["path"][0], false);
}

function executeCat()
{
    $catItem = &getItem($_SESSION["context"]["inputArgs"]["path"][0]);
    if (is_a($catItem, SCROLL::class))
    {
        $catItem->openScroll();
    }
    else if (is_a($catItem, LOG::class))
    {
        $_SESSION["context"]["response"] = $catItem->content;
    }
    else
    {
        throw new Exception("item not readable");
    }
}

function executeGrep()
{
    $searchMatching = true;
    $searchRecursive = false;
    $caseInsensitive = false;

    foreach ($_SESSION["context"]["inputArgs"]["flags"] as $flag)
    {
        echo "<br>Flag: " . $flag;
        switch ($flag)
        {
            case "-v":
                {
                    $searchMatching = false;
                    break;
                }
            case "-r":
                {
                    $searchRecursive = true;
                    break;
                }
            case "-i":
                {
                    $caseInsensitive = true;
                    break;
                }
            default:
                {
                    throw new Exception("invalid flag");
                }
        }
    }
    $grepElement = &getRoomOrItem($_SESSION["context"]["inputArgs"]["path"][0]);
    $matchingLines = [];

    if (is_a($grepElement, Room::class))
    {
        $matchingLines = grepDirectory(
            room: $grepElement,
            condition: $_SESSION["context"]["inputArgs"]["strings"][0],
            searchMatching: $searchMatching,
            searchRecursive: $searchRecursive,
            caseInsensitive: $caseInsensitive,
        );
    }
    else
    {
        $matchingLines = grepItem(
            $grepElement,
            $_SESSION["context"]["inputArgs"]["strings"][0]
        );
    }

    foreach ($matchingLines as $key => $line)
    {
        $_SESSION["context"]["response"] = $_SESSION["context"]["response"] . $key . " " . $line;
    }
}

function executeExecutable()
{
    if (strncmp($_SESSION["context"]["inputArgs"]["command"], "./", 2) == 0)
    {
        $itemExec = &getItem(explode("/", substr($_SESSION["context"]["inputArgs"]["command"], 2)));
        if (is_a($itemExec, Alter::class) || is_a($itemExec, Spell::class))
        {
            $itemExec->executeAction();
        }
        else
        {
            throw new Exception("item not executable");
        }
    }
    else
    {
        throw new Exception("invalid command");
    }
}
