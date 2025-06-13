<?php
$src = 'images/hero.hi-res.jpg';

// Oklab conversion helpers (from https://bottosson.github.io/posts/oklab/)
function rgb_to_oklab($r, $g, $b) {
  // Convert sRGB [0,255] to linear [0,1]
  $to_linear = function ($c) {
    $c = $c / 255;
    return $c <= 0.04045 ? $c / 12.92 : pow(($c + 0.055) / 1.055, 2.4);
  };
  $lr = $to_linear($r);
  $lg = $to_linear($g);
  $lb = $to_linear($b);
  // Linear RGB to LMS
  $l = 0.4122214708 * $lr + 0.5363325363 * $lg + 0.0514459929 * $lb;
  $m = 0.2119034982 * $lr + 0.6806995451 * $lg + 0.1073969566 * $lb;
  $s = 0.0883024619 * $lr + 0.2817188376 * $lg + 0.6299787005 * $lb;
  // LMS cube root
  $l_ = pow($l, 1 / 3);
  $m_ = pow($m, 1 / 3);
  $s_ = pow($s, 1 / 3);
  // LMS to Oklab
  $L = 0.2104542553 * $l_ + 0.7936177850 * $m_ - 0.0040720468 * $s_;
  $A = 1.9779984951 * $l_ - 2.4285922050 * $m_ + 0.4505937099 * $s_;
  $B = 0.0259040371 * $l_ + 0.7827717662 * $m_ - 0.8086757660 * $s_;
  return ['L' => $L, 'a' => $A, 'b' => $B];
}

// Find best Oklab bits (ll, aaa, bbb) for a given Oklab color
function find_oklab_bits($targetL, $targetA, $targetB) {
  $best = null;
  $best_diff = INF;
  for ($ll = 0; $ll <= 3; $ll++) {
    $L = $ll / 3 * 0.6 + 0.2;
    for ($aaa = 0; $aaa <= 7; $aaa++) {
      $a = $aaa / 8 * 0.7 - 0.35;
      for ($bbb = 0; $bbb <= 7; $bbb++) {
        $b = ($bbb + 1) / 8 * 0.7 - 0.35;
        $diff = sqrt(pow($L - $targetL, 2) + pow($a - $targetA, 2) + pow($b - $targetB, 2));
        if ($diff < $best_diff) {
          $best_diff = $diff;
          $best = ['ll' => $ll, 'aaa' => $aaa, 'bbb' => $bbb];
        }
      }
    }
  }
  return $best;
}

// Get dominant color using a simple histogram (fallback to average if needed)
function get_dominant_color_palette($image_path) {
  $img = @imagecreatefromjpeg($image_path);
  if (!$img) return ['red' => 204, 'green' => 204, 'blue' => 204];
  // Downscale to 16x16 for speed and robustness
  $w = 16;
  $h = 16;
  $thumb = imagecreatetruecolor($w, $h);
  imagecopyresampled($thumb, $img, 0, 0, 0, 0, $w, $h, imagesx($img), imagesy($img));
  $hist = [];
  for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
      $index = imagecolorat($thumb, $x, $y);
      $rgb = imagecolorsforindex($thumb, $index);
      $key = $rgb['red'] . ',' . $rgb['green'] . ',' . $rgb['blue'];
      if (!isset($hist[$key])) $hist[$key] = 0;
      $hist[$key]++;
    }
  }
  arsort($hist);
  $top = key($hist);
  $parts = explode(',', $top);
  imagedestroy($img);
  imagedestroy($thumb);
  return [
    'red' => intval($parts[0]),
    'green' => intval($parts[1]),
    'blue' => intval($parts[2])
  ];
}

