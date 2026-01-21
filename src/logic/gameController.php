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
    public int $currentSubLvl;
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
        $this->currentSubLvl++;
        $this->latestCommand = self::getNextSpell();
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
        $this->currentSubLvl = 0;
        while ($xp > $percentage)
        {
            $this->currentSubLvl++;
            $xp -= $percentage;
            array_push($this->unlockedCommands, current($this->currentLevelData));
            next($this->currentLevelData);
        }
        return current($this->currentLevelData);
    }
    public function getCurrentMessage()
    {
        switch ($this->latestCommand->value)
        {
            //LEVEL 1
            case Commands::ECHO->value:
                {
                    $response = "<br>A Spellcaster!? Haven't seen one of you people in years(fortunately...)<br>
                        Nobody was supposed to come here anymore.<br> Whats your name wanderer?<br>"
                        . colorizeString("<br> > echo [your name]", "action-tip");
                    $this->requiredCommand = Commands::ECHO->value;
                    break;
                }
            case Commands::CAT->value:
                {
                    $this->requiredCommand = NULL;
                    $this->userName = $_SESSION["tokens"]["strings"][0];
                    $response = "
                        <br><br>You accidentally just activated a rune?! No it can't be... must've been a coincidence... and weird name anywat <br>
                        Activating one requires a skilled caster and the correct chant.
                        You should make good use of the your luck and use the spell you just gained<br>
                        take a look at the old scrolls lying around and read something for once, you may learn something!<br> "
                        . colorizeString(getCommand($this->latestCommand->value)->description, "action-tip");
                    break;
                }
            case Commands::CD->value:
                {
                    $response = "Finally you learned how to walk, now you're a true wanderer,(Haha even more Puny now!)<br>
                        How about you look around here and get used to your new spell.<br>"
                        . colorizeString(getCommand($this->latestCommand->value)->description, "action-tip");
                    break;
                }
            case Commands::MAN->value:
                {
                    $response = "Since you wanderers love to forget how your spells work,<br>
                        here's a little something hat can help even the most wandererrest of wanderers 
                        And keep your eyes open for anything... interesting<br>"
                        . colorizeString(getCommand($this->latestCommand->value)->description . "action-tip");
                    break;
                }
            case Commands::EXECUTE->value:
                {
                    $response = "You already got here? Thats a surprise...<br>
                            Well then this should be no biggie, you'll figure out where, on what and how to use this one<br>
                            (or not and you're still just a wanderer)"
                        . colorizeString(getCommand($this->latestCommand->value)->description . "action-tip");
                    break;
                }
                //LEVEL 2
            case Commands::LS->value:
                {
                    $response = "While calling you a wanderer was fun, i unfortunately have to congratulate you on your rank Promotion,<br>
                     you are now officially an APPRENTICE. Not too shabby " . $this->userName . "<br> 
                        as you can see (or rather, as you can't), invoking the alter transported you to this empty place.<br>
                        ...unless it isn't empty and you just learnt a new spell?<br>"
                        . colorizeString(getCommand($this->latestCommand->value)->description, "action-tip");
                    break;
                }
            case Commands::PWD->value:
                {
                    $response = "this is a convenient one, use it when you're disoriented, or are wondering if you're inside a specific room <br>"
                        . colorizeString(getCommand($this->latestCommand->value)->description, "action-tip");
                    break;
                }
            case Commands::MKDIR->value:
                {
                    $response = "Now this slowly becomes interesting,<br>
                        you now can now create your own rooms.<br>
                        And i know its tempting, but make sure not to go crazy with creating your own maze you will never find your way out of!"
                        . colorizeString(getCommand($this->latestCommand->value)->description, "action-tip");
                    break;
                }
            case Commands::RM->value:
                {
                    $response = "Now what is this?! You can even clean up after yourself!!. <br>
                        How about you practise by cleaning up some rooms here (please?)<br>"
                        . colorizeString(getCommand($this->latestCommand->value)->description, "action-tip");
                    break;
                }
                //LEVEL 3
            case Commands::TOUCH->value:
                {
                    $response = "Oh no...<br>
                        Like everytime we get here, i will tell you to always keep things clean and organized,<br>
                        and you will happily ignore me and contribute to this mess around here, but anyways, here it is"
                        . colorizeString(getCommand($this->userRank->value)->description, "action-tip");
                    break;
                }
            case Commands::CP->value:
                {
                    $response = ""
                        . colorizeString("rm [name]", "action-tip");
                    break;
                }
            case Commands::MV->value:
                {
                }
            case Commands::NANO->value:
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
    }
    public function writeMessage($message)
    {

        $message =
            " ------- strangevoice ------- <br><br>" .
            $message .
            "<br><br>------------------------------ <br>";
        editLastHistory($message);
    }
    public function getNextSpell()
    {
        // $next =  $this->currentLevelData[$this->currentSubLvl + 1];
        return $this->currentSubLvl < count($this->currentLevelData)
            ?  $this->currentLevelData[$this->currentSubLvl]
            : false;
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