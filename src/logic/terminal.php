<?php

declare(strict_types=1);

function startTerminalProcess()
{
    try
    {
        $_SESSION["preserveState"] = false;
        checkAndHandleSpecialCases();
        prepareCommandExecution();
        executeCommand();
    }
    catch (Exception $e)
    {
        editMana($e->getCode());
        $_SESSION["response"] = $e->getMessage();
    }

    if ($_SESSION["preserveState"]) return;
    writeResponse();
    cleanUp();
}

function executeCd()
{
    switch ($_SESSION["tokens"]["path"][0][0])
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
                pushNewLastPath($_SESSION["curRoom"]->path);
                $_SESSION["curRoom"] = &getRoom($_SESSION["tokens"]["path"][0], true);
                break;
            }
    }
}
function executeMkdir()
{
    for ($i = 0; $i < count($_SESSION["tokens"]["path"]); $i++)
    {
        $roomName = end($_SESSION["tokens"]["path"][$i]);
        $tempRoom = &getRoom(array_slice($_SESSION["tokens"]["path"][0], 0, -1));

        if (in_array($roomName, array_keys($tempRoom->doors)) && !isset($_SESSION["prompt"]))
        {
            createPrompt($roomName . " exists, are you sure you want to replace it?<br>y/n");
        }
        $tempRoom->doors[$roomName] = new Room(
            name: $roomName,
            path: $tempRoom->path,
            requiredRole: $_SESSION["user"]["role"]
        );
    }
}
function executeLs()
{
    $tempRoom = getRoom($_SESSION["tokens"]["path"][0], true);
    $lsArray = array_merge(array_keys($tempRoom->doors), array_keys($tempRoom->items));
    $_SESSION["stdin"] = $lsArray;
    $_SESSION["response"] = "- " . implode(", ", $lsArray);
}

function executePwd()
{
    $_SESSION["response"] = implode("/", $_SESSION["curRoom"]->path);
}

function executeRm()
{
    for ($i = 0; $i < count($_SESSION["tokens"]["path"]); $i++)
    {
        deleteElement($_SESSION["tokens"]["path"][$i]);
    }
}

function executeCp()
{
    $destinationRoom = getRoom($_SESSION["tokens"]["path"][1]);
    $cpItem = getRoomOrItem($_SESSION["tokens"]["path"][0]);

    if (is_a($cpItem, Room::class))
    {
        $destinationRoom->doors[$cpItem->name] = clone $cpItem;
        updatePathsAfterMv($destinationRoom);
    }
    else
    {
        $destinationRoom->items[$cpItem->name] = clone $cpItem;
        updateItemPaths($destinationRoom);
    }
}

function executeMv()
{
    executeCp();
    deleteElement($_SESSION["tokens"]["path"][0], false);
}

function executeCat()
{
    $catItem = &getItem($_SESSION["tokens"]["path"][0]);
    if (is_a($catItem, SCROLL::class))
    {
        $catItem->openScroll();
    }
    else if (is_a($catItem, LOG::class))
    {
        $_SESSION["response"] = $catItem->content;
    }
    else
    {
        throw new Exception("item not readable");
    }
}

function executeGrep()
{
    $grepElement = getRoomOrItem($_SESSION["tokens"]["path"][0]);
    $matchingLines = [];
    $searchMatching = true;
    $searchRecursive = false;
    $caseInsensitive = false;

    foreach ($_SESSION["tokens"]["options"] as $flag)
    {
        match ($flag)
        {
            "-v" => $searchMatching = false,
            "-r" => $searchRecursive = true,
            "-i" => $caseInsensitive = true,
        };
    }

    if (is_a($grepElement, Room::class))
    {
        $matchingLines = grepDirectory(
            room: $grepElement,
            condition: $_SESSION["tokens"]["strings"][0],
            searchMatching: $searchMatching,
            searchRecursive: $searchRecursive,
            caseInsensitive: $caseInsensitive,
        );
    }
    else
    {
        $matchingLines = grepItem(
            $grepElement,
            $_SESSION["tokens"]["strings"][0]
        );
    }

    foreach ($matchingLines as $key => $line)
        $_SESSION["response"] .= $key . " " . $line;
}

function executeExecute()
{
    if (strncmp($_SESSION["tokens"]["command"], "./", 2) == 0)
    {
        $itemExec = &getItem(explode("/", substr($_SESSION["tokens"]["command"], 2)));
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

function executeEcho()
{
    $_SESSION["stdin"] = $_SESSION["tokens"]["command"];
    $_SESSION["response"] = substr($_POST["command"], 5);
}

function executeFind()
{
    // $_SESSION =
}