function get_lqip_int($image_path, &$dominant_rgb_out = null) {
  // 1. Get dominant color from full image
  $dom = get_dominant_color_palette($image_path);
  $dominant_rgb_out = $dom;
  $oklab = rgb_to_oklab($dom['red'], $dom['green'], $dom['blue']);
  $bits = find_oklab_bits($oklab['L'], $oklab['a'], $oklab['b']);
  $ll = $bits['ll'];
  $aaa = $bits['aaa'];
  $bbb = $bits['bbb'];

  // 2. Downscale to 3x2 and get Oklab L for each cell
  $img = @imagecreatefromjpeg($image_path);
  if (!$img) return 0;
  $w = 3;
  $h = 2;
  $thumb = imagecreatetruecolor($w, $h);
  imagecopyresampled($thumb, $img, 0, 0, 0, 0, $w, $h, imagesx($img), imagesy($img));
  $cell_L = [];
  for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
      $index = imagecolorat($thumb, $x, $y);
      $rgb = imagecolorsforindex($thumb, $index);
      $ok = rgb_to_oklab($rgb['red'], $rgb['green'], $rgb['blue']);
      $cell_L[] = $ok['L'];
    }
  }
  imagedestroy($img);
  imagedestroy($thumb);

  // 3. For each cell, quantize (clamp(0.5 + L - baseL, 0, 1) * 3) to 2 bits
  $baseL = ($ll / 3) * 0.6 + 0.2;
  $cell_bits = [];
  foreach ($cell_L as $L) {
    $v = max(0, min(1, 0.5 + $L - $baseL));
    $cell_bits[] = (int)round($v * 3);
  }
  // 4. Pack bits as in JS
  $lqip = - (1 << 19)
    + (($cell_bits[0] & 0b11) << 18)
    + (($cell_bits[1] & 0b11) << 16)
    + (($cell_bits[2] & 0b11) << 14)
    + (($cell_bits[3] & 0b11) << 12)
    + (($cell_bits[4] & 0b11) << 10)
    + (($cell_bits[5] & 0b11) << 8)
    + (($ll & 0b11) << 6)
    + (($aaa & 0b111) << 3)
    + ($bbb & 0b111);
  return $lqip;
}

$dominant_rgb = null;
$lqip_int = get_lqip_int($src, $dominant_rgb);
$dominant_hex = sprintf("#%02x%02x%02x", $dominant_rgb['red'], $dominant_rgb['green'], $dominant_rgb['blue']);

// Pass through simulated image latency from GET param if present.
$delay = isset($_GET['delay']) ? ('?delay=' . intval($_GET['delay'])) : '';
$hero_url = 'images/hero.hi-res.jpg.php' . $delay;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CSS-only LQIP</title>
  <link rel="stylesheet" href="styles/main.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🖼</text></svg>">
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
  </style>
</head>

