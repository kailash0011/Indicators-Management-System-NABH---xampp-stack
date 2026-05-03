<?php
/**
 * PHP built-in server router for NABH Indicators Management System.
 * Serves static files directly; routes all other requests to PHP scripts.
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $uri;

// Serve static files (CSS, JS, images, etc.)
if (is_file($file) && !preg_match('/\.php$/', $uri)) {
    return false;
}

// Route root to index.php
if ($uri === '/' || $uri === '') {
    require __DIR__ . '/index.php';
    return;
}

// Map the request to the correct PHP file
if (is_file($file) && preg_match('/\.php$/', $uri)) {
    require $file;
    return;
}

// Default: let PHP handle it
return false;
