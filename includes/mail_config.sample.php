<?php
// Copy to mail_config.php and replace the placeholders with your Mailtrap settings.
define('MAIL_MODE', 'log'); // Change to 'smtp' after configuring the credentials.
define('MAIL_HOST', 'sandbox.smtp.mailtrap.io');
define('MAIL_PORT', 2525);
define('MAIL_SECURE', 'tls');
define('MAIL_USER', 'your-mailtrap-username');
define('MAIL_PASS', 'your-mailtrap-password');
define('MAIL_FROM', 'watchstore@example.com');
define('MAIL_FROM_NAME', 'WatchStore');
define('ADMIN_EMAIL', 'admin@example.com');
