<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= $title ?? 'CLI Dungeon' ?></title>

  <!-- global stylesheet -->
  <link rel="stylesheet" href="/assets/css/base.css">

  <?php if (!empty($extraCss)): ?>
    <link rel="stylesheet" href="/assets/css/<?= htmlspecialchars($extraCss) ?>">
  <?php endif; ?>
</head>
<body>
  <?= $content ?>
</body>
</html>