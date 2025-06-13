<?php
// Pass through simulated image latency from GET param if present.
$delay = isset($_GET['delay']) ? ('?delay=' . intval($_GET['delay'])) : '';
$hero_url = 'images/hero.hi-res.jpg.php' . $delay;
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Baseline Eager</title>
  <link rel="stylesheet" href="styles/main.css">
</head>
<body>
  <?php $currentPage = 'baseline-eager.php'; include 'includes/nav.php'; ?>
  <div class="hero-wrapper">
    <img
      class="hero"
      width="1200"
      height="675"
      src="<?= $hero_url ?>"
      alt="Hero"
      loading="eager"
    />
  </div>
  <div class="container">
    <h1>Baseline Eager</h1>
    <p>This page demonstrates a baseline eager-loaded JPG hero image.</p>
  </div>
</body>
</html>
