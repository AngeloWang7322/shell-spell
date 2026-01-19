<?php

use function PHPSTORM_META\type;

class Command
{
    public $commandName;
    public array $tokenSyntax;
    public array $validOptions;
    public array $validKeyValueOptions;
    public string $description;
    public string $isWriter;
    public bool $isReader;
    public string $commandParser;
    public string $pathParser;
    public string $stringParser;
    public string $miscParser;
    public string $optionParser;
    public string $keyValueOptionParser;

    public function __construct(
        $commandName,
        $tokenSyntax,
        $validOptions = [],
        $validKeyValueOptions = [],
        $description = "",
        $isWriter = false,
        $isReader = false,
        $commandParser = "parseCommand",
        $pathParser = "parsePath",
        $stringParser = "parseString",
        $miscParser = "valiateMisc",
        $optionParser = "parseOption",
        $keyValueOptionParser = "parsekeyValueOption",
    )
    {
        $this->commandName = $commandName;
        $this->tokenSyntax = array_merge([TokenType::COMMAND], $tokenSyntax);
        $this->validOptions = $validOptions;
        $this->validKeyValueOptions = $validKeyValueOptions;
        $this->description = $description;
        $this->isWriter = $isWriter;
        $this->isReader = $isReader;
        $this->commandParser = $commandParser;
        $this->pathParser = $pathParser;
        $this->stringParser =  $stringParser;
        $this->miscParser =  $miscParser;
        $this->optionParser = $optionParser;
        $this->keyValueOptionParser = $keyValueOptionParser;
    }
    public function parseInput()
    {
        $syntaxArray = $this->tokenSyntax;
        $tokens = self::createTokens();

        for ($i = 0; $i < count($tokens); $i++)
        {
            $arg = $tokens[$i];

            switch (current($syntaxArray))
            {
                case TokenType::COMMAND:
                    {
                        $function = $this->commandParser;
                        $_SESSION["tokens"]["command"] = self::$function($arg);
                        break;
                    }
                case TokenType::OPTION:
                    {
                        $function = $this->optionParser;
                        self::$function($arg, $syntaxArray, $i);
                        break;
                    }
                case TokenType::KEYVALUEOPTION:
                    {
                        $function = $this->keyValueOptionParser;
                        self::$function($arg, $tokens, $syntaxArray, $i);
                        break;
                    }
                case TokenType::PATH:
                    {
                        $function = $this->pathParser;
                        if ((bool)self::$function(explode("/", $arg), $tokens, $syntaxArray, $i))
                        {
                            $_SESSION["tokens"]["path"][] = explode("/", $arg);
                        }
                        break;
                    }
                case TokenType::STRING:
                    {
                        $function = $this->stringParser;
                        $_SESSION["tokens"]["strings"][] = self::$function($arg);
                        break;
                    }
                case TokenType::MISC:
                    {
                        $function = $this->miscParser;
                        $_SESSION["tokens"]["misc"] = self::$function($arg);
                        break;
                    }
            }
            if (next($syntaxArray) == false)
            {
                end($syntaxArray);
            }
        }
    }
    public function createTokens(): array
    {
        $inputStr = $_POST["command"];
        $tokens = [$this->commandName];

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
    static public function parseCommand($arg)
    {
        if (Commands::tryFrom($arg) != NULL)
        {
            return $arg;
        }
        else
        {
            if (in_array($arg, Commands::cases()))
            {
                throw new Exception("command not unlocked yet");
            }
            else
            {
                throw new Exception("unknown commandasdasd");
            }
        }
    }
    static public function parsePath($path)
    {
        $validPathArgs = array_merge(array_keys($_SESSION["curRoom"]->doors), array_keys($_SESSION["curRoom"]->items), ["hall", "/", "-", ".."]);
        if (in_array($path[0], $validPathArgs))
        {
            return $path;
        }
        else
        {
            throw new Exception("invalid path provided");
        }
    }
    static public function parseString($arg): string
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
    static public function parseMisc($arg)
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
    public function parseOption($arg, &$syntaxArray, &$argIndex)
    {
        if (substr($arg, 0, 1) == '-')
        {
            if (in_array($arg, $this->validOptions))
            {
                $_SESSION["tokens"]["options"][] = $arg;
                prev($syntaxArray);
            }
        }
        else
        {
            $argIndex--;
        }
    }
    public function parseKeyValueOption($option, $tokens, &$syntaxArray, &$argIndex)
    {
        if (substr($option, 0, 1) == '-' && $argIndex <= count($tokens))
        {
            if (in_array($option, array_keys($this->validKeyValueOptions)))
            {
                switch (gettype($this->validKeyValueOptions[$option]))
                {
                    case "string":
                        {
                            $_SESSION["tokens"]["keyValueOptions"][$option] = $this->parseString($tokens[$argIndex + 1]);
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
    static public function parsePathNew($mkdirPath,  &$syntaxArray, &$argIndex)
    {
        return match (true)
        {
            $mkdirPath[0] == ""
            => throw new Exception("now argument provided"),
            count($mkdirPath) == 1
            => $mkdirPath,
            default
            => self::parsePath(array_slice($mkdirPath, 0, -1)),
        };
    }
    static public function parsePathFind($path)
    {
        return $path[0] == "." ? $path : self::parsePath($path);
    }
    static public function parsePathOptional($path)
    {
        if (!isset($_SESSION["stdout"]))
        {
            try
            {
                return self::parsePath($path);
            }
            catch (Exception $e)
            {
                return false;
            }
        }

        return false;
    }
    static public function parsePathRename($path, $tokens, &$syntaxArray, &$argIndex)
    {

        // if (substr($tokens[$argIndex], -1) != "/")
        // {
        //     $_SESSION["tokens"]["path"][] = array_slice($path, 0, -1);

        //     if (!empty($_SESSION["tokens"]["path"][0]))
        //     {
        //         $_SESSION["tokens"]["misc"] =  end($path);
        //         $_SESSION["tokens"]["path"][] = array_slice($path, 0, -1);
        //         return false;
        //     }
        // }
        // return self::parsePath($path);
        if (substr($tokens[$argIndex], -1) != "/" && !empty($_SESSION["tokens"]["path"][0]))
        {
            $_SESSION["tokens"]["misc"] =  end($path);
            $_SESSION["tokens"]["path"][] = array_slice($path, 0, -1);
            return false;
        }
        else
        {
            $end = end($path);
            if (end($path) == "")
            {
                $_SESSION["tokens"]["path"][] = self::parsePath(array_slice($path, 0, -1));
                return false;
            }
            return self::parsePath($path);
        }
    }
}

function getCommand($command)
{
    return match (true)
    {
        "cd" == $command
        => new Command(
            "cd",
            [TokenType::PATH],
            [],
            [],
            "movin around",
        ),
        "mkdir" == $command
        => new Command(
            "mkdir",
            [TokenTYPE::OPTION, TokenType::PATH],
            [],
            [],
            "",
            pathParser: "parsePathNew"
        ),
        "rm" == $command
        => new Command(
            "rm",
            [TokenTYPE::OPTION, TokenType::PATH],
            [],
            [],
            "",
        ),
        "mv" == $command
        => new Command(
            "mv",
            [TokenTYPE::OPTION, TokenType::PATH, TokenType::PATH],
            [],
            [],
            "",
            pathParser: "parsePathRename"
        ),
        "pwd" == $command
        => new Command(
            "pwd",
            [],
            [],
            [],
            "",
            true
        ),
        "ls" == $command
        => new Command(
            "ls",
            [TokenTYPE::OPTION, TokenType::PATH],
            ["-l", "-a"],
            [],
            "",
            true,
        ),
        "cp" == $command
        => new Command(
            commandName: "cp",
            tokenSyntax: [TokenTYPE::OPTION, TokenType::PATH, TokenType::PATH],
            validOptions: [],
            validKeyValueOptions: [],
            description: "",
        ),
        "grep" == $command
        => new Command(
            "grep",
            [TokenTYPE::OPTION, TokenType::STRING, TokenType::PATH],
            ["-r", "-i", "-v"],
            [],
            "",
            true,
            true,
        ),
        "find" == $command
        => new Command(
            "find",
            [TokenType::PATH, TokenType::KEYVALUEOPTION],
            [],
            ["-name" => "string"],
            "",
            true,
            pathParser: "parsePathFind"
        ),
        "./" == substr($command, 0, 2)
        => new Command(
            "execute",
            [TokenType::PATH],
            [],
            [],
            "",
        ),
        "echo" == $command
        => new Command(
            "echo",
            [TokenType::STRING],
            [],
            [],
            "",
            true,
        ),
        "man" == $command
        => new Command(
            "man",
            [TokenTYPE::MISC],
            [],
            [],
            "",
            true,
        ),
        "cat" == $command
        => new Command(
            "cat",
            [TokenTYPE::PATH],
            [],
            [],
            "",
            true,
        ),
        "touch" == $command
        => new Command(
            "touch",
            [TokenTYPE::PATH],
            [],
            [],
            "",
            true,
            pathParser: "parsePathNew",
        ),
        "wc" == $command
        => new Command(
            "wc",
            [TokenType::OPTION, TokenType::PATH],
            ["-l", "-w"],
            [],
            "",
            true,
            true,
            pathParser: "parsePathOptional"
        ),
        "head" == $command
        => new Command(
            "head",
            [TokenType::KEYVALUEOPTION, TokenType::PATH],
            [],
            ["-n" => 10],
            "",
            true,
            true,
            pathParser: "parsePathOptional"
        ),
        "tail" == $command
        => new Command(
            "tail",
            [TokenType::KEYVALUEOPTION, TokenType::PATH],
            [],
            ["-n" => 10],
            "",
            true,
            true,
            pathParser: "parsePathOptional"
        ),
        "nano" == $command
        => new Command(
            "nano",
            [TokenType::PATH],
            [],
            [],
            "",
        ),
        default => throw new Exception("unknown command")
    };
}
