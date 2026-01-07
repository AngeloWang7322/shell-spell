<?php

declare(strict_types=1);

if (empty($_POST["command"])) {
    return;
}

try {
    $response = "";
    $userRole = $_SESSION["user"]["role"];
    $inputBaseString = $_SESSION["user"]["username"] . "@" . $_SESSION["user"]["role"]->value . "  -" . end($_SESSION["curRoom"]->path) . ">";
    $inputArgs = organizeInput(explode(" ", $_POST["command"]));
    $inputPathLength = count(value: $inputArgs["path"]);

    switch ($inputArgs["command"]) {
        case "cd": {
                switch ($_POST["command"][3]) {
                    case "/": {
                            $_SESSION["curRoom"] = &$_SESSION["map"];
                            pushNewLastPath($_SESSION["curRoom"]->path);
                            break;
                        }
                    case "-": {
                            $_SESSION["curRoom"] = &getRoom(array_pop($_SESSION["lastPath"]));
                            break;
                        }
                    default: {
                            if (count($inputArgs["path"]) == 0) {
                                throw new Exception("no path provided");
                            }
                            pushNewLastPath($_SESSION["curRoom"]->path);

                            $_SESSION["curMana"] -= (count($inputArgs["path"]) - 1) * 2;
                            $_SESSION["curRoom"] = &getRoom($inputArgs["path"], true);
                            break;
                        }
                }
                break;
            }
        case "mkdir": {
                if (empty($inputArgs["path"]) || end($inputArgs["path"]) == "") {
                    throw new Exception("no directory name provided");
                }
                $roomName = end($inputArgs["path"]);
                $tempRoom = &getRoom(array_slice($inputArgs["path"], 0, -1));
                $tempRoom->doors[$roomName] = new Room(name: $roomName, requiredRole: $_SESSION["user"]["role"]);
                break;
            }
        case "ls": {
                $tempRoom = getRoom($inputArgs["path"], true);
                $lsArray = array_merge(array_keys($tempRoom->doors), array_keys($tempRoom->items));
                $response = "- " . implode(", ", $lsArray);
                break;
            }
        case "pwd": {
                $response = implode("/", $_SESSION["curRoom"]->path);
                break;
            }
        case "rm": {
                deleteElement($inputArgs[" path"]);
                break;
            }
        case "mv": {
                $destinationRoom = &getRoom($inputArgs["path_2"]);
                if (empty($inputArgs["path_2"])) {
                    throw new Exception("no source path provided");
                } else if ($inputArgs["path"][0] == $inputArgs["path_2"][0]) {
                    throw new Exception("cannot move room into itsself");
                }

                if (stristr(end($inputArgs["path"]), '.')) {
                    $tempItem = &getItem($inputArgs["path"]);
                    $destinationRoom->items[$tempItem->name] = $tempItem;
                    updateItemPaths($destinationRoom);
                } else {
                    $tempRoom = &getRoom($inputArgs["path"]);
                    $destinationRoom->doors[$tempRoom->name] = $tempRoom;
                    updatePathsAfterMv($destinationRoom);
                }
                deleteElement($inputArgs["path"], false);
                break;
            }
        case "cat": {
                $catItem = &getItem($inputArgs["path"]);
                if (is_a($catItem, Scroll::class)) {
                    $catItem->openScroll();
                    break;
                } else {
                    throw new Exception("item not readable");
                }
            }
        case "man": {
                if (empty($inputArgs["path"])) {
                    $response =
                    "CLI-Game Manual\n\n" .
                    "USAGE:\n" .
                    "  man command\n\n" .
                    "AVAILABLE COMMANDS:\n" .
                    "  cd, ls, mkdir, pwd, rm, mv, cat, man\n\n";
                break;
                }
            
                $command_to_define = strtolower($inputArgs["path"][0]);

                switch($command_to_define) {

                    case "cd": {
                        $response =
                        "cd - change current room\n\n".
                        "USAGE:\n". 
                        "  cd (path)\n". 
                        "  cd /\n".
                        "  cd -\n". 
                        "  cd ..\n". 
                        "DESCRIPTION:\n". 
                        "  you can switch into different rooms";
                    break;
                    }
                    case "ls": {
                        $response =
                        "ls - list all items/rooms\n\n". 
                        "USAGE:\n". 
                        "  ls \n". 
                        "  ls (path)\n";
                    break;
                    }
                    case "mkdir": {
                        $response =
                        "mkdir - create a new room\n\n". 
                        "USAGE:\n". 
                        "  mkdir (path)";
                    break;
                    }
                    case "pwd": {
                        $response = 
                        "pwd - print current room path";
                    break;
                    }
                    case "rm": {
                        $response = 
                        "rm - remove room or item\n\n". 
                        "WARNING:\n". 
                        "  This operation is irreversible";
                    break;
                    }
                    case "mv": {
                        $response = 
                        "mv - move room/item\n\n". 
                        "USAGE:\n". 
                        "  mv (source) (destination)";
                    break;
                    }
                    case "cat": {
                        $response = 
                        "cat - read a scroll\n\n". 
                        "USAGE:\n". 
                        "  cat (scroll)";
                    break;
                    }
                    case "man": {
                        $response = 
                        "man - show manual pages\n\n". 
                        "USAGE:\n". 
                        "  man (command)";
                    break;
                    }
                    default:
                        $response = "No manual entry for: " . $command_to_define;
                     break;
                }
                break;
            }
        default: {
                if (strncmp($inputArgs["command"], "./", 2) == 0) {
                    $itemExec = &getItem(explode("/", substr($inputArgs["command"], 2)));
                    if (is_a($itemExec, Alter::class) || is_a($itemExec, Spell::class)) {
                        $itemExec->executeAction();
                    } else {
                        throw new Exception("item not executable");
                    }
                    break;
                } else {
                    throw new Exception("invalid command");
                }
            }
    }
} catch (Exception $e) {
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
    if (empty($inputArray)) {
        throw new Exception("no command entered");
    }
    $inputArgs = [
        "command" => $inputArray[0],
        "path" => [],
        "path_2" => [],
        "flags" => [],
    ];
    for ($i = 1; $i < count($inputArray); $i++) {
        if ($inputArray[$i][0] == '-') {
            $inputArgs["flags"][] = $inputArray[$i];
        } else {
            if (!empty($inputArgs["path"])) {
                $inputArgs["path_2"] = explode("/", $inputArray[$i]);
            } else {
                $inputArgs["path"] = explode("/", $inputArray[$i]);
            }
        }
    }
    return $inputArgs;
}
function &getRoom($path, $rankRestrictive = false): Room
{
    $index = 0;
    $tempRoom = &$_SESSION["curRoom"];

    if (empty($path)) {
        return $tempRoom;
    }

    switch ($path[0]) {
        case "hall": {
                return getRoomAbsolute($path);
            }
        case '..': {
                if ($_SESSION["curRoom"]->name == "hall") {
                    throw new Exception("invalid path");
                }
                while ($path[$index] == '..' && $index < count($path)) {
                    $index++;
                }
                $tempRoom = &getRoomAbsolute(array_slice($_SESSION["curRoom"]->path, 0, -$index), $rankRestrictive);
            }
        default: {
                if ($index == count($path)) {
                    return $tempRoom;
                }
                return getRoomRelative(array_slice($path, $index), $tempRoom);
            }
    }
}
function &getRoomAbsolute($path, $rankRestrictive = false): Room
{
    $tempRoom = &$_SESSION["map"];
    for ($i = 1; $i < count($path); $i++) {
        if (in_array($path[$i], array_keys($tempRoom->doors))) {
            if ($rankRestrictive && $_SESSION["user"]["role"]->isLowerThan($tempRoom->doors[$path[$i]]->requiredRole)) {
                throw (new Exception("rank too low"));
            }
            $tempRoom = &$tempRoom->doors[$path[$i]];
        } else {
            throw (new Exception("path not found absolute"));
        }
    }
    return $tempRoom;
}
function &getRoomRelative($path, $rankRestrictive = false): Room
{
    $tempRoom = &$_SESSION["curRoom"];

    for ($i = 0; $i < count($path); $i++) {
        if (in_array($path[$i], array_keys($tempRoom->doors))) {
            if ($rankRestrictive && $_SESSION["user"]["role"]->isLowerThan($tempRoom->doors[$path[$i]]->requiredRole)) {
                throw (new Exception("rank too low"));
            }
            $tempRoom = &$tempRoom->doors[$path[$i]];
        } else {
            throw (new Exception("path not found relative"));
        }
    }
    return $tempRoom;
}
function &getItem($path)
{
    if (count($path) > 1) {
        $tempRoom = &getRoom(array_slice($path, 0, count($path) - 1));
    } else {
        $tempRoom = &$_SESSION["curRoom"];
    }

    if (in_array(end($path), array_keys($tempRoom->items))) {
        return $tempRoom->items[$path[count($path) - 1]];
    } else {
        throw new Exception("item not found");
    }
}

