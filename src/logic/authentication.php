<?php
session_start();
require_once 'db.php';

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
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

        // prüfen, ob E-Mail schon existiert
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = "Diese E-Mail ist bereits registriert.";
        } else {
            // Passwort hashen, aber in deiner Spalte `password` speichern
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $insert = $pdo->prepare("
                INSERT INTO users (name, email, password)
                VALUES (:name, :email, :password)
            ");
            $insert->execute([
                ':name'     => $name,
                ':email'    => $email,
                ':password' => $passwordHash
            ]);

            $success = "Registrierung erfolgreich. Du kannst dich jetzt einloggen.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Registrierung</title>
</head>
<body>
    <h1>Registrierung</h1>

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

    <p>Schon registriert? <a href="login.php">Zum Login</a></p>
</body>
</html>
