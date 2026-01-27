<?php

declare(strict_types=1);
exitIfNotLoggedIn();
$extraCss[] = "/assets/css/profile.css";
$script = "upload.js";
$default = "/assets/images/default.png";
$pic = $_SESSION["profile_pic"] ?? $default;

?>
<div class="page-content">
    <div class="page-title"> Profile </div>
    <form class="profile-upload-form" method="post" enctype="multipart/form-data" action="/profile">
        <input type="hidden" name="action" value="uploadProfilePic" enctype="multipart/form-data">
        <div class="profile-picture-container">
            <img id="defaultPicture" class="profile-picture" src="<?= htmlspecialchars($pic) ?>">
            <img id="profilePicture" style="display:none" class="profile-picture" src="">
            <input id="fileInput" class="upload-input" type="file" hidden name="profile_pic" accept="image/png,image/jpeg,image/webp" required />
            <label class="select-file-button" for="fileInput">
                <img class="icon" src="/assets/images/icon_edit.png">
            </label>
        </div>
        <h3 class="name-container"><?= $_SESSION["user"]["name"] ?? "User" ?></h3>
        <div class="file-status-container">
            <img id="preview" style="display:none; max-width:300px;">
            <p id="fileStatus" class="file-status"></p>
            <img id="imgPreview" class="img-preview">
            <button id="uploadButton" class="button-medium" style="display:none" type="submit">
                <div>Upload</div>
            </button>
        </div>
    </form>
    <a class="select-map-container" href="/menu">
        <button class="button-large" type="submit">
            <div>Enter Menu</div>
        </button>
    </a>
</div>