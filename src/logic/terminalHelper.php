<?php

declare(strict_types=1);
function checkAndHandleSpecialCases()
{
    if (!empty($_SESSION["promptData"]))
    {
        handlePrompt();
    }
    else if (strstr($_POST["command"], "|"))
    {
        $_SESSION["pipeCount"]++;
        startCommandChain("|");
        $_SESSION["pipeCount"]--;
    }
    else if (strstr($_POST["command"], "&&"))
    {
        startCommandChain("&&");
    }
}
function handlePrompt()
{
    $answer = $_POST["command"];

    switch (true)
    {
        case (in_array($_POST["command"], [$_SESSION["promptData"]["options"][0], ""])):
            {
                executeCommand();
                $answer = "y";
            }
        case (in_array($_POST["command"], ["n", "N"])):
            {
                $_SESSION["preserveState"] = true;
                editLastHistory("<br> " . $answer);
                cleanUp();
                throw new Exception("", 0);
            }
        default:
            {
                editLastHistory("<br>" . $_POST["command"] . "<br>" . implode("/", $_SESSION["promptData"]["options"]));
                $_SESSION["preserveState"] = true;
                throw new Exception("", 0);
            }
    }
}
function startCommandChain($seperator)
{
    $tempInput = $_POST["command"];
    $needlePos = strrpos($tempInput, $seperator);
    $beforePipe = trim(substr($tempInput, 0,$needlePos, ));
    $afterPipe = trim(substr($tempInput, $needlePos +strlen($seperator) + 1));

    $_POST["command"] = $beforePipe;
    startTerminalProcess();

    $_SESSION["tokens"] = [];
    $_POST["command"] = $afterPipe;
}
function prepareCommandExecution()
{
    if ($_POST["command"] == "") header("Location: " . $_SERVER["REQUEST_URI"]);

    getCommand(explode(" ", trim($_POST["command"]))[0])->parseInput();
}

function executeCommand()
{
    ("execute" . $_SESSION["tokens"]["command"])();
}
function editLastHistory($string)
{
    $lastHistoryEntry = end($_SESSION["history"]);
    $lastHistoryEntry["response"] .=  $string;
    array_pop($_SESSION["history"]);
    array_push($_SESSION["history"], $lastHistoryEntry);
}
function handleException(Exception $e)
{
    editMana($e->getCode());
    $_SESSION["response"] = $e->getMessage();
}
function closeProcess()
{
    if (!mustPreserveState())
    {
        writeResponse();
        cleanUp();
    }
}
function writeResponse()
{
    if (mustPreserveState())
    {
        editLastHistory($_SESSION["response"]);
    }
    else
    {
        $_SESSION["history"][] = [
            "directory" => $_POST["baseString"],
            "command" => $_SESSION["inputCommand"],
            "response" => $_SESSION["response"]
        ];
        if (count($_SESSION["history"]) >= 15)
        {
            $_SESSION["history"] = array_slice($_SESSION["history"], 1, 15);
        }
    }
}
function cleanUp()
{

    // $_SESSION["tokens"]["path"][0] = [];
    $_SESSION["tokens"]["command"] = "";
    $_SESSION["tokens"]["path"] = [];
    $_SESSION["tokens"]["options"] = [];
    $_SESSION["tokens"]["misc"] = [];
    $_SESSION["inputCommand"] = "";
    $_SESSION["response"] = "";
    $_SESSION["stdin"] = [];

    $_SESSION["pipeCount"] = 0;
    unset(
        $_SESSION["promptData"],
    );
}
function mustPreserveState()
{
    return isset($_SESSION["pipeCount"])
        && $_SESSION["pipeCount"] > 0
        || !empty(($_SESSION["promptData"]));
}

function getRoomOrItem($path, $tempRoom = null): mixed
{
    try
    {
        $tempRoom = &getRoom($path);
        return $tempRoom;
    }
    catch (Exception $e)
    {
        return getItem($path);
    }
}

