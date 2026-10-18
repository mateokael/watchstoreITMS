-- Already have the old database imported and don't want to lose your data?
-- Run this ONCE in phpMyAdmin (select db_watchstoreproj, open the SQL tab, paste, Go).
-- A brand-new setup doesn't need it: database.sql already includes all of this.
USE db_watchstoreproj;

-- 1. usernames (unique) + profile picture
ALTER TABLE users
  ADD COLUMN username VARCHAR(30) NULL AFTER user_id,
  ADD COLUMN profile_pic VARCHAR(255) NULL AFTER password;

-- give everyone a starting username from their email, made unique with their id; they can change it on the profile page
UPDATE users SET username = CONCAT(LEFT(SUBSTRING_INDEX(email, '@', 1), 15), '_', user_id);
UPDATE users SET username = 'admin' WHERE email = 'admin@watchstore.test';

ALTER TABLE users
  MODIFY username VARCHAR(30) NOT NULL,
  ADD UNIQUE KEY uq_users_username (username);

-- 2. forgot-password tokens
CREATE TABLE IF NOT EXISTS password_resets (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. only if you already uploaded watch photos with the old version (they were stored in uploads/):
--    move the files from uploads/ into images/products/ first, then run this line.
-- UPDATE item       SET img_path = REPLACE(img_path, 'uploads/', 'images/products/') WHERE img_path LIKE 'uploads/%';
-- UPDATE item_image SET img_path = REPLACE(img_path, 'uploads/', 'images/products/') WHERE img_path LIKE 'uploads/%';


-- ===== Version 3: employees, expenses, reports =====
-- 4. employee role (restock + confirm orders only)
ALTER TABLE users MODIFY role ENUM('user','employee','admin') NOT NULL DEFAULT 'user';

-- 5. when a sale was completed + the cost at the time of sale (profit stays correct if cost changes later)
ALTER TABLE orderinfo ADD COLUMN date_delivered DATETIME NULL AFTER date_shipped;
UPDATE orderinfo SET date_delivered = COALESCE(date_shipped, date_placed) WHERE status = 'delivered' AND date_delivered IS NULL;
ALTER TABLE orderline ADD COLUMN unit_cost DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER unit_price;
UPDATE orderline ol JOIN item i ON i.item_id = ol.item_id SET ol.unit_cost = i.cost_price WHERE ol.unit_cost = 0;

-- 6. expenses
CREATE TABLE IF NOT EXISTS expense_category (
  category_id INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS expense (
  expense_id   INT AUTO_INCREMENT PRIMARY KEY,
  category_id  INT NOT NULL,
  description  VARCHAR(200) NOT NULL,
  amount       DECIMAL(10,2) NOT NULL,
  expense_date DATE NOT NULL,
  created_by   INT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES expense_category(category_id),
  FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;
INSERT IGNORE INTO expense_category (name) VALUES
('Rent'), ('Salaries'), ('Utilities'), ('Supplies & packaging'), ('Marketing'), ('Delivery / courier'), ('Repairs & maintenance'), ('Other');


-- ===== Version 4: receipts (email or printed) =====
ALTER TABLE orderinfo ADD COLUMN receipt_pref ENUM('email','print') NOT NULL DEFAULT 'email' AFTER status;

-- One receipt per completed (delivered) order. The customer picks email or print at checkout (receipt_pref);
-- staff can email it, resend it, or print it for the customer in person. Totals are not stored: they come from the order lines.
CREATE TABLE receipt (
  receipt_id   INT AUTO_INCREMENT PRIMARY KEY,
  orderinfo_id INT NOT NULL UNIQUE,
  method       ENUM('email','print') NOT NULL DEFAULT 'email',   -- how it was (last) given to the customer
  issued_by    INT NULL,                                          -- the staff member who completed the order
  issued_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  emailed_at   DATETIME NULL,
  printed_at   DATETIME NULL,
  FOREIGN KEY (orderinfo_id) REFERENCES orderinfo(orderinfo_id) ON DELETE CASCADE,
  FOREIGN KEY (issued_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- receipts for orders that were already delivered
INSERT INTO receipt (orderinfo_id, method, issued_at)
SELECT orderinfo_id, 'print', COALESCE(date_delivered, date_shipped, date_placed) FROM orderinfo WHERE status = 'delivered';
