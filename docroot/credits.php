<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Credits</title>
  <link rel="stylesheet" href="styles/main.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🖼</text></svg>">
  <meta name="description" content="Credits for the images and techniques used in this demo.">
</head>

<body>
  <?php $currentPage = 'credits.php';
  include 'includes/nav.php'; ?>
  <div class="container">
    <h1>Credits</h1>
    <p><a href="https://unsplash.com/photos/low-angle-photo-of-beige-concrete-building-under-cloudy-sky-N2zk9yXjmLA">Hero image</a> by Anthony Esau on Unsplash.</p>
    <h2>Technique References</h2>
    <ul>
      <li>
        <a href="https://github.com/axe312ger/sqip" target="_blank" rel="noopener">SQIP (SVG-based LQIP technique)</a> by Benedikt Rötsch
      </li>
      <li>
        <a href="https://leanrada.com/notes/css-only-lqip" target="_blank" rel="noopener">Pure CSS LQIP (CSS-only dominant color/gradient)</a> by Lean Rada
      </li>
      <li>
        <a href="https://css-tricks.com/the-blur-up-technique-for-loading-background-images/" target="_blank" rel="noopener">CSS-Tricks: The Blur Up Technique</a> by Emil Björklund
      </li>
      <li>
        <a href="https://csswizardry.com/2023/09/the-ultimate-lqip-lcp-technique/" target="_blank" rel="noopener">CSS Wizardry: The Ultimate LQIP/LCP Technique</a> by Harry Roberts
      </li>
      <li>
        <a href="https://web.dev/lcp/" target="_blank" rel="noopener">Largest Contentful Paint (LCP) documentation</a>
      </li>
      <li>
        <a href="https://developer.mozilla.org/en-US/docs/Web/HTML/Element/img#attr-loading" target="_blank" rel="noopener">MDN: &lt;img loading&gt; attribute</a>
      </li>
    </ul>
  </div>
</body>

</html>
