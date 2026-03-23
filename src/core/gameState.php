<?php

declare(strict_types=1);
class GameState
{

    public Rank $userRank;
    public int $xp;
    public string $newestSpell;
    public int $currentSubLvl;
    public array $currentLevelData = [];
    public array $unlockedCommands = [];
    public string $userName;
    public string $mapName;
    public function __construct(
        $xp = 0,
        $userName = "guest",
        $mapName = "dungeon",
    )
    {
        $this->xp = $xp;
        $this->userName = $userName;
        $this->mapName = $mapName;
        $this->userRank = Rank::getRankFromXp($xp);
        $this->currentLevelData = Data::$levelData[$this->userRank->value];
        $this->newestSpell = self::calculateGameStats($xp);
    }

    public function levelUpUser($alter)
    {
        if ($alter->requiredRank != $this->userRank->next()) return;
        $alter->isActive = false;
        $this->userRank = $this->userRank->next();
        $this->currentLevelData = Data::$levelData[$this->userRank->value];
        $this->currentSubLvl = 0;
        $this->xp = $this->userRank->rank() * 100;
        self::createRewardRoom();
    }

    public function unlockSpell(Commands $newSpell)
    {
        if (
            !in_array($newSpell->value, $this->currentLevelData)
            || in_array($newSpell->value, $this->unlockedCommands)
        )
        {
            Streams::$stderr[] = "command already unlocked";
            return false;
        }
        $this->newestSpell = $newSpell->value;
        $this->xp += (int)(1 / count($this->currentLevelData) * 100);

        $this->currentSubLvl++;
        array_push($this->unlockedCommands, $newSpell->value);
        self::getCurrentMessage();
    }
    public function calculateLvlProgress()
    {
        return count($this->currentLevelData) == 0
            ? 0
            : (int)(($this->currentSubLvl + 1 )/ count($this->currentLevelData) * 100);
    }
    public function calculateGameStats($xp)
    {
        $this->unlockedCommands = [];
        for ($i = 0; $i < $this->userRank->rank(); $i++)
        {
            foreach (current(Data::$levelData) as $command)
            {
                array_push($this->unlockedCommands, $command);
            }
            next(Data::$levelData);
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
            ? current(Data::$levelData[$this->userRank->prev()->value])
            : current($this->currentLevelData);
    }

    public function getCurrentMessage()
    {
        $response = Data::getNewSpellMessage($this->newestSpell);
        if(empty ($response))
            $response = "coming soon!";
        self::writeMessage(colorizeString($response, "guide"));
    }

    public function writeMessage($message)
    {

        Streams::$stdout[] = 
        "------------------------------ <br>" .
            $message . "<br>
        ------------------------------ <br><br>";
        $_SESSION["terminal"]->editLastHistory();
    }
    // public function getNextSpell()
    // {
    //     $next =  $this->currentLevelData[$this->currentSubLvl + 1];
    //     return $this->currentSubLvl <= count($this->currentLevelData)
    //         ?  $this->currentLevelData[$this->currentSubLvl + 1]
    //         : false;
    // }

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
