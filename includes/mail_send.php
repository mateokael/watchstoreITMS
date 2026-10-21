<?php
// Sends ONE email (PHPMailer, bundled in includes/PHPMailer/). Set before including:
use PHPMailer\PHPMailer\PHPMailer;

include_once(ROOT_DIR . '/includes/PHPMailer/Exception.php');
include_once(ROOT_DIR . '/includes/PHPMailer/PHPMailer.php');
include_once(ROOT_DIR . '/includes/PHPMailer/SMTP.php');

$mailAttachments = $mailAttachments ?? [];
$mailReplyTo = $mailReplyTo ?? '';
$mailSent = false;
$mailPlain = trim(html_entity_decode(strip_tags(preg_replace('~<(br|/p|/tr|/h1|/table)[^>]*>~i', "\n", $mailBody)), ENT_QUOTES, 'UTF-8'));
$mailPlain = preg_replace("/[ \t]+/", ' ', preg_replace("/\n\s*\n+/", "\n\n", $mailPlain));
if (!is_dir(ROOT_DIR . '/logs')) {
    @mkdir(ROOT_DIR . '/logs', 0775, true);
}

if (MAIL_MODE !== 'smtp') {
    $mailAttachNames = '';
    foreach ($mailAttachments as $mailA) {
        $mailAttachNames .= ($mailAttachNames ? ', ' : '') . $mailA['name'] . ' (' . strlen($mailA['data']) . ' bytes)';
    }
    $mailLogText = '=== ' . date('Y-m-d H:i:s') . "\nTo: $mailTo\nSubject: $mailSubject\n"
        . ($mailReplyTo !== '' ? "Reply-To: $mailReplyTo\n" : '')
        . ($mailAttachNames ? "Attachments: $mailAttachNames\n" : '') . "\n$mailPlain\n\n";
    @file_put_contents(ROOT_DIR . '/logs/mail.log', $mailLogText, FILE_APPEND | LOCK_EX);
    $mailSent = true;
} else {
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->Port = MAIL_PORT;
        $mail->SMTPAuth = MAIL_USER !== '';
        $mail->Username = MAIL_USER;
        $mail->Password = MAIL_PASS;
        if (MAIL_SECURE === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif (MAIL_SECURE === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        }
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($mailTo);
        if ($mailReplyTo !== '') {
            $mail->addReplyTo($mailReplyTo);
        }
        $mail->isHTML(true);
        $mail->Subject = $mailSubject;
        $mail->Body = $mailBody;
        $mail->AltBody = $mailPlain;
        foreach ($mailAttachments as $mailA) {
            $mail->addStringAttachment($mailA['data'], $mailA['name'], PHPMailer::ENCODING_BASE64, 'application/pdf');
        }
        $mail->send();
        $mailSent = true;
    } catch (Throwable $mailError) {
        @file_put_contents(ROOT_DIR . '/logs/mail-error.log', '=== ' . date('Y-m-d H:i:s') . " to $mailTo ($mailSubject)\n" . $mailError->getMessage() . "\n\n", FILE_APPEND | LOCK_EX);
    }
}
$mailAttachments = [];
