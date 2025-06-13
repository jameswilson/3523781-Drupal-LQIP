<?php
$src = 'images/hero.hi-res.jpg';

function get_dominant_color($image_path) {
  $img = @imagecreatefromjpeg($image_path);
  if (!$img) return '#cccccc';
  $thumb = imagecreatetruecolor(1, 1);
  imagecopyresampled($thumb, $img, 0, 0, 0, 0, 1, 1, imagesx($img), imagesy($img));
  $index = imagecolorat($thumb, 0, 0);
  $rgb = imagecolorsforindex($thumb, $index);
  imagedestroy($img);
  imagedestroy($thumb);
  return $rgb;
}

function get_lqip_int($image_path) {
  $img = @imagecreatefromjpeg($image_path);
  if (!$img) return 0;
  $w = 3;
  $h = 2;
  $thumb = imagecreatetruecolor($w, $h);
  imagecopyresampled($thumb, $img, 0, 0, 0, 0, $w, $h, imagesx($img), imagesy($img));
  $brightness = [];
  for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
      $index = imagecolorat($thumb, $x, $y);
      $rgb = imagecolorsforindex($thumb, $index);
      // Simple grayscale: average
      $gray = ($rgb['red'] + $rgb['green'] + $rgb['blue']) / 3;
      // Quantize to 2 bits (0-3)
      $q = (int)round($gray / 255 * 3);
      $brightness[] = $q;
    }
  }
  // Get dominant color (already quantized to 8 bits per channel)
  $dom = get_dominant_color($image_path);
  // Quantize base color: 2 bits for R, 3 for G, 3 for B (total 8 bits)
  $r = (int)round($dom['red'] / 255 * 3); // 2 bits
  $g = (int)round($dom['green'] / 255 * 7); // 3 bits
  $b = (int)round($dom['blue'] / 255 * 7); // 3 bits
  $base_color = ($r << 6) | ($g << 3) | $b; // 8 bits
  // Pack brightness (6x2 bits = 12 bits)
  $brightness_bits = 0;
  for ($i = 0; $i < 6; $i++) {
    $brightness_bits |= ($brightness[$i] & 0x3) << (10 - $i * 2);
  }
  // Final packed int: [12 bits brightness][8 bits color]
  $packed = ($brightness_bits << 8) | $base_color;
  imagedestroy($img);
  imagedestroy($thumb);
  return $packed;
}

$dominant_color = get_dominant_color($src);
$dominant_hex = sprintf("#%02x%02x%02x", $dominant_color['red'], $dominant_color['green'], $dominant_color['blue']);
$lqip_int = get_lqip_int($src);

