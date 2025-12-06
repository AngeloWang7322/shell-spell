<?php

declare(strict_types=1);

require_once "./../src/db/db.php";
require_once "./../src/db/dbhelper.php";

$errors = [];
$success = "";
$title = "register";
$extraCss[] = "auth.css";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $errors[] = "Bitte alle Felder ausfüllen.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Bitte eine gültige E-Mail-Adresse eingeben.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Passwort muss mindestens 6 Zeichen lang sein.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);

        if ($stmt->fetch()) {
            $errors[] = json_encode($stmt->fetch());
            $errors[] = "Diese E-Mail ist bereits registriert.";
        } else {
            $dbHelper = new DBHelper($pdo);
            $dbHelper->createUserRows($password, $email, $name);
            header('Location: /');
        }
    }
}
?>
<div class="form-wrapper">
    <h1>Register</h1>
    <?php if (!empty($errors)): ?>
        <div style="color:red;">
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div style="color:green;"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <form method="post">
        <label>
            Name:<br>
            <input type="text" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
        </label><br><br>
        <label>
            E-Mail:<br>
            <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </label><br><br>

        <label>
            Passwort:<br>
            <input type="password" name="password">
        </label><br><br>
        <button type="submit">Registrieren</button>
    </form>
    <p>Schon registriert? <a href="login">Zum Login</a></p>
</div>