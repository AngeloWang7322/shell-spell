<?php

declare(strict_types=1);
require_once __DIR__ . "/../../src/db/dbhelper.php";

exitIfNotLoggedIn();

$extraCss[] = "/assets/css/gameStateSelection.css";
$gameStates = $dbHelper->getGameStates();
?>

<html>
<div class="page-content">
    <div class="page-title">SELECT GAME</div>

    <div class="selection-wrapper">
        <?php
        foreach ($gameStates as $id => $data)
        {
            echo "
            <div class='option-wrapper'>
            <form class='hidden-button' method='post' > 
                <input type='hidden' value='loadMap' name='action'>
                <input type='hidden' value='" . $id . "' name='mapId'>
                <button class='hidden-button' type='submit'>
                    <div class='button-large state-option-container'>
                        <div class='state-name'>" .
                $data['name'] .
                "</div>
                        <div class='" . $data['rank'] . "'>"
                . $data["rank"] .
                "</div>
                    </div>
                </button>
            </form>
            <form class='hidden-button' method='post'>                    
                <input type='hidden' value='deleteMap' name='action'>
                <input type='hidden' value='" . $id . "' name='mapId'>
                <button class='button-medium' type='submit'>
                    <div>delete</div>
                </button>
            </form>
            </div>
            ";
        }
        echo "</button></form>";
        for ($i = 0; $i < 3 - count($gameStates); $i++)
        {
            echo "
            <div class='empty-option-container'>
                Save " . count($gameStates) + $i + 1 . "
            </div>
            ";
        }
        ?>
        <a class="new-game" href="newgame" class="<?php if (count($gameStates) >= 3) echo 'disabled' ?>">
            <div class="button-large new-game-button">
                <div>NEW GAME</div>
            </div>
        </a>
    </div>
</div>

</html>