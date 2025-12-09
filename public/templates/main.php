<?php
$title = "Shell spell";
$extraCss[] = 'main.css';
$script = "main.js";
?>

<div class="game-container">
    <div class="header-wrapper">
        <div class="header-group">
            <div class="title-container">
                Shell Spell
                <img src="../assets/images/favicon-32x32.png">
            </div>
        </div>
        <div class="header-group">
            <?php
            if (!isset($_SESSION["user"]["id"])) {
                echo '            <a href="register">
                <div class="header-element">
                    Register
                    <img class="icon" src="../assets/images/icon_register_white.png" alt="register_icon">
                </div>
            </a>            
            <a href="login">
                <div class="header-element">
                    Sign In
                    <img class="icon" src="../assets/images/icon_profile_white.png" alt="profile_icon">
                </div>
            </a>';
            } else {
                echo '
            <a href="profile">
                <div class="header-element">
                    Profile
                    <img class="icon-medium" src="../assets/images/icon_profile_white.png" alt="profile_icon">
                </div>
            </a>';
            }
            ?>
        </div>
    </div>
    <div class="elements-container">
        <div class="elements-wrapper">
            <?php
            foreach ($_SESSION["curRoom"]->doors as $door) {
                echo "
                <div class='element-container'>
                    <div class='element door'></div>                        
                    <p class='element-name " . $door->requiredRole->value . "'>" . $door->name . "</p> 
                </div>";
            }
            ?>
        </div>
        <div class="elements-wrapper">
            <?php
            foreach ($_SESSION["curRoom"]->items as $item) {
                echo "
                <div class='element-container'>
                    <div class='element item " . $item->type->value . "'> </div>
                    <p class='element-name " . $item->requiredRole->value . "'>" . $item->name . "</p>
                </div> 
                ";
            }
            ?>
        </div>
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
            echo $_SESSION["curMana"] / $_SESSION["maxMana"] * 100;
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