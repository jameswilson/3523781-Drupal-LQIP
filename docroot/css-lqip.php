<?php
$src = 'images/hero.jpg';
$hero_url = $src . '?' . time();

function get_dominant_color($image_path) {
  $img = @imagecreatefromjpeg($image_path);
  if (!$img) return '#cccccc';
  $thumb = imagecreatetruecolor(1, 1);
  imagecopyresampled($thumb, $img, 0, 0, 0, 0, 1, 1, imagesx($img), imagesy($img));
  $index = imagecolorat($thumb, 0, 0);
  $rgb = imagecolorsforindex($thumb, $index);
  imagedestroy($img);
  imagedestroy($thumb);
  return sprintf("#%02x%02x%02x", $rgb['red'], $rgb['green'], $rgb['blue']);
}
$dominant_color = get_dominant_color($src);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CSS-only LQIP</title>
  <link rel="stylesheet" href="styles/main.css">
  <style>
    .hero-wrapper {
      width: 100vw;
      max-width: 1200px;
      margin: 0 auto;
      background: <?= $dominant_color ?>;
      position: relative;
      overflow: hidden;
      aspect-ratio: 16/9;
      /* Adjust as needed for your hero image */
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
  </style>
</head>
<body>
  <?php $currentPage = 'css-lqip.php'; include 'includes/nav.php'; ?>
  <div class="hero-wrapper">
    <img
      class="hero-img"
      width="1200"
      height="675"
      src="<?= $hero_url ?>"
      alt="Hero"
      loading="eager"
      onload="this.classList.add('loaded');" />
  </div>
  <div class="container">
    <h1>CSS-only LQIP</h1>
    <p>This page demonstrates a CSS-only LQIP technique using a dominant color or gradient as background, and the hero image fading in on top.</p>
    <p><strong>Dominant color used:</strong> <span style="background:<?= $dominant_color ?>;padding-left:1em;border-radius:0.3em;"></span>&nbsp;<kbd><?= $dominant_color ?></kbd></p>
  </div>
</body>
</html>
