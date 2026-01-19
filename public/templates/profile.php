<?php

declare(strict_types=1);
exitIfNotLoggedIn();
$extraCss[] = "/assets/css/profile.css";

?>
<div class="page-content">
    <div class="ui-wrapper">
        <a href="/">
            <div class="back-button">
                <img class="icon-medium" src="/assets/images/icon_back_white.png" alt="back">
            </div>
        </a>
    </div>
    <h1>Profile</h1>
    <div class="profile-picture-container">
        <?php
        $default = "/uploads/profile_pics/default.png";
        $pic = $_SESSION["profile_pic"] ?? $default;
        ?>
        <img src="<?= htmlspecialchars($pic) ?>" class="profile-picture">

        <form class="profile-upload-form" method="post" enctype="multipart/form-data" action="/profile">
            <input type="hidden" name="action" value="uploadProfilePic">
            <input type="file" name="profile_pic" accept="image/png,image/jpeg,image/webp" required>
            <button type="submit">Upload</button>
        </form>

    </div>

    
</div>