<?php

declare(strict_types=1);

$input = trim($_POST["command"] ?? "");
$response = "unknown spell";
$inputDirectory = implode("/", $_SESSION["curRoom"]->path);

echo "map:<br>" . json_encode($_SESSION["map"]) . "<br>";
echo "cur: <br>" . json_encode($_SESSION["curRoom"]) . "<br>";
try {
    $inputArray = explode(" ", $input);
    echo "<br>" . json_encode($inputArray) . "<br>";
    switch ($inputArray[0]) {
        case "cd": {
            $pathArray = explode("/", $inputArray[1]);
            $index = 0;
            $pathLength = sizeof($pathArray);
            echo json_encode($pathArray);
            switch ($pathArray[0]) {
                case "hall": {
                    moveToPath($inputArray);
                    break;
                }
                case '..': {
                    echo "in . path!<br>";
                    while ($pathArray[$index] == '..' && $index < $pathLength) {
                        $index++;
                        echo "index: $index <br>";
                    }
                    echo "calling moveToPath with " . json_encode(array_slice($_SESSION["curRoom"]->path, 0, count($_SESSION["curRoom"]->path) - $index)) . "<br>";
                    moveToPath(array_slice($_SESSION["curRoom"]->path, 0, count($_SESSION["curRoom"]->path) - $index));
                }
                default: {
                    $tempRoom =& $_SESSION["curRoom"];
                    for ($i = $index; $i < $pathLength; $i++) {
                        echo "comparing if $pathArray[$i] exists in " . json_encode($_SESSION["curRoom"]->doors);
                        $pathFound = false;
                        for ($j = 0; $j < count($tempRoom -> doors); $j++) {
                            echo "<br>comparing $pathArray[$i] to " . $tempRoom -> doors[$j] -> name . "<br>";
                            if ($pathArray[$i] == $tempRoom -> doors[$j] -> name) {
                                $tempRoom =& $tempRoom -> doors[$j];
                                echo "found!!!";
                                $pathFound = true;
                                break;
                            }
                        }
                        if (!$pathFound) {
                            throw (new Exception(message: "invalid path provided"));
                        }
                    }
                    $_SESSION["curRoom"] =& $tempRoom;

                }
            }
            $response = "moved";
            break;
        }
        case "mkdir": {
            if (sizeof($inputArray) <= 1 && trim($inputArray[1] ?? "") == "") {
                $response = "no directory name provided";
                break;
            }
            $response = "";

            array_push($_SESSION["curRoom"]->doors, new Room($inputArray[1]));
            break;
        }
        case "ls": {
            $tempRoomArray = [];
            foreach ($_SESSION["curRoom"]->doors as $door) {
                $tempRoomArray[] = $door->name;
            }
            $response = "- " . implode(", ", $tempRoomArray);
            break;
        }
        case "pwd": {
            $response = implode("/", $_SESSION["curRoom"]->path);
            break;
        }
        case "rm": {
            if ("") {

            }
        }
    }
} catch (Exception $e) {
    $response = $e->getMessage();
}
$_SESSION["history"][] =
    [
        "directory" => $inputDirectory,
        "command" => $input,
        "response" => $response
    ];
function moveToPath($path)
{
    echo "count: " . count($path);
    $tempRoomRef =& $_SESSION["map"];
    for ($i = 1; $i < count($path); $i++) {
        echo "<br>i: $i, path: $path[$i]";
        for ($j = 0; $j <  count($tempRoomRef -> doors); $j++) {
            echo "<br>comparing $path[$i] to " . $tempRoomRef-> doors[$j] -> name . "<br>";
            if ($path[$i] == $tempRoomRef->doors[$j] -> name) {
                $tempRoomRef =& $tempRoomRef -> doors[$j];
                echo "found!!!";
                continue 2;
            }
        }
    }
    echo "<br>curRoom changing";
    $_SESSION["curRoom"] =& $tempRoomRef;
}
?>