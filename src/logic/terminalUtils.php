
<?php
function &getRoomOrItem($path, $tempRoom = null): mixed
{
    try
    {
        return getRoom($path);
    }
    catch (Exception $e)
    {
        return getItem($path);
    }
}

function &getRoom($path, $rankRestrictive = false): Room
{
    $index = 0;
    $tempRoom = &$_SESSION["curRoom"];

    if (empty($path) || $path[0] == ".")
    {
        return $tempRoom;
    }

    switch ($path[0])
    {
        case "":
            {
                return getRoomAbsolute($path);
            }
        case '..':
            {
                if ($_SESSION["curRoom"]->name == "hall")
                {
                    throw new Exception("invalid path", 0);
                }
                while ($index < count($path) && $path[$index] == '..')
                {
                    $index++;
                }
                $tempRoom = &getRoomAbsolute(array_slice($_SESSION["curRoom"]->path, 0, -$index), $rankRestrictive);
            }
        default:
            {
                if ($index == count($path))
                    return $tempRoom;
                else
                    return getRoomRelative(array_slice($path, $index), $tempRoom);
            }
    }
}
function &getRoomAbsolute($path, $rankRestrictive = false): Room
{
    $path = removeFirstIfEmpty($path);
    $tempRoom = &$_SESSION["map"];
    for ($i = 0; $i < count($path); $i++)
    {
        if (in_array($path[$i], array_keys($tempRoom->doors)))
        {
            if ($rankRestrictive && $_SESSION["GameEngine"]->userRank->isLowerThan($tempRoom->doors[$path[$i]]->requiredRank))
            {
                throw (new Exception("rank too low"));
            }
            $tempRoom = &$tempRoom->doors[$path[$i]];
        }
        else
        {
            throw (new Exception("path not found absolute"));
        }
    }
    return $tempRoom;
}
function &getRoomRelative($path, $rankRestrictive = false): Room
{
    $tempRoom = &$_SESSION["curRoom"];

    for ($i = 0; $i < count($path); $i++)
    {
        if (in_array($path[$i], array_keys($tempRoom->doors)))
        {
            if ($rankRestrictive && $_SESSION["GameEngine"]->userRank->isLowerThan($tempRoom->doors[$path[$i]]->requiredRank))
            {
                throw (new Exception("Rank too low, required Rank: " . $tempRoom->doors[$path[$i]]->requiredRank->value));
            }
            $tempRoom = &$tempRoom->doors[$path[$i]];
        }
        else
        {
            throw (new Exception("path not found relative"));
        }
    }
    return $tempRoom;
}
function &getItem($path)
{
    if (count($path) > 1)
    {
        $tempRoom = &getRoom(array_slice($path, 0, count($path) - 1));
    }
    else
    {
        $tempRoom = &$_SESSION["curRoom"];
    }

    if (in_array(
        end($path),
        array_keys($tempRoom->items)
    ))
        return $tempRoom->items[end($path)];
    else
        throw new Exception("item not found");
}
function deleteElements($paths, $deleteOnlyRooms = false, $rankRestrictive = true)
{
    $deleteRooms = hasOption("-d");
    foreach ($paths as $path)
    {
        $parentRoom = &getRoomOrItem(array_slice($path, 0, -1));
        $element = &getRoomOrItem($path);

        $higherRank = canDelete($path,  $element);
        if ($rankRestrictive && is_a($higherRank, Rank::class))
            throw new Exception("Rank to low, required Rank: " . $higherRank->value);

        if (is_a($element, Room::class))
        {
            if (!$deleteRooms && !$deleteOnlyRooms) throw new Exception("Element is a Room");
            unset($parentRoom->doors[end($path)]);
        }
        else
            unset($parentRoom->items[end($path)]);
    }
}

function RankIsHigherThanRoomRecursive(Rank $Rank, $room)
{
    if ($Rank->isLowerThan($room->requiredRank))
        return $room->requiredRank;

    if (!is_a($room, Room::class))
        return true;

    foreach ($room->doors as &$door)
    {
        if (RankIsHigherThanRoomRecursive($Rank, $door))
        {
            return $room->requiredRank;
        }
    }
    return true;
}

