<?php

declare(strict_types=1);
$input = trim($_POST["command"] ?? "");
$response = "unknown spell";

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

            echo "imploded path: " . implode("/", $pathArray) . "<br>";

            switch ($pathArray[0]) {
                case "hall": {
                    moveToPath($inputArray);
                    break;
                }
                case '.': {
                    while ($pathArray[$index] == '.' && $index < $pathLength) {
                        $index++;
                    }
                }
                default: {
                    $tempRoom =& $_SESSION["curRoom"];
                    for ($i = $index; $i < $pathLength; $i++) {
                        echo "comparing if $pathArray[$i] exists in " . json_encode($_SESSION["curRoom"]->doors);
                        $pathFound = false;
                        foreach ($_SESSION["curRoom"]->doors as $door) {
                            echo "<br>comparing $pathArray[$i] to " . $door->name . "<br>";
                            if ($pathArray[$i] == $door->name) {
                                $tempRoom =& $door;
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
            $reponse = "moved?";
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
function moveToPath($path)
{
    foreach ($path as $door) {
        //if sollte nie false returnen, kann spaeter entfernt werden
        $tempRoomRef =& $_SESSION["map"];
        if (key_exists($door, $tempRoomRef->doors)) {
            $tempRoomRef =& $tempRoomRef->doors[$door];
            echo "<br>found door: $door";
        } else {
            echo "invalid path provided";
            throw (new Exception("unexpected: invalid path"));
        }
    }
    $_SESSION["curRoom"] =& $tempRoomRef;
    echo "moveToPath() curRoom reference or object: " . json_encode($_SESSION["curRoom"]);
}

?>