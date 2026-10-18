<?php
// Makes the PDF copy of a transaction receipt (Dompdf lives in includes/dompdf/, no Composer needed).
$pdfBytes = null;
$dataOrderId = $pdfOrderId;
include(ROOT_DIR . '/includes/order_data.php');
$pdfReceipt = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT * FROM receipt WHERE orderinfo_id = ?', [$pdfOrderId]));
$pdfName = $pdfReceipt ? 'Receipt-R-' . str_pad((string)(int)$pdfReceipt['receipt_id'], 6, '0', STR_PAD_LEFT) . '.pdf' : 'Receipt-Order-' . $pdfOrderId . '.pdf';

if ($ord) {
    try {
        $pdfNo = $pdfReceipt ? 'R-' . str_pad((string)(int)$pdfReceipt['receipt_id'], 6, '0', STR_PAD_LEFT) : 'Order #' . (int)$ord['orderinfo_id'];
        $pdfRows = '';
        foreach ($ord['lines'] as $pdfL) {
            $pdfRows .= '<tr><td>' . htmlspecialchars($pdfL['brand'] . ' ' . $pdfL['model']) . '</td><td class="r">' . CURRENCY . number_format($pdfL['unit_price'], 2) . '</td>'
                . '<td class="r">' . (int)$pdfL['quantity'] . '</td><td class="r">' . CURRENCY . number_format($pdfL['unit_price'] * $pdfL['quantity'], 2) . '</td></tr>';
        }
        $pdfNote = $ord['status'] === 'cancelled' ? 'This order was cancelled. Nothing was charged.'
            : ($ord['status'] === 'delivered' ? 'Payment received on delivery. Thank you for your purchase!' : 'Payment is collected on delivery.');

        $pdfHtml = '<!doctype html><html><head><meta charset="utf-8"><style>
          body{font-family:"DejaVu Sans",sans-serif;font-size:11px;color:#111}
          h1{font-size:22px;margin:0} .muted{color:#666}
          .head{border-bottom:2px solid #000;padding-bottom:8px;margin-bottom:14px}
          table{width:100%;border-collapse:collapse} th{background:#000;color:#fff;text-align:left;padding:6px}
          td{padding:6px;border-bottom:1px solid #ddd} .r{text-align:right}
          .tot td{border:0;padding:3px 6px} .grand td{font-weight:bold;font-size:13px;border-top:2px solid #000}
          .stamp{border:2px solid #111;padding:3px 10px;font-weight:bold;float:right}
        </style></head><body>
          <div class="head"><span class="stamp">' . htmlspecialchars(strtoupper($ord['status'])) . '</span><h1>WatchStore</h1><div class="muted">Transaction receipt</div></div>
          <p><strong>' . htmlspecialchars($pdfNo) . '</strong><br>Order #' . (int)$ord['orderinfo_id'] . ' &middot; placed ' . htmlspecialchars(date('M j, Y g:i A', strtotime($ord['date_placed']))) . '</p>
          <p><strong>Customer</strong><br>' . htmlspecialchars(trim(($ord['title'] ?? '') . ' ' . $ord['fname'] . ' ' . $ord['lname'])) . '<br>'
            . htmlspecialchars($ord['addressline']) . ', ' . htmlspecialchars($ord['town']) . ' ' . htmlspecialchars($ord['zipcode']) . '<br>' . htmlspecialchars($ord['phone']) . ' &middot; ' . htmlspecialchars($ord['email']) . '</p>
          <table><thead><tr><th>Watch</th><th class="r">Price</th><th class="r">Qty</th><th class="r">Amount</th></tr></thead><tbody>' . $pdfRows . '</tbody></table>
          <table class="tot" style="margin-top:8px">
            <tr><td class="r">Subtotal</td><td class="r" style="width:110px">' . CURRENCY . number_format($ord['subtotal'], 2) . '</td></tr>
            <tr><td class="r">Shipping</td><td class="r">' . CURRENCY . number_format($ord['shipping'], 2) . '</td></tr>
            <tr class="grand"><td class="r">Grand total</td><td class="r">' . CURRENCY . number_format($ord['subtotal'] + $ord['shipping'], 2) . '</td></tr>
          </table>
          <p class="muted" style="margin-top:18px">' . htmlspecialchars($pdfNote) . '</p>
        </body></html>';

        include_once(ROOT_DIR . '/includes/dompdf/autoload.inc.php');
        $pdfOptions = new \Dompdf\Options();
        $pdfOptions->set('defaultFont', 'DejaVu Sans');
        $pdfOptions->set('isRemoteEnabled', false);
        $pdf = new \Dompdf\Dompdf($pdfOptions);
        $pdf->loadHtml($pdfHtml, 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();
        $pdfBytes = $pdf->output();
    } catch (Throwable $pdfError) {
        @file_put_contents(ROOT_DIR . '/logs/mail-error.log', date('c') . " PDF for order #$pdfOrderId: " . $pdfError->getMessage() . "\n", FILE_APPEND | LOCK_EX);
        $pdfBytes = null;
    }
}
