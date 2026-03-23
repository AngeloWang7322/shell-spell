<?php

class Sandbox
{
    public Commands $spell;
    public array $commands;
    public string $description;
    public int $current;
    public Room $map;
    public function __construct(
        Commands $spell,
        array $commands,
        string $description,
        Room $map
    )
    {
        // if (count($commands) == 0)

        array_push($commands, [colorizeString(" Added To Spellbook !", "success"), "exit"]);
        $this->spell = $spell;
        $this->commands = $commands;
        $this->description = $description;
        $this->map = $map;
        $this->current = 0;
        if (count($commands) == 1)
        {
            Streams::$stdout = [colorizeString(
                colorizeString(getCommand($this->spell->value)->description, "info")
                    . "<br>",
                "narration"
            )];
        }
    }

    public function writeCurrentPrompt()
    {
        $_SESSION["history"] = [];
        $_SESSION["history"][] = [
            "directory" => "narration",
            "command" => colorizeString("Spell: ", "") .  colorizeString($this->spell->value, 'spell-name')
                . "<br>" . colorizeString($this->description, "info")
                . "<br>-------------<br>step " . ($this->current) . " of " . (count($this->commands) - 1) . " - " . $this->commands[$this->current][0]
                . "<br><br>",
            "response" => $_SESSION["terminal"]->renderStdout(),
        ];
    }

    public function isInputValid()
    {
        return trim($_POST["command"]) == $this->commands[$this->current][1];
    }

    public function nextCommand()
    {
        $this->current++;
        if ($this->current + 1 == count($this->commands))
        {
            Streams::$stdout = [colorizeString(
                colorizeString(getCommand($this->spell->value)->description, "info")
                    . "<br>",
                "narration"
            )];
        }
        if ($this->current == count($this->commands))
        {
            self::exitSandbox();
            $_SESSION["gameState"]->unlockSpell($this->spell);
            return false;
        }

        return true;
    }

    public function prepare()
    {
        $_SESSION["terminal"]->addNewHistory();

        $_SESSION["sandbox"] = $this;
        $_SESSION["terminal"]->isSandbox = true;

        $_SESSION["tempPath"] = $_SESSION["curRoom"]->path;

        $_SESSION["tempMap"] = $_SESSION["map"];
        $_SESSION["map"] = $this->map;
        $_SESSION["curRoom"] = &$_SESSION["map"];

        $_SESSION["tempHistory"] = $_SESSION["history"];
        $_SESSION["history"] = [];
        $_SESSION["history"][] = [
            "directory" => "",
            "command" => "",
            "response" => ""
        ];

        $_SESSION["tempMapName"] = $_SESSION["gameState"]->mapName;
        $_SESSION["gameState"]->mapName = $this->spell->value . ".sh";
    }

    public function exitSandbox()
    {
        $_SESSION["terminal"]->isSandbox = false;

        $_SESSION["map"] = $_SESSION["tempMap"];
        unset($_SESSION["tempMap"]);

        $_SESSION["curRoom"] = &getRoomAbsolute($_SESSION["tempPath"]);
        unset($_SESSION["tempPath"]);

        $_SESSION["history"] = $_SESSION["tempHistory"];
        unset($_SESSION["tempHistory"]);

        $_SESSION["gameState"]->mapName = $_SESSION["tempMapName"];
        unset($_SESSION["tempMapName"]);

        unset($_SESSION["sandbox"]);
    }
}
