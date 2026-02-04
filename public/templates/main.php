<?php
importCss("main.css");
importScript("main.js");

$baseString = colorizeString(" [ " . $_SESSION["user"]["username"] . "@" . $_SESSION["mapName"] . "  -" . end($_SESSION["curRoom"]->path) . " ]$ &nbsp", $_SESSION["gameController"]->userRank->value);
?>

<div class="game-container">
    <div class="elements-container">
        <div class="elements-wrapper">
            <?php
            // ROOMS
            foreach ($_SESSION["curRoom"]->doors as $door)
            {
                if ($door->isHidden && isset($_SESSION["displayAll"])) continue;
                echo "
                <div class='element-container'>
                    <div class='element door'></div>
                    <p class='element-name " . $door->requiredRank->value . "'>" . $door->name . "</p> 
                </div>";
            }
            foreach ($_SESSION["curRoom"]->items as $item)
            {
                $itemClasses = $item->type->value;
                if ($item->type == ItemType::ALTER && !$item->isActive) $itemClasses .= " alter-inactive";
                echo "
                <div class='element-container'>
                    <div class='element item " . $itemClasses . "'> </div>
                    <p class='element-name " . $item->requiredRank->value . "'>" . $item->name . "</p>
                </div> 
                ";
            }
            ?>
        </div>
    </div>
    <?php
    //SCROLL
    if (isset($_SESSION["openedScroll"]))
    {
        echo '
        <div class="scroll-container">
            <div class="header-container">
                <h1 class="scroll-header">' . $_SESSION["openedScroll"]["header"] . '</h1>
            </div>
            <form class="scroll-content" method="post">
                <input type="hidden" value="editScroll" name="action">
                <textarea name="newFileContent" class="file-text-input">'
            .  $_SESSION["openedScroll"]["content"] .
            '</textarea>
            <button type="submit" class="save-scroll-button"><h3>SAVE</h3></button>
            </form>
        </div>';
    }
    ?>
    <div class="ui-wrapper">
        <div class="spellbook-wrapper">
            <div class="history-container">
                <?php
                //HISTORY
                for ($i = 0; $i < count($_SESSION["history"]); $i++)
                    echo "<p class='prev-command'>"
                        . $_SESSION["history"][$i]["directory"]
                        . $_SESSION["history"][$i]["command"] . "<br>"
                        . $_SESSION["history"][$i]["response"]
                        . "</p>";
                ?>
            </div>
            <div class="input-line">
                <div class="base-string">
                    <?=
                    //INPUT LINE
                    $baseString
                    ?>
                </div>
                <form class="command-input" method="post">
                    <input type="hidden" value="enterCommand" name="action">
                    <input type="hidden" value=<?php echo '"' . $baseString . '"' ?> name="baseString">
                    <input id="commandInputField" name="command" class="command-input" type="text" autocomplete="off" autofocus>
                </form>
            </div>
            <div class="xp-display-container">
                <div class="xp-bar" style="width: <?= $_SESSION["gameController"]->calculateLvlProgress()?>%;">
                    <h4 class="xp-text">
                        <?=
                        $_SESSION["gameController"]->userRank->value
                        ?>
                    </h4>
                </div>
            </div>
        </div>
    </div>
</div>