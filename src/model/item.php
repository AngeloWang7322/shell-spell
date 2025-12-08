<?php
class Item
{
    public string $name;
    public string $baseName;
    public ItemType $type;
    public ActionType $action;
    public Rarity $rarity;
    public $content;
    public function __construct($name, $baseName, $type, $action = null, $rarity = Rarity::COMMON, $content = "")
    {
        $this->name = $name;
        $this->baseName = $baseName;
        $this->type = $type;
        $this->action = $action;
        $this->rarity = $rarity;
        $this->content = $content;
        if(!empty($name)){
            $this->name = $baseName . "." . match ($type) {
                ItemType::SCROLL => ItemType::SCROLL->value,
                ItemType::SPELL => ItemType::SPELL->value,
                ItemType::STATUE => ItemType::STATUE->value,
            };
        }

        else {
            $this->name = $name;
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
            content: $data['content']
        );
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
}

enum ItemType: string
{
    case SCROLL = "txt";
    case SPELL = "exe";
    case STATUE = "obj";
}
enum ActionType: string
{
    case MANA = "getMana";
    case OPEN_SCROLL = "openScroll";
}
enum Rarity: string
{
    case COMMON = "common";
    case RARE = "rare";
    case EPIC = "epic";
}