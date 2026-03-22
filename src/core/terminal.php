<?php

declare(strict_types=1);

class Terminal
{
    public bool $isSandbox;
    public bool $isPrompt;
    public array $promptData;
    public bool $inPipe;
    public int $pipeCount;
    public $stdin = "";
    static public array $stdout = [];
    static public $stderr = "";
    public function __construct()
    {
        $this->isSandbox = false;
        $this->isPrompt = false;
        $this->promptData = [];
        $this->inPipe = false;
        $this->pipeCount = 0;
        $this->stdin = "";
    }
    public function startTerminalProcess()
    {
        try
        {
            if ($this->isPrompt)
                self::handlePrompt();
            else if ($this->isSandbox)
                self::handleSandbox();
            else
            {
                match ($operator = getLastOccuringElementIn($_POST["command"]))
                {
                    ">>", ">" => self::handleRedirect($operator),
                    "|" => self::handlePipe($operator),
                    "&&" => self::handleCommandChain($operator),
                    "||" => self::handleFailSafe($operator),
                    default => self::handleDefault()
                };
            }
        }
        catch (Exception $e)
        {
            self::handleException($e);
        }

        self::closeProcess();
    }

    public function closeProcess()
    {
        if (self::mustPreserveState())
        {
            self::editLastHistory();
        }
        else
        {
            self::addNewHistory();
            self::reset();
        }
    }

    public function addNewHistory()
    {
        if (count($_SESSION["history"]) > 20)
        {
            $_SESSION["history"] = array_slice($_SESSION["history"], 1);
        }
        $_SESSION["history"][] = [
            "directory" => $_POST["baseString"],
            "command" => $_SESSION["inputCommand"],
            "response" => self::renderStdout()
        ];
    }
    public function editLastHistory($str = NULL)
    {
        $newStr = $str ?? self::renderStdout();
        end($_SESSION["history"]);
        current($_SESSION["history"])["response"] .= $newStr;
    }


    public function reset()
    {
        self::resetStreams();
        $this->isSandbox = false;
        $this->isPrompt = false;
        $this->inPipe = false;
        $this->pipeCount = 0;
    }
    public function resetStreams()
    {
        $this->stdin = "";
        self::$stdout = [];
        self::$stderr = "";
    }
    public function mustPreserveState()
    {
        return $this->pipeCount > 0
            || $this->isSandbox
            || $this->isPrompt;
    }

    /*
         ---------------  HANDLERS -------------------
    */

    public function handleDefault()
    {
        self::prepareCommand();
        self::executeCommand();
    }

