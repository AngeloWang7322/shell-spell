<?php
class Room
{
    public $name;
    public $path;
    public array $doors = [];
    public array $items = [];
    public Role $requiredRole;

    function __construct($name, $requiredRole = Role::WANDERER, array $path = [])
    {
        $this->name = $name;
        $this->path = empty($path) ? ["hall"] : ["hall"]+ $path ;
        array_push($this->path, $name);
        $this->requiredRole = $requiredRole;
    }
}
enum Role: string {
    case WANDERER = "wanderer";
    case APPRENTICE = "apprentice";
    case ARCHIVIST = "archivist";
    case CONJURER = "conjurer";
    case ROOT = "root";
}
?>