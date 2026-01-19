<?php
declare(strict_types=1);

function handleProfilePicUpload(): ?string
{
    unset($_SESSION["upload_error"]);

    if (!isset($_FILES["profile_pic"]) || ($_FILES["profile_pic"]["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $_SESSION["upload_error"] = "Upload failed (no file or upload error).";
        return null;
    }

    $file = $_FILES["profile_pic"];

    if (($file["size"] ?? 0) > 10 * 1024 * 1024) {
        $_SESSION["upload_error"] = "File too large (max 10MB).";
        return null;
    }

    $info = @getimagesize($file["tmp_name"]);
    if ($info === false) {
        $_SESSION["upload_error"] = "Not a valid image.";
        return null;
    }

    $allowed = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp",
    ];

    $mime = $info["mime"] ?? "";
    if (!isset($allowed[$mime])) {
        $_SESSION["upload_error"] = "Only JPG, PNG or WEBP allowed.";
        return null;
    }

    $publicDir = realpath(__DIR__ . "/../../public");
    if ($publicDir === false) {
        $_SESSION["upload_error"] = "Server error: could not locate public directory.";
        return null;
    }

    $uploadDir = $publicDir . "/uploads/profile_pics";
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            $_SESSION["upload_error"] = "Server error: could not create upload folder.";
            return null;
        }
    }

    $ext = $allowed[$mime];
    $filename = "profile_" . bin2hex(random_bytes(8)) . "." . $ext;

    $destFsPath = $uploadDir . "/" . $filename;
    $destUrlPath = "/uploads/profile_pics/" . $filename;
    if (!empty($_SESSION["profile_pic"]) && $_SESSION["profile_pic"] !== "/uploads/profile_pics/default.png") {
        $old = $publicDir . $_SESSION["profile_pic"];
        if (is_file($old)) {
            @unlink($old);
        }

    }

    if (!move_uploaded_file($file["tmp_name"], $destFsPath)) {
        $_SESSION["upload_error"] = "Server error: could not save file.";
        return null;
    }
    $_SESSION["profile_pic"] = $destUrlPath;
    return $destUrlPath;

}
