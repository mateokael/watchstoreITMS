<?php
// Copy to mail_config.php and set MAILTRAP_USER / MAILTRAP_PASS in Apache's environment.
$mailtrapUser = getenv('MAILTRAP_USER') ?: '';
$mailtrapPass = getenv('MAILTRAP_PASS') ?: '';

define('MAIL_MODE', ($mailtrapUser !== '' && $mailtrapPass !== '') ? 'smtp' : 'log');
define('MAIL_HOST', 'sandbox.smtp.mailtrap.io');
define('MAIL_PORT', 2525);
define('MAIL_SECURE', 'tls');
define('MAIL_USER', $mailtrapUser);
define('MAIL_PASS', $mailtrapPass);
define('MAIL_FROM', 'orders@watchstore.test');
define('MAIL_FROM_NAME', 'WatchStore');
define('ADMIN_EMAIL', '');
