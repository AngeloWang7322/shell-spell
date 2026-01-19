<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Shell Spell</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Pixelify+Sans:wght@400..700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Jersey+10&display=swap" rel="stylesheet">


  <link rel="icon" type="image/png" href="assets/images/favicon-32x32.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/base.css">

  <?php if (!empty($extraCss) && count($extraCss) > 0)
    foreach ($extraCss as $css)
    {
      echo '<link rel="stylesheet" href="' .  htmlspecialchars($css) . '">';
    }
  ?>

  <?php if (!empty($script)): ?>
    <script src="/scripts/<?= htmlspecialchars($script) ?>"></script>
  <?php endif; ?>
</head>

</html>