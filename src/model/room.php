<?php
class Room
{
    public $name;
    public $path;
    public array $doors = [];
    public array $elements = [];



    function __construct($name)
    {
        $this->name = $name;
        // array_merge($this->path, $_SESSION["curRoom"] -> path);
        $this->path = $_SESSION["curRoom"] -> path ?? ["root"];
        array_push($this->path, $name);
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

  
}
?>