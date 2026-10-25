-- WatchStore schema and sample data.
CREATE DATABASE IF NOT EXISTS db_watchstoreproj CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_watchstoreproj;

SET FOREIGN_KEY_CHECKS = 0;
DROP VIEW IF EXISTS v_order_totals, v_order_details;
DROP TABLE IF EXISTS receipt, expense, expense_category, password_resets, wishlist, review, orderline, orderinfo, item_image, stock, item, customer, users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  user_id    INT AUTO_INCREMENT PRIMARY KEY,
  username   VARCHAR(30) NOT NULL UNIQUE,
  email      VARCHAR(100) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  profile_pic VARCHAR(255) NULL,
  role       ENUM('user','employee','admin') NOT NULL DEFAULT 'user',
  status     ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Password reset tokens are stored as hashes.
CREATE TABLE password_resets (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE customer (
  customer_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL UNIQUE,
  title       VARCHAR(8) NULL,
  fname       VARCHAR(50) NOT NULL,
  lname       VARCHAR(50) NOT NULL,
  addressline VARCHAR(120) NOT NULL,
  town        VARCHAR(60) NOT NULL,
  zipcode     VARCHAR(10) NOT NULL,
  phone       VARCHAR(20) NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE item (
  item_id          INT AUTO_INCREMENT PRIMARY KEY,
  brand            VARCHAR(60) NOT NULL,
  model            VARCHAR(100) NOT NULL,
  description      TEXT NULL,
  movement         ENUM('quartz','automatic','mechanical','solar','smart') NOT NULL DEFAULT 'quartz',
  category         ENUM('men','women','unisex') NOT NULL DEFAULT 'unisex',
  case_size_mm     SMALLINT UNSIGNED NULL,
  strap_type       VARCHAR(40) NULL,
  water_resistance VARCHAR(20) NULL,
  cost_price       DECIMAL(10,2) NOT NULL,
  sell_price       DECIMAL(10,2) NOT NULL,
  img_path         VARCHAR(255) NULL,
  -- Inactive items are archived but retained for order history.
  is_active        TINYINT(1) NOT NULL DEFAULT 1,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE stock (
  item_id         INT PRIMARY KEY,
  quantity        INT NOT NULL DEFAULT 0,
  low_stock_level INT NOT NULL DEFAULT 3,
  FOREIGN KEY (item_id) REFERENCES item(item_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE item_image (
  image_id INT AUTO_INCREMENT PRIMARY KEY,
  item_id  INT NOT NULL,
  img_path VARCHAR(255) NOT NULL,
  FOREIGN KEY (item_id) REFERENCES item(item_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE orderinfo (
  orderinfo_id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id  INT NOT NULL,
  date_placed  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_shipped DATETIME NULL,
  date_delivered DATETIME NULL,
  shipping     DECIMAL(8,2) NOT NULL DEFAULT 0,
  status       ENUM('pending','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  receipt_pref ENUM('email','print') NOT NULL DEFAULT 'email',
  FOREIGN KEY (customer_id) REFERENCES customer(customer_id)
) ENGINE=InnoDB;

CREATE TABLE orderline (
  orderinfo_id INT NOT NULL,
  item_id      INT NOT NULL,
  quantity     INT NOT NULL,
  unit_price   DECIMAL(10,2) NOT NULL,
  unit_cost    DECIMAL(10,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (orderinfo_id, item_id),
  FOREIGN KEY (orderinfo_id) REFERENCES orderinfo(orderinfo_id) ON DELETE CASCADE,
  FOREIGN KEY (item_id) REFERENCES item(item_id)
) ENGINE=InnoDB;

-- One receipt per delivered order; totals are calculated from its order lines.
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

CREATE TABLE review (
  review_id  INT AUTO_INCREMENT PRIMARY KEY,
  item_id    INT NOT NULL,
  user_id    INT NOT NULL,
  rating     TINYINT NOT NULL,
  comment    VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY one_review_per_user (item_id, user_id),
  FOREIGN KEY (item_id) REFERENCES item(item_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE wishlist (
  user_id INT NOT NULL,
  item_id INT NOT NULL,
  PRIMARY KEY (user_id, item_id),
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (item_id) REFERENCES item(item_id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- Expense categories are stored separately to avoid repeated names.
CREATE TABLE expense_category (
  category_id INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE expense (
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

INSERT INTO expense_category (name) VALUES
('Rent'), ('Salaries'), ('Utilities'), ('Supplies & packaging'), ('Marketing'), ('Delivery / courier'), ('Repairs & maintenance'), ('Other');

-- Demo admin: admin@watchstore.test / admin123.
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@watchstore.test', '$2b$10$VTVlL6VAnslIDuxqKqtK3OQd/K3kMzIEv3V3V2nrDIQIxYcg5gJTG', 'admin');

-- Sample watches.
INSERT INTO item (brand, model, description, movement, category, case_size_mm, strap_type, water_resistance, cost_price, sell_price) VALUES
('Casio',   'F-91W',            'Digital classic with alarm, stopwatch and daily chime.',            'quartz',    'unisex', 35, 'Resin',           '30 m',   550.00,  995.00),
('Seiko',   '5 Sports SRPD',    'Automatic diver-style daily wear with day-date display.',          'automatic', 'men',    42, 'Stainless steel', '100 m', 9500.00, 14500.00),
('Citizen', 'Eco-Drive BM7',    'Light-powered movement, no battery changes. Sapphire-coated look.', 'solar',     'men',    40, 'Leather',         '100 m', 8200.00, 12990.00),
('Orient',  'Bambino Gen 2',    'Dress watch with domed crystal and in-house automatic movement.',   'automatic', 'men',    40, 'Leather',         '30 m',  7800.00, 11900.00),
('Timex',   'Weekender 38',     'Easy everyday quartz with a quick-release fabric strap.',           'quartz',    'unisex', 38, 'Nylon',           '30 m',  1900.00,  3490.00),
('Fossil',  'Carlyle Mini',     'Slim rose-tone dress watch sized for smaller wrists.',              'quartz',    'women',  34, 'Stainless steel', '50 m',  3600.00,  6450.00);

INSERT INTO stock (item_id, quantity, low_stock_level) VALUES
(1, 25, 5), (2, 8, 3), (3, 6, 3), (4, 4, 3), (5, 15, 5), (6, 2, 3);


-- One row per order line; totals are calculated in PHP from these raw values.
CREATE VIEW v_order_details AS
SELECT o.orderinfo_id, o.date_placed, o.date_shipped, o.date_delivered, o.status, o.shipping, o.receipt_pref,
       c.customer_id, c.user_id, c.title, c.fname, c.lname, c.addressline, c.town, c.zipcode, c.phone,
       u.username, u.email,
       i.item_id, i.brand, i.model, i.img_path,
       ol.quantity, ol.unit_price, ol.unit_cost
FROM orderinfo o
INNER JOIN customer  c  ON c.customer_id  = o.customer_id
INNER JOIN users     u  ON u.user_id      = c.user_id
INNER JOIN orderline ol ON ol.orderinfo_id = o.orderinfo_id
INNER JOIN item      i  ON i.item_id      = ol.item_id;
