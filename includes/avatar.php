<?php
// Round profile picture, or a circle with the person's initial when there is no picture yet.
$avatarSize = (int)$avatarSize ?: 40;
$avatarStyle = 'width:' . $avatarSize . 'px;height:' . $avatarSize . 'px;';
if ($avatarPath && !str_contains($avatarPath, '..') && is_file(ROOT_DIR . '/' . $avatarPath)) {
    echo '<img class="avatar ' . htmlspecialchars($avatarClass) . '" style="' . $avatarStyle . '" src="' . htmlspecialchars(BASE_URL . '/' . $avatarPath) . '" alt="' . htmlspecialchars($avatarName) . '">';
} else {
    $avatarInitial = strtoupper(mb_substr(trim($avatarName) !== '' ? trim($avatarName) : '?', 0, 1));
    echo '<span class="avatar avatar-initial ' . htmlspecialchars($avatarClass) . '" style="' . $avatarStyle . 'font-size:' . round($avatarSize * 0.42) . 'px" aria-label="' . htmlspecialchars($avatarName) . '">' . htmlspecialchars($avatarInitial) . '</span>';
}
