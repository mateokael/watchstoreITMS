# WatchStore

Plain PHP + MySQLi + Bootstrap 5 (includes, one page per job, session cart).

## Setup (XAMPP)
1. Put the project folder in `htdocs`. **Any folder name works**: the base URL is detected automatically (see "Images and GitHub").
2. Start Apache and MySQL, open phpMyAdmin, **Import** `database.sql` (it creates `db_watchstoreproj`).
   - Already have the old database with data? Run `migration.sql` instead (once). It adds usernames, profile pictures and the password-reset table without deleting anything.
3. Open `http://localhost/<your-folder>/`
4. Admin login: `admin@watchstore.test` / `admin123`. Change it from **My profile**.
5. Settings (currency, shipping fee, DB login) are at the top of `includes/config.php`.

## ITIM211 rubric coverage

| Requirement | Implementation |
|---|---|
| MP1 watch product CRUD, single and multiple photos | Admin watch forms, validated uploads and product gallery: `item/`, `includes/upload_one.php`, `includes/upload_many.php`, `product.php` |
| MP2 user CRUD, registration/profile photos, deactivation and role changes | `user/register.php`, `user/profile.php`, `admin/users.php`, `admin/user_status.php`, `admin/user_role.php` |
| MP3 email login, logout and role-based CRUD restrictions | `user/login.php`, `user/logout.php`, `includes/guard_login.php`, `includes/guard_admin.php`; guests receive a login redirect/message |
| MP4 verified-purchase reviews, detail-page display/update and foul-word filtering | Delivered customers can create or update one review per product; `product.php`, `user/review_store.php`, `includes/badwords.php` |
| MP5 PHP-only login and product-form validation | `user/login.php` and shared `includes/item_validate.php`; the forms disable browser validation |
| Term test: completed orders, admin status updates, customer email and PDF receipt | `checkout.php`, `admin/orders.php`, `admin/updateOrder.php`, `includes/order_email.php`, `includes/receipt_pdf.php`; SQL uses `mysqli_execute_query()` |
| Unit 1: normalized schema and order-detail views | Tables and relationships in `database.sql`; `v_order_details` and `v_order_totals` power order details and totals |
| Unit 2: execution and interface | XAMPP setup and guided flows in this README; responsive storefront and admin pages use Bootstrap |
| Quiz 3: homepage search | Search from `index.php` submits to the shop's prepared, parameterized product search in `shop.php` |

## Version 3: employees, expenses, reports (class requirements)
**Already have the database imported?** Run `migration.sql` once (the new part is at the bottom, "Version 3"). New setup: import `database.sql`, then optionally `sample_data.sql` for demo orders, expenses and accounts (password for all demo accounts: `admin123`).

| Requirement | Where (log in as admin) |
|---|---|
| MP1 Product CRUD | `item/index.php`, `create.php`, `edit.php`, `archive.php` (delete = archive, so old orders stay valid) |
| MP2 Customer / user CRUD | `admin/users.php`, `user_edit.php`, `user_save.php`, `user_status.php`, `user_delete.php` |
| MP3 Expenses CRUD | `admin/expenses.php`, `expense_edit.php`, `expense_save.php`, `expense_delete.php` |
| MP4 Five reports (INNER JOIN + SUM/COUNT/AVG/MAX/MIN, with charts, printable): Customer Report, Receipt Records, Sales Report, Monthly Income, Expense Report | `admin/reports.php` (one page: pick From / To, click a report) |
| MP5 Total income + date range picker | `admin/income.php` |
| Transaction CRUD + receipt | Create: `checkout.php`. Read: `admin/orders.php`, `user/myorders.php`. Update: status in `admin/orderDetails.php`. Delete = cancel (stock returns). In-store sale: `admin/sale_new.php`. Receipt: `user/receipt.php` (issued when the order is completed) |
| Roles | admin (everything), employee (restock + confirm orders only), customer (shop) |

**Employees:** the admin creates them in *Customers & employees > Add* (type: Employee). They log in with the normal login page and land on the orders list. They can mark orders shipped/delivered and update stock. They cannot cancel orders, edit watches or prices, or open reports, income, expenses or user management (those pages call `require_admin()`).

**Receipts (email or in person):** at checkout the customer chooses *email me* or *printed copy from the store*. When an order is marked delivered, the system makes a receipt (R-000123). If the customer chose email it is emailed automatically. On the order page (`admin/orderDetails.php`) any employee or admin can **Email / Resend** the receipt or **Print for customer**, which opens the printable receipt and records that it was printed. For a customer standing at the counter, use **New in-store sale** (`admin/sale_new.php`): pick the customer and watches, choose print or email, and the sale is completed with the receipt. Email works in `log` mode (see `logs/mail.log`) or real SMTP, see "Email setup". Admin sees every receipt in *Reports > Receipt Records*.

**How income is counted:** only *delivered* orders. Total income = watches sold + shipping fees collected. Net income = total income - cost of the watches sold - expenses in the same range. The cost of each watch is saved on the order line at checkout (`orderline.unit_cost`), so profit stays correct even if you change a cost price later.

Charts use Chart.js from a CDN (like Bootstrap), so the computer needs internet when you present. "Print / Save as PDF" uses the browser's print dialog.

