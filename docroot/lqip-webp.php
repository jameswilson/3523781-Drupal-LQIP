<?php
$src = 'images/hero.hi-res.jpg';
$display_w = 1200;
$display_h = 675;
$lqip_w = 8;
$lqip_h = 8;

// Generate tiny base64 LQIP
$hq = @imagecreatefromjpeg($src);
if (!$hq) {
  die('Source image not found.');
}
$lq = imagecreatetruecolor($lqip_w, $lqip_h);
imagecopyresampled($lq, $hq, 0, 0, 0, 0, $lqip_w, $lqip_h, imagesx($hq), imagesy($hq));

// Apply a simple box blur to the tiny image
function smooth_gd_image($hq, $w, $h) {
  $smoothed = imagecreatetruecolor($w, $h);
  for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
      $r = $g = $b = $count = 0;
      for ($dy = -1; $dy <= 1; $dy++) {
        for ($dx = -1; $dx <= 1; $dx++) {
          $nx = $x + $dx;
          $ny = $y + $dy;
          if ($nx >= 0 && $nx < $w && $ny >= 0 && $ny < $h) {
            $rgb = imagecolorat($hq, $nx, $ny);
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
$lq = smooth_gd_image($lq, $lqip_w, $lqip_h);

// Compute average color
$total_r = $total_g = $total_b = 0;
for ($y = 0; $y < $lqip_h; $y++) {
  for ($x = 0; $x < $lqip_w; $x++) {
    $rgb = imagecolorat($lq, $x, $y);
    $r = ($rgb >> 16) & 0xFF;
    $g = ($rgb >> 8) & 0xFF;
    $b = $rgb & 0xFF;
    $total_r += $r;
    $total_g += $g;
    $total_b += $b;
  }
}
$pixel_count = $lqip_w * $lqip_h;
$avg_r = round($total_r / $pixel_count);
$avg_g = round($total_g / $pixel_count);
$avg_b = round($total_b / $pixel_count);
$avg_color = sprintf('#%02x%02x%02x', $avg_r, $avg_g, $avg_b);

// Output 8x8 PNG as base64
ob_start();
imagewebp($lq, null, 30);
$lqip_data = ob_get_clean();
$lqip_base64 = base64_encode($lqip_data);
$lqip_base64_length = strlen($lqip_base64);

// Store the WebP file in the images folder
file_put_contents(__DIR__ . '/images/hero-lqip-' . $lqip_w . 'x' . $lqip_h . '.webp', $lqip_data);

imagedestroy($lq);

// Pass through simulated image latency from GET param if present.
$delay = isset($_GET['delay']) ? ('?delay=' . intval($_GET['delay'])) : '';
$hero_url = 'images/hero.hi-res.jpg.php' . $delay;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LQIP WebP (inline background)</title>
  <link rel="stylesheet" href="styles/main.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🖼</text></svg>">
  <meta name="description" content="This page demonstrates a simple LQIP technique: a tiny 8x8 inline WebP as a background-image, then the full-res image.">
  <style>
    .hero-wrapper {
      position: relative;
      width: 100vw;
      max-width: 1200px;
      margin: 0 auto;
      overflow: hidden;
      aspect-ratio: 16/9;
    }

    .hero-img {
      width: 100%;
      height: auto;
      display: block;
      position: absolute;
      top: 0;
      left: 0;
      transition: opacity 0.5s;
    }
  </style>
</head>

<body>
  <?php $currentPage = basename(__FILE__);
  include 'includes/nav.php'; ?>
  <div class="hero-wrapper" style="height: auto; min-height: 300px; aspect-ratio: 16/9;">
    <img class="hero-img" src="<?= $hero_url ?>" width="<?= $display_w ?>" height="<?= $display_h ?>" alt="Hero" loading="eager" style="background-image: url('data:image/png;base64,<?= $lqip_base64 ?>'); background-size: cover; background-position: center; background-color: <?= $avg_color ?>;" />
  </div>
  <div class="container">
    <h1>LQIP WebP (inline background)</h1>
    <p>This page demonstrates a simple LQIP technique: a tiny 8x8 inline WebP as a background-image, then the full-res image.</p>
    <ul>
      <li><strong>LQIP size:</strong> <?= $lqip_w ?>×<?= $lqip_h ?></li>
      <li><strong>LQIP base64 length:</strong> <?= $lqip_base64_length ?> chars</li>
      <li><strong>Average color:</strong> <?= $avg_color ?></li>
      <li><strong>Display size:</strong> <?= $display_w ?>×<?= $display_h ?></li>
    </ul>
  </div>
</body>

</html>