function updatePaths(&$room)
{
    updateItemPaths($room);

    foreach ($room->doors as &$door)
    {
        $door->path = array_merge($room->path, array($door->name));
        updatePaths($door);
    }
}
function moveWithCdOptions()
{
    switch (substr($_SESSION["inputCommand"], 3, 1))
    {
        case "-":
            {
                $_SESSION["curRoom"] = &getRoom(
                    array_pop($_SESSION["lastPath"])
                );
                break;
            }
        case "/":
            {
                pushNewLastPath($_SESSION["curRoom"]->path);
                $_SESSION["curRoom"] = &$_SESSION["map"];
                break;
            }
        default:
            {
                pushNewLastPath($_SESSION["curRoom"]->path);
                $_SESSION["curRoom"] = &getRoom(
                    $_SESSION["tokens"]["path"][0],
                    true
                );
                break;
            }
    }
}
function updateItemPaths(&$room)
{
    foreach ($room->items as $item)
    {
        $item->path = array_merge($room->path, (array) $item->name);
    }
}
function pushNewLastPath(array $newPath)
{
    $lastPathCount = count($_SESSION["lastPath"]);
    if ($lastPathCount > 10)
    {
        for ($i = 0; $i < $lastPathCount - 1; $i++)
        {
            $_SESSION["lastPath"][$i] = $_SESSION["lastPath"][$i + 1];
        }
        array_pop($_SESSION["lastPath"]);
    }

    array_push($_SESSION["lastPath"], $newPath);
}
function getLsArray($tempRoom)
{
    $tempLsArray = [];

    if (!empty($_SESSION["tokens"]["options"]) && in_array("-l", $_SESSION["tokens"]["options"]))
    {
        foreach (array_merge($tempRoom->doors, $tempRoom->items) as $element)
        {
            $tempEntry = [];
            $tempEntry[0] = $element->requiredRank->value;
            $tempEntry[1] = $element->timeOfLastChange;
            $tempEntry[2] = $element->name;

            array_push($tempLsArray, $tempEntry);
        }
        StateManager::$stdout = $tempLsArray;
    }
    else
    {
        $finalArray = array_merge(array_keys($tempRoom->doors), array_keys($tempRoom->items));
        StateManager::$stdout = $finalArray;
    }
}
function callCorrectGrepFunction()
{
    $matchingLines = [];
    $searchMatching = true;
    $searchRecursive = false;
    $isCaseInsensitive = false;

    getOptionsGrep(
        $searchMatching,
        $searchRecursive,
        $isCaseInsensitive
    );

    $matchingLines = [];
    if (isset($_SESSION["tokens"]["path"][0]))
    {
        $grepElement = getRoomOrItem($_SESSION["tokens"]["path"][0]);

        if (is_a($grepElement, Room::class))
        {
            $matchingLines = grepDirectory(
                room: $grepElement,
                condition: $_SESSION["tokens"]["strings"][0],
                searchMatching: $searchMatching,
                isCaseInsensitive: $isCaseInsensitive,
                searchRecursive: $searchRecursive,
            );
            echo "<br>matching lines ADSD" . json_encode($matchingLines);
        }
        else
        {
            $matchingLines = grepText(
                $grepElement->content,
                $_SESSION["tokens"]["strings"][0],
                $grepElement->path,
                searchMatching: $searchMatching,
                isCaseInsensitive: $isCaseInsensitive,
            );
        }
    }
    else
    {
        if (empty(StateManager::$stdout))
            throw new Exception("no path provided");
        foreach (StateManager::$stdout as $key => $line)
        {
            if (grepLine(
                $line,
                $_SESSION["tokens"]["strings"][0],
                searchMatching: $searchMatching,
                isCaseInsensitive: $isCaseInsensitive,
            ))
            {
                $matchingLines[$key] = $line;
            }
        }
        StateManager::$stdout = [];
    }
    return $matchingLines;
}
function grepDirectory(
    $room,
    $condition,
    $searchMatching = true,
    $searchRecursive = false,
    $isCaseInsensitive = false,
)
{
    $grepOutput = [];

    foreach ($room->items as $item)
    {
        $grepOutput = array_merge($grepOutput, grepText(
            $item->content,
            $condition,
            $item->path,
            $searchMatching,
            $isCaseInsensitive
        ));
    }
    if ($searchRecursive)
    {
        foreach ($room->doors as $door)
        {
            $grepOutput = array_merge($grepOutput, grepDirectory(
                room: $door,
                condition: $condition,
                searchMatching: $searchMatching,
                searchRecursive: $searchRecursive,
                isCaseInsensitive: $isCaseInsensitive,
            ));
        }
    }
    return $grepOutput;
}
function toPath($pathString)
{
    return explode("/", $pathString);
}
function grepText(
    $content,
    string $condition,
    $path = [],
    $searchMatching = true,
    $isCaseInsensitive = false,
)
{
    $lines = getLinesFromText($content);
    return grepArray(
        $lines,
        $condition,
        $searchMatching,
        $isCaseInsensitive
    );
}

