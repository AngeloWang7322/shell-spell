<?php

declare(strict_types=1);
class SpellSandbox
{
    public Spell $spell;
    public array $prompts;
    public Room $map;
    public function __construct(
        $spell,
        array $prompts,
        Room $map
    )
    {
        $this->spell = $spell;
        $this->prompts = $prompts;
        $this->map = $map;
    }

    public function enterSandbox()
    {
        $_SESSION["backUpMap"] =  $_SESSION["map"];
        $_SESSION["map"] = $this->map;
        $_SESSION["curRoom"] = &$_SESSION["map"];
    }
}
