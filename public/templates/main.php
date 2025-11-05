<?php
$title = "Shell spell";
$extraCss = 'main.css';
?>

<div class="main-wrapper">
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
            $type = gettype($_SESSION["curRoom"] -> path);
            // $tempPathString = implode("/", $_SESSION["curRoom"] -> path);
                echo $type . ">";
            ?>
            <form class="command-input" method="post">
                <input type="hidden" value="enterCommand" name="action">
                <input name="command" @class="command-input" type="text" autofocus>
            </form>
        </div>


    </div>
</div>