function &getRoom($path, $rankRestrictive = false): Room
{
    $index = 0;
    $tempRoom = &$_SESSION["curRoom"];

    if (empty($path))
    {
        return $tempRoom;
    }

    switch ($path[0])
    {
        case "hall":
            {
                return getRoomAbsolute($path);
            }
        case '..':
            {
                if ($_SESSION["curRoom"]->name == "hall")
                {
                    throw new Exception("invalid path");
                }
                while ($index < count($path) && $path[$index] == '..')
                {
                    $index++;
                }
                $tempRoom = &getRoomAbsolute(array_slice($_SESSION["curRoom"]->path, 0, -$index), $rankRestrictive);
            }
        default:
            {
                if ($index == count($path))
                {
                    return $tempRoom;
                }
                return getRoomRelative(array_slice($path, $index), $tempRoom);
            }
    }
}
function &getRoomAbsolute($path, $rankRestrictive = false): Room
{
    $tempRoom = &$_SESSION["map"];
    for ($i = 1; $i < count($path); $i++)
    {
        if (in_array($path[$i], array_keys($tempRoom->doors)))
        {
            if ($rankRestrictive && $_SESSION["user"]["role"]->isLowerThan($tempRoom->doors[$path[$i]]->requiredRole))
            {
                throw (new Exception("rank too low"));
            }
            $tempRoom = &$tempRoom->doors[$path[$i]];
        }
        else
        {
            throw (new Exception("path not found absolute"));
        }
    }
    return $tempRoom;
}
function &getRoomRelative($path, $rankRestrictive = false): Room
{
    $tempRoom = &$_SESSION["curRoom"];

    for ($i = 0; $i < count($path); $i++)
    {
        if (in_array($path[$i], array_keys($tempRoom->doors)))
        {
            if ($rankRestrictive && $_SESSION["user"]["role"]->isLowerThan($tempRoom->doors[$path[$i]]->requiredRole))
            {
                throw (new Exception("rank too low"));
            }
            $tempRoom = &$tempRoom->doors[$path[$i]];
        }
        else
        {
            throw (new Exception("path not found relative"));
        }
    }
    return $tempRoom;
}
function &getItem($path)
{
    if (count($path) > 1)
    {
        $tempRoom = &getRoom(array_slice($path, 0, count($path) - 1));
    }
    else
    {
        $tempRoom = &$_SESSION["curRoom"];
    }

    if (in_array(end($path), array_keys($tempRoom->items)))
    {
        return $tempRoom->items[$path[count($path) - 1]];
    }
    else
    {
        throw new Exception("item not found");
    }
}

function deleteElement($path, $rankRestrictive = true)
{
    if (count($path) > 1)
    {
        $tempRoom = &getRoom(array_slice($path, 0, -1));
    }
    else
    {
        $tempRoom = &$_SESSION["curRoom"];
    }

    if (in_array(end(array: $path), array_keys($tempRoom->doors)))
    {
        if (!roleIsHigherThanRoomRecursive($_SESSION["user"]["role"], getRoom($path)) && $rankRestrictive)
        {
            throw new Exception("rank too low");
        }
        unset($tempRoom->doors[end($path)]);
    }
    else if (
        in_array(end($path), array_keys($tempRoom->items))
    )
    {
        if ($_SESSION["user"]["role"]->isLowerThan($tempRoom->items[end($path)]->requiredRole) && $rankRestrictive)
        {
            throw new Exception("rank too low");
        }
        unset($tempRoom->items[end($path)]);
    }
    else
    {
        throw new Exception("element not found");
    }
}

function roleIsHigherThanRoomRecursive(Role $role, &$room)
{
    if ($role->isLowerThan($room->requiredRole))
    {
        return false;
    }
    foreach ($room->doors as &$door)
    {
        if (!roleIsHigherThanRoomRecursive($role, $door))
        {
            return false;
        }
    }
    return true;
}

