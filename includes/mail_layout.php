<?php
// One simple look for every email (inline styles, because mail apps ignore stylesheets).
$layoutUrl = $layoutUrl ?? null;
$layoutFooter = $layoutFooter ?? 'You are getting this email because of activity on your WatchStore account.';
$layoutButton = '';
if ($layoutUrl) {
    $layoutButton = '<p style="margin:24px 0 8px"><a href="' . htmlspecialchars($layoutUrl) . '" style="background:#000000;color:#ffffff;text-decoration:none;'
        . 'padding:12px 22px;border-radius:5px;font-weight:bold;display:inline-block">' . htmlspecialchars($layoutText) . '</a></p>';
}
$mailBody = '<!doctype html><html><body style="margin:0;background:#ffffff;font-family:Arial,Helvetica,sans-serif;color:#000000">'
    . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#ffffff;padding:24px 12px"><tr><td align="center">'
    . '<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#ffffff;border:1px solid #e5e5e5;border-radius:0">'
    . '<tr><td style="background:#000000;color:#ffffff;padding:18px 24px;font-size:22px;font-family:Georgia,serif;">WatchStore</td></tr>'
    . '<tr><td style="padding:24px;font-size:15px;line-height:1.55">'
    . '<h1 style="font-family:Georgia,serif;font-weight:normal;font-size:24px;margin:0 0 14px">' . htmlspecialchars($layoutHeading) . '</h1>'
    . $layoutInner . $layoutButton
    . '</td></tr>'
    . '<tr><td style="padding:14px 24px;font-size:12px;color:#6b6b6b;border-top:1px solid #e5e5e5">' . htmlspecialchars($layoutFooter) . '</td></tr>'
    . '</table></td></tr></table></body></html>';
$layoutUrl = null;
$layoutFooter = null;
