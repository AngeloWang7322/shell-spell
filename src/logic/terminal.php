<?php

declare(strict_types=1);

function startTerminalProcess()
{
    try
    {
        checkAndHandleSpecialCases();
        prepareCommandExecution();
        // echo "<br> tokens: " . json_encode($_SESSION["tokens"]);
        executeCommand();
    }
    catch (Exception $e)
    {
        handleException($e);
    }

    closeProcess();
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

        if (in_array($roomName, array_keys($tempRoom->doors)) && !isset($_SESSION["promptData"]))
        {
            echo "<br>creating prompt";
            createPrompt($roomName . " exists, are you sure you want to replace it?",);
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
    $path = isset($_SESSION["tokens"]["path"][0]) ? $_SESSION["tokens"]["path"][0] : NULL;
    $tempRoom = getRoom($path, true);
    getLsArray($tempRoom);
}

function executePwd()
{
    $pwd = implode("/", $_SESSION["curRoom"]->path);
    $_SESSION["stdin"] = $pwd;
    $_SESSION["response"] = $pwd;
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
    $_SESSION["response"] = $catItem->content;
    $_SESSION["stdin"] = $catItem->content;
}

function executeTouch()
{
    $fileName = array_pop($_SESSION["tokens"]["path"][0]);

    $destRoom = &getRoom($_SESSION["tokens"]["path"][0]);

    if (!isNameValid($fileName, "." . ItemType::SCROLL->value))
    {
        throw new Exception("invalid name given");
    }
    if (key_exists($fileName, $destRoom->items))
    {
        $touchFile = $destRoom->items[$fileName];
        if (is_a($touchFile, Scroll::class))
        {
            $touchFile->openScroll();
        }
    }
    else
    {
        $destRoom->items[$fileName] = new Scroll(
            name: $fileName,
            baseName: "",
            path: $destRoom->path,
            requiredRole: $_SESSION["user"]["role"]
        );
    }
}
function executeGrep()
{
    $matchingLines = [];
    $searchMatching = true;
    $searchRecursive = false;
    $isCaseInsensitive = false;

    getOptionsGrep(
        $searchMatching,
        $searchRecursive,
        $isCaseInsensitive
    );

    $matchingLines = callCorrectGrepFunction(
        $searchMatching,
        $searchRecursive,
        $isCaseInsensitive
    );

    $_SESSION["stdin"] = $matchingLines;
    foreach ($matchingLines as $key => $line)
        $_SESSION["response"] .= $key . " " . $line . "<br>";
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
    $findString = "";
    $findFunction = "";
    $startingRoom = getRoom($_SESSION["tokens"]["path"][0]);
    $findResult = [];

    getOptionsFind($findFunction, $findString,);

    $findResult = callFunctionOnRoomRec($startingRoom, "findByName", $findFunction, $findString);
    $_SESSION["stdin"] = $findResult;
    $_SESSION["response"] = implode("<br>", $findResult,);
}
