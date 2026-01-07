<?php

declare(strict_types=1);

$extraCss[] = "profile.css";

?>
<div class="page-content">

    <div class="ui-wrapper">
        <a href="/">
            <div class="back-button">
                <img class="icon-large" src="/assets/images/icon_back_white.png" alt="back">
            </div>
        </a>
    </div>
    Profile
    <div class="profile-picture-container">
        <?php 
            $pic = $_SESSION["profile_pic"] ?? "/uploads/default.png";
        ?>
        <?php
            $default = "/uploads/profile_pics/default.png";
            $pic = $_SESSION["profile_pic"] ?? $default;
        ?>
        <img src="<?= htmlspecialchars($pic) ?>" class="profile-picture">

        <form class="profile-upload-form" method="post" enctype="multipart/form-data" action="/profile">
            <input type="file" name="profile_pic" accept="image/png,image/jpeg,image/webp" required>
            <button type="submit" name="upload_profile_pic">Upload</button>
        </form>
    </div>
</div>