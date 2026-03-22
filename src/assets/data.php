<?php

declare(strict_types=1);
// require_once "../model/enums.php";
class Data
{
    static public $levelData = [
        // &&
        Rank::WANDERER->value => [
            Commands::ECHO->value,
            Commands::CAT->value,
            Commands::CD->value,
            Commands::MAN->value,
            Commands::EXECUTE->value,
        ],
        // ||
        Rank::APPRENTICE->value => [
            Commands::MKDIR->value,
            Commands::RM->value,
            Commands::RMDIR->value,
            Commands::PWD->value,
            Commands::LS->value,
        ],
        // >>, >
        Rank::ARCHIVIST->value => [
            Commands::CP->value,
            Commands::MV->value,
            Commands::NANO->value,
            Commands::TOUCH->value,
        ],
        // |
        Rank::CONJURER->value => [
            Commands::GREP->value,
            Commands::FIND->value,
            Commands::WC->value,
            Commands::HEAD->value,
            Commands::TAIL->value
        ],
        Rank::ROOT->value => []
    ];
    static public function getSandBox(Commands $cmd)
    {
        return match ($cmd)
        {
            Commands::CD => new SpellSandbox(
                Commands::CD,
                [
                    "enter room" => "cd door",
                    "enter multiple rooms subsequently" => "cd door/room",
                    "go back one room" => "cd ..",
                    "return to start" => "cd /"
                ],
                new Room("door")
            ),
        };
    }
    // $tutorials = [
    //     Commands::ECHO->value => [
    //         ""
    //     ],
    //     Commands::CAT->value => [
    //         "read item contents" => ["cat text.txt", "cat door/alter.exe"]
    //     ],
    //     Commands::CD->value => [
    //         "enter door" => ["cd door1"],
    //         "enter door from starting room" => ["cd /door"],
    //         "go back " => ["cd .."],
    //         "return to start" => [" cd /"],
    //         "enter multiple at once" => ["cd door1/door2"],
    //     ],
    //     Commands::MAN->value => [
    //         "look up any spell manual" => ["man cd", "man echo"]
    //     ],
    //     Commands::EXECUTE->value => [
    //         "activate a spell or alter" => ["./spell.sh", "./alter.exe"]
    //     ],
    //     Commands::MKDIR->value => [
    //         ""

    //     ],
    //     Commands::RM->value => [],
    //     Commands::PWD->value => [],
    //     Commands::LS->value => [],
    //     Commands::CP->value => [],
    //     Commands::MV->value => [],
    //     Commands::NANO->value => [],
    //     Commands::TOUCH->value => [],
    //     Commands::GREP->value => [],
    //     Commands::FIND->value => [],
    //     Commands::WC->value => [],
    //     Commands::HEAD->value => [],
    //     Commands::TAIL->value => [],
    // ];
}
