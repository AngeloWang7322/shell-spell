<?php

declare(strict_types=1);
function validateInput()
{
    /*
    PATHS:
        mkdir:  0-1
        ls:     0-1
        rm:     1 
        cd:     1
        grep:   1
        cat:    1
        exe:    1
        cp:     2
        mv:     2
    FLAGS:
        mkdir:  
        ls:     
        rm:      
        cd:     
        grep:   -r, -i, -v
        cat:    
        exe:    
        cp:     
        mv:     
    */

    $paths = $_SESSION["tokens"]["path"];
    $command = $_SESSION["tokens"]["command"];

    switch ($command)
    {
        case "mkdir":
        case "ls":
            {
                if (countNotEmpty($paths) > 1)
                {
                    throw new Exception("incorrect number of paths");
                }
                break;
            }
        case "rm":
        case "cd":
        case "grep":
        case "cat":
            {
                if (countNotEmpty($paths) != 1)
                {
                    throw new Exception("incorrect number of paths");
                }
                break;
            }
        case "mv":
        case "cp":
            {
                if (countNotEmpty($paths) != 2)
                {
                    throw new Exception("incorrect number of paths");
                }

                if ($command == "mv")
                {
                    $tempPath1 = getRoom($_SESSION["tokens"]["path"][0])->path;
                    $tempPath2 = getRoom($_SESSION["tokens"]["path"][1])->path;

                    if (
                        count(array_diff(
                            $tempPath1,
                            array_intersect($tempPath1, $tempPath2)
                        )) == 0
                    )
                    {
                        throw new Exception("cannot move room into itsself");
                    }
                }
                break;
            }
        case "echo":
            {
                if (strlen($_POST["command"]) < 6)
                {
                    throw new Exception("no argument given");
                }
            }
    }
    return;
}
function organizeInput(array $inputArray)
{
    if (countNotEmpty($inputArray) == 0)
    {
        throw new Exception("", 0);
    }
    $_SESSION["inputArray"] = $inputArray;
    $_SESSION["response"] = "";
    
    $tokens = [
        "command" => $inputArray[0],
        "path" => [],
        "strings" => [],
        "flags" => [],
    ];
    if (substr($inputArray[0], 0, 2) == "./")
    {
        $tokens["command"] = "Executable";
    }
    for ($i = 1; $i < count($inputArray); $i++)
    {
        if ($inputArray[$i][0] == '-')
        {
            $tokens["flags"][] = $inputArray[$i];
        }
        else if (
            ($inputArray[$i][0] == "'" && substr($inputArray[$i], -1) == "'") ||
            ($inputArray[$i][0] == '"' && substr($inputArray[$i], -1) == '"')
        )
        {
            array_push($tokens["strings"], substr($inputArray[$i], 1, -1));
        }
        else
        {
            array_push($tokens["path"], explode("/", $inputArray[$i]));
        }
    }
    return $tokens;
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
        echo "<br>trying to get item";
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
            echo "<br> is searching recursive;";
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
