<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LQIP/LCP Comparison Demo</title>
  <link rel="stylesheet" href="styles/main.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🖼</text></svg>">
</head>

<body>
  <?php $currentPage = 'index.php';
  include 'includes/nav.php'; ?>
  <div class="container">
    <h1>LQIP/LCP Comparison Demo</h1>
    <p>This project demonstrates and compares several <strong>Low-Quality Image Placeholder (LQIP)</strong> techniques for a hero image, with the goal of evaluating their impact on <strong>Largest Contentful Paint (LCP)</strong> and perceived load experience. Each test page implements a different approach:</p>
    <ul class="demo-list">
      <?php foreach ($pages as $page): ?>
        <?php if ($page['file'] === 'index.php') continue; ?>
        <li>
          <a href="<?= $page['file'] ?>"><?= $page['label'] ?></a>
          <div class="desc"><?= $page['desc'] ?></div>
        </li>
      <?php endforeach; ?>
    </ul>
    <h2>Points of Comparison</h2>
    <ul>
      <li><strong>Payload size:</strong> Inline WebP base64 vs. inline SVG vs. CSS (+ JS) size.</li>
      <li><strong>Placeholder render quality:</strong> Visual fidelity and smoothness of transition.</li>
      <li><strong>Load experience:</strong> Jank, visual shift, and need for fade-in effects.</li>
      <li><strong>LCP score:</strong> How each technique affects Lighthouse LCP measurement.</li>
      <li><strong>Processing requirements:</strong> Simplicity and efficiency of each approach.</li>
    </ul>
    <p>Open each test page and use Lighthouse or WebPageTest to compare LCP and user experience.</p>
  </div>
</body>

</html>
