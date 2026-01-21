<?php

declare(strict_types=1);

require_once "./../src/db/db.php";
require_once "./../src/db/dbhelper.php";

exitIfLoggedIn();

$extraCss[] = "/assets/css/auth.css";
$title = "Log In";
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

<div class="form-wrapper container">
    <h1>Log In</h1>
    <br>
    <form class="d-flex flex-column justify-content-center" method="post">
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
        <button class="btn btn-danger btn-lg" type="submit">Log In</button>
    </form> <?php if (!empty($errors)): ?>
        <div class="errors" style="color:red;">
            <?php foreach ($errors as $e): ?>
                <?php echo $e . "<br>"; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <br>
    <div class="well"> No Account Yet? </div>
    <form action="register">
        <button class="btn btn-danger btn-md">Register</button>
    </form>
</div>