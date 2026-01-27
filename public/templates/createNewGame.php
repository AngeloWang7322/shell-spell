<?php

declare(strict_types=1);
require_once __DIR__ . "/../../src/db/dbhelper.php";

exitIfNotLoggedIn();
$extraCss[] = "/assets/css/createNewGame.css";
$script = "newGame.js"
?>

<html>
<div class="page-content">
    <div class="page-title">CREATE NEW GAME</div>
    <form class="map-name-form" method="post">
        <div class="new-game-data">
            <input type="hidden" value="newMap" name="action">
            <input id="newMapNameInput" autocomplete="off" maxlength="15" class="button-large name-input" name="newMapName" placeholder="New Game">
            <select id="rankSelect" name="rank" class="rank-select" required>
                <option value="" disabled selected hidden>Select Commands To Learn</option>
                <?php foreach (Rank::cases() as $Rank): ?>
                    <option style="color:<?= $Rank->getColor() ?>" value="<?= $Rank->value ?>">
                        <div class="rank-option">
                            <span>Lvl <?= $Rank->rank() + 1 ?> - <?= strtoupper($Rank->value) ?></span>:&nbsp
                            <?php if ($Rank->value == "root"): ?>Sandbox Mode
                        <?php elseif ($Rank->value != "wanderer"): ?>
                        <?php endif; ?>
                        <?= implode(", ", array_values(GameController::$levelData[$Rank->value])) ?>
                        </div>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button id="createGameButton" disabled class='button-create-map' type="submit">
            <div>CREATE</div>
        </button>
    </form>
    <br>
</div>

</html>