    public function handlePrompt()
    {
        $answer = $_POST["command"];

        switch (true)
        {
            case (in_array($_POST["command"], [$this->promptData["options"][0], ""])):
                {
                    try
                    {
                        self::executeCommand();
                        $answer = "y";
                    }
                    catch (Exception $e)
                    {
                        $this->promptData = [];
                        throw new Exception($e->getMessage());
                    }
                }
            case (in_array($_POST["command"], ["n", "N"])):
                {
                    array_push(
                        self::$stdout,
                        ["<br> " . $answer]
                    );
                    self::editLastHistory();
                    self::reset();
                    throw new Exception("", 0);
                }
            default:
                {
                    array_push(
                        self::$stdout,
                        ["<br>" . $_POST["command"] . "<br>" . implode("/", $this->promptData["options"])]
                    );
                    self::editLastHistory();
                    throw new Exception("", 0);
                }
        }
    }
    public function handleFailSafe($seperator)
    {
        $beforeSeperator = "";
        $afterSeperator = "";
        splitString($_POST["command"], $beforeSeperator, $afterSeperator, $seperator);
        $_POST["command"] = $beforeSeperator;
        try
        {
            $this->startTerminalProcess();
        }
        catch (Exception $e)
        {
            $_SESSION["tokens"] = [];
            $_POST["command"] = $afterSeperator;
            self::startTerminalProcess();
        }
    }
    public function handleCommandChain($seperator)
    {
        $beforeSeperator = "";
        $afterSeperator = "";
        splitString($_POST["command"], $beforeSeperator, $afterSeperator, $seperator);
        $_POST["command"] = $beforeSeperator;
        self::startTerminalProcess();

        $_SESSION["tokens"] = [];
        $_POST["command"] = $afterSeperator;

        self::prepareCommand();
        self::executeCommand();
    }
    public function handlePipe($seperator)
    {
        $this->pipeCount++;
        $beforeSeperator = "";
        $afterSeperator = "";

        splitString($_POST["command"], $beforeSeperator, $afterSeperator, $seperator);
        $_POST["command"] = $beforeSeperator;
        self::startTerminalProcess();

        $this->stdin = self::$stdout;
        self::$stdout = [];

        $_SESSION["tokens"] = [];
        $_POST["command"] = $afterSeperator;
        $this->pipeCount--;
        self::prepareCommand();
        self::executeCommand();
    }
    public function handleRedirect($seperator)
    {
        $command = "";
        $redirectFilePath = "";

        splitString(
            $_POST["command"],
            $command,
            $redirectFilePath,
            $seperator
        );

        $redirectFilePath = toPath($redirectFilePath);
        $_POST["command"] = $command;
        self::startTerminalProcess();
        self::$stdout = [];
        self::canRedirect(
            $redirectFilePath,
            $seperator
        );

        self::redirectStdoutToFile(
            $seperator,
            $redirectFilePath,
            self::$stdout
        );
    }
    public function handleSandbox()
    {
    }
    public function enterSandbox(SpellSandbox $sandBox)
    {
        Terminal::$isSandbox = true;

        $_SESSION["backupMap"] =  $_SESSION["map"];
        $_SESSION["backupCurRoom"] =  $_SESSION["curRoom"];
        $_SESSION["map"] = $sandBox->map;
        $_SESSION["curRoom"] = &$_SESSION["map"];
    }
    public function exitSandbox()
    {
        Terminal::$isSandbox = false;
        $_SESSION["map"] = $_SESSION["backupMap"];
        $_SESSION["curRoom"] = &$_SESSION["backupCurRoom"];
        unset($_SESSION["backupCurRoom"], $_SESSION["backupMap"]);
    }
    public function handleException(Exception $e)
    {
        array_push(
            self::$stdout,
            colorizeString(colorizeRanks($e->getMessage()), "error")
        );
        $_SESSION["map"] = $_SESSION["backUpMap"];
    }


    static public function arrayKeyValueToString($array, $seperator = "<br>")
    {
        $finalString = "";
        foreach ($array as $key => $line)
        {
            $finalString .= $key . " " . $line . $seperator;
        }
        return $finalString;
    }

    static public function executeCommand()
    {
        ("execute" . $_SESSION["tokens"]["command"])();
    }

    public function canRedirect($redirectFilePath, $seperator)
    {
        parsePath($redirectFilePath,);
        if (!isset(self::$stdout))
            throw new Exception("invalid usage of '" . $seperator . "' operator");
    }
    static public function redirectStdoutToFile($seperator, $redirectFilePath, $stdout)
    {
        $newStr = strip_tags(self::arrayKeyValueToString($stdout));
        $destItem = &getItem($redirectFilePath);

        if ($seperator == ">>")
            $destItem->content .= $newStr;
        else
            $destItem->content = $newStr;
    }
    public function prepareCommand()
    {
        $_SESSION["command"] = getCommand(explode(" ", trim($_POST["command"]))[0]);
        $_SESSION["command"]->parseInput();
        self::checkPipe($_SESSION["command"]);
    }
    public function checkPipe($command)
    {
        if (
            $this->pipeCount > 0
            && !$command->isWriter
            //TODO check if empty works
            && !empty(self::$stdout)
        )
        {
            throw new Exception("command not pipable");
        }
    }
    public function renderStdout()
    {
        $responseString = "";
        if (count(self::$stdout) == 0)
            return $responseString;

        switch (gettype(current(self::$stdout)))
        {
            case "array":
                {
                    $responseString = renderGrid(self::$stdout);
                    break;
                }
            case "string":
                {
                    if (is_numeric(array_key_first(self::$stdout)))
                    {
                        $responseString .= implode(", ", self::$stdout);
                    }
                    else
                    {
                        foreach (self::$stdout as $key => $entry)
                        {
                            if ($entry == "")
                                $responseString .= $key . "<br>";
                            else
                                $responseString .= $key . $entry . "<br>";
                        }
                    }
                    break;
                }
            default:
                {
                    $responseString = implode("<br>", self::$stdout);
                    break;
                }
        }

        return colorizeRanks($responseString);
    }
}
