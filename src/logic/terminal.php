<?php

declare(strict_types=1);

use FFI\CData;

function startTerminalProcess()
{
    try
    {
        manageExecution();
    }
    catch (Exception $e)
    {
        if ($e->getCode() == -1) return;
        handleException($e);
    }

    closeProcess();
}

function executeCd()
{
    moveWithCdOptions();
}
function executeMkdir()
{
    for ($i = 0; $i < count($_SESSION["tokens"]["path"]); $i++)
    {
        $roomName = end($_SESSION["tokens"]["path"][$i]);
        $tempRoom = &getRoom(
            array_slice(
                $_SESSION["tokens"]["path"][0],
                0,
                -1
            )
        );

        if (
            in_array(
                $roomName,
                array_keys($tempRoom->doors)
            ) && !isset($_SESSION["promptData"])
        )
        {
            throw new Exception("room already exists");
        }

        $tempRoom->doors[$roomName] = new Room(
            name: $roomName,
            path: $tempRoom->path,
            requiredRank: $_SESSION["gameController"]->userRank
        );
    }
}
function executeLs()
{
    $path = $_SESSION["tokens"]["path"][0] ?? [];

    getLsArray(
        getRoom(
            $path,
            true
        )
    );
}

function executePwd()
{
    $pwd = implode("/", $_SESSION["curRoom"]->path);
    writeOutput(
        $pwd,
        $pwd
    );
}

function executeRm()
{
    deleteElements($_SESSION["tokens"]["path"]);
}

function executeCp()
{
    $matches = getMatchingElements();
    $destRoom = &getRoom($_SESSION["tokens"]["path"][1]);

    copyElementsTo(
        $matches,
        $destRoom
    );
    updatePaths($destRoom);
}

function executeMv()
{
    $matches = getMatchingElements();

    executeCp();
    deleteElements(
        getPathsFromElements($matches),
        false
    );
}

function executeCat()
{
    $catItem = &getItem($_SESSION["tokens"]["path"][0]);

    writeOutput(
        getLinesFromText($catItem->content),
        $catItem->content
    );
}

function executeTouch()
{
    $fileName = array_pop($_SESSION["tokens"]["path"][0]);
    $destRoom = &getRoom($_SESSION["tokens"]["path"][0]);

    if (!isNameValid($fileName, "." . ItemType::SCROLL->value))
        throw new Exception("invalid name given");

    if (key_exists($fileName, $destRoom->items))
        $destRoom->items[$fileName]->timeOfLastChange = generateDate(true);
    else
        $destRoom->items[$fileName] = new Scroll(
            name: $fileName,
            baseName: "",
            path: $destRoom->path,
            requiredRank: $_SESSION["gameController"]->userRank,
            content: "",
            curDate: true
        );
}
function executeGrep()
{
    $matchingLines = callCorrectGrepFunction();

    writeOutput(
        $matchingLines,
        arrayKeyValueToString($matchingLines)
    );
}

function executeExecute()
{
    $itemExec = &getItem(
        explode(
            "/",
            substr(
                $_POST["command"],
                2
            )
        )
    );

    if (is_a($itemExec, Alter::class))
        $_SESSION["gameController"]->levelUpUser($itemExec);
    else
        throw new Exception("item not executable");
}

function executeEcho()
{
    checkForRune();

    writeOutput(
        getLinesFromText($_SESSION["tokens"]["strings"][0]),
        $_SESSION["tokens"]["strings"][0]
    );
}

function executeFind()
{
    $findString = "";
    $findFunction = "";
    $startingRoom = getRoom($_SESSION["tokens"]["path"][0]);
    $matches = [];

    getOptionsFind(
        $findString,
        $findFunction,
    );

    $matches = pathArrayFromElements(
        getElementsFind(
            $startingRoom,
            $findFunction,
            $findString
        )
    );

    writeOutput(
        $matches,
        implode("<br>", $matches)
    );
}

function executeWc()
{
    $lines = getLines();
    $counts = getCounts($lines);

    writeOutput(
        $counts,
        arrayKeyValueToString($counts, " ")
    );
}
function executeHead()
{
    $lines = getLines();
    $lines = getPartialArray($lines);

    writeOutput(
        $lines,
        arrayKeyValueToString($lines, " ")
    );
}
function executeTail()
{
    $lines = getPartialArray(
        getLines(),
        false
    );

    writeOutput(
        $lines,
        arrayKeyValueToString($lines, " ")
    );
}

function executeNano()
{
    $textFile = getItem($_SESSION["tokens"]["path"][0]);

    openScrollIfIsScroll(
        $textFile
    );
}

function executeMan()
{
    $description = getCommand($_SESSION["tokens"]["misc"])->description;

    writeOutput(
        getLinesFromText($description),
        $description
    );
}
