<?php

declare(strict_types=1);
$input = trim($_POST["command"] ?? "");
$response = "unknown spell";

echo "map: " . json_encode($_SESSION["map"]);
try {
    $inputArray = explode(" ", $input);
    echo "<br>" . $inputArray[0] . "<br>";
    switch ($inputArray[0]) {
        case "cd": {

                $tempRoom = &getCurRoom();
                $pathArray = explode("/", trim(substr($input, 3) ?? ""));
                echo "<br>pathArray: " . json_encode($pathArray);
                if ($pathArray[0] == "hall") {
                    $_SESSION["curRoom"] = &getRoomByPath($input);
                } else {
                    foreach ($pathArray as $path) {
                        echo "json: " . json_encode($_SESSION["curRoom"] -> doors);
                        if ($path == ".") {
                            $_SESSION["curRoom"] = &$_SESSION["curRoom"]->parentRoom;
                        } else if (in_array($path, $tempRoom->doors)) {
                            foreach ($tempRoom->doors as $door) {
                                if ($door->getName() == $path) {
                                    $_SESSION["curRoom"] = &$door;
                                    $_SESSION["currentDirectory"][] = $path;
                                    $tempRoom = &$door;
                                    break;
                                }
                            }
                        }
                        else{
                            $response = "no path found";
                            break;
                        }
                    }
                }
                $response = "directory not found";
                break;
            }
        case "mkdir": {
                if (sizeof($inputArray) <= 1 && trim($inputArray[1] ?? "") == "") {
                    $response = "no directory name provided";
                    break;
                }
                $response = "";
                $tempRoom = &getCurRoom();
                $tempRoom->doors[] = new Room($inputArray[1], $tempRoom);
                break;
            }
        case "ls": {
                $tempRoom = &getCurRoom();
                echo json_encode($tempRoom);
                $tempRoomArray = [];

                foreach ($tempRoom->doors as $door) {
                    $tempRoomArray[] = $door->getName();
                }
                $response = "- " . implode(", ", $tempRoomArray);
                break;
            }
        case "pwd": {
                $response = "/" . $_SESSION["currentDirectory"];
                break;
            }
        default: {
            }
    }
} catch (Exception $e) {
    $response = $e->getMessage();
}
$_SESSION["history"][] =
    [
        "directory" => $_SESSION["currentDirectory"],
        "command" => $input,
        "response" => $response
    ];
function getRoomByPath($path)
{
    $tempRoom = &$_SESSION["map"];
    $pathArray = explode("/", trim($path ?? ""));
    foreach ($pathArray as $path) {
        echo json_encode($pathArray);
        if (in_array($path, $tempRoom->doors)) {
            $tempRoom = &$tempRoom->doors[$path];
        }
    }
    if ($pathArray[0] == "hall") {
        return $tempRoom;
    } else if (in_array($pathArray[0], $tempRoom->doors)) {
        foreach ($pathArray as $path) {
            switch ($path) {
                case ".": {
                        if (count($_SESSION["currentDirectory"]) != 1) {
                            // $tempRoom =& tempRoom -> parentRoom;
                        }
                        break;
                    }
                default: {
                        if (in_array($path, $tempRoom->doors)) {
                            $_SESSION["currentDirectory"][] = $path;
                        } else {
                            return "this path does not exist";
                        }
                    }
            }
        }
    } else {
        throw (new Exception("directory not found"));
    }
}

function getCurRoom()
{
    return getRoomByPath($_SESSION["currentDirectory"]);
}
