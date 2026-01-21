<?php

declare(strict_types=1);
require_once __DIR__ . "/../../src/db/dbhelper.php";

exitIfNotLoggedIn();
$extraCss[] = "/assets/css/createNewGame.css";

?>

<html>
<div class="page-content">
    <div class="page-title">CREATE NEW GAME</div>
    <form class="map-name-form" method="post">
        <input type="hidden" value="newMap" name="action">
        <input maxlength="15" class="button-large name-input" name="newMapName" placeholder="New Game">
        <button class='button-large button-create-map' type="submit">
            <div>CREATE</div>
        </button>
    </form>
    <br>
</div>

</html>