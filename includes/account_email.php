<?php
// Account emails: welcome, password reset link, password changed.
$hi = '<p>Hi ' . htmlspecialchars($accountName) . ',</p>';
if ($accountEmailType === 'welcome') {
    $mailSubject = 'Welcome to WatchStore';
    $layoutInner = $hi . '<p>Your account is ready. You can browse the shop, save watches to a wishlist and track every order from your account.</p>';
    $layoutUrl = APP_URL . '/shop.php';
    $layoutText = 'Start shopping';
} elseif ($accountEmailType === 'reset') {
    $mailSubject = 'Reset your WatchStore password';
    $layoutInner = $hi . '<p>We got a request to reset your password. The button below works for <strong>1 hour</strong> and can be used once.</p>'
        . '<p style="font-size:13px;color:#6b6b6b">If the button does not work, copy this address into your browser:<br>' . htmlspecialchars($accountLink) . '</p>'
        . '<p>If you did not ask for this, you can ignore this email. Your password stays the same.</p>';
    $layoutUrl = $accountLink;
    $layoutText = 'Choose a new password';
} else {
    $mailSubject = 'Your WatchStore password was changed';
    $layoutInner = $hi . '<p>The password on your WatchStore account was just changed.</p>'
        . '<p>If this was not you, reset it right away and check your email account too.</p>';
    $layoutUrl = APP_URL . '/user/forgot_password.php';
    $layoutText = 'Reset password';
}
$layoutHeading = $mailSubject;
include(ROOT_DIR . '/includes/mail_layout.php');
$mailTo = $accountEmail;
$mailAttachments = [];
include(ROOT_DIR . '/includes/mail_send.php');
