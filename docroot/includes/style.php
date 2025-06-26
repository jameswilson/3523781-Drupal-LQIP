<?php
$file = '/styles/main.css';
$timestamp = file_exists(".." . $file) ? filemtime(".." . $file) : time();
echo '<link rel="stylesheet" href="' . $file . '?v=' . $timestamp . '">';