function grepLine(
    $tempLine,
    $condition,
    $isCaseInsensitive = false,
    $searchMatching = true,
)
{

    if ($isCaseInsensitive)
    {
        if ((bool)stristr($tempLine, (string)$condition) == $searchMatching)
        {
            return true;
        }
    }
    else
    {
        if ((bool)strstr($tempLine, (string)$condition) == $searchMatching)
        {
            return true;
        }
    }
    return false;
}
function grepArray($array, $condition, $searchMatching, $isCaseInsensitive)
{
    $matchingLines = [];
    foreach ($array as $key => $line)
    {
        if (grepLine(
            $line,
            $condition,
            $isCaseInsensitive,
            $searchMatching,
        ))
        {
            $matchingLines[$key . ": "] = $line;
        }
    }
    return $matchingLines;
}
function getLinesFromText($text)
{
    $textLen = strlen($text);
    $seperators = [".", "\n"];
    $lineCount = 0;
    $lines = [];
    $strOffset = 0;
    for ($i = 0; $i < $textLen; $i++)
    {
        if (in_array($text[$i], $seperators) || $i == $textLen - 1)
        {
            $line = substr($text, $strOffset + 1, $i - $strOffset);
            if ($line == "") continue;
            $lines[] = $line;
            $strOffset = $i + 1;
            $lineCount++;
        }
    }
    return $lines;
}
function counNonEmpty($array)
{
    $counter = 0;
    foreach ($array as $element)
    {
        if (!empty($element))
        {
            $counter++;
        }
    }
    return $counter;
}

function checkIfNamesExists(array $names, $hayStack): bool
{
    foreach ($names as $name)
    {
        if (array_key_exists($name, $hayStack));
        {
            return true;
        }
    }
    return false;
}

function createPrompt($prompt, $validAnswers = ["y", "n"])
{
    //TODO move prompt logic somewhere else
    StateManager::$promptData["prompt"] = $prompt . "&nbsp DEFAULT:&nbsp " . $validAnswers[0] . "<br>" . implode("/", $validAnswers);
    StateManager::$promptData["options"] = ["y", "n"];
    StateManager::$stdout = $_SESSION["promptData"]["prompt"];
    StateManager::addNewHistory();
    throw new Exception("", 0);
}

function isNameValid($name, $suffix = "", $additionalInvalidChars = [])
{
    $invalidChars = array_merge(["..", "*", "/", "&", "|", ""], $additionalInvalidChars);

    return
        str_ends_with($name, $suffix) &&
        !(bool)getLastOccuringElementIn(substr($name, 0, strlen(string: $suffix)), $invalidChars);
}

function colorizeString($string, $class = "")
{
    if ($class == "") $class = $string;
    return "<span class='" . $class . "'>" . $string . "</span>";
}

function spaceOf($length)
{
    $space = "";
    for ($i = 0; $i < $length; $i++)
    {
        $space .= "&nbsp ";
    }
    return $space;
}
function getElementsFind(
    $room,
    $findFunction,
    $findString
)
{
    $result = getElementsByNameWild(
        $room,
        $findFunction,
        $findString
    );
    foreach ($room->doors as $door)
    {
        $result = array_merge(
            $result,
            getElementsFind(
                $door,
                $findFunction,
                $findString
            )
        );
    }
    return $result;
}

