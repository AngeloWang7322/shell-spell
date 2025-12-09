<?php
enum ROLE: string
{
    case WANDERER = "wanderer";
    case APPRENTICE = "apprentice";
    case ARCHIVIST = "archivist";
    case CONJURER = "conjurer";
    case ROOT = "root";

    public function rank(): int
    {
        return match ($this) {
            self::WANDERER => 1,
            self::APPRENTICE => 2,
            self::ARCHIVIST => 3,
            self::CONJURER => 4,
            self::ROOT => 5,
        };
    }
    public function isLowerThan(Role $role): bool
    {
        return $this->rank() < $role->rank();
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