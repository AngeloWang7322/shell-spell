<?php

declare(strict_types=1);

class Terminal
{
    public bool $isSandbox;
    public bool $isPrompt;
    public array $promptData;

    public function __construct()
    {
        echo "CREATING TERMINAL!!";
        $this->isSandbox = false;
        $this->isPrompt = false;
        $this->promptData = [];
    }
    public function startTerminalProcess($isLast = true)
    {
        try
        {
            if ($this->isPrompt)
                self::handlePrompt();
            else if ($this->isSandbox)
            {
                self::handleSandbox();
                return;
            }
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
        if ($isLast)
            self::closeProcess();
    }

    public function closeProcess()
    {
        if (self::mustPreserveState())
            self::editLastHistory();
        else
            self::addNewHistory();
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
        $lastHistory = end($_SESSION["history"]);
        $lastHistory["response"] = $newStr;
        array_pop($_SESSION["history"]);
        array_push($_SESSION["history"], $lastHistory);
    }

    public function resetStreams()
    {
        Streams::$stdin = [];
        Streams::$stdout = [];
        Streams::$stderr = [];
    }
    public function mustPreserveState()
    {
        return  $this->isSandbox
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

        if (in_array($answer, [$this->promptData["options"][0], ""]))
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
        else if (in_array($_POST["command"], ["n", "N"]))
        {
            array_push(
                Streams::$stdout,
                ["<br> " . $answer]
            );
            self::editLastHistory();
            // self::reset();
            $this->isPrompt = false;
            throw new Exception("", 0);
        }
        else
        {
            array_push(
                Streams::$stdout,
                ["<br>" . $_POST["command"] . "<br>" . implode("/", $this->promptData["options"])]
            );
            self::editLastHistory();
            throw new Exception("", 0);
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
        self::startTerminalProcess(false);

        $_SESSION["tokens"] = [];
        $_POST["command"] = $afterSeperator;

        self::prepareCommand();
        self::executeCommand();
    }
    public function handlePipe($seperator)
    {
        $beforeSeperator = "";
        $afterSeperator = "";

        splitString($_POST["command"], $beforeSeperator, $afterSeperator, $seperator);
        $_POST["command"] = $beforeSeperator;
        self::startTerminalProcess(false);

        Streams::$stdin = Streams::$stdout;
        Streams::$stdout = [];

        $_SESSION["tokens"] = [];
        $_POST["command"] = $afterSeperator;
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
        Streams::$stdin = Streams::$stdout;
        Streams::$stdout = [];
        self::canRedirect(
            $redirectFilePath,
            $seperator
        );

        self::redirectStdoutToFile(
            $seperator,
            $redirectFilePath,
            Streams::$stdin
        );
    }
    public function handleSandbox()
    {
        if (!$_SESSION["sandbox"]->isInputValid())
        {
            Streams::$stdout = ["wrong spell"];
            return;
        }

        if ($_SESSION["sandbox"]->nextCommand())
        {
            self::prepareCommand();
            self::executeCommand();
            $_SESSION["sandbox"]->writeCurrentPrompt();
        }
    }

    public function handleException(Exception $e)
    {
        array_push(
            Streams::$stdout,
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
        if (!isset(Streams::$stdout))
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
        // self::checkPipe($_SESSION["command"]);
    }
    public function checkPipe($command)
    {
        if (
            !$command->isWriter
            //TODO check if empty works
            && !empty(Streams::$stdout)
        )
        {
            throw new Exception("command not pipable");
        }
    }
    public function renderStdout()
    {
        $responseString = "";
        if (count(Streams::$stdout) == 0)
            return $responseString;

        switch (gettype(current(Streams::$stdout)))
        {
            case "array":
                {
                    $responseString = renderGrid(Streams::$stdout);
                    break;
                }
            case "string":
                {
                    if (is_numeric(array_key_first(Streams::$stdout)))
                    {
                        $responseString .= implode(", ", Streams::$stdout);
                    }
                    else
                    {
                        foreach (Streams::$stdout as $key => $entry)
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
                    $responseString = implode("<br>", Streams::$stdout);
                    break;
                }
        }

        return colorizeRanks($responseString);
    }
}
