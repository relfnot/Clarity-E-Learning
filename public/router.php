<?php

declare(strict_types=1);

$uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$path = parse_url($uri, PHP_URL_PATH);
$path = is_string($path) ? rawurldecode($path) : '/';

if (preg_match('#^/(?:Clarity_E-Learning/(?:public/)?)?api/v1(?:/.*)?$#i', $path) === 1) {
    require __DIR__ . '/api/index.php';
    return true;
}

if (preg_match('#^/(pages/)?(index|login|dashboard|dashboard-dosen|courses|course-detail|assignment|attendance|course-manage|material-upload|submissions|attendance-manage|403|404)\.html$#', $path, $matches) === 1) {
    $targetPhp = __DIR__ . '/' . ($matches[1] ?? '') . $matches[2] . '.php';
    if (is_file($targetPhp)) {
        require $targetPhp;
        return true;
    }
}

$fullPath = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $path);
if ($path !== '/' && is_file($fullPath)) {
    return false;
}

if ($path === '/' || $path === '') {
    require __DIR__ . '/index.php';
    return true;
}

http_response_code(404);
require __DIR__ . '/404.php';
return true;
