<?php

class SpellSandbox
{
    static public Spell $spell;
    static public array $prompts;
    static public Room $map;
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
    
}
