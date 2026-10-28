<?php
function product_image_url(?string $path): string
{
    $fallback = BASE_URL . '/assets/placeholder.svg';
    $path = trim((string)$path);
    if ($path === '' || str_contains($path, "\0")) {
        return $fallback;
    }

    $normalized = str_replace('\\', '/', $path);
    if (preg_match('~(?:^|/)images/products/([^/]+)$~i', $normalized, $matches)) {
        $relative = 'images/products/' . $matches[1];
    } else {
        $relative = 'images/products/' . basename($normalized);
    }
    if (!preg_match('~^images/products/(?!\.{1,2}$)[A-Za-z0-9._-]+$~i', $relative)) {
        return $fallback;
    }

    $root = realpath(ROOT_DIR);
    $file = realpath(ROOT_DIR . '/' . $relative);
    if ($root === false || $file === false) {
        return $fallback;
    }

    $rootPath = rtrim(str_replace('\\', '/', $root), '/') . '/';
    $filePath = str_replace('\\', '/', $file);
    if (stripos($filePath, $rootPath) !== 0 || !is_file($file)) {
        return $fallback;
    }

    $urlPath = implode('/', array_map('rawurlencode', explode('/', $relative)));
    return BASE_URL . '/' . $urlPath;
}