function copyElementsTo($elements, &$destRoom)
{
    $shouldRename = !empty($_SESSION["tokens"]["misc"]);
    $newName = $shouldRename ? $_SESSION["tokens"]["misc"] : "";

    if ($shouldRename  && count($elements) > 1)
        throw new Exception("Cant rename multiple elements at once");

    if (wouldReplaceElemment($elements, $newName) && !isset($_SESSION["promptData"]))
        createPrompt("replace existing elements?", ["y", "n"]);

    for ($i = 0; $i < count($elements); $i++)
    {
        $name = $shouldRename ? $newName : $elements[$i]->name;

        if (is_a($elements[$i], Room::class))
        {
            if (!isNameValid($name)) throw new Exception("invalid name");

            $destRoom->doors[$name] = clone $elements[$i];
            $destRoom->doors[$name]->name = $name;
        }
        else
        {
            if (!isNameValid($name, $elements[$i]->type->value)) throw new Exception("invalid name");

            $destRoom->items[$name] = clone $elements[$i];
            $destRoom->items[$name]->name = $name;
            $destRoom->items[$name]->baseName = substr($name, 0, -4);
        }
    }
}

function wouldReplaceElemment($elements, $newName)
{
    $threshhold = empty($_SESSION["tokens"]["path"][1]) ? 1 : 0;
    return count($elements) - count(array_diff(array_merge($elements, (array)[$newName]))) > $threshhold;
}
function getElementsByNameWild($room, $findFunction, $findString)
{
    $matches = [];
    foreach (array_merge($room->items, $room->doors,) as $element)
    {
        if (cmpStrWildcard($element->name, $findString, $findFunction))
        {
            $matches[] = $element;
        }
    }
    return $matches;
}
function getMatchingElements()
{
    $cmpFunction = "";
    $elementName = end($_SESSION["tokens"]["path"][0]);
    getWildCardStringAndFunction($elementName, $cmpFunction);
    $matches = getElementsByNameWild(getRoom(array_slice($_SESSION["tokens"]["path"][0], 0, -1)), $cmpFunction, $elementName);
    return count($matches) > 0
        ? $matches
        : throw new Exception("No elements found");
}
function getOptionsFind(&$substr, &$findFunction)
{
    if (empty($_SESSION["tokens"]["keyValueOptions"])) return;
    foreach ($_SESSION["tokens"]["keyValueOptions"] as $key => $value)
    {
        switch ($key)
        {
            case "-name":
                {
                    $substr = $value;
                    getWildCardStringAndFunction($substr, $findFunction);
                }
        }
    }
}
function getOptionsGrep(&$searchMatching, &$searchRecursive, &$isCaseInsensitive)
{
    if (isset($_SESSION["tokens"]["options"]))
    {
        foreach ($_SESSION["tokens"]["options"] as $flag)
        {
            echo "<br> setting option: " . $flag;
            match ($flag)
            {
                "-v" => $searchMatching = false,
                "-r" => $searchRecursive = true,
                "-i" => $isCaseInsensitive = true,
            };
        }
    }
}

function getWildCardStringAndFunction(&$substr, &$cmpFunction = "")
{
    switch (substr_count($substr, "*"))
    {
        case 0:
            {
                $cmpFunction = "strcmp";
                return;
            }
        case 1:
            {
                if (substr($substr, 0, 1) == "*")
                {
                    $substr = substr($substr, 1);
                    $cmpFunction = "str_ends_with";
                }
                else if (substr($substr, -1, 1) == "*")
                {
                    $substr = substr($substr, 0, -1);
                    $cmpFunction = "str_starts_with";
                }
                return;
            }
        case 2:
            {
                if (
                    substr($substr, 0, 1) == "*"
                    && substr($substr, -1) == "*"
                )
                {
                    $cmpFunction = "strstr";
                    $substr = substr($substr, 1, -1);
                    return;
                }
            }
        default:
            throw new Exception("false usage of '*' operator");
    }
}
function cmpStrWildcard($baseStr, $substr, $cmpFunction)
{
    return
        $substr == "" ||
        $cmpFunction($baseStr, $substr) ==
        ($cmpFunction != "strcmp");
}
function getCounts($lines)
{
    $counts = [];
    if (empty($_SESSION["tokens"]["options"]))
    {
        $counts["lines"] = count($lines);
        $counts["words"] = str_word_count(implode(" ", $lines));
        $counts["characters"] = strlen(implode(" ", $lines));
        return $counts;
    }
    foreach ($_SESSION["tokens"]["options"] as $option)
    {
        match ($option)
        {
            "-l" => $counts["lines"] = count($lines),
            "-w" => $counts["words"] = str_word_count(implode(" ", $lines)),
            "-c" => $counts["characters"] = strlen(implode(" ", $lines)),
        };
    }
    return $counts;
}
function getLines()
{
    return isset(StateManager::$stdout) ?
        StateManager::$stdout :
        getLinesFromText(getItem($_SESSION["tokens"]["path"][0])->content);
}

