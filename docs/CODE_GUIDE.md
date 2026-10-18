# Code guide (class-sample style)

Every script starts the same way, exactly like the class sample:

```php
<?php
session_start();
include('../includes/config.php');        // the $conn database connection + shop settings
include('../includes/guard_admin.php');   // only on pages that need a login / admin / staff
```

then it reads `$_POST` / `$_GET`, runs `mysqli_execute_query($conn, $sql, [$params])`, sets a message and redirects:

```php
$_SESSION['success'] = 'Saved.';          // green alert   ($_SESSION['message'] = red, ['warning'] = yellow, ['info'] = blue)
header('Location: ' . BASE_URL . '/item/index.php');
exit();
```

`includes/alert.php` (included by `header.php`) prints the messages once and clears them.

## The include files (no functions: each one reads some variables and sets others)

| File | What it does | You set before it | You get after it |
|---|---|---|---|
| `config.php` | database connection, settings (currency, shipping fee), `BASE_URL`, signs out deactivated users | | `$conn` |
| `alert.php` | shows `$_SESSION['message' / 'warning' / 'info' / 'success']` | | |
| `guard_login.php` | guests are sent to the login page with a message | | |
| `guard_admin.php` / `guard_staff.php` | only admins (or admins + employees) may stay | | |
| `upload_one.php` | saves one image (JPG/PNG/WEBP, 3 MB) | `$upFile`, `$upFolder`, `$upPrefix` | `$upPath`, `$upError` |
| `upload_many.php` | saves the gallery photos | `$upFiles`, `$upFolder` | `$upPaths`, `$upError` |
| `item_validate.php` | PHP validation of the watch form | (reads `$_POST`) | `$d` (clean values), `$err` (messages) |
| `item_form.php` | the add/edit watch form | `$formAction`, `$item`, `$submitLabel` | |
| `badwords.php` | regex filter for foul words in reviews | `$badText` | `$badFound`, `$badMessage` |
| `cart_lines.php` | cart rows with prices, limited to stock | | `$lines` |
| `change_status.php` | moves an order to the next status, restocks on cancel, emails the customer | `$statusOrderId`, `$statusNew` | `$statusError` |
| `order_email.php` | email with products, subtotal, shipping, grand total + PDF receipt | `$emailOrderId`, `$emailType` | `$mailSent` |
| `receipt_pdf.php` | builds the PDF receipt (Dompdf) | `$pdfOrderId` | `$pdfBytes`, `$pdfName` |
| `mail_send.php` | sends one email through Mailtrap (PHPMailer) | `$mailTo`, `$mailSubject`, `$mailBody` | `$mailSent` |

## MySQL views (see `database.sql`)
`v_order_details` = one row per order line, `v_order_totals` = one row per order with subtotal and grand total.
The admin orders list, order details, receipt page, emails and PDF all read from them.

## Needs PHP 8.2 or newer
`mysqli_execute_query()` (used by the class sample too) was added in PHP 8.2. XAMPP 8.2+ has it.
