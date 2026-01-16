<?php
class Command
{
    public $commandName;
    public array $tokenSyntax;
    public array $validOptions;
    public string $description;
    public string $isWriter;
    public bool $isReader;
    public string $commandParser;
    public string $pathParser;
    public string $stringParser;
    public string $miscParser;
    public string $optionParser;

    public function __construct(
        $commandName,
        $tokenSyntax,
        $validOptions,
        $description,
        $isWriter = false,
        $isReader = false,
        $commandParser = "parseCommand",
        $pathParser = "parsePath",
        $stringParser = "parseString",
        $miscParser = "valiateMisc",
        $optionParser = "parseOption",
    )
    {
        $this->commandName = $commandName;
        $this->tokenSyntax = array_merge([TokenType::COMMAND], $tokenSyntax);
        $this->validOptions = $validOptions;
        $this->description = $description;
        $this->isWriter = $isWriter;
        $this->isReader = $isReader;
        $this->commandParser = $commandParser;
        $this->pathParser = $pathParser;
        $this->stringParser =  $stringParser;
        $this->miscParser =  $miscParser;
        $this->optionParser = $optionParser;
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
                case TokenType::PATH:
                    {
                        $function = $this->pathParser;
                        $_SESSION["tokens"]["path"][] = self::$function(explode("/", $arg),  $syntaxArray, $i);
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
                if (!in_array($first, ["'", '"']) && !in_array($last, ["'", '"']) || $first == $last)
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
    public function parsePath($arg)
    {
        $validPathArgs = array_merge(array_keys($_SESSION["curRoom"]->doors + $_SESSION["curRoom"]->items), ["hall", "/", "-", ".."]);
        if (in_array($arg[0], $validPathArgs))
        {
            echo "<br>path: " . json_encode($arg);
            return $arg;
        }
        else
        {
            throw new Exception("invalid path provided");
        }
    }
    public function parseString($arg): string
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
    public function parseMisc($arg)
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
            }
        }
        else
        {
            $argIndex--;
        }
    }
    public function parsePathNew($mkdirPath,  $syntaxArray, &$argIndex)
    {
        if (count($mkdirPath) <= 1)
        {
            return $mkdirPath;
        }
        else
        {
            return self::parsePath(array_slice($mkdirPath, 0, -1));
        }
    }
    public function parsePathGrep($mkdirPath,  $syntaxArray, &$argIndex)
    {
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
            "movin around",
        ),
        "mkdir" == $command
        => new Command(
            "mkdir",
            [TokenTYPE::OPTION, TokenType::PATH],
            [],
            "",
            pathParser: "parsePathNew"
        ),
        "rm" == $command
        => new Command(
            "rm",
            [TokenTYPE::OPTION, TokenType::PATH],
            [],
            "",
        ),
        "mv" == $command
        => new Command(
            "mv",
            [TokenTYPE::OPTION, TokenType::PATH, TokenType::PATH],
            [],
            "",
        ),
        "pwd" == $command
        => new Command(
            "pwd",
            [],
            [],
            "",
        ),
        "ls" == $command
        => new Command(
            "ls",
            [TokenTYPE::OPTION, TokenType::PATH],
            ["-l"],
            "",
            true,
        ),
        "cp" == $command
        => new Command(
            "cp",
            [TokenTYPE::OPTION, TokenType::PATH, TokenType::PATH],
            [],
            "",
        ),
        "grep" == $command
        => new Command(
            "grep",
            [TokenTYPE::OPTION, TokenType::STRING, TokenType::PATH],
            ["-r", "-i", "-v"],
            "",
            true,
            true,
        ),
        "find" == $command
        => new Command(
            "find",
            [TokenType::PATH, TokenType::OPTION],
            ["-name"],
            "",
        ),
        "./" == substr($command, 0, 2)
        => new Command(
            "execute",
            [TokenType::PATH],
            [],
            "",
        ),
        "echo" == $command
        => new Command(
            "echo",
            [TokenTYPE::OPTION, TokenType::PATH],
            [],
            "",
            true,
        ),
        "man" == $command
        => new Command(
            "man",
            [TokenTYPE::MISC],
            [],
            "",
            true,
        ),
        "cat" == $command
        => new Command(
            "cat",
            [TokenTYPE::PATH],
            [],
            "",
            true,
        ),
        "touch" == $command
        => new Command(
            "touch",
            [TokenTYPE::PATH],
            [],
            "",
            true,
            pathParser: "parsePathnew",
        ),
        default => throw new Exception("unknown command")
    };
}
/*
Funcionalities
    CD:
        -absolute, relative, /, -, ..
    MKDIR:
        - multiple at one
        - prompt before replacing
    RM:
        - multiple at once
    MV:
    PWD:
    LS:
    CP:
    GREP:
    ECHO:
    EXECUTE:
    MAN:
    TOUCH: 
        - create file
        - open file
 */
