<?php

declare(strict_types=1);
class GameController
{
    public static $levelData = [
        // &&
        Role::WANDERER->value => [
            Commands::EXECUTE,
            Commands::LS,
            Commands::CD,
            Commands::CAT
        ],
        // ||
        Role::APPRENTICE->value => [
            Commands::MKDIR,
            Commands::RM,
            Commands::PWD,
            Commands::ECHO,
            Commands::MAN
        ],
        // >>, >
        Role::ARCHIVIST->value => [
            Commands::CP,
            Commands::MV,
            Commands::MV,
            Commands::NANO,
            Commands::TOUCH
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
    public function __construct(
        $xp = 1
    )
    {
        self::calculateGameStats($xp);
        $this->xpPercentage = self::calculateLevelPercentage();
        $this->xp = $xp;
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

        // $currentCount = count(array_intersect(self::$levelData[$this->userRank->value], $this->current));
        return (int)round(($counter / $lvlCount) * 100);
    }
    public function calculateGameStats($xp)
    {
        $this->userRank = Role::getRoleFromRank((int) floor($xp / 100) + 1);
        $this->currentLevelData = self::$levelData[$this->userRank->value];
        $this->latestCommand = reset($this->currentLevelData);
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
        $this->latestCommand = current($this->currentLevelData);
    }
}
