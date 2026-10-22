-- Run once on an existing database; new installs should import database.sql instead.
USE db_watchstoreproj;

-- Add usernames and profile photos.
ALTER TABLE users
  ADD COLUMN username VARCHAR(30) NULL AFTER user_id,
  ADD COLUMN profile_pic VARCHAR(255) NULL AFTER password;

-- Generate unique usernames from each email.
UPDATE users SET username = CONCAT(LEFT(SUBSTRING_INDEX(email, '@', 1), 15), '_', user_id);
UPDATE users SET username = 'admin' WHERE email = 'admin@watchstore.test';

ALTER TABLE users
  MODIFY username VARCHAR(30) NOT NULL,
  ADD UNIQUE KEY uq_users_username (username);

-- Add password-reset tokens.
CREATE TABLE IF NOT EXISTS password_resets (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Optional: move legacy image paths to images/products/ after moving the files.
-- UPDATE item       SET img_path = REPLACE(img_path, 'uploads/', 'images/products/') WHERE img_path LIKE 'uploads/%';
-- UPDATE item_image SET img_path = REPLACE(img_path, 'uploads/', 'images/products/') WHERE img_path LIKE 'uploads/%';


-- Add employee roles, delivery dates, sale costs, and expenses.
ALTER TABLE users MODIFY role ENUM('user','employee','admin') NOT NULL DEFAULT 'user';

ALTER TABLE orderinfo ADD COLUMN date_delivered DATETIME NULL AFTER date_shipped;
UPDATE orderinfo SET date_delivered = COALESCE(date_shipped, date_placed) WHERE status = 'delivered' AND date_delivered IS NULL;
ALTER TABLE orderline ADD COLUMN unit_cost DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER unit_price;
UPDATE orderline ol JOIN item i ON i.item_id = ol.item_id SET ol.unit_cost = i.cost_price WHERE ol.unit_cost = 0;

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


-- Add receipt preferences and receipt records.
ALTER TABLE orderinfo ADD COLUMN receipt_pref ENUM('email','print') NOT NULL DEFAULT 'email' AFTER status;

CREATE TABLE receipt (
  receipt_id   INT AUTO_INCREMENT PRIMARY KEY,
  orderinfo_id INT NOT NULL UNIQUE,
  method       ENUM('email','print') NOT NULL DEFAULT 'email',
  issued_by    INT NULL,
  issued_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  emailed_at   DATETIME NULL,
  printed_at   DATETIME NULL,
  FOREIGN KEY (orderinfo_id) REFERENCES orderinfo(orderinfo_id) ON DELETE CASCADE,
  FOREIGN KEY (issued_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Create receipts for previously delivered orders.
INSERT INTO receipt (orderinfo_id, method, issued_at)
SELECT orderinfo_id, 'print', COALESCE(date_delivered, date_shipped, date_placed) FROM orderinfo WHERE status = 'delivered';
