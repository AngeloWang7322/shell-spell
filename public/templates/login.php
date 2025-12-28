<?php
declare(strict_types=1);

require_once "./../src/db/db.php";
require_once "./../src/db/dbhelper.php";

$extraCss[] = "auth.css";
$title = "Log In";
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $dbHelper = new DBHelper(pdo: $pdo);

    if ($email === '' || $password === '') {
        $errors[] = "Bitte alle Felder ausfüllen.";        
        exit();
    }
    
    try {
        $dbHelper->loginUser($password, $email);     
        header('Location: selection');
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}
?>

<div class="form-wrapper">
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

    <form method="post">
        <label class="test">
            E-Mail:<br>
            <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </label><br><br>

        <label>
            Passwort:<br>
            <input type="password" name="password">
        </label><br><br>

        <button type="submit">Einloggen</button>
    </form>
    <p>Noch kein Konto? <a href="register">Registrieren</a></p>
</div>