<?php
class Item
{
    public string $name;
    public ItemType $type;
    public ActionType $action;
    public Rarity $rarity;
    public string $content;

    public function __construct($name, $type, $action = null, $rarity = Rarity::COMMON, $content = "")
    {
        $this->name = $name;
        $this->type = $type;
        $this->action = $action;
        $this->rarity = $rarity;
        $this->content = $content;

        $this->name = $name . match ($type) {
            ItemType::SCROLL => ItemType::SCROLL->value,
            ItemType::SPELL => ItemType::SPELL->value,
            ItemType::STATUE => ItemType::STATUE->value,
        };
    }
    public function executeAction()
    {
        $actionFunction = $this -> action -> value;
        $this -> $actionFunction();
    }
    function manaAction()
    {
        switch ($this->rarity) {
            case Rarity::COMMON:
                $_SESSION["user"]->curMana += 10;
                break;
            case Rarity::RARE:
                $_SESSION["user"]->curMana += 25;
                break;
            case Rarity::EPIC:
                $_SESSION["user"]->curMana += 50;
                break;
        }
    }
}

enum ItemType: string
{
    case SCROLL = ".txt";
    case SPELL = ".exe";
    case STATUE = ".obj";
}
enum ActionType: string
{
    case MANA = "manaAction";
    case OPEN_SCROLL = "openScroll";
}
enum Rarity: string
{
    case COMMON = "common";
    case EPIC = "epic";
    case RARE = "rare";
}
