<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title><?= $title ?? 'CLI Dungeon' ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Pixelify+Sans:wght@400..700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/base.css">

  <?php if (count($extraCss) > 0)
    foreach ($extraCss as $css) {
      echo '<br><link rel="stylesheet" href="/assets/css/' .  htmlspecialchars($css) . '">';
    }
  ?>
  <?php if (!empty($script)): ?>
    <script src="/scripts/<?= htmlspecialchars($script) ?>"></script>
  <?php endif; ?>
</head>

<body>
</body>

</html>