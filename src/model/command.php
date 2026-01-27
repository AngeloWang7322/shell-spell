<?php

declare(strict_types=1);

require __DIR__ . "/../logic/tokenParsers.php";

class Command
{
    public $commandName;
    public array $tokenSyntax;
    public array $validOptions;
    public array $validKeyValueOptions;
    public string $description;
    public bool $isWriter;
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
        $tokens = array_merge([$this->commandName], createTokens());

        for ($i = 0; $i < count($tokens); $i++)
        {
            $arg = $tokens[$i];

            switch (current($syntaxArray))
            {
                case TokenType::COMMAND:
                    {
                        $function = $this->commandParser;
                        $_SESSION["tokens"]["command"] = $function($arg, $tokens, $syntaxArray, $i);
                        break;
                    }
                case TokenType::OPTION:
                    {
                        $function = $this->optionParser;
                        $function($arg, $tokens, $syntaxArray, $i, $this->validOptions);
                        break;
                    }
                case TokenType::KEYVALUEOPTION:
                    {
                        $function = $this->keyValueOptionParser;
                        $function($arg, $tokens, $syntaxArray, $i, $this->validOptions);
                        break;
                    }
                case TokenType::PATH:
                    {
                        $function = $this->pathParser;
                        if ((bool)$function(explode("/", $arg), $tokens, $syntaxArray, $i))
                        {
                            $_SESSION["tokens"]["path"][] = explode("/", $arg);
                            $_SESSION["tokens"]["pathStr"][] = $arg;
                        }
                        break;
                    }
                case TokenType::STRING:
                    {
                        $function = $this->stringParser;
                        $_SESSION["tokens"]["strings"][] = $function($arg, $tokens, $syntaxArray, $i);
                        break;
                    }
                case TokenType::MISC:
                    {
                        $function = $this->miscParser;
                        $_SESSION["tokens"]["misc"] = $function($arg);
                        break;
                    }
            }
            if (next($syntaxArray) == false)
            {
                end($syntaxArray);
            }
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
            "NAME<br>
                              cd - change current room<br>
                        <br>
                        SYNOPSIS<br>
                              cd (path)<br>
                              cd ..<br>
                              cd /<br>
                              cd -<br>   
                        <br>
                        DESCRIPTION<br>
                              Changes the current room to the given destination.<br>
                        <br>
                              cd (path)  Move into the specified room if it exists.<br>
                              cd ..       Move to the parent room.<br>
                              cd /         Move to the root room.<br>
                              cd -     Move back to the previous room.<br>
                        <br>
                              Movement may fail if the target room does not exist<br>
                              or your role is not high enough to enter it.",
        ),
        "mkdir" == $command
        => new Command(
            "mkdir",
            [TokenTYPE::OPTION, TokenType::PATH],
            [],
            [],
            "NAME<br>
                              mkdir - create a new room<br>
                        <br>
                          SYNOPSIS<br>
                              mkdir (path)<br>
                        <br>
                          DESCRIPTION<br>
                              Creates a new room at the given path.<br>
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
            "NAME<br>
                              rm - remove a room or item<br>
                        <br>
                          SYNOPSIS<br>
                              rm (name)<br>
                        <br>
                          DESCRIPTION<br>
                              Deletes a room or an item from the current location<br>
                              if it exists and permissions allow it.
                              ",
        ),
        "mv" == $command
        => new Command(
            "mv",
            [TokenTYPE::OPTION, TokenType::PATH, TokenType::PATH],
            [],
            [],
            "NAME<br>
                              mv - move a room or item<br>
                        <br>
                          SYNOPSIS<br>
                              mv (source) (destination)<br>
                        <br>
                          DESCRIPTION<br>
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
            "NAME<br>
                              pwd - print current room path<br>
                        <br>
                          SYNOPSIS<br>
                              pwd<br>
                        <br>
                          DESCRIPTION<br>
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
            "NAME<br>
                              ls - list rooms and items<br>
                        <br>
                          SYNOPSIS<br>
                              ls<br>
                              ls (path)<br>
                        <br>
                          DESCRIPTION<br>
                              Lists all visible rooms and items in the current room<br>
                              or in the specified path.<br>
                        <br>
                              Rooms are displayed as exits.<br>
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
            description: "NAME<br>
                              cp - copy a file<br>
                        <br>
                          SYNOPSIS<br>
                              cp (source) (destination)<br>
                        <br>
                          DESCRIPTION<br>
                              Copies a file from source path to destination path.
                              ",
        ),
        "grep" == $command
        => new Command(
            "grep",
            [TokenTYPE::OPTION, TokenType::STRING, TokenType::PATH],
            ["-r", "-i", "-v"],
            [],
            "NAME<br>
                              grep - search text in scrolls<br>
                        <br>
                          SYNOPSIS<br>
                              grep (pattern) (scrollname)<br>
                        <br>
                          DESCRIPTION<br>
                              Searches for a text pattern inside a scroll<br>
                              and prints matching lines.
                              ",
            true,
        ),
        "find" == $command
        => new Command(
            "find",
            [TokenType::PATH, TokenType::KEYVALUEOPTION],
            [],
            ["-name" => "string"],
            "NAME<br>
                              find - search for rooms or items<br>
                        <br>
                          SYNOPSIS<br>
                              find (name)<br>
                        <br>
                          DESCRIPTION<br>
                              Searches the entire map for rooms or items<br>
                              matching the given name and prints their paths.
                              ",
            true,
            pathParser: "parsePathFind"
        ),

