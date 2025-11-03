<?php
declare(strict_types=1);
$input = trim($_POST["command"] ?? "");
$response = "";

echo "<br>terminal.php ack";
try {
    switch ($input) {
        case "cd": {
            echo "<br>cd ack";
            $pathArray = explode("/", trim(substr($input, 3) ?? ""));
            foreach ($pathArray as $path) {
                switch ($path) {
                    case ".": {
                        if (count($_SESSION["currentDirectory"]) != 1) {
                            array_pop($_SESSION["currentDirectory"]);
                        }
                        break;
                    }
                    default: {
                        if (in_array($path, $this->doors)) {
                            $_SESSION["currentDirectory"][] = $path;
                        } else {
                            return "this path does not exist";
                        }
                    }
                }
            }
        }
        case "mv": {

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
    $tempRoom =& $_SESSION["map"];
    $pathArray = explode("/", trim($path ?? ""));
    foreach ($pathArray as $path) {
        if (in_array($path, $tempRoom["doors"])) {
            $tempRoom =& $tempRoom["doors"][$path];
        }
    }
    if ($pathArray[0] == "hall") {
        return $tempRoom;
    } else if (in_array($pathArray[0], $tempRoom["doors"])) {
          foreach ($pathArray as $path) {
            switch ($path) {
                case ".": {
                    if (count($_SESSION["currentDirectory"]) != 1) {
                        $tempRoom =& tempRoom -> parentRoom;
                    }
                    break;
                }
                default: {
                    if (in_array($path, $tempRoom["doors"])) {
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
?>