## What's new in this version
| Feature | Where |
|---|---|
| **Home page** with log in / create account buttons for guests, welcome message when logged in, new arrivals | `index.php` |
| Shop with filters (the old home page) | `shop.php` |
| **Profile like Facebook**: picture on the left, credentials on the right (username, email, delivery details, password) | `user/profile.php` |
| **Unique usernames** (case-insensitive: `Sam` and `sam` are the same name). Log in with email | `user/register.php`, `user/store.php`, `user/login.php` |
| **Item archive**: Archive takes a watch out of the shop, Restore brings it back. Nothing is deleted, old orders keep working | `item/index.php`, `item/archive.php` |
| **Emails**: welcome, order confirmation, shipped, delivered, cancelled, forgot password, password changed, optional new-order alert to the admin | `includes/order_email.php`, `includes/account_email.php`, `includes/mail_send.php` |
| **Forgot password** (link valid 1 hour, one use, only a hash is stored) | `user/forgot_password.php`, `user/reset_password.php` |
| **`images/` folder** for every uploaded picture | see below |

## Email setup
Out of the box email is in **log mode**: nothing is sent, each email is written to `logs/mail.log` (open it to copy the reset link while testing).

To send email through the required Mailtrap service:
1. Copy `includes/mail_config.sample.php` to `includes/mail_config.php`.
2. Set the `MAILTRAP_USER` and `MAILTRAP_PASS` environment variables to the credentials for your Mailtrap Email Testing inbox before starting Apache. SMTP mode is enabled when both are set; otherwise emails stay in log mode.
3. `mail_config.php` is in `.gitignore`, so credentials are not pushed to GitHub. Never commit Mailtrap credentials.

PHPMailer is bundled in `includes/PHPMailer/`, so there is nothing to install with Composer. If an email fails to send, the order or account action still goes through and the reason is written to `logs/mail-error.log`.

## Images and GitHub
- Watch photos the admin uploads go to **`images/products/`**. Profile pictures go to **`images/avatars/`**.
- In the admin watch form, select the main photo and use **Add another photo** to create a separate picker for each gallery photo. The main photo appears first on the product page; the arrows navigate through the additional photos.
- The database stores a relative path like `images/products/watch_ab12cd.jpg`. The pages build the image link from it, and show `assets/placeholder.svg` if the file is missing, so you never get a broken image.
- Images used to break after cloning because the folder name was typed into `BASE_URL`. It is now detected from where the project sits, so it works in any folder.
- `images/products/` is **not** ignored by git. Uploaded photos are saved there, but to make them available on GitHub you still need to commit the files along with the database export/seed data that references their relative paths. Avatars are ignored (they are user data).
- The database is not part of the repo. If you want the watches (with their photos) to appear for whoever clones the project, export your database from phpMyAdmin and replace `database.sql`.

## Where each function lives
| # | Function | File(s) |
|---|---|---|
| 1 | Register (unique username) | `user/register.php`, `user/store.php` |
| 2 | Login (username or email) | `user/login.php` |
| 3 | Logout | `user/logout.php` |
| 4 | Profile picture, account, delivery details, change password | `user/profile.php` |
| 5 | Forgot / reset password | `user/forgot_password.php`, `user/reset_password.php` |
| 6 | List and search watches (admin), Active / Archived | `item/index.php` |
| 7 | Add watch | `item/create.php`, `item/store.php` |
| 8 | Edit watch (keeps old photo) | `item/edit.php`, `item/update.php` |
| 9 | Archive and restore | `item/archive.php` |
| 10 | Update stock | `item/stock_update.php` (inline on the list), also in the edit form |
| 11 | Home page | `index.php` |
| 12 | Shop grid + filters | `shop.php` |
| 13 | Watch details | `product.php` |
| 14 | Cart | `cart_update.php`, `view_cart.php` |
| 15 | Checkout transaction (+ confirmation email) | `checkout.php` |
| 16 | My orders | `user/myorders.php`, `user/order_view.php` |
| 17 | Admin orders + status (+ status emails) | `admin/orders.php`, `admin/orderDetails.php`, `admin/updateOrder.php` |
| 18 | Access guards | `includes/guard_login.php`, `guard_admin.php`, `guard_staff.php` |
| 19 | Prepared statements | `mysqli_execute_query($conn, $sql, [$params])` in every script |
| 20 | Emails | `includes/order_email.php`, `includes/account_email.php`, `includes/mail_send.php` |

Also: low-stock alerts, multiple photos, reviews (buyers only), wishlist, sales dashboard.

## Safety notes
- Logic and redirects run **before** `header.php`, so there are no "headers already sent" problems.
- Every script that changes data only accepts POST. (Same as the class sample: no CSRF tokens.)
- Uploads are checked by real file type (JPG/PNG/WEBP, 3 MB max), renamed to random names, and `images/.htaccess` stops scripts from running in that folder.
- The forgot-password page gives the same answer whether or not the email exists.
- Order status moves one way: pending, shipped, delivered, or pending/cancelled. Cancelling returns stock.

## Manual test checklist
1. Register (try a taken username, in a different case), then log in with the email address. Username-only login should not work.
2. Profile: upload a picture, change username, save delivery details, change password.
3. Admin: add a watch with photos, check the file appears in `images/products/`, archive it, find it under Archived, restore it.
4. Customer: add to cart, check out, check `logs/mail.log` (or your inbox) for the confirmation.
5. Admin: mark the order shipped, then delivered; check both emails.
6. Log out, use **Forgot password**, open the link, set a new password, log in. The same link must not work twice.
7. Clone the repo into a folder with a different name and check the photos still show.

## Code style
The logic follows the class sample: plain procedural PHP, `session_start()` then `include('../includes/config.php')`, messages in
`$_SESSION['message']` / `['success']`, `mysqli_execute_query`, `header("Location: ...")`. No custom functions.
See **`docs/CODE_GUIDE.md`** for how the include files fit together.
