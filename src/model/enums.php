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
    case LOG = "log";
}
enum ActionType: string
{
    case GET_MANA = "getMana";
    case OPEN_SCROLL = "openScroll";
    case CREATE_DOOR = "createDoor";
}
enum Commmand: string {
    case CD = "cd";
    case LS = "ls";
    case PWD = "pwd";
    case MKDIR = "mkdir";
    case RM = "rm";
    case MV = "mv";
    case CAT = "cat";

    public function getDescription(): string {
        return match($this) {
            self::CD => "cd [path]<br> -Change directory to the specified path.<br>",
            self::LS => "ls <br> -List the contents of the current directory.",
            self::PWD => "pwd <br> -Print the current working directory.",
            self::MKDIR => "mkdir [optional path][door name]<br> -Create a new directory with the specified name.",
            self::RM => "rm [optional path][element name]<br> -Remove a file or directory at the specified path.",
            self::MV => "mv [source path][destination path]<br> -Move or rename a file or directory.",
            self::CAT => "cat [path]<br> -Display the contents of a scroll.",
        };
    }
}