// Pass through simulated image latency from GET param if present.
$delay = isset($_GET['delay']) ? ('?delay=' . intval($_GET['delay'])) : '';
$hero_url = 'images/hero.hi-res.jpg.php' . $delay;
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
    /* LQIP CSS decoder, adapted from leanrada.com */
    [style*="--lqip:"]:is(
      :not(img),
      img[loading=lazy],
      .force-lqip
    ) {
      --lqip-ca: mod(round(down, calc((var(--lqip) + pow(2, 19)) / pow(2, 18))), 4);
      --lqip-cb: mod(round(down, calc((var(--lqip) + pow(2, 19)) / pow(2, 16))), 4);
      --lqip-cc: mod(round(down, calc((var(--lqip) + pow(2, 19)) / pow(2, 14))), 4);
      --lqip-cd: mod(round(down, calc((var(--lqip) + pow(2, 19)) / pow(2, 12))), 4);
      --lqip-ce: mod(round(down, calc((var(--lqip) + pow(2, 19)) / pow(2, 10))), 4);
      --lqip-cf: mod(round(down, calc((var(--lqip) + pow(2, 19)) / pow(2, 8))), 4);
      --lqip-ll: mod(round(down, calc((var(--lqip) + pow(2, 19)) / pow(2, 6))), 4);
      --lqip-aaa: mod(round(down, calc((var(--lqip) + pow(2, 19)) / pow(2, 3))), 8);
      --lqip-bbb: mod(calc(var(--lqip) + pow(2, 19)), 8);

      --lqip-ca-clr: hsl(0 0% calc(var(--lqip-ca) / 3 * 60% + 20%));
      --lqip-cb-clr: hsl(0 0% calc(var(--lqip-cb) / 3 * 60% + 20%));
      --lqip-cc-clr: hsl(0 0% calc(var(--lqip-cc) / 3 * 60% + 20%));
      --lqip-cd-clr: hsl(0 0% calc(var(--lqip-cd) / 3 * 60% + 20%));
      --lqip-ce-clr: hsl(0 0% calc(var(--lqip-ce) / 3 * 60% + 20%));
      --lqip-cf-clr: hsl(0 0% calc(var(--lqip-cf) / 3 * 60% + 20%));
      --lqip-base-clr: oklab(
        calc(var(--lqip-ll) / 3 * 0.6 + 0.2)
          calc(var(--lqip-aaa) / 8 * 0.7 - 0.35)
          calc((var(--lqip-bbb) + 1) / 8 * 0.7 - 0.35)
      );

      --lqip-stop10: 2%;
      --lqip-stop20: 8%;
      --lqip-stop30: 18%;
      --lqip-stop40: 32%;
      background-blend-mode:
        hard-light, hard-light, hard-light, hard-light, hard-light, hard-light,
        normal;
      background-image: radial-gradient(
          50% 75% at 16.67% 25%,
          var(--lqip-ca-clr),
          rgb(from var(--lqip-ca-clr) r g b / calc(100% - var(--lqip-stop10))) 10%,
          rgb(from var(--lqip-ca-clr) r g b / calc(100% - var(--lqip-stop20))) 20%,
          rgb(from var(--lqip-ca-clr) r g b / calc(100% - var(--lqip-stop30))) 30%,
          rgb(from var(--lqip-ca-clr) r g b / calc(100% - var(--lqip-stop40))) 40%,
          rgb(from var(--lqip-ca-clr) r g b / calc(var(--lqip-stop40))) 60%,
          rgb(from var(--lqip-ca-clr) r g b / calc(var(--lqip-stop30))) 70%,
          rgb(from var(--lqip-ca-clr) r g b / calc(var(--lqip-stop20))) 80%,
          rgb(from var(--lqip-ca-clr) r g b / calc(var(--lqip-stop10))) 90%,
          transparent
        ),
        radial-gradient(
          50% 75% at 50% 25%,
          var(--lqip-cb-clr),
          rgb(from var(--lqip-cb-clr) r g b / calc(100% - var(--lqip-stop10))) 10%,
          rgb(from var(--lqip-cb-clr) r g b / calc(100% - var(--lqip-stop20))) 20%,
          rgb(from var(--lqip-cb-clr) r g b / calc(100% - var(--lqip-stop30))) 30%,
          rgb(from var(--lqip-cb-clr) r g b / calc(100% - var(--lqip-stop40))) 40%,
          rgb(from var(--lqip-cb-clr) r g b / calc(var(--lqip-stop40))) 60%,
          rgb(from var(--lqip-cb-clr) r g b / calc(var(--lqip-stop30))) 70%,
          rgb(from var(--lqip-cb-clr) r g b / calc(var(--lqip-stop20))) 80%,
          rgb(from var(--lqip-cb-clr) r g b / calc(var(--lqip-stop10))) 90%,
          transparent
        ),
        radial-gradient(
          50% 75% at 83.33% 25%,
          var(--lqip-cc-clr),
          rgb(from var(--lqip-cc-clr) r g b / calc(100% - var(--lqip-stop10))) 10%,
          rgb(from var(--lqip-cc-clr) r g b / calc(100% - var(--lqip-stop20))) 20%,
          rgb(from var(--lqip-cc-clr) r g b / calc(100% - var(--lqip-stop30))) 30%,
          rgb(from var(--lqip-cc-clr) r g b / calc(100% - var(--lqip-stop40))) 40%,
          rgb(from var(--lqip-cc-clr) r g b / calc(var(--lqip-stop40))) 60%,
          rgb(from var(--lqip-cc-clr) r g b / calc(var(--lqip-stop30))) 70%,
          rgb(from var(--lqip-cc-clr) r g b / calc(var(--lqip-stop20))) 80%,
          rgb(from var(--lqip-cc-clr) r g b / calc(var(--lqip-stop10))) 90%,
          transparent
        ),
        radial-gradient(
          50% 75% at 16.67% 75%,
          var(--lqip-cd-clr),
          rgb(from var(--lqip-cd-clr) r g b / calc(100% - var(--lqip-stop10))) 10%,
          rgb(from var(--lqip-cd-clr) r g b / calc(100% - var(--lqip-stop20))) 20%,
          rgb(from var(--lqip-cd-clr) r g b / calc(100% - var(--lqip-stop30))) 30%,
          rgb(from var(--lqip-cd-clr) r g b / calc(100% - var(--lqip-stop40))) 40%,
          rgb(from var(--lqip-cd-clr) r g b / calc(var(--lqip-stop40))) 60%,
          rgb(from var(--lqip-cd-clr) r g b / calc(var(--lqip-stop30))) 70%,
          rgb(from var(--lqip-cd-clr) r g b / calc(var(--lqip-stop20))) 80%,
          rgb(from var(--lqip-cd-clr) r g b / calc(var(--lqip-stop10))) 90%,
          transparent
        ),
        radial-gradient(
          50% 75% at 50% 75%,
          var(--lqip-ce-clr),
          rgb(from var(--lqip-ce-clr) r g b / calc(100% - var(--lqip-stop10))) 10%,
          rgb(from var(--lqip-ce-clr) r g b / calc(100% - var(--lqip-stop20))) 20%,
          rgb(from var(--lqip-ce-clr) r g b / calc(100% - var(--lqip-stop30))) 30%,
          rgb(from var(--lqip-ce-clr) r g b / calc(100% - var(--lqip-stop40))) 40%,
          rgb(from var(--lqip-ce-clr) r g b / calc(var(--lqip-stop40))) 60%,
          rgb(from var(--lqip-ce-clr) r g b / calc(var(--lqip-stop30))) 70%,
          rgb(from var(--lqip-ce-clr) r g b / calc(var(--lqip-stop20))) 80%,
          rgb(from var(--lqip-ce-clr) r g b / calc(var(--lqip-stop10))) 90%,
          transparent
        ),
        radial-gradient(
          50% 75% at 83.33% 75%,
          var(--lqip-cf-clr),
          rgb(from var(--lqip-cf-clr) r g b / calc(100% - var(--lqip-stop10))) 10%,
          rgb(from var(--lqip-cf-clr) r g b / calc(100% - var(--lqip-stop20))) 20%,
          rgb(from var(--lqip-cf-clr) r g b / calc(100% - var(--lqip-stop30))) 30%,
          rgb(from var(--lqip-cf-clr) r g b / calc(100% - var(--lqip-stop40))) 40%,
          rgb(from var(--lqip-cf-clr) r g b / calc(var(--lqip-stop40))) 60%,
          rgb(from var(--lqip-cf-clr) r g b / calc(var(--lqip-stop30))) 70%,
          rgb(from var(--lqip-cf-clr) r g b / calc(var(--lqip-stop20))) 80%,
          rgb(from var(--lqip-cf-clr) r g b / calc(var(--lqip-stop10))) 90%,
          transparent
        ),
        linear-gradient(0deg, var(--lqip-base-clr), var(--lqip-base-clr)
      );
    }
  </style>
</head>
<body>
  <?php $currentPage = 'css-lqip.php'; include 'includes/nav.php'; ?>
  <div class="hero-wrapper" style="--lqip:<?= $lqip_int ?>;">
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
    <p>This page demonstrates a CSS-only LQIP technique using a packed integer and gradients, inspired by <a href='https://leanrada.com/notes/css-only-lqip/'>leanrada.com</a>. The hero image fades in on top of a blurry CSS placeholder.</p>
    <p><strong>Dominant color used:</strong> <span style="background:<?= $dominant_hex ?>;padding-left:1em;border-radius:0.3em;"></span>&nbsp;<kbd><?= $dominant_hex ?></kbd></p>
    <p><strong>LQIP integer:</strong> <kbd><?= $lqip_int ?></kbd></p>
  </div>
</body>
</html>
