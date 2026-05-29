<?php

/**
 * PHP built-in server router.
 * Serve existing files in public/ directly (CSS, JS, images).
 * Route everything else through Symfony (including /admin/stats.json).
 */
if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $file = __DIR__ . $path;

    if ($path !== '/' && is_file($file)) {
        return false;
    }
}

require __DIR__ . '/index.php';
