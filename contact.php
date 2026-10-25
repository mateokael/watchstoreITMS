<?php
session_start();
include('includes/config.php');

if (empty($_SESSION['contact_csrf'])) {
    $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = (string)($_POST['csrf_token'] ?? '');
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));

    if (!hash_equals((string)$_SESSION['contact_csrf'], $token)) {
        $_SESSION['warning'] = 'Your session expired. Please try sending your message again.';
    } elseif ($name === '' || strlen($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 || $message === '' || strlen($message) > 5000) {
        $_SESSION['warning'] = 'Please provide a valid name and email, and a message no longer than 5,000 characters.';
        $_SESSION['contact_old'] = ['name' => $name, 'email' => $email, 'message' => $message];
    } else {
        $badText = $message;
        include('includes/badwords.php');
        if ($badFound) {
            $_SESSION['warning'] = 'Your message was not sent because it contains language that is not allowed. Please reword it respectfully and try again.';
            $_SESSION['contact_old'] = ['name' => $name, 'email' => $email, 'message' => $message];
        } else {
            $mailTo = 'lanstro@store.ph';
            $mailSubject = 'Website contact form message';
            $mailReplyTo = $email;
            $mailAttachments = [];
            $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
            $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
            $layoutHeading = 'Website contact form message';
            $layoutInner = '<p style="margin:0 0 20px;color:#555555">A visitor sent a message through the WatchStore contact form. Reply to this email to respond directly.</p>'
                . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 20px">'
                . '<tr><td style="width:90px;padding:10px 12px;border-top:1px solid #e5e5e5;color:#6b6b6b;font-size:13px;font-weight:bold;text-transform:uppercase">Name</td>'
                . '<td style="padding:10px 12px;border-top:1px solid #e5e5e5;font-size:15px">' . $safeName . '</td></tr>'
                . '<tr><td style="width:90px;padding:10px 12px;border-top:1px solid #e5e5e5;color:#6b6b6b;font-size:13px;font-weight:bold;text-transform:uppercase">Email</td>'
                . '<td style="padding:10px 12px;border-top:1px solid #e5e5e5;font-size:15px"><a href="mailto:' . $safeEmail . '" style="color:#000000;text-decoration:underline">' . $safeEmail . '</a></td></tr></table>'
                . '<p style="margin:0 0 8px;color:#6b6b6b;font-size:13px;font-weight:bold;letter-spacing:1px;text-transform:uppercase">Message</p>'
                . '<div style="padding:16px 18px;border-left:3px solid #b49a68;background:#f5f5f5;font-size:15px;line-height:1.6;overflow-wrap:anywhere">' . $safeMessage . '</div>';
            $layoutUrl = null;
            $layoutFooter = 'WatchStore website contact form • Reply to this email to contact the sender.';
            include(ROOT_DIR . '/includes/mail_layout.php');
            include(ROOT_DIR . '/includes/mail_send.php');
            $layoutFooter = null;

            if ($mailSent && MAIL_MODE === 'smtp') {
                $_SESSION['success'] = 'Thanks for your message. We’ll get back to you soon.';
                unset($_SESSION['contact_old']);
            } elseif ($mailSent) {
                $_SESSION['info'] = 'Your message was recorded locally, but email delivery is not configured. Please email us directly at lanstro@store.ph.';
                unset($_SESSION['contact_old']);
            } else {
                $_SESSION['warning'] = 'We could not send your message right now. Please email us directly at lanstro@store.ph.';
                $_SESSION['contact_old'] = ['name' => $name, 'email' => $email, 'message' => $message];
            }
        }
    }

    unset($_SESSION['contact_csrf']);
    header('Location: ' . BASE_URL . '/contact.php');
    exit();
}

$contactOld = $_SESSION['contact_old'] ?? [];
unset($_SESSION['contact_old']);
$pageTitle = 'Contact Us';
include('includes/header.php');
?>
<section class="info-page contact-page">
  <div class="contact-intro">
    <p class="contact-kicker">We’re here to help</p>
    <h1>Contact Us</h1>
    <p class="lead">Have a question about a watch, your order, or the store? Send us a message and we’ll be happy to help.</p>
    <a class="contact-email" href="mailto:lanstro@store.ph">
      <i class="fa-regular fa-envelope" aria-hidden="true"></i>
      <span><strong>Email us</strong><br>lanstro@store.ph</span>
      <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
    </a>
  </div>
  <div class="contact-form-wrap">
    <p class="contact-kicker">Get in touch</p>
    <h2>Send us a message</h2>
    <form method="post" action="<?= BASE_URL ?>/contact.php" class="contact-form">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$_SESSION['contact_csrf'], ENT_QUOTES, 'UTF-8') ?>">
      <div class="mb-3">
        <label class="form-label" for="contact-name">Your name</label>
        <input class="form-control" type="text" id="contact-name" name="name" maxlength="100" autocomplete="name" required value="<?= htmlspecialchars((string)($contactOld['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label" for="contact-email">Your email</label>
        <input class="form-control" type="email" id="contact-email" name="email" maxlength="254" autocomplete="email" required value="<?= htmlspecialchars((string)($contactOld['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label" for="contact-message">Message</label>
        <textarea class="form-control" id="contact-message" name="message" rows="7" maxlength="5000" required><?= htmlspecialchars((string)($contactOld['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
      </div>
      <button class="btn btn-primary btn-lg" type="submit">Send message</button>
    </form>
  </div>
</section>
<?php include('includes/footer.php'); ?>
