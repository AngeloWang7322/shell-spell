<?php

declare(strict_types=1);


exitIfLoggedIn();
importCss("auth.css");

$errors = [];
$success = "";

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
            header('Location: menu');
        }
    }
}
?>
<div class="page-content">
    <div class="page-title">Register</div>

    <?php if ($success): ?>
        <div style="color:green;"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <form class="form-wrapper" method="post">
        <div class="form-group">
            <label>
                Name
                <input class="form-control" type="text" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
            </label>
        </div><br>
        <div class="form-group">
            <label>
                E-Mail
                <input class="form-control" type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </label>
        </div><br>
        <div class="form-group">
            <label>
                Password
                <input class="form-control" type="password" name="password">
            </label>
        </div><br>
        <button class="button-large" type="submit">Register</button>
    </form>
    <?php if (!empty($errors)): ?>
        <div class="errors" style="color:red;">
            <?php foreach ($errors as $e): ?>
                <?php echo $e . "<br>"; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <br>
    <div action="alternative-auth-wrapper">
        <h3>Already Have An Account?</h3>
        <a href="/login">
            <div class="button-medium">
                Login
            </div>
        </a>
    </div>

</div>