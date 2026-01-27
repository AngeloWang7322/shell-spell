<?php

declare(strict_types=1);
class GameController
{
    public static $levelData = [
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
    public Rank $userRank;
    public int $xp;
    public string $latestCommand;
    public int $currentSubLvl;
    public array $currentLevelData = [];
    public array $unlockedCommands = [];
    public string $userName;
    public $requiredCommand;
    public function __construct(
        $xp = 0,
        $userName = "wanderer",
    )
    {
        $this->xp = $xp;
        $this->userName = $userName;
        $this->userRank = Rank::getRankFromXp($xp);
        $this->currentLevelData = self::$levelData[$this->userRank->value];
        $this->latestCommand = self::calculateGameStats($xp);
    }

    public function levelUpUser($alter)
    {
        if ($alter->requiredRank != $this->userRank->next()) return;
        $alter->isActive = false;
        $this->userRank = $this->userRank->next();
        $this->currentLevelData = self::$levelData[$this->userRank->value];
        $this->latestCommand = current($this->currentLevelData);
        $this->currentSubLvl = 0;
        $this->xp = $this->userRank->rank() * 100;
        self::createRewardRoom();
        writeNewHistory();
        self::getCurrentMessage();
        throw new Exception("", -1);
    }

    public function unlockNextCommand()
    {
        $this->xp += (int)(1 / count($this->currentLevelData) * 100);
        $this->latestCommand = self::getNextSpell();
        $this->currentSubLvl++;
        array_push($this->unlockedCommands, $this->latestCommand);
        self::getCurrentMessage();
    }
    public function calculateLvlProgress()
    {
        return (int)($this->currentSubLvl / count($this->currentLevelData) * 100);
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
        $percentage = count($this->currentLevelData) == 0
            ? 100
            : 100 / count($this->currentLevelData);
        $xp %= 100;
        $this->currentSubLvl = 0;
        array_push($this->unlockedCommands, current($this->currentLevelData));
        while ($xp > $percentage)
        {
            $this->currentSubLvl++;
            $xp -= $percentage;
            array_push($this->unlockedCommands, current($this->currentLevelData));
            next($this->currentLevelData);
        }

        return $this->userRank == Rank::ROOT
            ? current(self::$levelData[$this->userRank->prev()->value])
            : current($this->currentLevelData);
    }
    public function getCurrentMessage()
    {
        switch ($this->latestCommand)
        {
            //LEVEL 1
            case Commands::ECHO->value:
                {
                    $response = "<br>A Spellcaster!? Haven't seen one of you people in years(fortunately...)<br>
                        Nobody was supposed to come here anymore.<br> Whats your name wanderer?<br>"
                        . colorizeString("<br><br> > echo [your name]", "action-tip");
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
                        . colorizeString("<br> cat [filename]", "action-tip");
                    break;
                }
            case Commands::CD->value:
                {
                    $response = "Finally you learned how to walk, now you're a true wanderer,(Haha even more Puny now!)<br>
                        How about you look around here and get used to your new spell.<br>"
                        . colorizeString("<br>cd [doorname]", "action-tip");
                    break;
                }
            case Commands::MAN->value:
                {
                    $response = "Since you wanderers love to forget how your spells work,<br>
                        here's a little something hat can help even the most wandererrest of wanderers 
                        And keep your eyes open for anything... interesting<br>"
                        . colorizeString(getCommand($this->latestCommand)->description, "action-tip");
                    break;
                }
            case Commands::EXECUTE->value:
                {
                    $response = "You already got here? Thats a surprise...<br>
                            Well then this should be no biggie, you'll figure out where, on what and how to use this one<br>
                            (or not and you're still just a wanderer)"
                        . colorizeString(getCommand($this->latestCommand)->description, "action-tip");
                    break;
                }
                //LEVEL 2
            case Commands::LS->value:
                {
                    $response = "While calling you a wanderer was fun, i unfortunately have to congratulate you on your rank Promotion,<br>
                     you are now officially an APPRENTICE. Not too shabby " . $this->userName . "<br> 
                        as you can see (or rather, as you can't), invoking the alter transported you to this empty place.<br>
                        ...unless it isn't empty and you just learnt a new spell?<br>"
                        . colorizeString(getCommand($this->latestCommand)->description, "action-tip");
                    break;
                }
            case Commands::PWD->value:
                {
                    $response = "this is a convenient one, use it when you're disoriented, or are wondering if you're inside a specific room <br>"
                        . colorizeString(getCommand($this->latestCommand)->description, "action-tip");
                    break;
                }
            case Commands::MKDIR->value:
                {
                    $response = "Now this slowly becomes interesting,<br>
                        you now can now create your own rooms.<br>
                        And i know its tempting, but make sure not to go crazy with creating your own maze you will never find your way out of!"
                        . colorizeString(getCommand($this->latestCommand)->description, "action-tip");
                    break;
                }
            case Commands::RM->value:
                {
                    $response = "Now what is this?! You can even clean up after yourself!!. <br>
                        How about you practise by cleaning up some rooms here (please?)<br>"
                        . colorizeString(getCommand($this->latestCommand)->description, "action-tip");
                    break;
                }
                //LEVEL 3
            case Commands::TOUCH->value:
                {
                    $response = "Oh no...<br>
                        Like everytime we get here, i will tell you to always keep things clean and organized,<br>
                        and you will happily ignore me and contribute to this mess around here, but anyways, here it is"
                        . colorizeString(getCommand($this->userRank)->description, "action-tip");
                    break;
                }
            case Commands::CP->value:
                {
                    $response = ""
                        . colorizeString("cp [sourcepath] [destinationPath]", "action-tip");
                    break;
                }
            case Commands::MV->value:
                {
                    break;
                }
            case Commands::NANO->value:
                {
                    break;
                }
                //level 4
            case Commands::GREP->value:
                {
                    break;
                }
            case Commands::FIND->value:
                {
                    break;
                }
            case Commands::WC->value:
                {
                    break;
                }
            case Commands::HEAD->value:
                {
                    break;
                }
            case Commands::TAIL->value:
                {
                    break;
                }
            default:
                {
                    if ($this->userRank->value == "root") break;
                    throw new Exception("unknownCommand");
                }
        }
        self::writeMessage(colorizeString($response, "guide"));
    }
    public function writeMessage($message)
    {
        $message = "
        -------- strangevoice -------- <br>" .
            $message . "<br>
        ------------------------------ <br>";
        editLastHistory($message);
    }
    public function getNextSpell()
    {
        $next =  $this->currentLevelData[$this->currentSubLvl + 1];
        return $this->currentSubLvl <= count($this->currentLevelData)
            ?  $this->currentLevelData[$this->currentSubLvl + 1]
            : false;
    }

    public function createRewardRoom()
    {
        switch ($this->userRank->value)
        {
            case Rank::APPRENTICE:
                {
                    $_SESSION["curRoom"]->doors["doorofwisdom"] =
                        new Room(
                            "doorofwisdom",
                            [],
                            $this->userRank
                        );

                    break;
                }
            case Rank::ARCHIVIST:
                {
                }
            default:
                {
                }
        }
    }
}
