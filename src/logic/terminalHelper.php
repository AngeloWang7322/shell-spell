<?php

declare(strict_types=1);
function checkAndHandleSpecialCases()
{
    if (!empty($_SESSION["prompt"]))
    {
        handlePrompt();
    }
    else if (strstr($_POST["command"], "|"))
    {
        managePipe();
    }
}
function handlePrompt()
{
    $response = $_POST["command"];

    switch (true)
    {
        case (in_array($_POST["command"], [$_SESSION["prompt"]["options"][0], ""])):
            {
                executeCommand();
                $response = "y";
            }
        case (in_array($_POST["command"], ["n", "N"])):
            {
                $_SESSION["preserveState"] = true;
                addToPreviousHistory("<br> " . $response);
                cleanUp();
                throw new Exception("", 0);
            }
        default:
            {
                addToPreviousHistory("<br>" . $_POST["command"] . "<br>" . implode("/", $_SESSION["prompt"]["options"]));
                $_SESSION["preserveState"] = true;
                throw new Exception("", 0);
            }
    }
}
function managePipe()
{
    //check if pipe commands are valid combination
    $_SESSION["isPipe"] = true;

    $afterNeedle = strstr($_POST["command"], "|",);
    $_POST["command"] = strstr($_POST["command"], "|", true);
    startTerminalProcess();
    $_POST["command"] = $afterNeedle;
    startTerminalProcess();

    header("Location: " . $_SERVER["REQUEST_URI"]);
}
function prepareCommandExecution()
{
    if ($_POST["command"] == "") header("Location: " . $_SERVER["REQUEST_URI"]);

    getCommand(explode(" ", trim($_POST["command"]))[0])->interpretInput();
    echo "<br>tokens: " . json_encode($_SESSION["tokens"]);
}

function executeCommand()
{
    ("execute" . $_SESSION["tokens"]["command"])();
    if (isset($_SESSION["isPipe"]))

    {
    }
}
function addToPreviousHistory($string)
{
    $lastHistoryEntry = end($_SESSION["history"]);
    $lastHistoryEntry["response"] .=  $string;
    array_pop($_SESSION["history"]);
    array_push($_SESSION["history"], $lastHistoryEntry);
}
function writeResponse()
{
    if ($_SESSION["preserveState"])
    {
        addToPreviousHistory($_SESSION["response"]);
    }
    else
    {
        $_SESSION["history"][] = [
            "directory" => $_POST["baseString"],
            "command" => $_POST["command"],
            "response" => $_SESSION["response"]
        ];
    }
}
function cleanUp()
{
    $_SESSION["tokens"] = [];
    unset($_SESSION["prompt"]);
    $_SESSION["response"] = "";
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
                while ($path[$index] == '..' && $index < count($path))
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
function grepDirectory(
    $room,
    $condition,
    $searchMatching = true,
    $searchRecursive = false,
    $caseInsensitive = false,
)
{
    $grepOutput = [];

    foreach ($room->items as $item)
    {
        $grepOutput = array_merge($grepOutput, grepItem(
            $item,
            $condition,
            $searchMatching,
            $caseInsensitive
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
                caseInsensitive: $caseInsensitive,
            ));
        }
    }
    return $grepOutput;
}
function grepItem(
    $item,
    string $condition,
    $searchMatching = true,
    $caseInsensitive = false,
)
{
    $matchingLines = [];
    $contentLen = strlen($item->content);
    $lineCounter = 0;
    $strOffset = -1;

    for ($i = 0; $i < $contentLen; $i++)
    {
        if ($item->content[$i] == "." || $i == $contentLen - 1)
        {
            grepLine(
                $item,
                $condition,
                $caseInsensitive,
                $matchingLines,
                $i,
                $strOffset,
                $lineCounter,
                $searchMatching,
            );
        }
    }

    return $matchingLines;
}
function grepLine(
    $item,
    $condition,
    $caseInsensitive = false,
    &$matchingLines,
    $lineStart,
    &$strOffset,
    &$lineCounter,
    $searchMatching
)
{
    $tempLine = substr($item->content, $strOffset + 1, $lineStart - $strOffset);

    if ($caseInsensitive)
    {
        if ((bool)stristr($tempLine, (string)$condition) == $searchMatching)
        {
            $matchingLines[implode('/', $item->path) . "&nbsp" . $lineCounter . ":"] = $tempLine . "<br>";
        }
    }
    else
    {
        if ((bool)strstr($tempLine, (string)$condition) == $searchMatching)
        {
            $matchingLines[implode('/', $item->path) . "&nbsp" . $lineCounter . ":"] = $tempLine . "<br>";
        }
    }

    $strOffset = $lineStart + 1;
    $lineCounter++;
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
    echo "<br>CHECKING";
    foreach ($names as $name)
    {
        echo "<br>checking name: " . $name;
        if (array_key_exists($name, $hayStack));
        {
            return true;
        }
    }
    return false;
}

function createPrompt($prompt, $validAnswers = ["y", "n"])
{
    $_SESSION["prompt"] = [];
    $_SESSION["prompt"]["text"] = $prompt . "&nbsp - &nbsp DEFAULT: " . $validAnswers[0];
    $_SESSION["prompt"]["options"] = ["y", "n"];
    $_SESSION["response"] = $prompt . "&nbsp - &nbsp DEFAULT: " . $validAnswers[0];

    throw new Exception($prompt, 0);
}
