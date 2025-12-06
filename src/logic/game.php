<?php
declare(strict_types= 1);

if($_POST["action"] == "closeScroll")
{
    closeScroll();
}
function closeScroll(){
        $_SESSION["openedScroll"]->header = ""; 
        $_SESSION["openedScroll"]->content = "";
        $_SESSION["openedScroll"]->isOpen = false;
}
