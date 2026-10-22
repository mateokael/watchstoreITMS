<?php
// For one or more <input type="file" name="gallery[]"> fields.
$upPaths = [];
$upError = null;
$upPrefix = 'watch_';
if (!empty($upFiles['name']) && is_array($upFiles['name'])) {
    foreach ($upFiles['name'] as $upI => $upN) {
        $upFile = ['name' => $upN, 'type' => $upFiles['type'][$upI], 'tmp_name' => $upFiles['tmp_name'][$upI],
                   'error' => $upFiles['error'][$upI], 'size' => $upFiles['size'][$upI]];
        include(ROOT_DIR . '/includes/upload_one.php');
        if ($upError) {
            foreach ($upPaths as $upDone) {
                if (is_file(ROOT_DIR . '/' . $upDone)) {
                    unlink(ROOT_DIR . '/' . $upDone);
                }
            }
            $upPaths = [];
            break;
        }
        if ($upPath) {
            $upPaths[] = $upPath;
        }
    }
}
