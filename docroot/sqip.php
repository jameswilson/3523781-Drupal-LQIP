<?php
$src = 'images/hero.hi-res.jpg';
$svg_path = 'images/hero.sqip-128.svg';
$display_w = 1200;
$display_h = 675;
$svg = file_exists($svg_path) ? file_get_contents($svg_path) : '';

// Ensure SVG has width/height attributes
if ($svg) {
  if (!preg_match('/width="\d+"/', $svg) || !preg_match('/height="\d+"/', $svg)) {
    $svg = preg_replace('/<svg(\s+)/', '<svg width="' . $display_w . '" height="' . $display_h . '"$1', $svg, 1);
  }
}

// Optionally pad the SVG to test BPP threshold
if (isset($_GET['pad']) && $svg) {
  $target_bpp = 0.06;
  $target_size = intval($display_w * $display_h * $target_bpp);
  $current_size = strlen($svg);
  if ($current_size < $target_size) {
    $pad_len = $target_size - $current_size;
    $svg = preg_replace('/<\/svg>/', "<!--" . str_repeat('X', $pad_len) . "--></svg>", $svg, 1);
  }
}

$svg_bytes = strlen($svg);
$bpp = $svg_bytes / ($display_w * $display_h);
$warn = $bpp < 0.055;
// Pass through simulated image latency from GET param if present.
$delay = isset($_GET['delay']) ? ('?delay=' . intval($_GET['delay'])) : '';
$hero_url = 'images/hero.hi-res.jpg.php' . $delay;
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SQIP Placeholder</title>
  <link rel="stylesheet" href="styles/main.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🖼</text></svg>">
  <style>
    .hero-wrapper {
      position: relative;
      width: 100vw;
      max-width: 1200px;
      margin: 0 auto;
      overflow: hidden;
      aspect-ratio: 16/9;
    }

    .hero-sqip {
      width: 100%;
      display: block;
      position: absolute;
      top: 0;
      left: 0;
      z-index: 1;
      transition: opacity 0.5s;
    }

    .hero-sqip svg {
      width: 100%;
      height: auto;
    }

    .hero-full {
      width: 100%;
      display: block;
      opacity: 0;
      transition: opacity 0.5s;
      position: relative;
      z-index: 2;
    }

    .hero-full.loaded {
      opacity: 1;
    }

    .hero-sqip.hide {
      opacity: 0;
    }

    .bpp-warn {
      color: #fff;
      background: #f31f65;
      padding: 0.3em 0.7em;
      border-radius: 0.3em;
      font-weight: bold;
      display: inline-block;
      margin-top: 0.5em;
    }
  </style>
</head>

<body>
  <?php $currentPage = 'sqip.php';
  include 'includes/nav.php'; ?>
  <div class="hero-wrapper">
    <div class="hero-sqip" id="sqip-placeholder">
      <?= $svg ?>
    </div>
    <img
      class="hero-full"
      width="<?= $display_w ?>"
      height="<?= $display_h ?>"
      src="<?= $hero_url ?>"
      alt="Hero"
      loading="eager"
      onload="this.classList.add('loaded');document.getElementById('sqip-placeholder').classList.add('hide');" />
  </div>
  <div class="container">
    <h1>SQIP Placeholder</h1>
    <p>This page demonstrates an inline SQIP SVG placeholder, swapped for the full-res hero image.</p>
    <ul>
      <li><strong>SVG path:</strong> <?= $svg_path ?></li>
      <li><strong>SVG byte size:</strong> <?= $svg_bytes ?> bytes</li>
      <li><strong>Display size:</strong> <?= $display_w ?>×<?= $display_h ?></li>
      <li><strong>BPP:</strong> <?= number_format($bpp, 4) ?> (minimum recommended: 0.055)</li>
    </ul>
    <?php if ($warn): ?>
      <div class="bpp-warn">Warning: BPP is below 0.055. This SVG may not be considered a valid LCP candidate by Chrome.</div>
    <?php endif; ?>
    <p>
      <a href="?pad=1">Pad SVG to meet BPP threshold</a>
    </p>
  </div>
</body>

</html>