function updatePathsAfterMv(&$room)
{
    foreach ($room->doors as &$door)
    {
        $path = $room->path;
        foreach ($door->items as &$item)
        {
            $item->path = array_merge($path, (array) $item->name);
        }
        $door->path = array_merge($path, array($door->name));
        updatePathsAfterMv($door);
    }
}
function updateItemPaths(&$room)
{
    foreach ($room->items as $item)
    {
        $item->path = array_merge($room->path, (array) $item->name);
    }
}
function editMana($amount)
{
    $_SESSION["curMana"] -= $amount;
}
function pushNewLastPath(array $newPath)
{
    $lastPathCount = count($_SESSION["lastPath"]);
    if ($lastPathCount > 10)
    {
        for ($i = 0; $i < $lastPathCount - 1; $i++)
        {
            $_SESSION["lastPath"][$i] = $_SESSION["lastPath"][$i + 1];
        }
        array_pop($_SESSION["lastPath"]);
    }

    array_push($_SESSION["lastPath"], $newPath);
}
function getLsArray($tempRoom)
{
    $tempLsArray = [];
    if (in_array("-l", $_SESSION["tokens"]["options"]))
    {
        foreach (array_merge($tempRoom->doors, $tempRoom->items) as $element)
        {
            $tempEntry = [];
            $tempEntry[0] = colorizeString($element->requiredRole->value);
            $tempEntry[1] = $element->timeOfLastChange;
            $tempEntry[2] = $element->name;

            array_push($tempLsArray, $tempEntry);
        }

        $finalArray = array_fill(0, count($tempLsArray), "");

        for ($i = 0; $i < 3; $i++)
        {
            $longest = 5;
            for ($j = 0; $j < count($tempLsArray); $j++)
            {
                if (strlen($tempLsArray[$j][$i]) + 12 > $longest)
                {
                    $longest = strlen($tempLsArray[$j][$i]) + 15 ;
                }
                $finalArray[$j] .= $tempLsArray[$j][$i] . " ";
            }
            for ($j = 0; $j < count($finalArray); $j++)
            {
                $finalArray[$j] .= spaceOf((int)(($longest - strlen($tempLsArray[$j][$i])) * 0.6));
            }
        }
        $_SESSION["stdin"] = $finalArray;
        $_SESSION["response"] = implode("<br> ", $finalArray);
    }
    else
    {
        $finalArray = array_merge(array_keys($tempRoom->doors), array_keys($tempRoom->items));
        $_SESSION["stdin"] = $finalArray;
        $_SESSION["response"] = implode(", ", $finalArray);
    }
}
function grepDirectory(
    $room,
    $condition,
    $searchMatching = true,
    $searchRecursive = false,
    $isCaseInsensitive = false,
)
{
    $grepOutput = [];

    foreach ($room->items as $item)
    {
        $grepOutput = array_merge($grepOutput, grepText(
            $item->content,
            $condition,
            $item->path,
            $searchMatching,
            $isCaseInsensitive
        ));
    }
    if ($searchRecursive)
    {
        foreach ($room->doors as $door)
        {
            $grepOutput = array_merge($grepOutput, grepDirectory(
                room: $door,
                condition: $condition,
                searchMatching: $searchMatching,
                searchRecursive: $searchRecursive,
                isCaseInsensitive: $isCaseInsensitive,
            ));
        }
    }
    return $grepOutput;
}
function grepText(
    $content,
    string $condition,
    $path = [],
    $searchMatching = true,
    $isCaseInsensitive = false,
)
{
    $matchingLines = [];
    $contentLen = strlen($content);
    $lineCounter = 0;
    $strOffset = -1;

    for ($i = 0; $i < $contentLen; $i++)
    {
        if ($content[$i] == "." || $i == $contentLen - 1)
        {
            $tempLine = substr($content, $strOffset + 1, $i - $strOffset);
            if (grepLine(
                $tempLine,
                $condition,
                $isCaseInsensitive,
                $searchMatching,
            ))
            {
                $matchingLines[implode('/', $path) . "&nbsp" . $lineCounter . ":"] = $tempLine;
            }

            $strOffset = $i + 1;
            $lineCounter++;
        }
    }

    return $matchingLines;
}

function grepLine(
    $tempLine,
    $condition,
    $isCaseInsensitive = false,
    $searchMatching = true,
)
{

    if ($isCaseInsensitive)
    {
        if ((bool)stristr($tempLine, (string)$condition) == $searchMatching)
        {
            return true;
        }
    }
    else
    {
        if ((bool)strstr($tempLine, (string)$condition) == $searchMatching)
        {
            return true;
        }
    }
    return false;
}
function countNotEmpty($array)
{
    $counter = 0;
    foreach ($array as $element)
    {
        if (!empty($element))
        {
            $counter++;
        }
    }
    return $counter;
}

function checkIfNamesExists(array $names, $hayStack): bool
{
    foreach ($names as $name)
    {
        if (array_key_exists($name, $hayStack));
        {
            return true;
        }
    }
    return false;
}

function createPrompt($prompt, $validAnswers = ["y", "n"])
{
    $_SESSION["promptData"]["prompt"] = $prompt . "<br>" . implode("/", $validAnswers) . "&nbsp - &nbsp DEFAULT: " . $validAnswers[0];
    $_SESSION["promptData"]["options"] = ["y", "n"];
    $_SESSION["response"] = $_SESSION["promptData"]["prompt"];

    throw new Exception($prompt, 0);
}
function isNameValid($name, $suffix)
{
    $illegalBaseNames = ["..",];
    if (!str_ends_with($name, $suffix)) return false;

    $baseName = substr($name, 0, strlen($suffix));
    return !in_array($baseName, $illegalBaseNames);
}

function colorizeString($string, $class = "")
{
    if ($class == "") $class = $string;
    return "<span class='" . $class . "'>" . $string . "</span>";
}

function spaceOf($length)
{
    $space = "";
    for ($i = 0; $i < $length; $i++)
    {
        $space .= "&nbsp ";
    }
    return $space;
}
