<?php
$results = json_decode(file_get_contents('../test/lighthouse-results.json'), true);

// Define sortable columns
$columns = [
  'page' => 'Page',
  'lcp' => '<abbr title="Largest Contentful Paint">LCP</abbr> (ms)',
  'fcp' => '<abbr title="First Contentful Paint">FCP</abbr> (ms)',
  'tti' => '<abbr title="Time to Interactive">TTI</abbr> (ms)',
  'si' => '<abbr title="Speed Index">SI</abbr> (ms)',
  'score' => 'Score (%)'
];

// Get sort parameters from GET
$sort = $_GET['sort'] ?? 'page';
$dir = $_GET['dir'] ?? 'asc';

// Validate sort column
if (!array_key_exists($sort, $columns)) {
  $sort = 'page';
}

// Sort results
usort($results, function ($a, $b) use ($sort, $dir) {
  $aVal = $a[$sort] ?? null;
  $bVal = $b[$sort] ?? null;
  if ($aVal == $bVal) return 0;
  if ($dir === 'asc') {
    return ($aVal < $bVal) ? -1 : 1;
  } else {
    return ($aVal > $bVal) ? -1 : 1;
  }
});

// Helper to build sort URLs
function sort_url($col, $currentSort, $currentDir) {
  $dir = ($col === $currentSort && $currentDir === 'asc') ? 'desc' : 'asc';
  return '?sort=' . urlencode($col) . '&dir=' . $dir;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lighthouse Results</title>
  <link rel="stylesheet" href="styles/main.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🖼</text></svg>">
  <meta name="description" content="Lighthouse results for the LQIP/LCP Comparison Demo.">
</head>

<body>
  <?php $currentPage = basename(__FILE__);
  include 'includes/nav.php'; ?>
  <div class="container">
    <h1>Lighthouse Results</h1>
    <table>
      <thead>
      <tr>
        <?php foreach ($columns as $col => $label): ?>
          <th class="<?= $sort === $col ? 'sorted' : '' ?>">
            <a href="<?= sort_url($col, $sort, $dir) ?>">
              <span><?= $label ?></span>
              <?php if ($sort === $col): ?>
                <span><?= $dir === 'asc' ? '▲' : '▼' ?></span>
              <?php endif; ?>
            </a>
          </th>
        <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($results as $row): ?>
        <tr>
          <td><a href="<?= htmlspecialchars($row['url']) ?>" target="_blank"><?= htmlspecialchars($row['page']) ?></a></td>
          <?php if (empty($row['score'])): ?>
            <td colspan="4">
              <?php if (!empty($row['errorMessage'])): ?>
                <span style="color: red;">Error: <?= htmlspecialchars($row['errorMessage']) ?></span>
              <?php else: ?>
                Not audited
              <?php endif; ?>
            </td>
          <?php else: ?>
            <td><?= round($row['lcp']) ?></td>
            <td><?= round($row['fcp']) ?></td>
            <td><?= round($row['si']) ?></td>
            <td><?= round($row['tti']) ?></td>
            <td><?= round($row['score'] * 100) ?></td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>

</html>
