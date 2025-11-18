<?php
class Room
{
    public $name;
    public array $path = [];
    public array $doors = [];
    public array $items = [];
    public Role $requiredRole;

    function __construct($name, $requiredRole = Role::WANDERER, array $path = [])
    {
        $this->name = $name;

        //if statement nur im development noetig
        if ($name != "hall") {
            $this->path = empty($path) ? $_SESSION["curRoom"]->path : $path;

            array_push($this->path, $name);
        }
        $this->requiredRole = $requiredRole;
    }
}
enum Role: string
{
    case WANDERER = "wanderer";
    case APPRENTICE = "apprentice";
    case ARCHIVIST = "archivist";
    case CONJURER = "conjurer";
    case ROOT = "root";
}
