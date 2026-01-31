<?php

declare(strict_types=1);

function createTokens(): array
{
    $inputStr = $_POST["command"];
    $tokens = [];
    $tempToken = "";
    $quoteCount = substr_count($inputStr, '"');
    if ($quoteCount % 2 == 1)
    {
        throw new Exception("incorrect string usage");
    }

    foreach (array_slice(explode(" ", $inputStr), 1) as $word)
    {
        $first = substr($word, 0, 1);
        $last = substr($word, -1, 1);

        if ($tempToken == "")
        {
            if (!in_array($first, ["'", '"']) && !in_array($last, ["'", '"']) || ($first == $last && strlen($word) > 1))
            {
                array_push($tokens, $word);
            }
            else
            {
                if (in_array($first, ["'", '"']))
                {
                    $tempToken = $word;
                }
                else
                {
                    throw new Exception("incorrect string usage");
                }
            }
        }
        else
        {
            if (!in_array($first, ["'", '"']) && !in_array($last, ["'", '"']))
            {
                $tempToken .= " " . $word;
            }
            else
            {
                if (in_array($last, ["'", '"']))
                {
                    $tempToken .= " " . $word;
                    array_push($tokens, $tempToken);
                    $tempToken = "";
                }
                else
                {
                    throw new Exception("incorrect string usage");
                }
            }
        }
    }
    return $tokens;
}
function parseCommand($arg)
{
    if (in_array($arg, $_SESSION["gameController"]->unlockedCommands))
    {
        return $arg;
    }
    else
    {
        if (Commands::tryFrom($arg))
        {
            throw new Exception("command not unlocked yet...");
        }
        else
        {
            throw new Exception("unknown command");
        }
    }
}
function parsePath($path, $tokens = "", &$syntaxArray = [], &$argIndex = NULL, $validChars = [])
{
    $validChars = array_merge($validChars, ["", "/", "-", ".."]);
    $validFirstPathArgs = array_merge(array_keys($_SESSION["curRoom"]->doors), array_keys($_SESSION["curRoom"]->items), $validChars);

    return
        in_array($path[0], $validFirstPathArgs) 
        // || count($path) == 1 && !empty(getWildCardStringAndFunction($path[0]) hab ich da muell geschrieben ?
        
        ?  $path
        : throw new Exception("invalid path provided");
}
function parseString($arg): string
{
    $firstAndLast = [substr($arg, 0, 1), substr($arg, -1, 1)];

    if (
        (in_array("'", $firstAndLast) || in_array('"', $firstAndLast))
        && $firstAndLast[0] == $firstAndLast[1]
    )
    {
        if (strlen($arg) <= 2)
        {
            throw new Exception("empty string given");
        }
        return substr($arg, 1, -1);
    }
    else
    {
        throw new Exception("empty string given");
    }
}
function parseMisc($arg)
{
    switch ($_SESSION["tokens"]["command"])
    {
        case "man":
            {
                if (in_array($arg, Commands::cases()))
                {
                    return $arg;
                }
                else
                {
                    throw new Exception("unknown misc");
                }
            }
    }
}
function parseOption($arg, $tokens, &$syntaxArray, &$argIndex, $validOptions)
{
    if (substr($arg, 0, 1) == '-')
    {
        if (in_array($arg, $validOptions))
        {
            $_SESSION["tokens"]["options"][] = $arg;
            prev($syntaxArray);
        }
        else
        {
            throw new Exception("invalid option '" . $tokens[$argIndex] . "'");
        }
    }
    else
    {
        $argIndex--;
    }
}
function parseKeyValueOption($option, $tokens, &$syntaxArray, &$argIndex, $validKeyValueOptions)
{
    if (substr($option, 0, 1) == '-' && $argIndex <= count($tokens))
    {
        if (in_array($option, array_keys($validKeyValueOptions)))
        {
            switch (gettype($validKeyValueOptions[$option]))
            {
                case "string":
                    {
                        $_SESSION["tokens"]["keyValueOptions"][$option] = parseString($tokens[$argIndex + 1]);
                        $argIndex++;
                        break;
                    }
                case "integer":
                    {
                        $_SESSION["tokens"]["keyValueOptions"][$option] = is_numeric($tokens[$argIndex + 1])
                            ? $tokens[$argIndex + 1]
                            : throw new Exception(($option . "entered, no integer recieved"));
                        $argIndex++;
                    }
            }
        }
        else
        {
            throw new Exception("incorrect option given");
        }
    }
    else
    {
        if (next($syntaxArray) == NULL)
        {
            throw new Exception("invalid syntax");
        }
        $argIndex--;
    }
}
function parsePathNew($mkdirPath, $tokens, &$syntaxArray, &$argIndex)
{
    return match (true)
    {
        $mkdirPath[0] == ""
        => throw new Exception("now argument provided"),
        count($mkdirPath) == 1
        => $mkdirPath,
        default
        => parsePath(array_slice($mkdirPath, 0, -1), $tokens, $syntaxArray, $argIndex),
    };
}
function parsePathFind($path, $tokens, &$syntaxArray, &$argIndex)
{
    return $path[0] == "." ? $path : parsePath($path, $tokens, $syntaxArray, $argIndex);
}
function parsePathOptional($path, $tokens, &$syntaxArray, &$argIndex)
{
    if (!isset($_SESSION["stdout"]))
    {
        try
        {
            return parsePath($path, $tokens, $syntaxArray, $argIndex);
        }
        catch (Exception $e)
        {
            return false;
        }
    }

    return false;
}
function parsePathRename($path, $tokens, &$syntaxArray, &$argIndex)
{
    if (substr($tokens[$argIndex], -1) != "/" && !empty($_SESSION["tokens"]["path"][0]))
    {
        $_SESSION["tokens"]["misc"] =  end($path);

        $_SESSION["tokens"]["path"][] = array_slice($path, 0, -1);
        $_SESSION["tokens"]["pathStr"][] = $tokens[$argIndex];
        return false;
    }
    else
    {
        if (end($path) == "")
        {
            $_SESSION["tokens"]["path"][] = parsePath(array_slice($path, 0, -1), $tokens, $syntaxArray, $argIndex,);
            $_SESSION["tokens"]["pathStr"][] = $tokens[$argIndex];

            return false;
        }
        return parsePath($path, $tokens, $syntaxArray, $argIndex);
    }
}
function parseStringEcho($path, $tokens, &$syntaxArray, &$argIndex)
{
    try
    {
        return parseString($tokens[$argIndex]);
    }
    catch (Exception $e)
    {

        return implode(" ", array_slice($tokens, 1));
    }
}
function parseMiscMan($arg)
{
    return parseCommand($arg);
}

function finalValidateMan(Command $man)
{
    if (empty($_SESSION["tokens"]["misc"])) throw new Exception("No spell given");
}
function finalValidateMv(Command $man)
{
    if (count($_SESSION["tokens"]["path"]) != 2) throw new Exception("must provide 2 paths");
}
