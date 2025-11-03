<?php
class Room
{
    public $name;
    public $parentRoom;
    public array $doors = [];
    public array $elements = [];



    function __construct($name, $parentRoom)
    {
        $this->name = $name;
        $this->parentRoom = $parentRoom;
    }

    function _cd($newDir)
    {
        $pathArray = explode("/", $newDir);
        for ($i = 0; $i < count($pathArray); $i++) {
            switch ($pathArray[$i]) {
                case ".": {
                    if (count($_SESSION["currentDirectory"]) != 1) {
                        array_pop($_SESSION["currentDirectory"]);
                    }
                    break;
                }
                default: {
                    if (in_array($pathArray[$i], $this->doors)) {
                        $_SESSION["currentDirectory"][] = $pathArray[$i];
                    } else {
                        return "this path does not exist";
                    }
                }
            }
        }
    }
    function _mkdir($newDoor)
    {
        $tempRoom =& $_SESSION["map"];
        foreach ($_SESSION["currentDirectory"] as $seg) {
            $tempRoom =& $tempRoom["doors"][$seg];
        }
        $tempRoom["doors"][] = [$newDoor];
    }
    function getCurRoom(){
        return $this -> getRoomByPath($_SESSION["currentDirectory"]);
    }
    function getRoomByPath($path)
    {
        $tempRoom =& $_SESSION["map"];
        $pathArray = explode("/", trim($path ?? ""));

        foreach ($_SESSION["currentDirectory"] as $seg) {
            $tempRoom =& $tempRoom["doors"][$seg];
        }
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
                        throw(new Exception("directory not found"));
                    }
                }
            }
        }
        echo json_encode($tempRoom);
        return $tempRoom;
    }
}
?>