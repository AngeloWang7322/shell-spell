<?php
class Command
{
    public static $commandName;
    public static array $tokenStructure;
    public static array $validOptions;
    public static string $description;

    public function __construct($commandName, $tokenStructure, $validOptions, $description)
    {
        $this->commandName = $commandName;
        $this->tokenStructure = $tokenStructure;
        $this->validOptions = $validOptions;
        $this->description = $description;
    }
    public function validateInput(array $inputArgs)
    {
        echo "<br>commandName: " . $this->commandName;
        $structure = array_merge([TokenType::COMMAND], $this->tokenStructure);
        $tokens = self::createTokens($inputArgs);
        for ($i = 0; $i < count($tokens); $i++)
        {
            $arg = $tokens[$i];
            // echo "<br>current Arg: " . $arg;
            // echo "<br>current TokenType: " . json_encode(var_dump(current($structure)));

            switch (current($structure))
            {
                case TokenType::COMMAND:
                    {
                        if (Commands::tryFrom($arg) != NULL)
                        {
                            $_SESSION["tokens"]["command"] = $arg;
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
                        break;
                    }
                case TokenType::OPTION:
                    {
                        if ($arg[0] == '-')
                        {
                            if (in_array($arg, $this->validOptions))
                            {
                                $_SESSION["tokens"]["options"][] = $arg;
                            }
                        }
                        else
                        {
                            // echo "<br>No options";
                            // next($structure);
                            $i--;
                        }
                        break;
                    }
                case TokenType::PATH:
                    {
                        $validArgs = array_merge(array_keys($_SESSION["curRoom"]->doors + $_SESSION["curRoom"]->items), ["hall", "/", ""]);

                        if (in_array(explode("/", $arg)[0], $validArgs))
                        {
                            // echo "<br>path: " . json_encode($arg);
                            $_SESSION["tokens"]["path"][] = explode("/", $arg);
                        }
                        break;
                    }
                case TokenType::STRING:
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
                            $_SESSION["tokens"]["strings"][] = substr($arg, 1, -1);
                        }
                        break;
                    }
                case TokenType::MISC:
                    {
                        switch ($_SESSION["tokens"]["command"])
                        {
                            case "man":
                                {
                                    if (in_array($arg, Commands::cases()))
                                    {
                                        $_SESSION["tokens"]["manCommand"] = $arg;
                                    }
                                    else
                                    {
                                        throw new Exception("unknown command");
                                    }
                                }
                        }
                        break;
                    }
            }
            if (next($structure) == NULL)
            {
                echo "<br> is null? " . current($structure);
                prev($structure);
            }
        }
    }
    public function createTokens($inputArgs): array
    {
        $inputStr = $_POST["command"];
        $inputLen = strlen($inputStr);
        $tokens = [$this->commandName];

        $inStr = "";
        $tempToken = "";

        foreach (array_slice(explode(" ", $inputStr), 1) as $word);
        {
            $first = substr($word, 0, 1);
            $last = substr($word, -1, 1);
            echo "<br> first" . $first . "<br>last: " . $last;
            if (in_array($first, ["'", '"']) && $first != $last)
            {
                echo "<br> in if: " . $word;
                if ($tempToken != "") throw new Exception("incorrect string usage");

                $tempToken = $word;
            }
            else if (in_array($last, ["'", '"']))
            {
                echo "<br>in else if: " . $word;
                if (empty($tempToken)) throw new Exception("incorrect string usage");

                array_push($tokens, (array)($tempToken . $word));
                $tempToken = "";
            }
            else
            {
                array_push($tokens, $tempToken);
            }
        }
        return [];
        //fix multi word string arguments
        //execute 
    }
}

function createCommand($command)
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