<body>
  <?php $currentPage = 'css-lqip.php';
  include 'includes/nav.php'; ?>
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
  <style>
    /* LQIP CSS decoder, adapted from leanrada.com */
    [style*="--lqip:"] {
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
      --lqip-base-clr: oklab(calc(var(--lqip-ll) / 3 * 0.6 + 0.2) calc(var(--lqip-aaa) / 8 * 0.7 - 0.35) calc((var(--lqip-bbb) + 1) / 8 * 0.7 - 0.35));

      --lqip-stop10: 2%;
      --lqip-stop20: 8%;
      --lqip-stop30: 18%;
      --lqip-stop40: 32%;
      background-blend-mode:
        hard-light, hard-light, hard-light, hard-light, hard-light, hard-light,
        normal;
      background-image: radial-gradient(50% 75% at 16.67% 25%,
          var(--lqip-ca-clr),
          rgb(from var(--lqip-ca-clr) r g b / calc(100% - var(--lqip-stop10))) 10%,
          rgb(from var(--lqip-ca-clr) r g b / calc(100% - var(--lqip-stop20))) 20%,
          rgb(from var(--lqip-ca-clr) r g b / calc(100% - var(--lqip-stop30))) 30%,
          rgb(from var(--lqip-ca-clr) r g b / calc(100% - var(--lqip-stop40))) 40%,
          rgb(from var(--lqip-ca-clr) r g b / calc(var(--lqip-stop40))) 60%,
          rgb(from var(--lqip-ca-clr) r g b / calc(var(--lqip-stop30))) 70%,
          rgb(from var(--lqip-ca-clr) r g b / calc(var(--lqip-stop20))) 80%,
          rgb(from var(--lqip-ca-clr) r g b / calc(var(--lqip-stop10))) 90%,
          transparent),
        radial-gradient(50% 75% at 50% 25%,
          var(--lqip-cb-clr),
          rgb(from var(--lqip-cb-clr) r g b / calc(100% - var(--lqip-stop10))) 10%,
          rgb(from var(--lqip-cb-clr) r g b / calc(100% - var(--lqip-stop20))) 20%,
          rgb(from var(--lqip-cb-clr) r g b / calc(100% - var(--lqip-stop30))) 30%,
          rgb(from var(--lqip-cb-clr) r g b / calc(100% - var(--lqip-stop40))) 40%,
          rgb(from var(--lqip-cb-clr) r g b / calc(var(--lqip-stop40))) 60%,
          rgb(from var(--lqip-cb-clr) r g b / calc(var(--lqip-stop30))) 70%,
          rgb(from var(--lqip-cb-clr) r g b / calc(var(--lqip-stop20))) 80%,
          rgb(from var(--lqip-cb-clr) r g b / calc(var(--lqip-stop10))) 90%,
          transparent),
        radial-gradient(50% 75% at 83.33% 25%,
          var(--lqip-cc-clr),
          rgb(from var(--lqip-cc-clr) r g b / calc(100% - var(--lqip-stop10))) 10%,
          rgb(from var(--lqip-cc-clr) r g b / calc(100% - var(--lqip-stop20))) 20%,
          rgb(from var(--lqip-cc-clr) r g b / calc(100% - var(--lqip-stop30))) 30%,
          rgb(from var(--lqip-cc-clr) r g b / calc(100% - var(--lqip-stop40))) 40%,
          rgb(from var(--lqip-cc-clr) r g b / calc(var(--lqip-stop40))) 60%,
          rgb(from var(--lqip-cc-clr) r g b / calc(var(--lqip-stop30))) 70%,
          rgb(from var(--lqip-cc-clr) r g b / calc(var(--lqip-stop20))) 80%,
          rgb(from var(--lqip-cc-clr) r g b / calc(var(--lqip-stop10))) 90%,
          transparent),
        radial-gradient(50% 75% at 16.67% 75%,
          var(--lqip-cd-clr),
          rgb(from var(--lqip-cd-clr) r g b / calc(100% - var(--lqip-stop10))) 10%,
          rgb(from var(--lqip-cd-clr) r g b / calc(100% - var(--lqip-stop20))) 20%,
          rgb(from var(--lqip-cd-clr) r g b / calc(100% - var(--lqip-stop30))) 30%,
          rgb(from var(--lqip-cd-clr) r g b / calc(100% - var(--lqip-stop40))) 40%,
          rgb(from var(--lqip-cd-clr) r g b / calc(var(--lqip-stop40))) 60%,
          rgb(from var(--lqip-cd-clr) r g b / calc(var(--lqip-stop30))) 70%,
          rgb(from var(--lqip-cd-clr) r g b / calc(var(--lqip-stop20))) 80%,
          rgb(from var(--lqip-cd-clr) r g b / calc(var(--lqip-stop10))) 90%,
          transparent),
        radial-gradient(50% 75% at 50% 75%,
          var(--lqip-ce-clr),
          rgb(from var(--lqip-ce-clr) r g b / calc(100% - var(--lqip-stop10))) 10%,
          rgb(from var(--lqip-ce-clr) r g b / calc(100% - var(--lqip-stop20))) 20%,
          rgb(from var(--lqip-ce-clr) r g b / calc(100% - var(--lqip-stop30))) 30%,
          rgb(from var(--lqip-ce-clr) r g b / calc(100% - var(--lqip-stop40))) 40%,
          rgb(from var(--lqip-ce-clr) r g b / calc(var(--lqip-stop40))) 60%,
          rgb(from var(--lqip-ce-clr) r g b / calc(var(--lqip-stop30))) 70%,
          rgb(from var(--lqip-ce-clr) r g b / calc(var(--lqip-stop20))) 80%,
          rgb(from var(--lqip-ce-clr) r g b / calc(var(--lqip-stop10))) 90%,
          transparent),
        radial-gradient(50% 75% at 83.33% 75%,
          var(--lqip-cf-clr),
          rgb(from var(--lqip-cf-clr) r g b / calc(100% - var(--lqip-stop10))) 10%,
          rgb(from var(--lqip-cf-clr) r g b / calc(100% - var(--lqip-stop20))) 20%,
          rgb(from var(--lqip-cf-clr) r g b / calc(100% - var(--lqip-stop30))) 30%,
          rgb(from var(--lqip-cf-clr) r g b / calc(100% - var(--lqip-stop40))) 40%,
          rgb(from var(--lqip-cf-clr) r g b / calc(var(--lqip-stop40))) 60%,
          rgb(from var(--lqip-cf-clr) r g b / calc(var(--lqip-stop30))) 70%,
          rgb(from var(--lqip-cf-clr) r g b / calc(var(--lqip-stop20))) 80%,
          rgb(from var(--lqip-cf-clr) r g b / calc(var(--lqip-stop10))) 90%,
          transparent),
        linear-gradient(0deg, var(--lqip-base-clr), var(--lqip-base-clr));
    }
  </style>
</body>

</html>
