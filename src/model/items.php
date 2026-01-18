<?php
class Item
{
    public string $name;
    public string $baseName;
    public ItemType $type;
    public Role $requiredRole;
    public array $path;
    public string $content = "";

    public function __construct(
        $name,
        $baseName,
        $path,
        $requiredRole = Role::WANDERER,
        $content = "gibberish",
    )
    {
        $this->name = $name;
        $this->baseName = $baseName;
        $this->requiredRole = $requiredRole;
        $this->path = $path;
        $this->content = $content;

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
        string $content = ""
    )
    {
        $this->type = ItemType::SCROLL;

        parent::__construct(
            $name,
            $baseName,
            $path,
            $requiredRole,
            $content
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
            content: $data['content']
        );
    }
    function openScroll()
    {
        $_SESSION["openedScroll"]->header = $this->name;
        $_SESSION["openedScroll"]->content = $this->content;
        $_SESSION["openedScroll"]->isOpen = true;
    }
}
class Alter extends Item
{
    public bool $isActive;
    public $requiredElements;
    public Room $newDoor;
    public string $spellReward;
    public int $xpReward;

    public function __construct(
        $name,
        $baseName,
        array $path = [],
        $requiredRole = Role::WANDERER,
        $isActive = true,
        $requiredElements = [],
        $newDoor,
        $spellReward = "",
        $xpReward = 0
    )
    {
        $this->type = ItemType::ALTER;
        $this->isActive = $isActive;
        $this->requiredElements = $requiredElements;
        $this->newDoor = $newDoor;
        $this->spellReward = $spellReward;
        $this->xpReward = $xpReward;

        parent::__construct(
            $name,
            $baseName,
            $path,
            $requiredRole,
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
            isActive: $data["isActive"],
            requiredElements: $data["requiredElements"],
            newDoor: Room::fromArray($data["newDoor"]),
            spellReward: $data["spellReward"],
            xpReward: $data["xpReward"]
        );
    }
}
class Spell extends Item
{
    public ActionType $action;
    public function __construct(
        $name,
        $baseName,
        $path,
        $action = null,
        $requiredRole = Role::WANDERER
    )
    {
        $this->action = $action;
        $this->type = ItemType::SPELL;

        parent::__construct(
            $name,
            $baseName,
            $path,
            $requiredRole
        );
    }
    public function executeAction()
    {
        $actionFunction = $this->action->value;
        $this->$actionFunction();
    }
    function getMana()
    {
        match ($this->requiredRole)
        {
            ROLE::WANDERER => $_SESSION["curMana"] += 10,
            ROLE::APPRENTICE => $_SESSION["curMana"] += 20,
            ROLE::ARCHIVIST => $_SESSION["curMana"] += 30,
            ROLE::CONJURER => $_SESSION["curMana"] += 40,
            ROLE::ROOT => $_SESSION["curMana"] += 50,
        };
    }
    public static function fromArray(array $data)
    {
        $requiredRole = Role::from($data["requiredRole"]);
        $action = ActionType::from($data["action"]);
        return new self(
            name: $data['name'],
            baseName: $data["baseName"],
            path: pathFromArray($data["path"]),
            action: $action,
            requiredRole: $requiredRole,
        );
    }
}
class Log extends Item
{

    public function __construct(
        $name,
        $baseName,
        $requiredRole = Role::WANDERER,
        string $content,
    )
    {
        $this->name = $name;
        $this->baseName = $baseName;
        $this->requiredRole = $requiredRole;
        $this->content = $content;
        $this->type = ItemType::LOG;
        if (empty($name))
        {
            $this->name = $baseName . "." . ItemType::SPELL->value;
        }
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
