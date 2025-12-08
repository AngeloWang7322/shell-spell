<?php
class Room
{
    public $name;
    public array $path = [];

    /** @var Room[] $doors */

    public array $doors;


    /** @var Item[] $items */
    public array $items = [];

    public ROLE $requiredRole;

    function __construct($name, array $path = [], $doors = [], $items = [], $requiredRole = ROLE::WANDERER)
    {
        $this->name = $name;
        $this->path = $path;
        $this->doors = $doors;
        $this->items = $items;
        //if statement nur im development noetig
        if ($name != "hall") {
            $this->path = empty($path) ? $_SESSION["curRoom"]->path : $path;

            array_push($this->path, $name);
        }
        $this->requiredRole = $requiredRole;
    }
}