function getPartialArray($lines, $fromTop = true)
{
    $count = isset($_SESSION["tokens"]["keyValueOptions"]["-n"]) ? $_SESSION["tokens"]["keyValueOptions"]["-n"] : 10;
    return $fromTop ?
        $lines = array_slice($lines, 0, $count) :
        $lines = array_slice($lines, -$count);
}

function openScrollIfIsScroll($textFile)
{
    if (is_a($textFile, Scroll::class))
    {
        $textFile->openScroll();
    }
}
function canDelete($path, $element = NULL)
{
    if (
        isset($_SESSION["tokens"]["path"][1]) &&
        isset($_SESSION["tokens"]["command"]) != "rm" &&
        is_a($element, Room::class) &&
        count(array_diff($path, getRoom($_SESSION["tokens"]["path"][1])->path)) == 0
    )
    {
        throw new Exception("Cant move room into itsself",);
    }
    $result = RankIsHigherThanRoomRecursive($_SESSION["GameEngine"]->userRank, getRoomOrItem($path));

    return $result;
}

function getPathsFromElements($elements)
{
    $paths = [];
    foreach ($elements as $element)
    {
        array_push($paths, $element->path);
    }
    return $paths;
}
function pathArrayFromElements($elements)
{
    $pathArray = [];
    foreach ($elements as $element)
    {
        $pathArray[] = implode("/", $element->path);
    }
    return $pathArray;
}



function isExecutable($element)
{
    return is_a($element, Alter::class) || is_a($element, Spell::class);
}
function colorizeRanks($text)
{
    $colorizedResponse = "";
    foreach (explode(" ", $text) as $word)
    {
        $colorizedResponse .= Rank::tryFrom(strtolower($word))
            ? colorizeString($word) . " "
            : $word . " ";
    }
    return $colorizedResponse;
}
function checkForRune()
{
    $arg = $_SESSION["tokens"]["strings"][0];
    $spells = [];
    foreach ($_SESSION["curRoom"]->items as $item)
    {
        // $isA = is_a($item, Spell::class);
        // $is2 = strtolower($item->key) == $arg;
        // $lower = strtolower($item->key);
        // $nextSpell = $_SESSION["GameEngine"]->getNextSpell();
        // $isSame = $item->spellReward->value == $_SESSION["GameEngine"]->getNextSpell();
        if (
            is_a($item, Spell::class) &&
            $item->key == $arg &&
            $item->spellReward->value == $_SESSION["GameEngine"]->getNextSpell()
        )
        {
            StateManager::addNewHistory();
            $_SESSION["GameEngine"]->unlockNextCommand();
            StateManager::reset();
            throw new Exception("", -1);
        }
    }
}

function removeFirstIfEmpty($array)
{
    return reset($array) == ""
        ? array_slice($array, 1)
        : $array;
}
function removeLastIfEmpty($array)
{
    return end($array) == ""
        ? array_slice($array, 0, -1)
        : $array;
}

function hasOption($flag)
{
    return in_array($flag, $_SESSION["tokens"]["options"]);
}
function getLastOccuringElementIn($needle, $haystack = [">>", ">", "||", "|", "&&",])
{
    for ($i = strlen($needle); $i > 0; $i--)
    {
        foreach ($haystack as $element)
        {
            $len = strlen($element);
            $substr = substr($needle, $i - $len, $len);
            if ($substr == $element)
            {
                return $element;
            }
        }
    }
    return false;
}
function renderGrid(array $grid)
{
    if (count($grid) == 0)
        return "";

    $columnCount = count($grid[0]);
    $rowCount = count($grid);

    $lines = [];
    for ($i = 0; $i < $columnCount; $i++)
    {
        $longest = 5;
        for ($j = 0; $j < $rowCount; $j++)
        {
            if (strlen($grid[$j][$i]) + 5 > $longest)
            {
                $longest = strlen($grid[$j][$i]) + 5;
            }
            $lines[$j] .= $grid[$j][$i] . " ";
        }
        for ($j = 0; $j < count($lines); $j++)
        {
            $lines[$j] .= spaceOf((int)(($longest - strlen($grid[$j][$i])) * 0.9));
        }
    }

    return implode("<br>", $lines);
}
