<?php

declare(strict_types=1);
class StateManager
{
    static public bool $isSandbox = false;
    static public bool $isPrompt = false;
    static public array $promptData = [];
    static public bool $inPipe = false;
    static public int $pipeCount = 0;
    static public $stdin = "";
    static public array $stdout = [];
    static public $stderr = "";

    static public function startTerminalProcess()
    {
        try
        {
            if (self::$isPrompt)
                self::handlePrompt();
            else if (self::$isSandbox)
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

    static public function closeProcess()
    {
        if (self::mustPreserveState())
        {
            self::editLastHistory();
            self::resetStreams();
        }
        else
        {
            self::addNewHistory();
            self::reset();
        }
    }

    static public function addNewHistory()
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
    static public function editLastHistory($str = NULL)
    {
        $newStr = $str ?? self::renderStdout();
        end($_SESSION["history"]);
        current($_SESSION["history"])["response"] .= $newStr;
    }


    static public function reset()
    {
        self::resetStreams();
        self::$isSandbox = false;
        self::$isPrompt = false;
        self::$inPipe = false;
        self::$pipeCount = 0;
    }
    static public function resetStreams()
    {
        self::$stdin = "";
        self::$stdout = [];
        self::$stderr = "";
    }
    static public function mustPreserveState()
    {
        return self::$pipeCount > 0
            || self::$isSandbox
            || self::$isPrompt;
    }

    /*
         ---------------  HANDLERS -------------------
    */

    static public function handleDefault()
    {
        self::prepareCommand();
        self::executeCommand();
    }

    static public function handlePrompt()
    {
        $answer = $_POST["command"];

        switch (true)
        {
            case (in_array($_POST["command"], [self::$promptData["options"][0], ""])):
                {
                    try
                    {
                        self::executeCommand();
                        $answer = "y";
                    }
                    catch (Exception $e)
                    {
                        self::$promptData = [];
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
                        ["<br>" . $_POST["command"] . "<br>" . implode("/", self::$promptData["options"])]
                    );
                    self::editLastHistory();
                    throw new Exception("", 0);
                }
        }
    }
    static public function handleFailSafe($seperator)
    {
        $beforeSeperator = "";
        $afterSeperator = "";
        splitString($_POST["command"], $beforeSeperator, $afterSeperator, $seperator);
        $_POST["command"] = $beforeSeperator;
        try
        {
            self::startTerminalProcess();
        }
        catch (Exception $e)
        {
            $_SESSION["tokens"] = [];
            $_POST["command"] = $afterSeperator;
            self::startTerminalProcess();
        }
    }
    static public function handleCommandChain($seperator)
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
    static public function handlePipe($seperator)
    {
        self::$pipeCount++;
        self::handleCommandChain($seperator);
        self::$pipeCount--;
    }
    static public function handleRedirect($seperator)
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
    static public function handleSandbox()
    {
    }
    static public function handleException(Exception $e)
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

    static public function canRedirect($redirectFilePath, $seperator)
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
    static public function prepareCommand()
    {
        $_SESSION["command"] = getCommand(explode(" ", trim($_POST["command"]))[0]);
        $_SESSION["command"]->parseInput();
        self::checkPipe($_SESSION["command"]);
    }
    static public function checkPipe($command)
    {
        if (
            self::$pipeCount > 0
            && !$command->isWriter
            //TODO check if empty works
            && !empty(self::$stdout)
        )
        {
            throw new Exception("command not pipable");
        }
    }
    static public function renderStdout()
    {
        $responseString = "";
        $seperator = "<br>";
        if ($_SESSION["tokens"]["command"] == Commands::LS->value && !in_array("-l", $_SESSION["tokens"]["options"]))
        {
            $seperator = ", ";
        }
        switch (gettype(self::$stdout))
        {
            case "array":
                {
                    $responseString = renderGrid(self::$stdout);
                    break;
                }
            case "string":
                {
                    if (is_numeric(key(self::$stdout[0])))
                    {
                        $responseString .= implode(", ", self::$stdout);
                    }
                    else
                    {
                        $responseString .= implode("<br>", self::$stdout);
                    }
                    break;
                }
            default:
                {
                    $responseString = implode("<br>", self::$stdout);
                    break;
                }
        }
        // foreach (self::$stdout as $key => $entry)
        // {
        //     if (!is_numeric($key))
        //     {
        //         $responseString .= $key . " - " . $entry . "<br>";
        //     }
        //     else
        //     {
        //         $responseString .= $entry . $seperator;
        //     }
        // }
        return colorizeRanks($responseString);
    }
}
