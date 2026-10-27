<?php

// 'smtp' = send through Mailtrap.    
define('MAIL_MODE', 'smtp');

define('MAIL_HOST', 'sandbox.smtp.mailtrap.io');   // older inboxes: smtp.mailtrap.io
define('MAIL_PORT', 2525);                          // 2525, 587 or 25 all work with 'tls'
define('MAIL_SECURE', 'tls');                       // 'tls' (STARTTLS), 'ssl' (port 465) or '' (none)
define('MAIL_USER', 'b53f49eb490299');
define('MAIL_PASS', '0509388e4b7607');
define('MAIL_FROM', 'orders@watchstore.test');      // any address works, Mailtrap never delivers it
define('MAIL_FROM_NAME', 'WatchStore');

// get an email every time someone places an order ('' = off)
define('ADMIN_EMAIL', '');



