<?php
// Saves ONE uploaded image into images/<folder>/.
$upPath = null;
$upError = null;
if (($upFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
} elseif ($upFile['error'] !== UPLOAD_ERR_OK) {
    $upError = 'Image upload failed (error code ' . (int)$upFile['error'] . ').';
} elseif ($upFile['size'] > 3 * 1024 * 1024) {
    $upError = 'Each image must be 3 MB or smaller.';
} else {
    $upFinfo = finfo_open(FILEINFO_MIME_TYPE);
    $upMime = finfo_file($upFinfo, $upFile['tmp_name']);
    $upExt = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$upMime] ?? null;
    if (!$upExt) {
        $upError = 'Only JPG, PNG or WEBP images are allowed.';
    } else {
        $upDir = ROOT_DIR . '/images/' . $upFolder . '/';
        if (!is_dir($upDir)) {
            mkdir($upDir, 0775, true);
        }
        $upName = $upPrefix . bin2hex(random_bytes(8)) . '.' . $upExt;
        if (move_uploaded_file($upFile['tmp_name'], $upDir . $upName)) {
            $upPath = 'images/' . $upFolder . '/' . $upName;
        } else {
            $upError = 'Could not save the image. Is the images folder writable?';
        }
    }
}
