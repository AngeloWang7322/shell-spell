<?php

declare(strict_types=1);
class GameStateHandler
{
    public bool $isSandbox = false;
    public bool $isPrompt = false;
    public array $promptData;
    public bool $inPipe = false;
    public int $pipeCount = 0;
    public $stdin = "";
    public $stdout = "";
    public $stderr = "";
    public array $history = [];

    public function startTerminalProcess()
    {
        try
        {
            if ($this->isPrompt)
                $this->handlePrompt();
            else if ($this->isSandbox)
                $this->handleSandbox();
            else
            {
                match ($operator = getLastOccuringElementIn($_POST["command"]))
                {
                    ">>", ">" => $this->handleRedirect($operator),
                    "|" => $this->handlePipe($operator),
                    "&&" => $this->handleCommandChain($operator),
                    "||" => $this->handleFailSafe($operator),
                    default => $this->handleDefault()
                };
            }
        }
        catch (Exception $e)
        {
            $this->handleException($e);
        }

        $this->closeProcess();
    }

    public function closeProcess()
    {
        if ($this->mustPreserveState())
        {
            $this->editLastHistory();
            $this->resetStreams();
        }
        else
        {
            $this->addNewHistory();
            $this->reset();
        }
    }

    public function addNewHistory()
    {
        if (count($this->history) > 20)
        {
            $this->history = array_slice($this->history, 1);
        }
        $this->history = [
            "directory" => $_POST["baseString"],
            "command" => $_SESSION["inputCommand"],
            "response" => $this->history
        ];
    }
    function editLastHistory($str = NULL)
    {
        $newStr  =  $str ?? $this->stdout;
        //TODO check if this works:
        end($this->history)["response"] .= $newStr;
    }


    public function reset()
    {
        $this->resetStreams();
        $this->isSandbox = false;
        $this->isPrompt = false;
        $this->inPipe = false;
        $this->pipeCount = 0;
    }
    public function resetStreams()
    {
        $this->stdin = "";
        $this->stdout = "";
        $this->stderr = "";
    }
    function mustPreserveState()
    {
        return
            $this->pipeCount > 0 ||
            $this->isSandbox ||
            isset($this->promptData);
    }


    /*
         ---------------  HANDLERS -------------------
    */
    function handleDefault()
    {
        prepareCommand();
        executeCommand();
    }

    function handlePrompt()
    {
        $answer = $_POST["command"];

        switch (true)
        {
            case (in_array($_POST["command"], [$this->promptData["options"][0], ""])):
                {
                    try
                    {
                        executeCommand();
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
                    $this->stdout = "<br> " . $answer;
                    $this->editLastHistory();
                    $this->reset();
                    throw new Exception("", 0);
                }
            default:
                {
                    $this->stdout = "<br>" . $_POST["command"] . "<br>" . implode("/", $this->promptData["options"]);
                    $this->editLastHistory();
                    throw new Exception("", 0);
                }
        }
    }
    function handleFailSafe($seperator)
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
            $this->startTerminalProcess();
        }
    }
    function handleCommandChain($seperator)
    {
        $beforeSeperator = "";
        $afterSeperator = "";
        splitString($_POST["command"], $beforeSeperator, $afterSeperator, $seperator);
        $_POST["command"] = $beforeSeperator;
        $this->startTerminalProcess();

        $_SESSION["tokens"] = [];
        $_POST["command"] = $afterSeperator;

        prepareCommand();
        executeCommand();
    }
    function handlePipe($seperator)
    {
        $_SESSION["state"]->pipeCount++;
        $this->handleCommandChain($seperator);
        $_SESSION["state"]->pipeCount--;
    }
    function handleRedirect($seperator)
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
        $this->startTerminalProcess();
        $_SESSION["state"]->stdout = "";
        canRedirect(
            $redirectFilePath,
            $seperator
        );

        redirectStdoutToFile(
            $seperator,
            $redirectFilePath,
            $this->stdout
        );
    }
    function handleSandbox()
    {
    }
    function handleException(Exception $e)
    {
        $_SESSION["state"]->stdout = colorizeString(colorizeResponseForRank($e->getMessage()), "error");
        $_SESSION["map"] = $_SESSION["backUpMap"];
    }
}

function arrayKeyValueToString($array, $seperator = "<br>")
{
    $finalString = "";
    foreach ($array as $key => $line)
    {
        $finalString .= $key . " " . $line . $seperator;
    }
    return $finalString;
}
function getLastOccuringElementIn($needle, $haystack = [">>", ">", "||", "|", "&&",])
{
    for ($i = strlen($needle); $i > 0; $i--)
    {
        foreach ($haystack as $element)
        {
            $len = strlen($element);
            $substr = substr($needle, $i - $len, $len);
            if ($substr == $element)
            {
                return $element;
            }
        }
    }
    return false;
}
function executeCommand()
{
    ("execute" . $_SESSION["tokens"]["command"])();
}

function canRedirect($redirectFilePath, $seperator)
{
    parsePath($redirectFilePath,);
    if (!isset($_SESSION["state"]->stdout))
        throw new Exception("invalid usage of '" . $seperator . "' operator");
}
function redirectStdoutToFile($seperator, $redirectFilePath, $stdout)
{
    $newStr = strip_tags(arrayKeyValueToString($stdout));
    $destItem = &getItem($redirectFilePath);

    if ($seperator == ">>")
        $destItem->content .= $newStr;
    else
        $destItem->content = $newStr;
}
function prepareCommand()
{
    $_SESSION["command"] = getCommand(explode(" ", trim($_POST["command"]))[0]);
    $_SESSION["command"]->parseInput();
    checkPipe($_SESSION["command"]);
}
function checkPipe($command)
{
    if (
        $_SESSION["state"]->pipeCount > 0
        && !$command->isWriter
        //TODO check if empty works
        && !empty($_SESSION["state"]->stdout)
    )
    {
        throw new Exception("command not pipable");
    }
}
