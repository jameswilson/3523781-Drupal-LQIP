<?php
$src = 'images/hero.hi-res.jpg';
$display_w = 1200;
$display_h = 675;
$lqip_tiny_w = 16;
$lqip_tiny_h = 9;
$lqip_lcp_w = $display_w;
$lqip_lcp_h = $display_h;
$lqip_lcp_path = "images/hero.low-res.webp";
$min_bpp = 0.05;
$min_size = intval($lqip_lcp_w * $lqip_lcp_h * $min_bpp);
$target_bpp = 0.055;
$target_size = intval($lqip_lcp_w * $lqip_lcp_h * $target_bpp);

// Generate tiny base64 LQIP
$im = imagecreatefromjpeg($src);
$tiny = imagecreatetruecolor($lqip_tiny_w, $lqip_tiny_h);
imagecopyresampled($tiny, $im, 0, 0, 0, 0, $lqip_tiny_w, $lqip_tiny_h, imagesx($im), imagesy($im));

// Apply a simple box blur to the tiny image
function smooth_gd_image($im, $w, $h) {
  $smoothed = imagecreatetruecolor($w, $h);
  for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
      $r = $g = $b = $count = 0;
      for ($dy = -1; $dy <= 1; $dy++) {
        for ($dx = -1; $dx <= 1; $dx++) {
          $nx = $x + $dx;
          $ny = $y + $dy;
          if ($nx >= 0 && $nx < $w && $ny >= 0 && $ny < $h) {
            $rgb = imagecolorat($im, $nx, $ny);
            $r += ($rgb >> 16) & 0xFF;
            $g += ($rgb >> 8) & 0xFF;
            $b += $rgb & 0xFF;
            $count++;
          }
        }
      }
      $r = round($r / $count);
      $g = round($g / $count);
      $b = round($b / $count);
      $color = imagecolorallocate($smoothed, $r, $g, $b);
      imagesetpixel($smoothed, $x, $y, $color);
    }
  }
  return $smoothed;
}
$tiny = smooth_gd_image($tiny, $lqip_tiny_w, $lqip_tiny_h);

ob_start();
imagewebp($tiny, null, 30);
$lqip_tiny_data = ob_get_clean();
file_put_contents(__DIR__ . '/images/hero-lqip-' . $lqip_tiny_w . 'x' . $lqip_tiny_h . '.webp', $lqip_tiny_data);
$lqip_tiny_base64 = base64_encode($lqip_tiny_data);
$lqip_tiny_base64_length = strlen($lqip_tiny_base64);
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
  <title>Ultimate LQIP with Blur</title>
  <?php include_once 'includes/style.php'; ?>
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🖼</text></svg>">
  <meta name="description" content="This page demonstrates the two-level LQIP technique: a tiny blurred base64 placeholder, then a display-size LQIP-LCP image at 0.055 BPP, then the full-res image.">
</head>

<body>
  <?php $currentPage = basename(__FILE__);
  include 'includes/nav.php'; ?>
  <div class="hero-wrapper hero-wrapper--lqip-ultimate">
    <img
      class="hero-placeholder hero-placeholder--lqip-ultimate blur-12px"
      alt=""
      width="<?= $display_w ?>"
      height="<?= $display_w ?>"
      src="data:image/webp;base64,<?= $lqip_tiny_base64 ?>" />
    <img
      class="hero-lo-res hero-lo-res--lqip-ultimate fade-in blur-12px"
      alt=""
      loading="eager"
      width="<?= $lqip_lcp_w ?>"
      height="<?= $lqip_lcp_h ?>"
      src="<?= $lqip_lcp_url ?>"
      onload="this.classList.add('loaded');" />
    <img
      class="hero-hi-res hero-hi-res--lqip-ultimate fade-in"
      alt="Hero"
      loading="eager"
      width="<?= $display_w ?>"
      height="<?= $display_w ?>"
      src="<?= $hero_url ?>"
      onload="this.classList.add('loaded');" />
  </div>
  <div class="container">
    <h1>The Ultimate LQIP technique with Blur</h1>
    <p>This page demonstrates the two-level LQIP technique from <a href="https://csswizardry.com/2023/09/the-ultimate-lqip-lcp-technique/">Harry Roberts</a>: a tiny blurred base64 placeholder, then a display-size LQIP image at 0.055 BPP to satisfy LCP with a blur applied, then the full-res image. Note: <strong>Based on the overall lower Performance Score stats on <a href="/results.php">Results page</a>, it appears that applying the blur to the LQIP-LCP image is having a net negative effect.</strong></p>
    <ul>
      <li><strong>LQIP-LCP min size:</strong> <?= $min_size ?> bytes (0.05 BPP)</li>
      <li><strong>LQIP-LCP target size:</strong> <?= $target_size ?> bytes (0.055 BPP)</li>
      <li><strong>LQIP-LCP actual size:</strong> <?= file_exists($lqip_lcp_path) ? filesize($lqip_lcp_path) : '?' ?> bytes</li>
      <li><strong>Tiny WebP base64 length:</strong> <?= $lqip_tiny_base64_length ?> chars</li>
      <li><strong>Display size:</strong> <?= $lqip_lcp_w ?>×<?= $lqip_lcp_h ?></li>
    </ul>
  </div>
</body>

</html>
