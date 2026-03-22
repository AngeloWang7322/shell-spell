<?php
require __DIR__ . "/../assets/data.php";
class Item
{
    public string $name;
    public string $baseName;
    public ItemType $type;
    public Rank $requiredRank;
    public array $path;
    public string $content;
    public string $timeOfLastChange;
    public bool $isExecutable;

    public function __construct(
        $name,
        $baseName,
        $path,
        $requiredRank = Rank::WANDERER,
        $content = "gibberish",
        $curDate = true,
        $date = "",
        $isExecutable = false
    )
    {
        $this->name = $name;
        $this->baseName = $baseName;
        $this->requiredRank = $requiredRank;
        $this->path = $path;
        $this->content = $content;
        $this->isExecutable = $isExecutable;
        $this->timeOfLastChange = !$date == ""
            ? $date
            :  generateDate($curDate);

        if (empty($name))
        {
            $this->name = $baseName . "." . $this->type->value;
        }
        else
        {
            $this->name = $name;
        }
        array_push($this->path, $this->name);
    }
}
class Scroll extends Item
{
    public bool $isOpen = false;
    public function __construct(
        $name,
        $baseName,
        array $path = [],
        $requiredRank = Rank::WANDERER,
        string $content = "",
        $curDate = true,
        $date = "",
    )
    {
        $this->type = ItemType::SCROLL;

        parent::__construct(
            $name,
            $baseName,
            $path,
            $requiredRank,
            $content,
            $curDate,
            $date,
        );
    }
    public static function fromArray(array $data)
    {
        $requiredRank = Rank::from($data["requiredRank"]);
        return new self(
            name: $data['name'],
            baseName: $data["baseName"],
            path: pathFromArray($data["path"]),
            requiredRank: $requiredRank,
            content: $data['content'],
            date: $data["timeOfLastChange"],
        );
    }
    function openScroll()
    {
        $_SESSION["openedScroll"]["header"] = $this->name;
        $_SESSION["openedScroll"]["content"] = $this->content;
        $_SESSION["openedScroll"]["path"] = $this->path;
    }
}
class Alter extends Item
{
    public bool $isActive;
    public $requiredElements;

    public function __construct(
        $name,
        $baseName,
        array $path = [],
        $requiredRank = Rank::WANDERER,
        $content = "",
        $isActive = true,
        $requiredElements = [],
        $curDate = false,
        $date = "",
    )
    {
        $this->type = ItemType::ALTER;
        $this->isActive = $isActive;
        $this->requiredElements = $requiredElements;

        parent::__construct(
            $name,
            $baseName,
            $path,
            $requiredRank,
            $content,
            $curDate,
            $date,
            true
        );
    }
    public function execute()
    {
        $_SESSION["gameState"]->levelUpUser($this);
    }
    public static function fromArray(array $data)
    {
        return new self(
            name: $data['name'],
            baseName: $data["baseName"],
            path: pathFromArray($data["path"]),
            requiredRank: Rank::from($data["requiredRank"]),
            content: $data["content"],
            isActive: $data["isActive"],
            requiredElements: $data["requiredElements"],
            date: $data["timeOfLastChange"],
        );
    }
}
class Spell extends Item
{
    public Commands $rewardSpell;

    public function __construct(
        $name,
        $baseName,
        $path,
        $requiredRank = Rank::WANDERER,
        $content = "",
        $rewardSpell,
        $curDate = true,
        $date = ""
    )
    {
        $this->type = ItemType::SPELL;
        $this->rewardSpell = $rewardSpell;

        parent::__construct(
            $name,
            $baseName,
            $path,
            $requiredRank,
            $content,
            $curDate,
            $date,
            true
        );
    }

    public function execute()
    {
        $sandbox = Data::getSandBox($this->rewardSpell);
        $sandbox->prepare();
        $sandbox->writeCurrentPrompt();
    }
    
    public static function fromArray(array $data)
    {
        // $requiredRank = Rank::from($data["requiredRank"]);
        // $rewardSpell = Commands::from($data["rewardSpell"]);
        // return new self(
        //     name: $data['name'],
        //     baseName: $data["baseName"],
        //     path: pathFromArray($data["path"]),
        //     requiredRank: $requiredRank,
        //     content: $data["content"],
        //     rewardSpell: $rewardSpell,
        //     key: $data["key"],
        //     date: $data["timeOfLastChange"],
        // );
    }
}
function pathFromArray($path)
{
    if (count($path) > 1)
    {
        $path = array_slice($path, 0, -1);
    }
    return $path;
}
function generateDate($curDate)
{
    if ($curDate)
    {
        return date("Y-m-d H:i:s", NULL);
    }
    return date("Y-m-d H:i:s", mktime(
        rand(0, 24),
        rand(0, 60),
        rand(0, 60),
        rand(1, 12),
        rand(1, 30),
        rand(1980 - (rand(1, 30)), 1980),
    ));
}
