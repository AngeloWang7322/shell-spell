<?php

declare(strict_types=1);

$response = "";
$inputDirectory = implode("/", $_SESSION["curRoom"]->path);

// echo "map:<br>" . json_encode($_SESSION["map"]) . "<br>";
// echo "curRoom: <br>" . json_encode($_SESSION["curRoom"]) . "<br>";
// echo "<br>items: " . json_encode($_SESSION["curRoom"] ->items);
try {
    if (empty($_POST["command"])) {
        return;
    }
    $inputArgs = organizeInput(explode(" ", $_POST["command"]));
    $inputPathLength = count($inputArgs["path"]);
    // echo "<br>" . json_encode($inputArgs) . "<br>";
    switch ($inputArgs["command"]) {
        case "cd": {
                if (count($inputArgs["path"]) == 0) {
                    throw new Exception("no path provided");
                }
                $_SESSION["user"]->curMana -= (count($inputArgs["path"]) - 1) * 2;
                $_SESSION["curRoom"] = &getRoom($inputArgs["path"]);
                break;
            }
        case "mkdir": {
                if (empty($inputArgs["path"]) || end($inputArgs["path"]) == "") {
                    throw new Exception("no directory name provided");
                }
                $roomName = end($inputArgs["path"]);
                $tempRoom = &getRoom(array_slice($inputArgs["path"], 0, length: $inputPathlength - 1));
                $tempRoom->doors[$roomName] = new Room($roomName);
                break;
            }
        case "ls": {
                $lsArray = [];

                $tempRoom = getRoom($inputArgs["path"]);
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
                }
                else if ($inputArgs["path"][0] == $inputArgs["path_2"][0])
                {
                    throw new Exception ("cannot move room into itsself");
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
                $_SESSION["openedScroll"]->header = $catItem->name; 
                $_SESSION["openedScroll"]->content = $catItem->content;
                $_SESSION["openedScroll"]->isOpen = true;
                break;
            }
        default: {
            // echo "<br>substr: " . substr($inputArgs["command"], 2);
                if (strncmp($inputArgs["command"], "./", 2) == 0) {
                    $itemExec = &getItem(explode("/", substr($inputArgs["command"], 2)));
                    $itemExec->executeAction();
                    // echo "<br> items after: " . json_encode($_SESSION["curRoom"] -> items);
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
function &getRoom($path): Room
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
                $tempRoom = &getRoomAbsolute(array_slice($_SESSION["curRoom"]->path, 0, count($tempRoom->path) - $index));
            }
        default: {
                if ($index == count($path)) {
                    return $tempRoom;
                }
                return getRoomRelative(array_slice($path, $index), $tempRoom);
            }
    }
}
function &getRoomAbsolute($path): Room
{
    $tempRoom = &$_SESSION["map"];
    for ($i = 1; $i < count($path); $i++) {
        if (in_array($path[$i], array_keys($tempRoom->doors))) {
            $tempRoom = &$tempRoom->doors[$path[$i]];
        } else {
            throw (new Exception("path not found absolute"));
        }
    }
    return $tempRoom;
}
function &getRoomRelative($path, $tempRoom = null): Room
{
    if ($tempRoom == null) {
        $tempRoom = &$_SESSION["curRoom"];
    }
    for ($i = 0; $i < count($path); $i++) {
        if (in_array($path[$i], array_keys($tempRoom->doors))) {
            $tempRoom = &$tempRoom->doors[$path[$i]];
        } else {
            throw (new Exception("path not found relative"));
        }
    }
    return $tempRoom;
}
function &getItem($path): Item
{
    if (count($path) > 1) {
        $tempRoom = &getRoom(array_splice($path, 0, count($path) - 2));
    } else {
        $tempRoom = &$_SESSION["curRoom"];
    }
    echo "<br>path in getItem: " . json_encode($path); 
    echo "<br>items: " . json_encode($tempRoom -> items); 

    if (in_array(end($path), array_keys($tempRoom->items))) {
        return $tempRoom->items[$path[count($path) - 1]];
    } else {
        throw new Exception("item not found");
    }
}

function deleteElement($path)
{
    if (count($path) > 1) {
        $tempRoom = &getRoom(array_slice($path, 0, count($path) - 1));
    } else {
        $tempRoom = &$_SESSION["curRoom"];
    }

    if (in_array(end($path), array_keys($tempRoom->doors))) {
        unset($tempRoom->doors[end($path)]);
    } else if (in_array(end($path), array_keys($tempRoom->items))) {
        unset($tempRoom->items[end($path)]);
    } else {
        throw new Exception("element not found");
    }
}

function updatePathsAfterMv(&$room) {
    foreach ($room -> doors as &$door)
    {
        $path = $room -> path;
        foreach($door -> items as &$item){
            $item -> path = array_merge($path, $item -> name);
        }
        $door -> path = array_merge($path, array($door -> name));
        updatePathsAfterMv($door);  
    }
}
function updateItemPaths(&$room)
{
    foreach($room -> items as $item)
    {
        $item -> path = array_merge($room-> path, $item -> name);
    }
}
function editMana($amount)
{
    $_SESSION["user"]->curMana -= $amount;
}
