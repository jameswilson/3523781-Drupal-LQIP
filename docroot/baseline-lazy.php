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
  <title>Baseline Lazy</title>
  <link rel="stylesheet" href="styles/main.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🖼</text></svg>">
</head>

<body>
  <?php $currentPage = 'baseline-lazy.php';
  include 'includes/nav.php'; ?>
  <div class="hero-wrapper">
    <img
      class="hero"
      width="1200"
      height="675"
      src="<?= $hero_url ?>"
      alt="Hero"
      loading="lazy" />
  </div>
  <div class="container">
    <h1>Baseline Lazy</h1>
    <p>This page demonstrates a baseline lazy-loaded JPG hero image.</p>
  </div>
</body>

</html>
