<?php
class Item
{
    public string $name;
    public string $baseName;
    public ItemType $type;
    public Rarity $rarity;
    public function __construct($name, $baseName, $type, $rarity = Rarity::COMMON)
    {
        $this->name = $name;
        $this->baseName = $baseName;
        $this->type = $type;
        $this->rarity = $rarity;
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
        $rarity = Rarity::from($data["rarity"]);
        return new self(
            name: $data['name'],
            baseName: $data["baseName"],
            type: $type,
            rarity: $rarity,
        );
    }
}
class Scroll extends Item
{
    public bool $isOpen = false;
    public string $content;
    public function __construct($name, $baseName, $type, $rarity = Rarity::COMMON, string $content = "")
    {
        $this->name = $name;
        $this->baseName = $baseName;
        $this->type = $type;
        $this->rarity = $rarity;
        $this->content = $content;
        if (empty($name)) {
            $this->name = $baseName . "." . ItemType::SCROLL->value;
        }
    }
    public static function fromArray(array $data)
    {
        $type = ItemType::from($data["type"]);
        $rarity = Rarity::from($data["rarity"]);
        return new self(
            name: $data['name'],
            baseName: $data["baseName"],
            type: $type,
            rarity: $rarity,
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

    public function __construct($name, $baseName, $type, $rarity = Rarity::COMMON, $newDoor = "", $isActive = true, $spellReward = "", $xpReward = 0)
    {
        $this->name = $name;
        $this->baseName = $baseName;
        $this->type = $type;
        $this->rarity = $rarity;
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
        $rarity = Rarity::from($data["rarity"]);
        return new self(
            name: $data['name'],
            baseName: $data["baseName"],
            type: $type,
            rarity: $rarity,
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
    public function __construct($name, $baseName, $type, $action = null, $rarity = Rarity::COMMON, )
    {
        $this->name = $name;
        $this->baseName = $baseName;
        $this->type = $type;
        $this->action = $action;
        $this->rarity = $rarity;
        if (empty($name)) {
            $this->name = $baseName . "." . ItemType::SPELL->value;
        }
    }
    public function executeAction()
    {
        $actionFunction = $this->action->value;
        echo "<br>executing: " . $actionFunction;
        $this->$actionFunction();
    }
    function getMana()
    {
        switch ($this->rarity) {
            case Rarity::COMMON:
                $_SESSION["curMana"] += 10;
                break;
            case Rarity::RARE:
                $_SESSION["curMana"] += 25;
                break;
            case Rarity::EPIC:
                $_SESSION["curMana"] += 50;
                break;
        }
    }
    public static function fromArray(array $data)
    {
        $type = ItemType::from($data["type"]);
        $rarity = Rarity::from($data["rarity"]);
        $action = ActionType::from($data["action"]);
        return new self(
            name: $data['name'],
            baseName: $data["baseName"],
            type: $type,
            action: $action,
            rarity: $rarity,
        );
    }
}
enum ItemType: string
{
    case SCROLL = "txt";
    case SPELL = "sh";
    case ALTER = "exe";
}
enum ActionType: string
{
    case GET_MANA = "getMana";
    case OPEN_SCROLL = "openScroll";
    case CREATE_DOOR = "createDoor";
}
enum Rarity: string
{
    case COMMON = "common";
    case RARE = "rare";
    case EPIC = "epic";
}