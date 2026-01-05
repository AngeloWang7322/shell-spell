<?php

declare(strict_types=1);

if (empty($_POST["command"]))
{
    return;
}

try
{
    $response = "";
    $userRole = $_SESSION["user"]["role"];
    $inputBaseString = "[ " . $_SESSION["user"]["username"] . "@" . $_SESSION["user"]["role"]->value . "  -" . end($_SESSION["curRoom"]->path) . " ]$&nbsp";
    $inputArgs = organizeInput(explode(" ", $_POST["command"]));

    switch ($inputArgs["command"])
    {
        case "cd":
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
                            if (count($inputArgs["path"][0]) == 0)
                            {
                                throw new Exception("no path provided");
                            }
                            pushNewLastPath($_SESSION["curRoom"]->path);

                            $_SESSION["curMana"] -= (count($inputArgs["path"][0]) - 1) * 2;
                            $_SESSION["curRoom"] = &getRoom($inputArgs["path"][0], true);
                            break;
                        }
                }
                break;
            }
        case "mkdir":
            {
                if (empty($inputArgs["path"][0]))
                {
                    throw new Exception("no directory name provided");
                }
                $roomName = end($inputArgs["path"][0]);
                $tempRoom = &getRoom(array_slice($inputArgs["path"][0], 0, -1));
                $tempRoom->doors[$roomName] = new Room(
                    name: $roomName,
                    path: $tempRoom->path,
                    requiredRole: $_SESSION["user"]["role"]
                );
                break;
            }
        case "ls":
            {
                $tempRoom = getRoom($inputArgs["path"][0], true);
                $lsArray = array_merge(array_keys($tempRoom->doors), array_keys($tempRoom->items));
                $response = "- " . implode(", ", $lsArray);
                break;
            }
        case "pwd":
            {
                $response = implode("/", $_SESSION["curRoom"]->path);
                break;
            }
        case "rm":
            {
                deleteElement($inputArgs["path"][0]);
                break;
            }
        case "mv":
            {
                $destinationRoom = &getRoom($inputArgs["path"][1]);
                if (empty($inputArgs["path"][1]))
                {
                    throw new Exception("no source path provided");
                }
                else if ($inputArgs["path"][0][0] == $inputArgs["path"][1][0])
                {
                    throw new Exception("cannot move room into itsself");
                }

                if (stristr(end($inputArgs["path"][0]), '.'))
                {
                    $tempItem = &getItem($inputArgs["path"][0]);
                    $destinationRoom->items[$tempItem->name] = $tempItem;
                    updateItemPaths($destinationRoom);
                }
                else
                {
                    $tempRoom = &getRoom($inputArgs["path"][0]);
                    $destinationRoom->doors[$tempRoom->name] = $tempRoom;
                    updatePathsAfterMv($destinationRoom);
                }
                deleteElement($inputArgs["path"][0], false);
                break;
            }
        case "cat":
            {
                $catItem = &getItem($inputArgs["path"][0]);
                if (is_a($catItem, SCROLL::class))
                {
                    $catItem->openScroll();
                    break;
                }
                else if (is_a($catItem, LOG::class))
                {
                    $response = $catItem->content;
                    break;
                }
                else
                {
                    throw new Exception("item not readable");
                }
            }
        case "grep":
            {
                $isInverted = false;
                $searchMatching = false;
                $searchRecursive = false;

                foreach ($inputArgs["flags"] as $flag)
                {
                    echo "<br> flag: " . $flag;
                    switch ($flag)
                    {
                        case "-v":
                            {
                                $isInverted = true;
                                break;
                            }
                        case "-i":
                            {
                                $searchMatching = false;
                                break;
                            }
                        case "-r":
                            {
                                $searchRecursive = true;
                                break;
                            }
                        default:
                            {
                                throw new Exception("invalid flag");
                            }
                    }
                }
                $grepElement = &getRoomOrItem($inputArgs["path"][0]);
                $matchingLines;

                if (is_a($grepElement, Room::class))
                {
                    $matchingLines = grepDirectory(
                        room: $grepElement,
                        condition: $inputArgs["strings"][0],
                        isInverted: $isInverted,
                        searchMatching: $searchMatching,
                        searchRecursive: $searchRecursive
                    );
                }
                else
                {
                    $matchingLines = grepItem(
                        $grepElement,
                        $inputArgs["strings"][0]
                    );
                }

                foreach ($matchingLines as $key => $line)
                {
                    $response = $response . $key . " " . $line;
                }
                break;
            }
        default:
            {
                if (strncmp($inputArgs["command"], "./", 2) == 0)
                {
                    $itemExec = &getItem(explode("/", substr($inputArgs["command"], 2)));
                    if (is_a($itemExec, Alter::class) || is_a($itemExec, Spell::class))
                    {
                        $itemExec->executeAction();
                    }
                    else
                    {
                        throw new Exception("item not executable");
                    }
                    break;
                }
                else
                {
                    throw new Exception("invalid command");
                }
            }
    }
}
catch (Exception $e)
{
    editMana(amount: 10);
    $response = $e->getMessage();
}

