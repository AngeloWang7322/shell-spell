<?php

declare(strict_types=1);
function manageExecution()
{
    if (isset($_SESSION["promptData"]))
    {
        handlePrompt();
        return;
    }
    match ($operator = getLastOccuringElementIn($_POST["command"]))
    {
        ">>", ">" => handleRedirect($operator),
        "|" => handlePipe($operator),
        "&&" => handleCommandChain($operator),
        "||" => handleFailSafe($operator),
        default => handleDefault()
    };
}
function handleDefault()
{
    prepareCommandExecution();
    executeCommand();
}
function handlePrompt()
{
    $answer = $_POST["command"];

    switch (true)
    {
        case (in_array($_POST["command"], [$_SESSION["promptData"]["options"][0], ""])):
            {
                try
                {
                    executeCommand();
                    $answer = "y";
                }
                catch (Exception $e)
                {
                    unset($_SESSION["promptData"]);
                    throw new Exception($e->getMessage());
                }
            }
        case (in_array($_POST["command"], ["n", "N"])):
            {
                editLastHistory("<br> " . $answer);
                cleanUp();
                throw new Exception("", 0);
            }
        default:
            {
                editLastHistory("<br>" . $_POST["command"] . "<br>" . implode("/", $_SESSION["promptData"]["options"]));
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
        manageExecution();
    }
    catch (Exception $e)
    {
        $_SESSION["tokens"] = [];
        $_POST["command"] = $afterSeperator;
        manageExecution();
    }
}
function handleCommandChain($seperator)
{
    $beforeSeperator = "";
    $afterSeperator = "";
    splitString($_POST["command"], $beforeSeperator, $afterSeperator, $seperator);
    $_POST["command"] = $beforeSeperator;
    manageExecution();
    $_SESSION["tokens"] = [];
    $_POST["command"] = $afterSeperator;
    prepareCommandExecution();
    executeCommand();
}
function handlePipe($seperator)
{
    $_SESSION["pipeCount"]++;
    handleCommandChain($seperator);
    $_SESSION["pipeCount"]--;
}
function handleRedirect($seperator)
{
    $command = "";
    $redirectFilePath = "";

    splitString($_POST["command"], $command, $redirectFilePath, $seperator);
    $redirectFilePath = toPath($redirectFilePath);
    $_POST["command"] = $command;
    manageExecution();
    $_SESSION["response"] = "";

    checkIfCanRedirect($redirectFilePath, $seperator);
    addStdoutToFile($seperator, $redirectFilePath);
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
function splitString($baseString, &$beforeSeperator, &$afterSeperator, $seperator)
{
    $needlePos = strrpos($baseString, $seperator);
    $beforeSeperator = trim(substr($baseString, 0, $needlePos));
    $afterSeperator = trim(substr($baseString, $needlePos + strlen($seperator) + 1));
}
function checkIfCanRedirect($redirectFilePath, $seperator)
{
    Command::parsePath($redirectFilePath);
    if (!isset($_SESSION["stdout"])) throw new Exception("invalid usage of '" . $seperator . "' operator");
}
function addStdoutToFile($seperator, $redirectFilePath)
{
    $newStr = arrayKeyValueToString($_SESSION["stdout"]);
    $destItem = &getItem($redirectFilePath);
    if ($seperator == ">>")
    {
        $destItem->content .= $newStr;
    }
    else
    {
        $destItem->content = $newStr;
    }
}
function prepareCommandExecution()
{
    $command = getCommand(explode(" ", trim($_POST["command"]))[0]);
    $command->parseInput();
    checkPipe($command);
}
function checkPipe($command)
{
    if (
        $_SESSION["pipeCount"] > 0
        && !$command->isWriter
        && !isset($_SESSION["stdout"])
    )
    {
        throw new Exception("command not pipable");
    }
}
function executeCommand()
{
    ("execute" . $_SESSION["tokens"]["command"])();
}
function editLastHistory($string)
{
    $lastHistoryEntry = end($_SESSION["history"]);
    $lastHistoryEntry["response"] .=  $string;
    array_pop($_SESSION["history"]);
    array_push($_SESSION["history"], $lastHistoryEntry);
}
function handleException(Exception $e)
{
    editMana($e->getCode());

    if ($e->getCode() == 0)
    {
        $_SESSION["response"] = colorizeString($e->getMessage(), "error");
    }
    else
    {
        $_SESSION["response"] = $e->getMessage();
    }
}
function closeProcess()
{
    if (!mustPreserveState())
    {
        writeResponse();
        cleanUp();
    }
}
function writeResponse()
{
    if (mustPreserveState())
    {
        editLastHistory($_SESSION["response"]);
    }
    else
    {
        writeNewHistory();
    }
}
function writeNewHistory()
{
    $_SESSION["history"][] = [
        "directory" => $_POST["baseString"],
        "command" => $_SESSION["inputCommand"],
        "response" => $_SESSION["response"]
    ];
    if (count($_SESSION["history"]) >= 15)
    {
        $_SESSION["history"] = array_slice($_SESSION["history"], 1, 15);
    }
}
function cleanUp()
{
    $_SESSION["tokens"]["command"] = "";
    $_SESSION["tokens"]["path"] = [];
    $_SESSION["tokens"]["options"] = [];
    $_SESSION["tokens"]["keyValueOptions"] = [];
    $_SESSION["tokens"]["strings"] = [];
    $_SESSION["tokens"]["misc"] = [];
    $_SESSION["inputCommand"] = "";
    $_SESSION["response"] = "";

    $_SESSION["pipeCount"] = 0;
    unset(
        $_SESSION["promptData"],
        $_SESSION["stdout"],
    );
}
function mustPreserveState()
{
    return isset($_SESSION["pipeCount"])
        && $_SESSION["pipeCount"] > 0
        || !empty(($_SESSION["promptData"]));
}
