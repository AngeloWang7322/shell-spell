<?php
$title = "Shell spell";
$extraCss = 'main.css';
$script = "main.js";
?>
<div class="game-container">
    <div class="elements-wrapper">
        <?php
        foreach ($_SESSION["curRoom"]->doors as $door) {
            echo "<div class='element'> <p>" . $door->name . "</p> </div>";
        }
        ?>
    </div>
    <div class="elements-wrapper">
        <?php
        foreach ($_SESSION["curRoom"]->items as $item) {
            echo "<div class='element item'> <p>" . $item->name . "</p> </div>";
        }
        ?>
    </div>
    <div class="ui-wrapper">
        <div class="spellbook-wrapper">
            <div class="history-container">
                <?php
                for ($i = 0; $i < count($_SESSION["history"]); $i++)
                    echo "<p class='prev-command'>"
                        . $_SESSION["history"][$i]["directory"] . ">"
                        . $_SESSION["history"][$i]["command"] . "<br>"
                        . $_SESSION["history"][$i]["response"]
                        . "</p>";
                ?>
            </div>
            <div class="input-line">
                <?php
                $type = gettype($_SESSION["curRoom"]->path);
                $tempPathString = implode("/", $_SESSION["curRoom"]->path);
                echo $tempPathString . ">";
                ?>
                <form class="command-input" method="post">
                    <input type="hidden" value="enterCommand" name="action">
                    <input name="command" class="command-input" type="text" autocomplete="off" autofocus>
                </form>
            </div>
        </div>
        <div class="mana-display-container">
            <div class="mana-bar" style="width:
            <?php
            echo $_SESSION["user"]->curMana;
            ?>%;">
            </div>
            <h3 class="mana-text">
                MANA
            </h3>
        </div>
    </div>
    <div class="scroll-container" style="visibility: 
    <?php
    if ($_SESSION["openedScroll"]->isOpen) {
        echo "visible";
    } else {
        echo "hidden";
    }
    ?>;">
        <div class="header-container">
            <h1 class="scroll-header">
                <?php echo $_SESSION["openedScroll"]->header; ?>

            </h1>
        </div>
        <div class="scroll-content">
            <p>
                <?php echo $_SESSION["openedScroll"]->content; ?>
            </p>
        </div>
    </div>
</div>