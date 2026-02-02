<?php
enum Rank: string
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
            self::WANDERER => Rank::APPRENTICE,
            self::APPRENTICE => Rank::ARCHIVIST,
            self::ARCHIVIST => Rank::CONJURER,
            default => Rank::ROOT
        };
    }
    public function prev()
    {
        return match ($this)
        {
            self::WANDERER,
            self::APPRENTICE => Rank::WANDERER,
            self::ARCHIVIST => Rank::APPRENTICE,
            self::CONJURER => Rank::ARCHIVIST,
            self::ROOT => Rank::CONJURER,
            default => Rank::ROOT
        };
    }
    public function isLowerThan(Rank $Rank): bool
    {
        return $this->rank() < $Rank->rank();
    }
    public static function getRankFromXp($xp)
    {
        return match ((int)floor($xp / 100))
        {
            0 => Rank::WANDERER,
            1 => Rank::APPRENTICE,
            2 => Rank::ARCHIVIST,
            3 => Rank::CONJURER,
            4 => Rank::ROOT,
        };
    }
    public function getColor()
    {
        return match ($this->value)
        {
            Rank::WANDERER->value => "rgb(253, 250, 199)",
            Rank::APPRENTICE->value => "rgb(171, 253, 139)",
            Rank::ARCHIVIST->value => "rgb(109, 232, 248)",
            Rank::CONJURER->value => "rgb(165, 117, 255)",
            Rank::ROOT->value => "rgb(255, 99, 71)",
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
    case RMDIR = "rmdir";
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
