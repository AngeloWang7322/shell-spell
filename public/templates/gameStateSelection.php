<?php

declare(strict_types=1);
require_once __DIR__ . "/../../src/db/dbhelper.php";

exitIfNotLoggedIn();

$extraCss[] = "/assets/css/gameStateSelection.css";
$gameStates = $dbHelper->getGameStates();
?>

<html>
<div class="center-wrapper">
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
                    <div class='state-option-container'>
                        <div class='state-name'>"
                . $data['name'] .
                "  </div>
                            <p class='" . $data['rank'] . "'>"
                . $data["rank"] .
                "</p>
                    </div>
                </button>
            </form>
            <form class='' method='post'>                    
                <input type='hidden' value='deleteMap' name='action'>
                <input type='hidden' value='" . $id . "' name='mapId'>
                <button class='delete-button' type='submit'>
                    delete
                </button>
            </form>
            </div>
            ";
        }
        ?>

        <?php
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
        <br>
        <a href="newgame" class="<?php if (count($gameStates) >= 3) echo 'disabled' ?>">
            <div class="state-option-container">
                <div class="state-name"> NEW GAME</div>
            </div>
        </a>
    </div>


</div>

</html>