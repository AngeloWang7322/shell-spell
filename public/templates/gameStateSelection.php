<?php
declare(strict_types=1);
require_once __DIR__ . "/../../src/db/dbhelper.php";

$gameStates = $dbHelper->getGameStateOptions();

foreach($gameStates as $key => $role){
    echo "
    <div class='card-select'>"
        . $key 
        . $rank
    . "</div>";
}

?>