function deleteElement($path, $rankRestrictive = true)
{
    if (count($path) > 1) {
        $tempRoom = &getRoom(array_slice($path, 0, -1));
    } else {
        $tempRoom = &$_SESSION["curRoom"];
    }

    if (in_array(end(array: $path), array_keys($tempRoom->doors))) {
        if (!roleIsHigherThanRoomRecursive($_SESSION["user"]["role"], getRoom($path)) && $rankRestrictive) {
            throw new Exception("rank too low");
        }
        unset($tempRoom->doors[end($path)]);
    } else if (
        in_array(end($path), array_keys($tempRoom->items))

    ) {
        if ($_SESSION["user"]["role"]->isLowerThan($tempRoom->items[end($path)]->requiredRole) && $rankRestrictive) {
            throw new Exception("rank too low");
        }
        unset($tempRoom->items[end($path)]);
    } else {
        throw new Exception("element not found");
    }
}

function roleIsHigherThanRoomRecursive(Role $role, &$room)
{
    if ($role->isLowerThan($room->requiredRole)) {
        return false;
    }
    foreach ($room->doors as &$door) {
        if (!roleIsHigherThanRoomRecursive($role, $door)) {
            return false;
        }
    }
    return true;
}

function updatePathsAfterMv(&$room)
{
    foreach ($room->doors as &$door) {
        $path = $room->path;
        foreach ($door->items as &$item) {
            $item->path = array_merge($path, (array) $item->name);
        }
        $door->path = array_merge($path, array($door->name));
        updatePathsAfterMv($door);
    }
}
function updateItemPaths(&$room)
{
    foreach ($room->items as $item) {
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
    if ($lastPathCount > 10) {
        for ($i = 0; $i < $lastPathCount - 1; $i++) {
            $_SESSION["lastPath"][$i] = $_SESSION["lastPath"][$i + 1];
        }
        array_pop($_SESSION["lastPath"]);
    }

    array_push($_SESSION["lastPath"], $newPath);
}
