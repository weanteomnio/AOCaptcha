<?php

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (preg_match('#^/dist/#', $path)) {
    $file = __DIR__ . '/..' . $path;
    if (is_file($file)) {
        $types = ['js' => 'application/javascript', 'css' => 'text/css'];
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
        readfile($file);
        return true;
    }
}

return false;
