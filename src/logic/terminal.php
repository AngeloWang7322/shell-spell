<?php

declare(strict_types=1);

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
            ) && !empty(Controller::$promptData)
            //TODO: check if empty works here
        )
        {
            throw new Exception("room already exists");
        }

        $tempRoom->doors[$roomName] = new Room(
            name: $roomName,
            path: $tempRoom->path,
            requiredRank: $_SESSION["GameState"]->userRank
        );
    }
}
function executeLs()
{
    $path = $_SESSION["tokens"]["path"][0] ?? [];
    StateManager::$stdout = getLsArray(
        getRoom(
            $path,
            true
        )
    );
}

function executePwd()
{
    Controller::$stdout = $_SESSION["curRoom"]->path;
}

function executeRm()
{
    deleteElements($_SESSION["tokens"]["path"]);
}
function executeRmdir()
{
    deleteElements($_SESSION["tokens"]["path"], deleteOnlyRooms: true);
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
    Controller::$stdout = getLinesFromText($catItem->content);
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
            requiredRank: $_SESSION["GameState"]->userRank,
            content: "",
            curDate: true
        );
}
function executeGrep()
{
    $matchingLines = callCorrectGrepFunction();
    Controller::$stdout = $matchingLines;
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
        $_SESSION["GameState"]->levelUpUser($itemExec);
    else
        throw new Exception("item not executable");
}

function executeEcho()
{
    Controller::$stdout = [$_SESSION["tokens"]["strings"][0]];
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

    Controller::$stdout = $matches;
}

function executeWc()
{
    $lines = getLines();
    $counts = getCounts($lines);

    Controller::$stdout = $counts;
}
function executeHead()
{
    $lines = getLines();
    $lines = getPartialArray($lines);

    Controller::$stdout = $lines;
}
function executeTail()
{
    $lines = getPartialArray(
        getLines(),
        false
    );

    Controller::$stdout = $lines;
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
    Controller::$stdout = getLinesFromText($description);
}
