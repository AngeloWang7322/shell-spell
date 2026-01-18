<?php

declare(strict_types=1);
function manageExecution()
{
    if (isset($_SESSION["promptData"]))
    {
        handlePrompt();
    }
    else if (strstr($_POST["command"], "|"))
    {
        //isPipeValid();
        $_SESSION["pipeCount"]++;
        handleCommandChain("|");
        $_SESSION["pipeCount"]--;
    }
    else if (strstr($_POST["command"], "&&"))
    {
        handleCommandChain("&&");
    }
    else if (strstr($_POST["command"], " > ") || strstr($_POST["command"], " >> "))
    {
        handleRedirect();
        return;
    }

    prepareCommandExecution();
    // echo "<br> tokens: " . json_encode($_SESSION["tokens"]);
    executeCommand();
}
function handlePrompt()
{
    $answer = $_POST["command"];

    switch (true)
    {
        case (in_array($_POST["command"], [$_SESSION["promptData"]["options"][0], ""])):
            {
                executeCommand();
                $answer = "y";
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
function handleCommandChain($seperator)
{
    $beforeSeperator = "";
    $afterSeperator = "";
    splitString($_POST["command"], $beforeSeperator, $afterSeperator, $seperator);

    $_POST["command"] = $beforeSeperator;

    $_SESSION["tokens"] = [];
    $_POST["command"] = $afterSeperator;
}

function handleRedirect()
{
    $seperator = strstr($_POST["command"], ">>") ? ">>" : ">";
    $command = "";
    $redirectFilePath = "";

    splitString($_POST["command"], $command, $redirectFilePath, $seperator);
    $_POST["command"] = $command;
    prepareCommandExecution();
    executeCommand();
    $_SESSION["response"] = "";

    $redirectFilePath = toPath($redirectFilePath);
    checkIfCanRedirect($redirectFilePath, $seperator);
    addStdinToFile($seperator, $redirectFilePath);
}
function arrayToString($array, $seperator = "<br>", $includeKeys = true)
{
    $finalString = "";
    foreach ($array as $key => $line)
    {
        $finalString .= $includeKeys ?
            $key . " " . $line . $seperator
            : $line . $seperator;
    }
    return $finalString;
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
    if (!isset($_SESSION["stdin"])) throw new Exception("invalid usage of '" . $seperator . "' operator");
}
function addStdinToFile($seperator, $redirectFilePath)
{
    $newStr = arrayToString($_SESSION["stdin"]);
    $destItem = &getItem($redirectFilePath);
    if ($seperator == ">>")
    {
        $destItem->content = $newStr;
    }
    else
    {
        $destItem->content .= $newStr;
    }
}
function prepareCommandExecution()
{
    $command = getCommand(explode(" ", trim($_POST["command"]))[0]);
    $command->parseInput();
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
    $_SESSION["response"] = $e->getMessage();
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
        $_SESSION["stdin"],
    );
}
function mustPreserveState()
{
    return isset($_SESSION["pipeCount"])
        && $_SESSION["pipeCount"] > 0
        || !empty(($_SESSION["promptData"]));
}
