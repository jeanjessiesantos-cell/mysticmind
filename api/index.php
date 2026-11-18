<?php
/**
 * Vercel entry point.
 * Vercel runs PHP only inside the api/ folder, so every page request
 * (e.g. /mysticmind/login.php) is sent here, and this file loads the real page.
 * Not used on XAMPP.
 */
$root = realpath(__DIR__ . '/..');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = rawurldecode($path);
$path = preg_replace('#^/mysticmind#', '', $path);
if ($path === '' || $path === '/') $path = '/index.php';
if (substr($path, -1) === '/') $path .= 'index.php';

$file = realpath($root . $path);
$ok = $file !== false
    && strpos($file, $root . DIRECTORY_SEPARATOR) === 0
    && pathinfo($file, PATHINFO_EXTENSION) === 'php';

// Never run private files directly
foreach (['includes', 'tools', 'api'] as $private) {
    if ($ok && strpos($file, $root . DIRECTORY_SEPARATOR . $private . DIRECTORY_SEPARATOR) === 0) $ok = false;
}

if (!$ok) {
    http_response_code(404);
    echo 'Page not found.';
    exit;
}

$_SERVER['SCRIPT_FILENAME'] = $file;
$_SERVER['SCRIPT_NAME']     = '/mysticmind' . substr($file, strlen($root));
$_SERVER['PHP_SELF']        = $_SERVER['SCRIPT_NAME'];
chdir(dirname($file));
require $file;
