<?php
$title = "Shell spell";
$extraCss = 'main.css';
?>

<div class="main-wrapper">
    <div class="spellbook-wrapper">

        <div class="history-container">
            <?php
            echo "count" . count($_SESSION["history"]);
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
                echo $_SESSION["currentDirectory"] . ">";
            ?>
            <form class="command-input" method="post">
                <input type="hidden" value="enterCommand" name="action">
                <input name="command" @class="command-input" type="text" autofocus>
            </form>
        </div>


    </div>
</div>