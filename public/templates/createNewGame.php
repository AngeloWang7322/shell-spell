<?php
declare(strict_types=1);
require_once __DIR__ . "/../../src/db/dbhelper.php";

exitIfNotLoggedIn();
$extraCss[] = "/assets/css/createNewGame.css";

?>

<html>
<div class="center-wrapper">
    <div class="page-title">CREATE NEW GAME</div>
    <form class="map-name-form" method="post">
        <input type="hidden" value="newMap" name="action">
        <input class="name-input" name="newMapName" placeholder="New Game">
        <button class='name-submit-button' type="submit">
            <p>create</p>
        </button>
    </form>
    <br>
</div>

</html>