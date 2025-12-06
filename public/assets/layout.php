<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title><?= $title ?? 'CLI Dungeon' ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Pixelify+Sans:wght@400..700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/base.css">

  <!-- <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico"> -->
  <link rel="icon" type="image/png" href="assets/images/favicon-32x32.png">

  <?php if (count($extraCss) > 0)
    foreach ($extraCss as $css) {
      echo '<link rel="stylesheet" href="/assets/css/' .  htmlspecialchars($css) . '">';
    }
  ?>
  <?php if (!empty($script)): ?>
    <script src="/scripts/<?= htmlspecialchars($script) ?>"></script>
  <?php endif; ?>
</head>
</html>