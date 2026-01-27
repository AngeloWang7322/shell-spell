<?php

declare(strict_types=1);

require_once "./../src/db/db.php";
require_once "./../src/db/dbhelper.php";

exitIfLoggedIn();

$extraCss[] = "/assets/css/auth.css";
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $dbHelper = new DBHelper(pdo: $pdo);

    if ($email == '' || $password == '')
    {
        $errors[] = "Bitte alle Felder ausfüllen.";
        // header('Location: /login');
    }

    try
    {
        $dbHelper->loginUser($password, $email);
        header('Location: menu');
    }
    catch (Exception $e)
    {
        $errors[] = $e->getMessage();
    }
}
?>

<div class="page-content">
    <div class="page-title">Log In</div>
    <form class="form-wrapper" method="post">
        <div class="form-group">
            <label>E-Mail
                <input class="form-control" type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </label>
        </div>
        <br>
        <div class="form-group">
            <label>
                Password
                <input class="form-control" type="password" name="password">
            </label>
        </div>
        <br>
        <button class="button-large" type="submit">Log In</button>
    </form> <?php if (!empty($errors)): ?>
        <div class="errors" style="color:red;">
            <?php foreach ($errors as $e): ?>
                <?php echo $e . "<br>"; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <br>
    <div action="alternative-auth-wrapper">
        <h3>Don't Have An Account?</h3>
        <a href="/register">
            <div class="button-medium">
                Register
            </div>
        </a>
    </div>
</div>