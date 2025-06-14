<?php
require_once __DIR__ . '/../vendor/autoload.php';

use kornrunner\Blurhash\Blurhash;

$src = 'images/hero.hi-res.jpg';
$display_w = 1200;
$display_h = 675;
$blurhash_w = 32;
$blurhash_h = 18;
$components_x = 4;
$components_y = 3;

$image = @imagecreatefromjpeg($src);
if ($image) {
  $width = imagesx($image);
  $height = imagesy($image);
  // Downscale for BlurHash input
  $thumb = imagecreatetruecolor($blurhash_w, $blurhash_h);
  imagecopyresampled($thumb, $image, 0, 0, 0, 0, $blurhash_w, $blurhash_h, $width, $height);
  $pixels = [];
  for ($y = 0; $y < $blurhash_h; ++$y) {
    $row = [];
    for ($x = 0; $x < $blurhash_w; ++$x) {
      $index = imagecolorat($thumb, $x, $y);
      $colors = imagecolorsforindex($thumb, $index);
      $row[] = [$colors['red'], $colors['green'], $colors['blue']];
    }
    $pixels[] = $row;
  }
  $blurhash = Blurhash::encode($pixels, $components_x, $components_y);
  imagedestroy($thumb);
  imagedestroy($image);
} else {
  $blurhash = '';
}

// Pass through simulated image latency from GET param if present.
$delay = isset($_GET['delay']) ? ('?delay=' . intval($_GET['delay'])) : '';
$hero_url = 'images/hero.hi-res.jpg.php' . $delay;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BlurHash LQIP</title>
  <link rel="stylesheet" href="styles/main.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🖼</text></svg>">
  <meta name="description" content="This page demonstrates a BlurHash LQIP technique using the php-blurhash library.">
  <style>
    .hero-wrapper {
      width: 100vw;
      max-width: 1200px;
      margin: 0 auto;
      position: relative;
      overflow: hidden;
      aspect-ratio: 16/9;
    }

    .hero-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      opacity: 0;
      transition: opacity 0.7s;
      position: absolute;
      top: 0;
      left: 0;
    }

    .hero-img.loaded {
      opacity: 1;
    }

    .blurhash-canvas {
      width: 100%;
      height: 100%;
      display: block;
      position: absolute;
      top: 0;
      left: 0;
      z-index: 1;
      background: #eee;
    }
  </style>
</head>

<body>
  <?php $currentPage = basename(__FILE__);
  include 'includes/nav.php'; ?>
  <div class="hero-wrapper">
    <canvas
      class="blurhash-canvas"
      id="blurhash-canvas"
      width="<?= $blurhash_w ?>"
      height="<?= $blurhash_h ?>"
      data-blurhash="<?= htmlspecialchars($blurhash) ?>"
      style="aspect-ratio:16/9;width:100%;height:100%;">
    </canvas>
    <img
      class="hero-img"
      width="1200"
      height="675"
      src="<?= $hero_url ?>"
      alt="Hero"
      loading="eager"
      onload="this.classList.add('loaded');document.getElementById('blurhash-canvas').style.opacity=0;" />
  </div>
  <div class="container">
    <h1>BlurHash LQIP</h1>
    <p>This page demonstrates a BlurHash LQIP technique using the <a href='https://github.com/kornrunner/php-blurhash'>php-blurhash</a> library. The hero image fades in on top of a BlurHash placeholder rendered to a canvas.</p>
    <p><strong>BlurHash string:</strong> <kbd><?= htmlspecialchars($blurhash) ?></kbd></p>
  </div>
  <script src="scripts/blurhash-decode.js"></script>
  <script>
    // Render BlurHash to canvas using JS (client-side decode)
    document.addEventListener('DOMContentLoaded', function() {
      var canvas = document.getElementById('blurhash-canvas');
      var blurhash = canvas.dataset.blurhash;
      if (!blurhash) return;
      var width = canvas.width;
      var height = canvas.height;
      var pixels = window.blurhashDecode(blurhash, width, height);
      var ctx = canvas.getContext('2d');
      var imageData = ctx.createImageData(width, height);
      for (var i = 0; i < pixels.length; i++) {
        imageData.data[i * 4 + 0] = pixels[i][0];
        imageData.data[i * 4 + 1] = pixels[i][1];
        imageData.data[i * 4 + 2] = pixels[i][2];
        imageData.data[i * 4 + 3] = 255;
      }
      ctx.putImageData(imageData, 0, 0);
      // Upscale canvas to fit container
      canvas.style.width = '100%';
      canvas.style.height = '100%';
    });
  </script>
</body>

</html>
