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
        $this->stringParser = $stringParser;
        $this->miscParser = $miscParser;
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
                        $_SESSION["tokens"]["command"] = self::$function($arg, $tokens, $syntaxArray, $i);
                        break;
                    }
                case TokenType::OPTION:
                    {
                        $function = $this->optionParser;
                        self::$function($arg, $tokens, $syntaxArray, $i);
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
                            $_SESSION["tokens"]["pathStr"][] = $arg;
                        }
                        break;
                    }
                case TokenType::STRING:
                    {
                        $function = $this->stringParser;
                        $_SESSION["tokens"]["strings"][] = self::$function($arg, $tokens, $syntaxArray, $i);
                        break;
                    }
                case TokenType::MISC:
                    {
                        $function = $this->miscParser;
                        $_SESSION["tokens"]["misc"] = self::$function($arg);
                        break;
                    }
                    break;
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
    static public function parsePath($path, $tokens = "", &$syntaxArray = [], &$argIndex = NULL, $validChars = [])
    {
        $validChars = array_merge($validChars, ["hall", "/", "-", ".."]);
        $validPathArgs = array_merge(array_keys($_SESSION["curRoom"]->doors), array_keys($_SESSION["curRoom"]->items), $validChars);

        return
            in_array($path[0], $validPathArgs) ||
            count($path) == 1 && !empty(getWildCardStringAndFunction($path[0]))
            ?  $path
            : throw new Exception("invalid path provided");
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
    public function parseOption($arg, $tokens, &$syntaxArray, &$argIndex)
    {
        if (substr($arg, 0, 1) == '-')
        {
            if (in_array($arg, $this->validOptions))
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
    static public function parsePathNew($mkdirPath, $tokens, &$syntaxArray, &$argIndex)
    {
        return match (true)
        {
            $mkdirPath[0] == ""
            => throw new Exception("now argument provided"),
            count($mkdirPath) == 1
            => $mkdirPath,
            default
            => self::parsePath(array_slice($mkdirPath, 0, -1), $tokens, $syntaxArray, $argIndex),
        };
    }
    static public function parsePathFind($path, $tokens, &$syntaxArray, &$argIndex)
    {
        return $path[0] == "." ? $path : self::parsePath($path, $tokens, $syntaxArray, $argIndex);
    }
    static public function parsePathOptional($path, $tokens, &$syntaxArray, &$argIndex)
    {
        if (!isset($_SESSION["stdout"]))
        {
            try
            {
                return self::parsePath($path, $tokens, $syntaxArray, $argIndex);
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
                $_SESSION["tokens"]["path"][] = self::parsePath(array_slice($path, 0, -1), $tokens, $syntaxArray, $argIndex,);
                $_SESSION["tokens"]["pathStr"][] = $tokens[$argIndex];

                return false;
            }
            return self::parsePath($path, $tokens, $syntaxArray, $argIndex);
        }
    }
    static public function parseStringEcho($path, $tokens, &$syntaxArray, &$argIndex)
    {
        try
        {
            return self::parseString($tokens[$argIndex]);
        }
        catch (Exception $e)
        {

            return implode(" ", array_slice($tokens, 1));
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
            "NAME
                              cd - change current room

                        SYNOPSIS
                              cd <path>
                              cd ..
                              cd /
                              cd -

                        DESCRIPTION
                              Changes the current room to the given destination.

                              cd <path>    Move into the specified room if it exists.
                              cd ..        Move to the parent room.
                              cd /         Move to the root room.
                              cd -         Move back to the previous room.

                              Movement may fail if the target room does not exist
                              or your role is not high enough to enter it.",
        ),
        "mkdir" == $command
        => new Command(
            "mkdir",
            [TokenTYPE::OPTION, TokenType::PATH],
            [],
            [],
            "NAME
                              mkdir - create a new room

                          SYNOPSIS
                              mkdir <path>

                          DESCRIPTION
                              Creates a new room at the given path.
                              The room will only be created if the parent room exists.
                              ",
            pathParser: "parsePathNew"
        ),
        "rm" == $command
        => new Command(
            "rm",
            [TokenTYPE::OPTION, TokenType::PATH],
            [],
            [],
            "NAME
                              rm - remove a room or item

                          SYNOPSIS
                              rm <name>

                          DESCRIPTION
                              Deletes a room or an item from the current location
                              if it exists and permissions allow it.
                              ",
        ),
        "mv" == $command
        => new Command(
            "mv",
            [TokenTYPE::OPTION, TokenType::PATH, TokenType::PATH],
            [],
            [],
            "NAME
                              mv - move a room or item

                          SYNOPSIS
                              mv <source> <destination>

                          DESCRIPTION
                              Moves a room or item from source path to destination path.
                              ",
            pathParser: "parsePathRename"
        ),
        "pwd" == $command
        => new Command(
            "pwd",
            [],
            [],
            [],
            "NAME
                              pwd - print current room path

                          SYNOPSIS
                              pwd

                          DESCRIPTION
                              Displays the full path of the current room.
                              ",
            true
        ),
        "ls" == $command
        => new Command(
            "ls",
            [TokenTYPE::OPTION, TokenType::PATH],
            ["-l", "-a"],
            [],
            "NAME
                              ls - list rooms and items

                          SYNOPSIS
                              ls
                              ls <path>

                          DESCRIPTION
                              Lists all visible rooms and items in the current room
                              or in the specified path.

                              Rooms are displayed as exits.
                              Items (scrolls, objects) are displayed as files.
                              ",
            true,
        ),
        "cp" == $command
        => new Command(
            commandName: "cp",
            tokenSyntax: [TokenTYPE::OPTION, TokenType::PATH, TokenType::PATH],
            validOptions: [],
            validKeyValueOptions: [],
            description: "NAME
                              cp - copy a file

                          SYNOPSIS
                              cp <source> <destination>

                          DESCRIPTION
                              Copies a file from source path to destination path.
                              ",
        ),
        "grep" == $command
        => new Command(
            "grep",
            [TokenTYPE::OPTION, TokenType::STRING, TokenType::PATH],
            ["-r", "-i", "-v"],
            [],
            "NAME
                              ./ - execute a file

                          SYNOPSIS
                              ./<filename>

                          DESCRIPTION
                              Executes a runnable item if it exists
                              and your role allows execution.
                              ",
            true,
        ),
        "find" == $command
        => new Command(
            "find",
            [TokenType::PATH, TokenType::KEYVALUEOPTION],
            [],
            ["-name" => "string"],
            "NAME
                              find - search for rooms or items

                          SYNOPSIS
                              find <name>

                          DESCRIPTION
                              Searches the entire map for rooms or items
                              matching the given name and prints their paths.
                              ",
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
            "NAME
                              echo - print text

                          SYNOPSIS
                              echo <text>

                          DESCRIPTION
                              Prints the given text into the terminal.
                              ",
            stringParser: "parseStringEcho",
            isWriter: true,
            true,
        ),
        "man" == $command
        => new Command(
            "man",
            [TokenTYPE::MISC],
            [],
            [],
            "NAME
                              man - display command manual

                          SYNOPSIS
                              man
                              man <command>

                          DESCRIPTION
                              Displays the manual page for a command.

                              man             Shows a list of available commands.
                              man <command>   Shows detailed help for the given command.
                              ",
            true,
        ),
        "cat" == $command
        => new Command(
            "cat",
            [TokenTYPE::PATH],
            [],
            [],
            "NAME
                              cat - read a scroll

                          SYNOPSIS
                              cat <scrollname>

                          DESCRIPTION
                              Opens and displays the contents of a scroll
                              if it exists in the current room
                              and your role is high enough to read it.
                              ",
            true,
        ),
        "touch" == $command
        => new Command(
            "touch",
            [TokenTYPE::PATH],
            [],
            [],
            "NAME
                              touch - create an empty file

                          SYNOPSIS
                              touch <filename>

                          DESCRIPTION
                              Creates a new empty item (file) in the current room.
                              If the file already exists, nothing is changed.
                              ",
            true,
            pathParser: "parsePathNew",
        ),
        "wc" == $command
        => new Command(
            "wc",
            [TokenType::OPTION, TokenType::PATH],
            ["-l", "-w"],
            [],
            "NAME
                              wc - count words in a scroll

                          SYNOPSIS
                              wc <scrollname>

                          DESCRIPTION
                              Counts the number of words in a scroll
                              if it exists in the current room.
                              ",
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
            "NAME
                              head - show beginning of a scroll

                          SYNOPSIS
                              head <scrollname>

                          DESCRIPTION
                              Displays the first few lines of a scroll.
                              ",
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
            "NAME
                              tail - show end of a scroll

                          SYNOPSIS
                              tail <scrollname>

                          DESCRIPTION
                              Displays the last few lines of a scroll.
                              ",
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
            "NAME
                              nano - edit a scroll

                          SYNOPSIS
                              nano <scrollname>

                          DESCRIPTION
                              Opens a scroll for editing.
                              If the scroll does not exist, it will be created.
                              ",
        ),
        default => throw new Exception("unknown command")
    };
}
