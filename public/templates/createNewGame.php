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
            <input id="newMapNameInput" maxlength="15" class="button-large name-input" name="newMapName" placeholder="New Game">
            <select id="rankSelect" name="rank" class="rank-select" required>
                <option value="" disabled selected hidden>Select Commands To Learn</option>
                <?php foreach (Role::cases() as $role): ?>
                    <option style="color:<?= $role->getColor() ?>" value="<?= $role->value ?>">
                        <div class="rank-option">
                            <span>Lvl <?= $role->rank() ?> - <?= strtoupper($role->value) ?></span>:&nbsp
                            <?php if ($role->value == "root"): ?>Sandbox Mode
                        <?php elseif ($role->value != "wanderer"): ?>
                        <?php endif; ?>
                        <?= implode(", ", array_values(GameController::$levelData[$role->value])) ?>
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