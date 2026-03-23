<?php

declare(strict_types=1);
class Data
{
    static public $levelData = [
        // &&
        Rank::WANDERER->value => [
            Commands::EXECUTE->value,
            Commands::ECHO->value,
            Commands::CD->value,
            Commands::CAT->value,
            Commands::MAN->value,
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

    static public function getNewSpellMessage(string $command)
    {
        return match ($command)
        {
            Commands::EXECUTE->value =>
            "A new student has entered Shell Spell!"
                . colorizeString("
                <br> " . colorizeString("<br>Finally. It has been... a long time since anyone found this place", "quiet") .  "
                <br>You showing up means someone is finally interested in learning the Spells of Shell again! So lets get started.
                <br>
                <br>Everything here is yours to command, or at least will be... 
                <br>First you have to earn the Spells and skills to use them.
                <br>Lets start by activating the Spell Rune infront of you.
                <br>
                <br>repeat after me. And good luck... I won't be able to see you through it.
                <br>
                <br>
                ", "info") . colorizeString("./cd.sh", "action-tip"),
            Commands::CD->value => colorizeString("That was quick!
                <br>Keep going and use your first spell to traverse Shell Spell and gain more skils.
                <br>
                <br>And QUICK TIP: You can always reactive the Spell Runes incase you're out of practise", "info")
                . colorizeString("<br>cd [doorname]", "action-tip"),
            Commands::ECHO->value => "echo [text]" .  colorizeString("<br><br>echo [your name]", "action-tip"),
            Commands::CAT->value => "Wow you can finally read! Maybe you can learn something useful from all these scrolls lying around"
                . colorizeString("<br>cat [filename]", "action-tip"),
            Commands::MAN->value => "Since you wanderers love to forget how your spells work,<br>
                        here's a little something hat can help even the most wandererrest of wanderers 
                        And keep your eyes open for anything... interesting<br>"
                . colorizeString("man [spell]", "action-tip"),
            Commands::LS->value => "While calling you a wanderer was fun, i unfortunately have to congratulate you on your rank Promotion,<br>
                        you are now officially an APPRENTICE. Not too shabby  <br> 
                        as you can see (or rather, as you can't), invoking the alter transported you to this empty place.<br>
                        ...unless it isn't empty and you just learnt a new spell?<br>"
                . colorizeString(getCommand($command)->description, "action-tip"),
            Commands::PWD->value => "this is a convenient one, use it when you're disoriented, or are wondering if you're inside a specific room <br>"
                . colorizeString(getCommand($command)->description, "action-tip"),
            Commands::MKDIR->value => "Now this slowly becomes interesting,<br>
                        you now can now create your own rooms.<br>
                        And i know its tempting, but make sure not to go crazy with creating your own maze you will never find your way out of!"
                . colorizeString(getCommand($command)->description, "action-tip"),
            Commands::RM->value => "Now what is this?! You can even clean up after yourself!!. <br>
                        How about you practise by cleaning up some rooms here (please?)<br>"
                . colorizeString(getCommand($command)->description, "action-tip"),
            Commands::TOUCH->value => "" . colorizeString(getCommand($command)->description, "action-tip"),
            Commands::CP->value => colorizeString("cp [sourcepath] [destinationPath]", "action-tip"),
            Commands::MV->value => "",
            Commands::NANO->value => "",
            Commands::GREP->value => "",
            Commands::FIND->value => "",
            Commands::WC->value => "",
            Commands::HEAD->value => "",
            Commands::TAIL->value => "",
            default => "NO COMMAND FOUND!!"
        };
    }
    static public function getSandBox(Commands $cmd)
    {
        $sandboxMap = new Room($cmd->value);
        switch ($cmd)
        {
            case Commands::CD:
                {
                    $sandboxMap->doors["door"] = new Room("door");
                    $sandboxMap->doors["door"]->doors["room"] = new Room("room");
                    return  new Sandbox(
                        $cmd,
                        [
                            ["enter room", "cd door"],
                            ["go back one room",  "cd .."],
                            ["enter multiple rooms subsequently", "cd door/room"],
                            ["return to start", "cd /"]
                        ],
                        "moves you to another room",
                        $sandboxMap
                    );
                }
            case Commands::CAT:
                {
                    $sandboxMap->doors["room"] = new Room("room");
                    $sandboxMap->items["text.txt"] = new Scroll("text.txt", "text", content: 'scroll content');
                    $sandboxMap->doors["room"]->items["text.txt"] = new Scroll("text.txt", "text", content: "other scrolls content");
                    return  new Sandbox(
                        $cmd,
                        [
                            ["read item", "cat text.txt"],
                            ["read item in next room", "cat room/text.txt"],
                        ],
                        "reveals an items contents",
                        $sandboxMap
                    );
                }
            case Commands::ECHO:
                {
                    return  new Sandbox(
                        $cmd,
                        [],
                        "description",
                        $sandboxMap
                    );
                }
            case Commands::MAN:
                {
                    return  new Sandbox(
                        $cmd,
                        [],
                        "description",
                        $sandboxMap
                    );
                }
            case Commands::MKDIR:
                {
                    return  new Sandbox(
                        $cmd,
                        [],
                        "description",
                        $sandboxMap
                    );
                }
            case Commands::RM:
                {
                    return  new Sandbox(
                        $cmd,
                        [],
                        "description",
                        $sandboxMap
                    );
                }
            case Commands::RMDIR:
                {
                    return  new Sandbox(
                        $cmd,
                        [],
                        "description",
                        $sandboxMap
                    );
                }
            case Commands::PWD:
                {
                    return  new Sandbox(
                        $cmd,
                        [],
                        "description",
                        $sandboxMap
                    );
                }
            case Commands::LS:
                {
                    return  new Sandbox(
                        $cmd,
                        [],
                        "description",
                        $sandboxMap
                    );
                }
            case Commands::CP:
                {
                    return  new Sandbox(
                        $cmd,
                        [],
                        "description",
                        $sandboxMap
                    );
                }
            case Commands::MV:
                {
                    return  new Sandbox(
                        $cmd,
                        [],
                        "description",
                        $sandboxMap
                    );
                }
            case Commands::GREP:
                {
                    return  new Sandbox(
                        $cmd,
                        [],
                        "description",
                        $sandboxMap
                    );
                }
            case Commands::NANO:
                {
                    return  new Sandbox(
                        $cmd,
                        [],
                        "description",
                        $sandboxMap
                    );
                }
            case Commands::TOUCH:
                {
                    return  new Sandbox(
                        $cmd,
                        [],
                        "description",
                        $sandboxMap
                    );
                }
            case Commands::FIND:
                {
                    return  new Sandbox(
                        $cmd,
                        [],
                        "description",
                        $sandboxMap
                    );
                }
            case Commands::WC:
                {
                    return  new Sandbox(
                        $cmd,
                        [],
                        "description",
                        $sandboxMap
                    );
                }
            case Commands::HEAD:
                {
                    return  new Sandbox(
                        $cmd,
                        [],
                        "description",
                        $sandboxMap
                    );
                }
            case Commands::TAIL:
                {
                    return  new Sandbox(
                        $cmd,
                        [],
                        "description",
                        $sandboxMap
                    );
                }
            default:
                throw new Exception("commmand not found");
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
