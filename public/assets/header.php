<?php
$extraCss[] = "assets/css/header.css";
?>
<div class="header-wrapper">
    <div class="header-group">
        <a href="/">
            <div class="title-container">
                <img class="icon-large" src="../assets/images/favicon-32x32.png">
                Shell Spell
                <img class="icon-large" src="../assets/images/favicon-32x32.png">
            </div>
        </a>
    </div>
    <div class="header-group">
        <?php
        if (!isset($_SESSION["user"]["id"])): ?>
            <a href="register">
                <div class="header-element">
                    Register
                    <img class="icon-small" src="../assets/images/icon_register_white.png" alt="register_icon">
                </div>
            </a>
            <a href="login">
                <div class="header-element">
                    Sign In
                    <img class="icon-small" src="../assets/images/icon_profile_white.png" alt="profile_icon">
                </div>
            </a>
        <?php else: ?>
            <?php if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) == "/profile"): ?>
                <form class="logout-form" method="post">
                    <input name="action" value="logoutUser" hidden>
                    <button type="submit" class="logout-button header-element">
                        Logout
                        <img class="icon-small" src="../assets/images/icon_logout.png" alt="logout_icon">
                    </button>
                </form>
            <?php else: ?>
                <a href="profile">
                    <div class="header-element">
                        Profile
                        <img class="icon-small" src="../assets/images/icon_profile_white.png" alt="profile_icon">
                    </div>
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>