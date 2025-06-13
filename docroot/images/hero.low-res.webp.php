<?php
$delay = isset($_GET['delay']) ? intval($_GET['delay']) : 0;
if ($delay > 0) {
    usleep($delay * 1000); // Convert ms to µs
}
$image_path = __DIR__ . '/hero.low-res.webp';
if (!file_exists($image_path)) {
    http_response_code(404);
    echo 'Image not found.';
    exit;
}
header('Content-Type: image/webp');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
readfile($image_path);
