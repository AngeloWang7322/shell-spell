<?php

declare(strict_types=1);

require_once "./../src/db/db.php";
require_once "./../src/db/dbhelper.php";

exitIfLoggedIn();

$errors = [];
$success = "";
$title = "register";
$extraCss[] = "https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css";
$extraCss[] = "/assets/css/auth.css";

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $password === '')
    {
        $errors[] = "Bitte alle Felder ausfüllen.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    {
        $errors[] = "Bitte eine gültige E-Mail-Adresse eingeben.";
    }

    if (strlen($password) < 6)
    {
        $errors[] = "Passwort muss mindestens 6 Zeichen lang sein.";
    }

    if (empty($errors))
    {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);

        if ($stmt->fetch())
        {
            $errors[] = json_encode($stmt->fetch());
            $errors[] = "Diese E-Mail ist bereits registriert.";
        }
        else
        {
            $dbHelper = new DBHelper($pdo);
            $dbHelper->registerUser($password, $email, $name);
            header('Location: selection');
        }
    }
}
?>
<div class="page-content form-wrapper">
    <h1>Register</h1>

    <?php if ($success): ?>
        <div style="color:green;"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <form class="d-flex flex-column justify-content-center" method="post">
        <div class="form-group">
            <label>
                <div class="well">Name</div>
                <input class="form-control" type="text" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
            </label>
        </div><br>
        <div class="form-group">
            <label>
                E-Mail:<br>
                <input class="form-control" type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </label>
        </div><br>
        <div class="form-group">
            <label>
                Password<br>
                <input class="form-control" type="password" name="password">
            </label>
        </div><br>
        <button class="btn btn-danger btn-lg" type="submit">Registrieren</button>
    </form>
    <?php if (!empty($errors)): ?>
        <div class="errors" style="color:red;">
                <?php foreach ($errors as $e): ?>
                    <?php echo $e . "<br>"; ?>
                <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <br>
    <form action="login">
Already Registered?
        <button class="btn btn-danger btn-md">Log In</button>
    </form>

</div>