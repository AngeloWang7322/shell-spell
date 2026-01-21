<?php
class Item
{
    public string $name;
    public string $baseName;
    public ItemType $type;
    public Role $requiredRole;
    public array $path;
    public string $content = "";
    public string $timeOfLastChange;

    public function __construct(
        $name,
        $baseName,
        $path,
        $requiredRole = Role::WANDERER,
        $content = "gibberish",
        $curDate = true,
        $date = "",
    )
    {
        $this->name = $name;
        $this->baseName = $baseName;
        $this->requiredRole = $requiredRole;
        $this->path = $path;
        $this->content = $content;
        $this->timeOfLastChange = $date;
        if (!$date == "")
        {
            $this->timeOfLastChange = $date;
        }
        else
        {
            $this->timeOfLastChange = generateDate($curDate);
        }

        if (empty($name))
        {
            $this->name = $baseName . "." . $this->type->value;
        }
        else
        {
            $this->name = $name;
        }
        array_push($this->path, $this->name);
    }
}
class Scroll extends Item
{
    public bool $isOpen = false;
    public function __construct(
        $name,
        $baseName,
        array $path = [],
        $requiredRole = Role::WANDERER,
        string $content = "",
        $curDate = true,
        $date = "",
    )
    {
        $this->type = ItemType::SCROLL;

        parent::__construct(
            $name,
            $baseName,
            $path,
            $requiredRole,
            $content,
            $curDate,
            $date,
        );
    }
    public static function fromArray(array $data)
    {
        $requiredRole = ROLE::from($data["requiredRole"]);
        return new self(
            name: $data['name'],
            baseName: $data["baseName"],
            path: pathFromArray($data["path"]),
            requiredRole: $requiredRole,
            content: $data['content'],
            date: $data["timeOfLastChange"],
        );
    }
    function openScroll()
    {
        $_SESSION["openedScroll"]["header"] = $this->name;
        $_SESSION["openedScroll"]["content"] = $this->content;
        $_SESSION["openedScroll"]["path"] = $this->path;
    }
}
class Alter extends Item
{
    public bool $isActive;
    public $requiredElements;
    public Room $newDoor;

    public function __construct(
        $name,
        $baseName,
        array $path = [],
        $requiredRole = Role::WANDERER,
        $newDoor,
        $content = "",
        $isActive = true,
        $requiredElements = [],
        $curDate = false,
        $date = "",
    )
    {
        $this->type = ItemType::ALTER;
        $this->isActive = $isActive;
        $this->requiredElements = $requiredElements;
        $this->newDoor = $newDoor;

        parent::__construct(
            $name,
            $baseName,
            $path,
            $requiredRole,
            $content,
            $curDate,
            $date,
        );
    }
    public function executeAction()
    {
        if (!$this->isActive)
        {
            return;
        }
        foreach ($this->requiredElements as $element)
        {

            if (!array_key_exists($element, $_SESSION["curRoom"]->items) || $_SESSION["curRoom"]->items[$element]->requiredRole != $this->requiredRole)
            {
                throw new Exception("conditions demanded by the alter not met.");
            }
        }

        $_SESSION["curRoom"]->doors[$this->newDoor->name] = $this->newDoor;
        $this->isActive = false;

        if (!empty($this->spellReward))
        {
        }
        if (!empty($this->xpReward))
        {
        }
    }
    public static function fromArray(array $data)
    {
        return new self(
            name: $data['name'],
            baseName: $data["baseName"],
            path: pathFromArray($data["path"]),
            requiredRole: ROLE::from($data["requiredRole"]),
            content: $data["content"],
            isActive: $data["isActive"],
            requiredElements: $data["requiredElements"],
            newDoor: Room::fromArray($data["newDoor"]),
            date: $data["timeOfLastChange"],
        );
    }
}
class Spell extends Item
{
    public Commands $spellReward;
    public string $key;
    public function __construct(
        $name,
        $baseName,
        $path,
        $requiredRole = Role::WANDERER,
        $spellReward,
        $key = NULL,
        $curDate = true,
        $date = ""
    )
    {
        $this->type = ItemType::SPELL;
        $this->spellReward = $spellReward;
        $this->key = empty($key) ? $spellReward->value : $key;

        parent::__construct(
            $name,
            $baseName,
            $path,
            $requiredRole,
            "",
            $curDate,
            $date,
        );
    }
    public static function fromArray(array $data)
    {
        $requiredRole = Role::from($data["requiredRole"]);
        $spellReward = Commands::from($data["spellReward"]);
        return new self(
            name: $data['name'],
            baseName: $data["baseName"],
            path: pathFromArray($data["path"]),
            requiredRole: $requiredRole,
            spellReward: $spellReward,
            key: $data["key"],
            date: $data["timeOfLastChange"],
        );
    }
}
function pathFromArray($path)
{
    if (count($path) > 1)
    {
        $path = array_slice($path, 0, -1);
    }
    return $path;
}
function generateDate($curDate)
{
    if ($curDate)
    {
        return date("Y-m-d H:i:s", NULL);
    }
    return date("Y-m-d H:i:s", mktime(
        rand(0, 24),
        rand(0, 60),
        rand(0, 60),
        rand(1, 12),
        rand(1, 30),
        rand(1980 - (rand(1, 30)), 1980),
    ));
}
