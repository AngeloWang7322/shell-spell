<?php

class Sandbox
{
    public Commands $spell;
    public array $commands;
    public int $current;
    public Room $map;
    public function __construct(
        Commands $spell,
        array $commands,
        Room $map
    )
    {
        $this->spell = $spell;
        $this->commands = $commands;
        $this->map = $map;
        $this->current = 0;
    }

    public function writeCurrentPrompt()
    {
        $_SESSION["history"][] = [
            "directory" => "",
            "command" => $this->current . "/" . count($this->commands),
            "response" => $this->commands[$this->current][0]
        ];
    }
    public function isInputValid()
    {
        return  trim($_POST["command"]) == $this->commands[$this->current][1];
    }

    public function nextCommand()
    {
        $this->current++;
        if ($this->current == count($this->commands))
        {
            $_SESSION["gameState"]->unlockSpell($this->spell);
            self::exit();
        }
        self::writeCurrentPrompt();
    }

    public function prepare()
    {
        $_SESSION["sandbox"] = $this;
        $_SESSION["terminal"]->isSandbox = true;

        $_SESSION["tempMap"] =  $_SESSION["map"];
        $_SESSION["map"] = $this->map;

        $_SESSION["tempCurRoom"] = $_SESSION["curRoom"];
        $_SESSION["curRoom"] = &$_SESSION["map"];

        $_SESSION["tempHistory"] = $_SESSION["history"];
        $_SESSION["history"] = [];

        $_SESSION["tempMapName"] = $_SESSION["gameState"]->mapName;
        $_SESSION["gameState"]->mapName = $this->spell->value . ".sh";
    }

    public function exit()
    {
        $_SESSION["terminal"]->isSandbox = false;

        $_SESSION["map"] = $_SESSION["tempMap"];
        unset($_SESSION["tempMap"]);

        $_SESSION["curRoom"] = &$_SESSION["tempCurRoom"];
        unset($_SESSION["tempCurRoom"]);

        $_SESSION["history"] = $_SESSION["tempHistory"];
        unset($_SESSION["tempHistory"]);

        $_SESSION["gameState"]->mapName = $_SESSION["tempMapName"];
        unset($_SESSION["tempMapName"]);

        unset($_SESSION["sandbox"]);
    }
}
