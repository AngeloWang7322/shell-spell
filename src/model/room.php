<?php
class Room
{
    public $name;
    public array $path = [];

    /** @var Room[] $doors */
    public array $doors;

    /** @var Item[] $items */
    public array $items = [];

    public Rank $requiredRank;
    public string $timeOfLastChange;
    public bool $isHidden;

    function __construct(
        $name,
        array $path = [],
        $doors = [],
        $items = [],
        $requiredRank = Rank::WANDERER,
        $curDate = true,
        $isHidden = false,
        $date = NULL,
    )
    {
        $this->name = $name;
        $this->path = $path;
        $this->doors = $doors;
        $this->items = $items;
        $this->timeOfLastChange = generateDate($curDate);
        $this->isHidden = $isHidden;

        $this->timeOfLastChange = ($date == NULL) ?
            generateDate($curDate) :
            $date;
        //if statement nur im development noetig
        if ($name != "hall")
        {
            $this->path = empty($path) ? $_SESSION["curRoom"]->path : $path;

            array_push($this->path, $name);
        }
        $this->requiredRank = $requiredRank;
    }
    public static function fromArray($data): Room
    {
        $doors = [];
        foreach ($data->doors as $key => $roomData)
        {
            $doors[$key] = self::fromArray($roomData);
        }

        $items = [];
        foreach ($data->items as $key => $itemData)
        {
            $items[$key] = match ($itemData->type)
            {
                ItemType::SCROLL->value => Scroll::fromArray((array) $itemData),
                ItemType::SPELL->value => Spell::fromArray((array) $itemData),
                ItemType::ALTER->value => Alter::fromArray((array) $itemData),
            };
        }

        $path = $data->path;
        if (count($path) > 1)
        {
            $path = array_slice($data->path, 0, -1);
        }

        $requiredRank = Rank::from((string) $data->requiredRank);

        return new Room(
            $data->name,
            $path,
            $doors,
            $items,
            $requiredRank,
            true,
            $data->isHidden,
            $data->timeOfLastChange,
        );
    }
}
function copyRoom(Room $room)
{
}
