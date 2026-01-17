<?php

declare(strict_types=1);

require_once "./../src/db/db.php";
require_once "./../src/db/dbhelper.php";

exitIfLoggedIn();

$extraCss[] = "/assets/css/auth.css";
$extraCss[] = "https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css";
$title = "Log In";
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $dbHelper = new DBHelper(pdo: $pdo);

    if ($email === '' || $password === '')
    {
        $errors[] = "Bitte alle Felder ausfüllen.";
        exit();
    }

    try
    {
        $dbHelper->loginUser($password, $email);
        header('Location: selection');
    }
    catch (Exception $e)
    {
        $errors[] = $e->getMessage();
    }
}
?>

<div class="form-wrapper container">
    <h1>Log In</h1>
    <?php if (!empty($errors)): ?>
        <div class="errors">
            <ul>
                <?php foreach ($errors as $e): ?>
                    <?php echo htmlspecialchars($e); ?><br>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
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
                Password:
                <input class="form-control" type="password" name="password">
            </label>
        </div>
        <br>
        <button class="btn btn-primary btn-lg" type="submit">Log In</button>
    </form>
    <br>
    <div class="well"> No Account Yet? </div>
    <form action="register">
        <button class="btn btn-primary btn-sm">Register</button>
    </form>
</div>