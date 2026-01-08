<?php
class Command
{
    public $commandName;
    public array $tokenSyntax;
    public array $validOptions;
    public string $description;
    public string $commandValidator;
    public string $pathValidator;
    public string $stringValidator;
    public string $miscValidator;
    public string $optionValidator;

    public function __construct(
        $commandName,
        $tokenSyntax,
        $validOptions,
        $description,
        $commandValidator = "validateCommand",
        $pathValidator = "validatePath",
        $stringValidator = "validateString",
        $miscValidator = "valiateMisc",
        $optionValidator = "validateOption",
    )
    {
        $this->commandName = $commandName;
        $this->tokenSyntax = $tokenSyntax;
        $this->validOptions = $validOptions;
        $this->description = $description;
        $this->commandValidator = $commandValidator;
        $this->pathValidator = $pathValidator;
        $this->stringValidator =  $stringValidator;
        $this->miscValidator =  $miscValidator;
        $this->optionValidator = $optionValidator;
    }
    public function validateInput(array $inputArgs)
    {
        $syntaxArray = array_merge([TokenType::COMMAND], $this->tokenSyntax);
        $tokens = self::createTokens();
        echo "<br> token output: " . json_encode($tokens);

        for ($i = 0; $i < count($tokens); $i++)
        {
            $arg = $tokens[$i];
            switch (current($syntaxArray))
            {
                case TokenType::COMMAND:
                    {
                        $function = $this->commandValidator;
                        $_SESSION["tokens"]["command"] = self::$function($arg);
                        break;
                    }
                case TokenType::OPTION:
                    {
                        $function = $this->optionValidator;
                        self::$function($arg, $syntaxArray, $i);
                        break;
                    }
                case TokenType::PATH:
                    {
                        $function = $this->pathValidator;
                        $_SESSION["tokens"]["path"][] = self::$function(explode("/", $arg));
                        break;
                    }
                case TokenType::STRING:
                    {
                        $function = $this->stringValidator;
                        $_SESSION["tokens"]["strings"][] = self::$function($arg);
                        break;
                    }
                case TokenType::MISC:
                    {
                        $function = $this->miscValidator;
                        $_SESSION["tokens"]["misc"] = self::$function($arg);
                        break;
                    }
            }
            if (next($syntaxArray) == NULL)
            {
                echo "<br> syntax Element is null " . current($syntaxArray);
                prev($syntaxArray);
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
                        throw new Exception("incorrect string usage IF");
                    }
                }
            }
            else
            {
                if (!in_array($first, ["'", '"']) && !in_array($last, ["'", '"']))
                {
                    $tempToken = $tempToken . " " . $word;
                }
                else
                {
                    if (in_array($last, ["'", '"']))
                    {
                        $tempToken = $tempToken . " " . $word;
                        array_push($tokens, $tempToken);
                        $tempToken = "";
                    }
                    else
                    {
                        throw new Exception("incorrect string usage IF");
                    }
                }
            }
        }
        return $tokens;
        //fix multi word string arguments
    }
    public function validateCommand($arg)
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
                throw new Exception("unknown command");
            }
        }
    }
    public function validatePath($arg)
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
    public function validateString($arg): string
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
    public function validateMisc($arg)
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
                        throw new Exception("unknown command");
                    }
                }
        }
    }
    public function validateOption($arg, $syntaxArray, &$argIndex)
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
    public function mkdirPathValidator($mkdirPath)
    {
        if (count($mkdirPath) <= 1)
        {
            return $mkdirPath;
        }
        else
        {
            return self::validatePath(array_slice($mkdirPath, 0, -1));
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
            "movin around",
        ),
        "mkdir" == $command
        => new Command(
            "mkdir",
            [TokenTYPE::OPTION, TokenType::PATH],
            [],
            "",
            pathValidator: "mkdirPathValidator"
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
            [],
            "",
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
        ),
        "man" == $command
        => new Command(
            "man",
            [TokenTYPE::MISC],
            [],
            "",
        )
    };
}
