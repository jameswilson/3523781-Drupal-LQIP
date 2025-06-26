<?php
$src = 'images/hero.hi-res.jpg';
$display_w = 1200;
$display_h = 675;

// Pass through simulated image latency from GET param if present.
$delay = isset($_GET['delay']) ? ('?delay=' . intval($_GET['delay'])) : '';
$hero_url = 'images/hero.hi-res.jpg.php' . $delay;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Baseline Eager Fade-in</title>
  <?php include_once 'includes/style.php'; ?>
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🖼</text></svg>">
  <meta name="description" content="This page demonstrates a simple image load effect: an eager-loaded JPG image with a fade-in transition effect. There is no LQIP.">
</head>

<body>
  <?php $currentPage = basename(__FILE__);
  include 'includes/nav.php'; ?>
  <div class="hero-wrapper hero-wrapper--baseline-eager-fade-in">
    <img
      class="hero-hi-res hero-hi-res--baseline-eager-fade-in fade-in"
      src="<?= $hero_url ?>"
      width="<?= $display_w ?>"
      height="<?= $display_h ?>"
      alt="Hero"
      loading="eager"
      onload="this.classList.add('loaded');" />
  </div>
  <div class="container">
    <h1>Eager JPG with fade-in on load</h1>
    <p>This page demonstrates a simple image load effect: an eager-loaded JPG image with a fade-in transition effect. There is no LQIP.</p>
    <ul>
      <li><strong>Display size:</strong> <?= $display_w ?>×<?= $display_h ?></li>
    </ul>
  </div>
</body>

</html>
