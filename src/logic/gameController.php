<?php

declare(strict_types=1);
class GameController
{
    public static $levelData = [
        // &&
        Role::WANDERER->value => [
            Commands::ECHO,
            Commands::CAT,
            Commands::CD,
            Commands::MAN,
            Commands::EXECUTE,
        ],
        // ||
        Role::APPRENTICE->value => [
            Commands::MKDIR,
            Commands::RM,
            Commands::PWD,
            Commands::LS,
        ],
        // >>, >
        Role::ARCHIVIST->value => [
            Commands::CP,
            Commands::MV,
            Commands::NANO,
            Commands::TOUCH,
        ],
        // |
        Role::CONJURER->value => [
            Commands::GREP,
            Commands::FIND,
            Commands::WC,
            Commands::HEAD,
            Commands::TAIL
        ],
        Role::ROOT->value => []
    ];
    public Role $userRank;
    public int $xp;
    public $latestCommand;
    public array $currentLevelData = [];
    public array $unlockedCommands = [];
    public int $xpPercentage;
    public string $userName;
    public $requiredCommand;
    public function __construct(
        $xp = 1,
        $userName = "wanderer",
    )
    {
        $this->xp = $xp;
        $this->userName = $userName;
        $this->userRank = Role::getRoleFromRank((int) floor($xp / 100) + 1);
        $this->currentLevelData = self::$levelData[$this->userRank->value];
        $this->latestCommand = reset($this->currentLevelData);
        $this->latestCommand = current($this->currentLevelData);
        $this->xpPercentage = self::calculateLevelPercentage();
        $this->latestCommand = self::calculateGameStats($xp);
    }

    public function levelUpUser()
    {
        $_SESSION["user"]["role"] = $_SESSION["user"]["role"]->getRoleFromRank($_SESSION["user"]["role"]->rank() + 1);
        $this->latestCommand = reset(self::$levelData[$_SESSION["user"]["role"]->value]);
    }

    public function unlockNextCommand()
    {
        next($this->latestCommand);
        if ($this->latestCommand == NULL)
        {
            $this->levelUpUser();
        }
    }

    public function calculateLevelPercentage()
    {
        $lvlCount = count($this->currentLevelData);
        $counter = 1;
        $tempLvlData = self::$levelData[$this->userRank->value];

        while ($counter < $lvlCount)
        {
            $current = current($tempLvlData) == $this->latestCommand;
            if ($current) break;
            next($tempLvlData);
            $counter++;
        }

        return (int)round(($counter / $lvlCount) * 100);
    }
    public function calculateGameStats($xp)
    {
        $this->unlockedCommands = [];
        for ($i = 1; $i < $this->userRank->rank(); $i++)
        {
            foreach (current(self::$levelData) as $command)
            {
                array_push($this->unlockedCommands, $command);
            }
            next(self::$levelData);
        }
        reset($this->currentLevelData);
        $percentage = 100 / count($this->currentLevelData);
        $xp %= 100;
        while ($xp > $percentage)
        {
            $xp -= $percentage;
            array_push($this->unlockedCommands, current($this->currentLevelData));
            next($this->currentLevelData);
        }
        return current($this->currentLevelData);
    }
    public function handleLvlUp(Commands $command)
    {
        self::levelUpUser();
        switch ($command->value)
        {
            //LEVEL 1

            case Commands::ECHO->value:
                {
                    $response =
                        "A new Spellcaster!? not this again...<br>
                    Well lets start by telling me your name wanderer?"
                        . colorizeString("echo [your name]", "action-tip");
                    $this->requiredCommand = Commands::ECHO->value;
                    break;
                }
            case Commands::CAT->value:
                {
                    $this->userName = $_SESSION["tokens"]["misc"];
                    $response =
                        "who is called " . $this->userName . "?...<br>
                        Your name accidentally just activated a rune?! maybe theres some potential... <br>
                        Anyways, you should take a look at the old scrolls lying around and read something for once, you may learn something! "
                        . colorizeString("cat [scrollname]", "action-tip");
                    break;
                }
            case Commands::CD->value:
                {
                    $response = "Finally you learned how to walk, now you're a true wanderer,(Haha even more Puny now!)<br>
                        How about you look around here and get used to your new spell.<br>"
                        . colorizeString("SYNOPSIS<br> cd (path)<br> cd ..<br> cd /<br> cd -<br>   <br> DESCRIPTION<br> Changes the current room to the given destination.<br> <br> cd (path)  Move into the specified room if it exists.<br> cd ..       Move to the parent room.<br> cd /         Move to the root room.<br> cd -     Move back to the previous room.<br>", "action-tip");
                    break;
                }
            case Commands::MAN->value:
                {
                    $response = "Since you wanderers love to forget how your spells work,<br>
                        here's a little something hat can help even the most wandererrest of wanderers 
                        And keep your eyes open for anything... interesting<br>"
                        . colorizeString("man [command]<br>" . "action-tip");
                    break;
                }
            case Commands::EXECUTE->value:
                {
                    $response = "You already got here? Thats a surprise...<br>
                            Well then this should be no biggie, you'll figure out where, on what and how to use this one<br>
                            (or not and you're still just a wanderer)"
                        . colorizeString("./[name]<br>" . "action-tip");
                    break;
                }
                //LEVEL 2         
            case Commands::LS->value:
                {
                    $response = "who is called " . $this->userName . "?...<br>
                        Your name accidentally just activated a rune?! maybe theres some potential... <br>
                        Anyways, you should take a look at the old scrolls lying around and read something for once, you may learn something! "
                        . colorizeString("cat [scrollname]", "action-tip");
                    break;
                }
            case Commands::MKDIR->value:
                {
                }

            case Commands::RM->value:
                {
                }
            case Commands::PWD->value:
                {
                }
                //LEVEL 3
            case Commands::CP->value:
                {
                }
            case Commands::MV->value:
                {
                }
            case Commands::NANO->value:
                {
                }
            case Commands::TOUCH->value:
                {
                }
                //level 4
            case Commands::GREP->value:
                {
                }
            case Commands::FIND->value:
                {
                }
            case Commands::WC->value:
                {
                }
            case Commands::HEAD->value:
                {
                }
            case Commands::TAIL->value:
                {
                }
            default:
                throw new Exception("unknownCommand");
        }
        self::writeMessage(colorizeString($response, "guide"));
        $this->requiredCommand = "";
    }
    public function writeMessage($message)
    {
        $_SESSION["history"][] = [
            "directory" => "insertGuideName",
            "command" => "",
            "response" => $message,
        ];
    }
}
/*
cat scroll: 
        Welcome, wanderer.

        You have entered the realm of ShellSpell
        a dungeon shaped like a command line.

        Here, rooms are directories,
        scrolls are files,
        and knowledge is your only weapon.

        Listen and read carefully to learn the spells the ancient shell
        to explore, solve riddles
        and uncover the secrets hidden in the depths of this dungeon.
        You will find alters at the end of each level.

        Type carefully — every command matters.

        Your journey begins here.
*/