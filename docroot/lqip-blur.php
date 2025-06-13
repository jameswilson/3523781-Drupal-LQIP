<?php
$src = 'images/hero.hi-res.jpg';
$display_w = 1200;
$display_h = 675;
$lqip_tiny_w = 16;
$lqip_tiny_h = 9;
$lqip_lcp_w = $display_w;
$lqip_lcp_h = $display_h;
$lqip_lcp_path = "images/hero.low-res.webp";
$target_bpp = 0.055;
$target_size = intval($lqip_lcp_w * $lqip_lcp_h * $target_bpp);

// Generate tiny base64 LQIP
$im = imagecreatefromjpeg($src);
$tiny = imagecreatetruecolor($lqip_tiny_w, $lqip_tiny_h);
imagecopyresampled($tiny, $im, 0, 0, 0, 0, $lqip_tiny_w, $lqip_tiny_h, imagesx($im), imagesy($im));
ob_start();
imagewebp($tiny, null, 30);
$lqip_tiny_data = ob_get_clean();
$lqip_tiny_base64 = base64_encode($lqip_tiny_data);
imagedestroy($tiny);

// Generate or load LQIP-LCP image at target BPP
if (!file_exists($lqip_lcp_path)) {
  $lcp = imagecreatetruecolor($lqip_lcp_w, $lqip_lcp_h);
  imagecopyresampled($lcp, $im, 0, 0, 0, 0, $lqip_lcp_w, $lqip_lcp_h, imagesx($im), imagesy($im));
  // Find the quality setting that gets us closest to the target size
  $best_quality = 75;
  $best_diff = PHP_INT_MAX;
  for ($q = 10; $q <= 90; $q += 2) {
    ob_start();
    imagewebp($lcp, null, $q);
    $data = ob_get_clean();
    $size = strlen($data);
    $diff = abs($size - $target_size);
    if ($diff < $best_diff) {
      $best_quality = $q;
      $best_diff = $diff;
      $best_data = $data;
    }
    if ($size <= $target_size && $diff < 200) break; // close enough
  }
  file_put_contents($lqip_lcp_path, $best_data);
  imagedestroy($lcp);
}
imagedestroy($im);

// Pass through simulated image latency from GET param if present.
$delay = isset($_GET['delay']) ? ('?delay=' . intval($_GET['delay'])) : '';
$hero_url = 'images/hero.hi-res.jpg.php' . $delay;
$lqip_lcp_url = 'images/hero.low-res.webp.php' . $delay;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LQIP with Blur (2-level)</title>
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

    .lqip-img,
    .lcp-img,
    .hero-img {
      width: 100%;
      height: auto;
      display: block;
      position: absolute;
      top: 0;
      left: 0;
      transition: opacity 0.5s;
    }

    .lqip-img {
      z-index: 1;
      opacity: 1;
      filter: blur(20px);
    }

    .lcp-img {
      z-index: 2;
      opacity: 0;
    }

    .hero-img {
      z-index: 3;
      opacity: 0;
    }

    .lcp-img.loaded {
      opacity: 1;
    }

    .hero-img.loaded {
      opacity: 1;
    }

    .lqip-img.hide {
      opacity: 0;
    }
  </style>
</head>

<body>
  <?php $currentPage = 'lqip-blur.php';
  include 'includes/nav.php'; ?>
  <div class="hero-wrapper" style="height: auto; min-height: 300px; aspect-ratio: 16/9;">
    <img class="lqip-img" src="data:image/webp;base64,<?= $lqip_tiny_base64 ?>" alt="LQIP placeholder" />
    <img class="lcp-img" src="<?= $lqip_lcp_url ?>" width="<?= $lqip_lcp_w ?>" height="<?= $lqip_lcp_h ?>" alt="LQIP-LCP" onload="this.classList.add('loaded');this.previousElementSibling.classList.add('hide');" />
    <img class="hero-img" src="<?= $hero_url ?>" width="<?= $lqip_lcp_w ?>" height="<?= $lqip_lcp_h ?>" alt="Hero" loading="eager" onload="this.classList.add('loaded');this.previousElementSibling.classList.add('hide');" />
  </div>
  <div class="container">
    <h1>LQIP with Blur (2-level)</h1>
    <p>This page demonstrates the two-level LQIP technique: a tiny blurred base64 placeholder, then a display-size LQIP-LCP image at 0.055 BPP, then the full-res image.</p>
    <ul>
      <li><strong>LQIP-LCP target size:</strong> <?= $target_size ?> bytes (0.055 BPP)</li>
      <li><strong>LQIP-LCP actual size:</strong> <?= file_exists($lqip_lcp_path) ? filesize($lqip_lcp_path) : '?' ?> bytes</li>
      <li><strong>Display size:</strong> <?= $lqip_lcp_w ?>×<?= $lqip_lcp_h ?></li>
    </ul>
  </div>
</body>

</html>
