<?php
class Item
{
    public string $name;
    public string $baseName;
    public ItemType $type;
    public Role $requiredRole;
    public function __construct($name, $baseName, $type, $requiredRole = Role::WANDERER)
    {
        $this->name = $name;
        $this->baseName = $baseName;
        $this->type = $type;
        $this->requiredRole = $requiredRole;
        if (empty($name)) {
            $this->name = $baseName . "." . match ($type) {
                ItemType::SCROLL => ItemType::SCROLL->value,
                ItemType::SPELL => ItemType::SPELL->value,
                ItemType::ALTER => ItemType::ALTER->value,
            };
        } else {
            $this->name = $name;
        }
    }
    public static function fromArray(array $data)
    {
        $type = ItemType::from($data["type"]);
        $requiredRole = Role::from($data["requiredRole"]);
        return new self(
            name: $data['name'],
            baseName: $data["baseName"],
            type: $type,
            requiredRole: $requiredRole,
        );
    }
}
class Scroll extends Item
{
    public bool $isOpen = false;
    public string $content;
    public function __construct($name, $baseName, $type, $requiredRole = Role::WANDERER, string $content = "")
    {
        $this->name = $name;
        $this->baseName = $baseName;
        $this->type = $type;
        $this->requiredRole = $requiredRole;
        $this->content = $content;
        if (empty($name)) {
            $this->name = $baseName . "." . ItemType::SCROLL->value;
        }
    }
    public static function fromArray(array $data)
    {
        $type = ItemType::from($data["type"]);
        $requiredRole = ROLE::from($data["requiredRole"]);
        return new self(
            name: $data['name'],
            baseName: $data["baseName"],
            type: $type,
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
    public string $newDoor;
    public string $spellReward;
    public int $xpReward;

    public function __construct($name, $baseName, $type, $requiredRole = Role::WANDERER, $newDoor = "", $isActive = true, $spellReward = "", $xpReward = 0)
    {
        $this->name = $name;
        $this->baseName = $baseName;
        $this->type = $type;
        $this->requiredRole = $requiredRole;
        $this->newDoor = $newDoor;
        $this->isActive = $isActive;
        $this->spellReward = $spellReward;
        $this->xpReward = $xpReward;

        if (empty($name)) {
            $this->name = $baseName . "." . ItemType::ALTER->value;
        }
    }
    public function executeAction()
    {
        //alter execution logic
    }
    public static function fromArray(array $data)
    {
        $type = ItemType::from($data["type"]);
        $requiredRole = ROLE::from($data["requiredRole"]);
        return new self(
            name: $data['name'],
            baseName: $data["baseName"],
            type: $type,
            requiredRole: $requiredRole,
            newDoor: $data["newDoor"],
            isActive: $data["isActive"],
            spellReward: $data["spellReward"],
            xpReward: $data["xpReward"]
        );
    }
}
class Spell extends Item
{
    public ActionType $action;
    public function __construct($name, $baseName, $type, $action = null, $requiredRole = Role::WANDERER, )
    {
        $this->name = $name;
        $this->baseName = $baseName;
        $this->type = $type;
        $this->action = $action;
        $this->requiredRole = $requiredRole;
        if (empty($name)) {
            $this->name = $baseName . "." . ItemType::SPELL->value;
        }
    }
    public function executeAction()
    {
        $actionFunction = $this->action->value;
        $this->$actionFunction();
    }
    function getMana()
    {
        match ($this->requiredRole) {
            ROLE::WANDERER => $_SESSION["curMana"] += 10,
            ROLE::APPRENTICE => $_SESSION["curMana"] += 20,
            ROLE::ARCHIVIST => $_SESSION["curMana"] += 30,
            ROLE::CONJURER => $_SESSION["curMana"] += 40,
            ROLE::ROOT => $_SESSION["curMana"] += 50,
        };
    }
    public static function fromArray(array $data)
    {
        $type = ItemType::from($data["type"]);
        $requiredRole = ROLE::from($data["requiredRole"]);
        $action = ActionType::from($data["action"]);
        return new self(
            name: $data['name'],
            baseName: $data["baseName"],
            type: $type,
            action: $action,
            requiredRole: $requiredRole,
        );
    }
}