        "./" == substr($command, 0, 2) || "execute" == $command
        => new Command(
            "execute",
            [TokenType::PATH],
            [],
            [],
            "NAME<br>
                              ./ - execute a file<br>
                        <br>
                          SYNOPSIS<br>
                              ./(filename)<br>
                        <br>
                          DESCRIPTION<br>
                              Executes a runnable item if it exists<br>
                              and your role allows execution.<br>
                              ",
        ),
        "echo" == $command
        => new Command(
            "echo",
            [TokenType::STRING],
            [],
            [],
            "NAME<br>
                              echo - print text<br>
                        <br>
                          SYNOPSIS<br>
                              echo (test)<br>
                        <br>
                          DESCRIPTION<br>
                              Prints the given text into the terminal.
                              ",
            stringParser: "parseStringEcho",
            isWriter: true,
        ),
        "man" == $command
        => new Command(
            "man",
            [TokenTYPE::MISC],
            [],
            [],
            "NAME<br>
                              man - display command manual<br>
                            <br>
                          SYNOPSIS<br>
                              man (command)<br>
                        <br>
                          DESCRIPTION<br>
                              Displays the manual page for a command.<br>
                        <br>
                              man (command)  Shows detailed help for the given command.
                              ",
            true,
            miscParser: "parseMiscMan"
        ),
        "cat" == $command
        => new Command(
            "cat",
            [TokenTYPE::PATH],
            [],
            [],
            "NAME<br>
                              cat - read a scroll<br>
                        <br>
                          SYNOPSIS<br>
                              cat (scrollname)<br>
                        <br>
                          DESCRIPTION<br>
                              Opens and displays the contents of a scroll<br>
                              if it exists in the current room<br>
                              and your level is high enough to read it.
                              ",
            true,
        ),
        "touch" == $command
        => new Command(
            "touch",
            [TokenTYPE::PATH],
            [],
            [],
            "NAME<br>
                              touch - create an empty file<br>
                        <br>
                          SYNOPSIS<br>
                              touch (filename)<br>
                        <br>
                          DESCRIPTION<br>
                              Creates a new empty item (file) in the current room.<br>
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
            "NAME<br>
                              wc - count words in a scroll<br>
                        <br>
                          SYNOPSIS<br>
                              wc (scrollname)<br>
                        <br>
                          DESCRIPTION<br>
                              Counts the number of words in a scroll<br>
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
            "NAME<br>
                              head - show beginning of a scroll<br>
                        <br>
                          SYNOPSIS<br>
                              head (scrollname)<br>
                        <br>
                          DESCRIPTION<br>
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
            "NAME<br>
                              tail - show end of a scroll<br>
                        <br>
                          SYNOPSIS<br>
                              tail (scrollname)<br>
                        <br>
                          DESCRIPTION<br>
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
            "NAME<br>
                              nano - edit a scroll<br>
                        <br>
                          SYNOPSIS<br>
                              nano (scrollname)<br>
                        <br>
                          DESCRIPTION<br>
                              Opens a scroll for editing.<br>
                              If the scroll does not exist, it will be created.
                              ",
        ),
        default => throw new Exception("unknown command")
    };
}
