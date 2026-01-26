<?php
enum Role: string
{
    case WANDERER = "wanderer";
    case APPRENTICE = "apprentice";
    case ARCHIVIST = "archivist";
    case CONJURER = "conjurer";
    case ROOT = "root";

    public function rank(): int
    {
        return match ($this)
        {
            self::WANDERER => 0,
            self::APPRENTICE => 1,
            self::ARCHIVIST => 2,
            self::CONJURER => 3,
            self::ROOT => 4,
        };
    }
    public function next()
    {
        return match ($this)
        {
            self::WANDERER => Role::APPRENTICE,
            self::APPRENTICE => Role::ARCHIVIST,
            self::ARCHIVIST => Role::CONJURER,
            default => Role::ROOT
        };
    }
    public function isLowerThan(Role $role): bool
    {
        return $this->rank() < $role->rank();
    }
    public static function getRoleFromXp($xp)
    {
        return match ((int)floor($xp / 100))
        {
            0 => Role::WANDERER,
            1 => Role::APPRENTICE,
            2 => Role::ARCHIVIST,
            3 => Role::CONJURER,
            4 => Role::ROOT,
        };
    }
    public function getColor()
    {
        return match ($this->value)
        {
            Role::WANDERER->value => "rgb(253, 250, 199)",
            Role::APPRENTICE->value => "rgb(171, 253, 139)",
            Role::ARCHIVIST->value => "rgb(109, 232, 248)",
            Role::CONJURER->value => "rgb(165, 117, 255)",
            Role::ROOT->value => "rgb(255, 99, 71)",
        };
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
enum Commands: string
{
    case CD = "cd";
    case MKDIR = "mkdir";
    case RM = "rm";
    case MV = "mv";
    case PWD = "pwd";
    case LS = "ls";
    case CP = "cp";
    case GREP = "grep";
    case ECHO = "echo";
    case EXECUTE = "execute";
    case MAN = "man";
    case CAT = "cat";
    case TOUCH = "touch";
    case FIND = "find";
    case WC = "wc";
    case HEAD = "head";
    case TAIL = "tail";
    case NANO = "nano";
}

enum TokenType: string
{
    case COMMAND = "command";
    case OPTION = "option";
    case KEYVALUEOPTION = "keyvalueoption";
    case PATH = "path";
    case STRING = "string";
    case MISC = "misc";
}
