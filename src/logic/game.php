<?php
declare(strict_types= 1);

if($_POST["action"] == "closeScroll")
{
    closeScroll();
}
function closeScroll(){
    unset($_SESSION["openedScroll"]);
}
