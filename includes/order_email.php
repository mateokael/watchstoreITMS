<?php
// Emails the customer about an order: the products, SUBTOTAL, shipping and GRAND TOTAL, plus the PDF receipt.
$mailSent = false;
try {
    $dataOrderId = $emailOrderId;
    include(ROOT_DIR . '/includes/order_data.php');
    if ($ord) {
        $emTable = '';
        foreach ($ord['lines'] as $emL) {
            $emTable .= '<tr><td style="padding:6px 0;border-bottom:1px solid #e5e5e5">' . htmlspecialchars($emL['brand'] . ' ' . $emL['model']) . ' &times; ' . (int)$emL['quantity'] . '</td>'
                . '<td align="right" style="padding:6px 0;border-bottom:1px solid #e5e5e5">' . CURRENCY . number_format($emL['unit_price'] * $emL['quantity'], 2) . '</td></tr>';
        }
        $emTable .= '<tr><td style="padding:6px 0">Subtotal</td><td align="right" style="padding:6px 0">' . CURRENCY . number_format($ord['subtotal'], 2) . '</td></tr>'
            . '<tr><td style="padding:6px 0">Shipping</td><td align="right" style="padding:6px 0">' . CURRENCY . number_format($ord['shipping'], 2) . '</td></tr>'
            . '<tr><td style="padding:6px 0;font-weight:bold">Grand total</td><td align="right" style="padding:6px 0;font-weight:bold">' . CURRENCY . number_format($ord['subtotal'] + $ord['shipping'], 2) . '</td></tr>';
        $emTable = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;margin:12px 0">' . $emTable . '</table>';

        $emAddress = '<p style="margin:0 0 4px"><strong>Deliver to</strong></p><p style="margin:0">'
            . htmlspecialchars(trim(($ord['title'] ?? '') . ' ' . $ord['fname'] . ' ' . $ord['lname'])) . '<br>'
            . htmlspecialchars($ord['addressline']) . ', ' . htmlspecialchars($ord['town']) . ' ' . htmlspecialchars($ord['zipcode']) . '<br>' . htmlspecialchars($ord['phone']) . '</p>';

        $emLink = APP_URL . '/user/order_view.php?id=' . $emailOrderId;
        $emHi = '<p>Hi ' . htmlspecialchars($ord['fname']) . ',</p>';
        $emPdf = true;
        $emSubject = '';
        if ($emailType === 'placed') {
            $emSubject = "Order #$emailOrderId confirmed";
            $emInner = $emHi . '<p>Thank you! We received your order <strong>#' . $emailOrderId . '</strong> and will get it ready.</p>' . $emTable . $emAddress
                . '<p style="margin-top:14px">Payment is collected on delivery. We will email you again when it ships.</p>';
            $emButton = 'View your order';
            $emPdf = false;
        } elseif ($emailType === 'shipped') {
            $emSubject = "Order #$emailOrderId is on its way";
            $emInner = $emHi . '<p>Good news: your order <strong>#' . $emailOrderId . '</strong> has shipped. Please keep your phone nearby and have the payment ready, because it is collected on delivery.</p>' . $emTable . $emAddress;
            $emButton = 'Track your order';
        } elseif ($emailType === 'delivered') {
            $emSubject = "Order #$emailOrderId was delivered";
            $emInner = $emHi . '<p>Your order <strong>#' . $emailOrderId . '</strong> was marked as delivered. We hope you enjoy your watch.</p>'
                . '<p>If something is wrong with it, reply to this email or contact the store. Once you have worn it for a bit, a short review helps other buyers a lot.</p>' . $emTable;
            $emButton = 'Review your watch';
        } elseif ($emailType === 'cancelled') {
            $emSubject = "Order #$emailOrderId was cancelled";
            $emInner = $emHi . '<p>Your order <strong>#' . $emailOrderId . '</strong> was cancelled. Nothing was charged, and the watches went back into stock.</p>' . $emTable;
            $emButton = 'View order';
        } elseif ($emailType === 'receipt') {
            $emReceipt = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT * FROM receipt WHERE orderinfo_id = ?', [$emailOrderId]));
            if ($emReceipt) {
                $emNo = 'R-' . str_pad((string)(int)$emReceipt['receipt_id'], 6, '0', STR_PAD_LEFT);
                $emSubject = "Your receipt $emNo";
                $emLink = APP_URL . '/user/receipt.php?id=' . $emailOrderId;
                $emInner = $emHi . '<p>Thank you for shopping at WatchStore. Here is your receipt <strong>' . htmlspecialchars($emNo) . '</strong> for order <strong>#' . $emailOrderId . '</strong>.</p>' . $emTable
                    . '<p style="margin-top:14px">Status: <strong>completed</strong>. Payment received on ' . htmlspecialchars(date('M j, Y', strtotime($ord['date_delivered'] ?: $ord['date_placed']))) . '. You can open or print this receipt any time from your account.</p>';
                $emButton = 'View receipt';
            }
        }

        if ($emSubject !== '') {
            if ($emPdf) {
                $emInner .= '<p style="font-size:13px;color:#6b6b6b">Your receipt is attached as a PDF.</p>';
            }
            $layoutHeading = $emSubject;
            $layoutInner = $emInner;
            $layoutUrl = $emLink;
            $layoutText = $emButton;
            include(ROOT_DIR . '/includes/mail_layout.php');

            $mailTo = $ord['email'];
            $mailSubject = $emSubject;
            $mailAttachments = [];
            if ($emPdf) {
                $pdfOrderId = $emailOrderId;
                include(ROOT_DIR . '/includes/receipt_pdf.php');
                if ($pdfBytes !== null) {
                    $mailAttachments = [['name' => $pdfName, 'data' => $pdfBytes]];
                }
            }
            include(ROOT_DIR . '/includes/mail_send.php');

            if ($emailType === 'placed' && ADMIN_EMAIL !== '') {
                $layoutHeading = "New order #$emailOrderId";
                $layoutInner = '<p>New order <strong>#' . $emailOrderId . '</strong> from ' . htmlspecialchars($ord['fname'] . ' ' . $ord['lname']) . ' (' . htmlspecialchars($ord['email']) . ').</p>' . $emTable;
                $layoutUrl = APP_URL . '/admin/orderDetails.php?id=' . $emailOrderId;
                $layoutText = 'Open in admin';
                include(ROOT_DIR . '/includes/mail_layout.php');
                $emSent = $mailSent;
                $mailTo = ADMIN_EMAIL;
                $mailSubject = "New order #$emailOrderId";
                include(ROOT_DIR . '/includes/mail_send.php');
                $mailSent = $emSent;
            }
        }
    }
} catch (Throwable $emailError) {
    @file_put_contents(ROOT_DIR . '/logs/mail-error.log', date('c') . " order #$emailOrderId: " . $emailError->getMessage() . "\n", FILE_APPEND | LOCK_EX);
    $mailSent = false;
}
