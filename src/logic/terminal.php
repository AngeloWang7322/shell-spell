<?php

declare(strict_types=1);

$input = trim($_POST["command"] ?? "");
$response = "";
$inputDirectory = implode("/", $_SESSION["curRoom"]->path);

$spellCosts = [
    "cd" => 1,
    "ls" => 1,
    "mkdir" => 5,
    "pwd" => 0,
    "rm" => 5,
];

echo "map:<br>" . json_encode($_SESSION["map"]) . "<br>";
echo "curRoom: <br>" . json_encode($_SESSION["curRoom"]) . "<br>";
echo "<br><br> mana: " . $_SESSION["user"]->curMana;
try {
    $inputArray = explode(" ", $input);
    $index = 0;

    echo "<br>" . json_encode($inputArray) . "<br>";
    switch ($inputArray[0]) {
        case "cd": {
                if (count($inputArray) <= 1) {
                    throw new Exception("no path provided");
                }
                $pathArray = explode("/", $inputArray[1]);
                $_SESSION["user"]->curMana -= (count($pathArray) - 1) * 2;
                $_SESSION["curRoom"] = &getRoom($pathArray);
                break;
            }
        case "mkdir": {
                if (sizeof($inputArray) <= 1 && trim($inputArray[1] ?? "") == "") {
                    $response = "no directory name provided";
                    break;
                }
                array_push($_SESSION["curRoom"]->doors, new Room($inputArray[1]));
                break;
            }
        case "ls": {
                $lsArray = [];
                foreach ($_SESSION["curRoom"]->doors as $door) {
                    $lsArray[] = $door->name;
                }
                foreach ($_SESSION["curRoom"]->items as $element) {
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
                $removeRoomIndex = hasElementWithName($_SESSION["curRoom"]->doors, $inputArray[1]);
                if ($removeRoomIndex >= 0) {
                    unset($_SESSION["curRoom"]->doors[$removeRoomIndex]);
                } else {
                    throw new Exception("couldn't find '$inputArray[1]'");
                }
                break;
            }
        default: {
                $item =& getItem(null, $inputArray[0]);
                $item -> executeAction();
                $fileType = stristr($inputArray[0], '.');
            }
    }
} catch (Exception $e) {
    editMana(amount: 10);
    $response = $e->getMessage();
}
$_SESSION["history"][] =
    [
        "directory" => $inputDirectory,
        "command" => $input,
        "response" => $response
    ];

function &getRoom($path, $tempRoom = null): Room
{
    $index = 0;
    if ($tempRoom == null) {
        $tempRoom = $_SESSION["curRoom"];
    }
    switch ($path[0]) {
        case "hall": {
                return getRoomAbsolute($path);
            }
        case '..': {
                if ($_SESSION["curRoom"]->name == "hall") {
                    throw new Exception("invalid path");
                }
                while ($path[$index] == '..' && $index < $path) {
                    $index++;
                }
                $tempRoom = &getRoomAbsolute(array_slice($_SESSION["curRoom"]->path, 0, count($tempRoom->path) - $index));
            }
        default: {
                if ($index == $path) {
                    return $tempRoom;
                }
                return getRoomRelative(array_slice($path, $index), $tempRoom);
            }
    }
}
function &getRoomAbsolute($path): Room
{
    $tempRoom = &$_SESSION["map"];
    $roomIndex = 0;
    for ($i = 1; $i < count($path); $i++) {
        $roomIndex = hasElementWithName($tempRoom->doors, $path[$i]);
        if ($roomIndex >= 0) {
            $tempRoom = &$tempRoom->doors[$roomIndex];
        } else {
            throw (new Exception("path not found"));
        }
    }
    return $tempRoom;
}
function &getRoomRelative($path, $tempRoom = null): Room
{
    $roomIndex = 0;
    if ($tempRoom == null) {
        $tempRoom = &$_SESSION["curRoom"];
    }
    for ($i = 0; $i < count($path); $i++) {
        $roomIndex = hasElementWithName($tempRoom->doors, $path[$i]);
        if ($roomIndex >= 0) {
            $tempRoom = &$tempRoom->doors[$roomIndex];
        } else {
            throw (new Exception("path not found"));
        }
    }
    return $tempRoom;
}
function &getItem($path = null, $itemName): Item
{
    echo "getting item $itemName<br>";
    $tempRoom = &$_SESSION["curRoom"];
    if ($path == null) {
        $path = &$_SESSION["curRoom"]->path;
    } else {
        $tempRoom = &getRoom(array_splice($path, 0, count($path) - 2));
    }
    $elementIndex = hasElementWithName($tempRoom->items, $itemName);
    if ($elementIndex >= 0) {
        return $tempRoom->items[$elementIndex];
    } else {
        throw new Exception("item not found");
    }
}
function hasElementWithName($array, $name)
{
    for ($i = 0; $i < count($array); $i++) {
        // echo "comparing $name with " . $array[$i]->name ."<br>";
        if ($name == $array[$i]->name) {
            return $i;
        }
    }
    return -1;
}
function editMana($amount)
{
    $_SESSION["user"]->curMana -= $amount;
}