$_SESSION["history"][] =
    [
        "directory" => $inputBaseString,
        "command" => $_POST["command"],
        "response" => $response
    ];
function organizeInput(array $inputArray)
{
    if (empty($inputArray))
    {
        throw new Exception("no command entered");
    }
    $inputArgs = [
        "command" => $inputArray[0],
        "path" => [],
        "strings" => [],
        "flags" => [],
    ];
    for ($i = 1; $i < count($inputArray); $i++)
    {
        if ($inputArray[$i][0] == '-')
        {
            $inputArgs["flags"][] = $inputArray[$i];
        }
        else if (
            ($inputArray[$i][0] == "'" && substr($inputArray[$i], -1) == "'") ||
            ($inputArray[$i][0] == '"' && substr($inputArray[$i], -1) == '"')
        )
        {
            array_push($inputArgs["strings"], substr($inputArray[$i], 1, -1));
        }
        else
        {
            array_push($inputArgs["path"], explode("/", $inputArray[$i]));
        }
    }
    // echo "<br>inputArgs: ". json_encode($inputArgs);
    return $inputArgs;
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
    $isInverted = false,
    $searchMatching = true,
    $searchRecursive = false
)
{
    $grepOutput = [];

    foreach ($room->items as $item)
    {
        $grepOutput = array_merge($grepOutput, grepItem($item, $condition));
        // echo "<br>matches from item: " . $item->name . json_encode($grepOutput);
    }
    if ($searchRecursive)
    {
        foreach ($room->doors as $door)
        {
            array_merge($grepOutput, grepDirectory(
                room: $door,
                condition: $condition,
                isInverted: $isInverted,
                searchMatching: $searchMatching,
                searchRecursive: $searchRecursive,
            ));
        }
    }
    return $grepOutput;
}
function grepItem($item, string $condition, $searchMatching = true)
{
    $matchingLines = [];
    $lineCounter = 0;
    $strOffset = -1;
    (string)$tempLine = "";
    for ($i = 0; $i < strlen($item->content); $i++)
    {
        if ($item->content[$i] == ".")
        {
            $tempLine = substr($item->content, $strOffset + 1, $i - $strOffset);

            if (stristr($tempLine, (string)$condition) == $searchMatching)
            {
                $matchingLines[implode('/', $item->path) . "&nbsp" . $lineCounter . ":"] = $tempLine . "<br>";
            }

            $strOffset = $i + 1;
            $lineCounter++;
        }
    }
    return $matchingLines;
}
function getRoomOrItem($path, $tempRoom = null): mixed
{
    try
    {
        $tempRoom =& getRoom($path);
        return $tempRoom;
    }
    catch (Exception $e)
    {
        echo "<br> caught! searching for item;";
        return getItem($path);
    }
    // if ($tempRoom == null)
    // {
    //     if(count($path) > 1){
    //         $tempRoom = &getRoom(array_slice($path, 0, -1));
    //     }
    // }

    // if (key_exists(end($path), $tempRoom->doors))
    // {
    //     return $tempRoom->doors[end($path)];
    // }
    // else if (key_exists(end($path), $tempRoom->items))
    // {
    //     return $tempRoom->items[end($path)];
    // }
    // else
    // {
    //     throw new Exception("incorrect path");
    // }
}
