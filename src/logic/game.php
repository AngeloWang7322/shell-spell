<?php
declare(strict_types= 1);


function closeScroll(){

    unset($_SESSION["openedScroll"]);
}
function editScroll(){
    $tempScroll = &getItem($_SESSION["openedScroll"]["path"]);
    $tempScroll->content = $_POST["newFileContent"];
    closeScroll();
}