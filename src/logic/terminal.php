<?php

declare(strict_types=1);

if (empty($_POST["command"])) {
    return;
}

try {
    $response = "";
    $userRole = $_SESSION["user"]["role"];
    $inputDirectory = implode("/", $_SESSION["curRoom"]->path);
    $inputArgs = organizeInput(explode(" ", $_POST["command"]));
    $inputPathLength = count(value: $inputArgs["path"]);

    switch ($inputArgs["command"]) {
        case "cd": {
            if (count($inputArgs["path"]) == 0) {
                throw new Exception("no path provided");
            }
            for ($i = 1; $i <= count($inputArgs["path"]); $i++) {
                if ($_SESSION["user"]["role"]->isLowerThan(getRoom(array_slice($inputArgs["path"], 0, $i))->requiredRole)) {
                    $response = "required rank: " . getRoom(array_slice($inputArgs["path"], 0, $i))->requiredRole->value;
                    break 2;
                }
            }
            $_SESSION["curMana"] -= (count($inputArgs["path"]) - 1) * 2;
            $_SESSION["curRoom"] = &getRoom($inputArgs["path"]);
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
            $lsArray = [];

            $tempRoom = getRoom($inputArgs["path"], true);
            if (roleIsHigherThanRoomRecursive($userRole, $tempRoom))
                foreach ($tempRoom->doors as $door) {
                    $lsArray[] = $door->name;
                }
            foreach ($tempRoom->items as $element) {
                $lsArray[] = $element->name;
            }
            $response = "- " . implode(", ", $lsArray);
            break;
        }
        case "pwd": {
            $response = implode("/", $_SESSION["curRoom"]->path);
            break;
        }
        case "rm": {
            deleteElement($inputArgs["path"]);
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
            deleteElement($inputArgs["path"]);
            break;
        }
        case "cat": {
            $catItem = &getItem($inputArgs["path"]);
            if (is_a($catItem, "Scroll")) {
                $catItem->openScroll();
                break;
            } else {
                throw new Exception("item not a scroll");
            }
        }
        default: {
            if (strncmp($inputArgs["command"], "./", 2) == 0) {
                $itemExec = &getItem(explode("/", substr($inputArgs["command"], 2)));
                switch ($itemExec->type) {
                }
                $itemExec->executeAction();
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
        "directory" => $inputDirectory,
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
        $tempRoom = &getRoom(array_splice($path, 0, count($path) - 2));
    } else {
        $tempRoom = &$_SESSION["curRoom"];
    }

    if (in_array(end($path), array_keys($tempRoom->items))) {
        return $tempRoom->items[$path[count($path) - 1]];
    } else {
        throw new Exception("item not found");
    }
}

function deleteElement($path)
{
    if (count($path) > 1) {
        $tempRoom = &getRoom(array_slice($path, 0, -1));
    } else {
        $tempRoom = &$_SESSION["curRoom"];
    }

    if (in_array(end(array: $path), array_keys($tempRoom->doors))) {
        if (!roleIsHigherThanRoomRecursive($_SESSION["user"]["role"], getRoom($path))) {
            throw new Exception("rank too low");
        }
        unset($tempRoom->doors[end($path)]);
    } else if (
        in_array(end($path), array_keys($tempRoom->items))

    ) {
        if ($_SESSION["user"]["role"]->isLowerThan($tempRoom->items[end($path)]->requiredRole)) {
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
            $item->path = array_merge($path, $item->name);
        }
        $door->path = array_merge($path, array($door->name));
        updatePathsAfterMv($door);
    }
}
function updateItemPaths(&$room)
{
    foreach ($room->items as $item) {
        $item->path = array_merge($room->path, $item->name);
    }
}
function editMana($amount)
{
    $_SESSION["curMana"] -= $amount;
}
