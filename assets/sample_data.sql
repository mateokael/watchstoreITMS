-- Optional demo accounts use admin123; dates are relative to import and orders do not change stock.
USE db_watchstoreproj;

INSERT INTO users (username, email, password, role) VALUES
('employee1', 'employee1@watchstore.test', '$2b$10$VTVlL6VAnslIDuxqKqtK3OQd/K3kMzIEv3V3V2nrDIQIxYcg5gJTG', 'employee'),
('maria',  'maria@example.test',  '$2b$10$VTVlL6VAnslIDuxqKqtK3OQd/K3kMzIEv3V3V2nrDIQIxYcg5gJTG', 'user'),
('juan',   'juan@example.test',   '$2b$10$VTVlL6VAnslIDuxqKqtK3OQd/K3kMzIEv3V3V2nrDIQIxYcg5gJTG', 'user'),
('ana',    'ana@example.test',    '$2b$10$VTVlL6VAnslIDuxqKqtK3OQd/K3kMzIEv3V3V2nrDIQIxYcg5gJTG', 'user'),
('carlo',  'carlo@example.test',  '$2b$10$VTVlL6VAnslIDuxqKqtK3OQd/K3kMzIEv3V3V2nrDIQIxYcg5gJTG', 'user');

INSERT INTO customer (user_id, title, fname, lname, addressline, town, zipcode, phone)
SELECT user_id, t, f, l, a, tw, z, p FROM (
  SELECT 'maria' un, 'Ms' t, 'Maria' f, 'Santos' l, '12 Rizal St' a, 'Taguig' tw, '1630' z, '09171234501' p UNION ALL
  SELECT 'juan',  'Mr', 'Juan',  'Dela Cruz', '45 Mabini Ave', 'Makati',  '1200', '09171234502' UNION ALL
  SELECT 'ana',   'Ms', 'Ana',   'Reyes',     '7 Bonifacio Rd', 'Pasig',   '1600', '09171234503' UNION ALL
  SELECT 'carlo', 'Mr', 'Carlo', 'Garcia',    '88 Aguinaldo St', 'Manila', '1000', '09171234504'
) d INNER JOIN users u ON u.username = d.un COLLATE utf8mb4_unicode_ci;

INSERT INTO orderinfo (customer_id, date_placed, date_shipped, date_delivered, shipping, status)
SELECT c.customer_id, DATE_SUB(NOW(), INTERVAL 83 DAY), DATE_SUB(NOW(), INTERVAL 81 DAY), DATE_SUB(NOW(), INTERVAL 80 DAY), 150.00, 'delivered'
FROM customer c INNER JOIN users u ON u.user_id = c.user_id WHERE u.username = 'maria';
SET @o = LAST_INSERT_ID();
INSERT INTO orderline (orderinfo_id, item_id, quantity, unit_price, unit_cost) SELECT @o, item_id, 1, sell_price, cost_price FROM item WHERE item_id = 2;
INSERT INTO orderline (orderinfo_id, item_id, quantity, unit_price, unit_cost) SELECT @o, item_id, 2, sell_price, cost_price FROM item WHERE item_id = 5;
INSERT INTO orderinfo (customer_id, date_placed, date_shipped, date_delivered, shipping, status)
SELECT c.customer_id, DATE_SUB(NOW(), INTERVAL 77 DAY), DATE_SUB(NOW(), INTERVAL 75 DAY), DATE_SUB(NOW(), INTERVAL 74 DAY), 150.00, 'delivered'
FROM customer c INNER JOIN users u ON u.user_id = c.user_id WHERE u.username = 'juan';
SET @o = LAST_INSERT_ID();
INSERT INTO orderline (orderinfo_id, item_id, quantity, unit_price, unit_cost) SELECT @o, item_id, 3, sell_price, cost_price FROM item WHERE item_id = 1;
INSERT INTO orderinfo (customer_id, date_placed, date_shipped, date_delivered, shipping, status)
SELECT c.customer_id, DATE_SUB(NOW(), INTERVAL 69 DAY), DATE_SUB(NOW(), INTERVAL 67 DAY), DATE_SUB(NOW(), INTERVAL 66 DAY), 150.00, 'delivered'
FROM customer c INNER JOIN users u ON u.user_id = c.user_id WHERE u.username = 'ana';
SET @o = LAST_INSERT_ID();
INSERT INTO orderline (orderinfo_id, item_id, quantity, unit_price, unit_cost) SELECT @o, item_id, 1, sell_price, cost_price FROM item WHERE item_id = 3;
INSERT INTO orderline (orderinfo_id, item_id, quantity, unit_price, unit_cost) SELECT @o, item_id, 1, sell_price, cost_price FROM item WHERE item_id = 6;
INSERT INTO orderinfo (customer_id, date_placed, date_shipped, date_delivered, shipping, status)
SELECT c.customer_id, DATE_SUB(NOW(), INTERVAL 61 DAY), DATE_SUB(NOW(), INTERVAL 59 DAY), DATE_SUB(NOW(), INTERVAL 58 DAY), 150.00, 'delivered'
FROM customer c INNER JOIN users u ON u.user_id = c.user_id WHERE u.username = 'carlo';
SET @o = LAST_INSERT_ID();
INSERT INTO orderline (orderinfo_id, item_id, quantity, unit_price, unit_cost) SELECT @o, item_id, 1, sell_price, cost_price FROM item WHERE item_id = 4;
INSERT INTO orderinfo (customer_id, date_placed, date_shipped, date_delivered, shipping, status)
SELECT c.customer_id, DATE_SUB(NOW(), INTERVAL 52 DAY), DATE_SUB(NOW(), INTERVAL 50 DAY), DATE_SUB(NOW(), INTERVAL 49 DAY), 150.00, 'delivered'
FROM customer c INNER JOIN users u ON u.user_id = c.user_id WHERE u.username = 'maria';
SET @o = LAST_INSERT_ID();
INSERT INTO orderline (orderinfo_id, item_id, quantity, unit_price, unit_cost) SELECT @o, item_id, 2, sell_price, cost_price FROM item WHERE item_id = 1;
INSERT INTO orderline (orderinfo_id, item_id, quantity, unit_price, unit_cost) SELECT @o, item_id, 1, sell_price, cost_price FROM item WHERE item_id = 5;
INSERT INTO orderinfo (customer_id, date_placed, date_shipped, date_delivered, shipping, status)
SELECT c.customer_id, DATE_SUB(NOW(), INTERVAL 44 DAY), DATE_SUB(NOW(), INTERVAL 42 DAY), DATE_SUB(NOW(), INTERVAL 41 DAY), 150.00, 'delivered'
FROM customer c INNER JOIN users u ON u.user_id = c.user_id WHERE u.username = 'juan';
SET @o = LAST_INSERT_ID();
INSERT INTO orderline (orderinfo_id, item_id, quantity, unit_price, unit_cost) SELECT @o, item_id, 1, sell_price, cost_price FROM item WHERE item_id = 2;
INSERT INTO orderinfo (customer_id, date_placed, date_shipped, date_delivered, shipping, status)
SELECT c.customer_id, DATE_SUB(NOW(), INTERVAL 36 DAY), DATE_SUB(NOW(), INTERVAL 34 DAY), DATE_SUB(NOW(), INTERVAL 33 DAY), 150.00, 'delivered'
FROM customer c INNER JOIN users u ON u.user_id = c.user_id WHERE u.username = 'ana';
SET @o = LAST_INSERT_ID();
INSERT INTO orderline (orderinfo_id, item_id, quantity, unit_price, unit_cost) SELECT @o, item_id, 2, sell_price, cost_price FROM item WHERE item_id = 6;
INSERT INTO orderinfo (customer_id, date_placed, date_shipped, date_delivered, shipping, status)
SELECT c.customer_id, DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 28 DAY), DATE_SUB(NOW(), INTERVAL 27 DAY), 150.00, 'delivered'
FROM customer c INNER JOIN users u ON u.user_id = c.user_id WHERE u.username = 'carlo';
SET @o = LAST_INSERT_ID();
INSERT INTO orderline (orderinfo_id, item_id, quantity, unit_price, unit_cost) SELECT @o, item_id, 1, sell_price, cost_price FROM item WHERE item_id = 3;
INSERT INTO orderline (orderinfo_id, item_id, quantity, unit_price, unit_cost) SELECT @o, item_id, 1, sell_price, cost_price FROM item WHERE item_id = 1;
INSERT INTO orderinfo (customer_id, date_placed, date_shipped, date_delivered, shipping, status)
SELECT c.customer_id, DATE_SUB(NOW(), INTERVAL 22 DAY), DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 19 DAY), 150.00, 'delivered'
FROM customer c INNER JOIN users u ON u.user_id = c.user_id WHERE u.username = 'maria';
SET @o = LAST_INSERT_ID();
INSERT INTO orderline (orderinfo_id, item_id, quantity, unit_price, unit_cost) SELECT @o, item_id, 1, sell_price, cost_price FROM item WHERE item_id = 4;
INSERT INTO orderline (orderinfo_id, item_id, quantity, unit_price, unit_cost) SELECT @o, item_id, 2, sell_price, cost_price FROM item WHERE item_id = 5;
INSERT INTO orderinfo (customer_id, date_placed, date_shipped, date_delivered, shipping, status)
SELECT c.customer_id, DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(NOW(), INTERVAL 13 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY), 150.00, 'delivered'
FROM customer c INNER JOIN users u ON u.user_id = c.user_id WHERE u.username = 'juan';
SET @o = LAST_INSERT_ID();
INSERT INTO orderline (orderinfo_id, item_id, quantity, unit_price, unit_cost) SELECT @o, item_id, 1, sell_price, cost_price FROM item WHERE item_id = 2;
INSERT INTO orderline (orderinfo_id, item_id, quantity, unit_price, unit_cost) SELECT @o, item_id, 2, sell_price, cost_price FROM item WHERE item_id = 1;
INSERT INTO orderinfo (customer_id, date_placed, date_shipped, date_delivered, shipping, status)
SELECT c.customer_id, DATE_SUB(NOW(), INTERVAL 11 DAY), DATE_SUB(NOW(), INTERVAL 9 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY), 150.00, 'delivered'
FROM customer c INNER JOIN users u ON u.user_id = c.user_id WHERE u.username = 'ana';
SET @o = LAST_INSERT_ID();
INSERT INTO orderline (orderinfo_id, item_id, quantity, unit_price, unit_cost) SELECT @o, item_id, 1, sell_price, cost_price FROM item WHERE item_id = 3;
INSERT INTO orderinfo (customer_id, date_placed, date_shipped, date_delivered, shipping, status)
SELECT c.customer_id, DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY), 150.00, 'delivered'
FROM customer c INNER JOIN users u ON u.user_id = c.user_id WHERE u.username = 'maria';
SET @o = LAST_INSERT_ID();
INSERT INTO orderline (orderinfo_id, item_id, quantity, unit_price, unit_cost) SELECT @o, item_id, 1, sell_price, cost_price FROM item WHERE item_id = 6;
INSERT INTO orderline (orderinfo_id, item_id, quantity, unit_price, unit_cost) SELECT @o, item_id, 1, sell_price, cost_price FROM item WHERE item_id = 1;
INSERT INTO orderinfo (customer_id, date_placed, date_shipped, date_delivered, shipping, status)
SELECT c.customer_id, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), 150.00, 'delivered'
FROM customer c INNER JOIN users u ON u.user_id = c.user_id WHERE u.username = 'carlo';
SET @o = LAST_INSERT_ID();
INSERT INTO orderline (orderinfo_id, item_id, quantity, unit_price, unit_cost) SELECT @o, item_id, 1, sell_price, cost_price FROM item WHERE item_id = 2;
INSERT INTO orderinfo (customer_id, date_placed, date_shipped, shipping, status)
SELECT c.customer_id, DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, 150.00, 'pending'
FROM customer c INNER JOIN users u ON u.user_id = c.user_id WHERE u.username = 'ana';
SET @o = LAST_INSERT_ID();
INSERT INTO orderline (orderinfo_id, item_id, quantity, unit_price, unit_cost) SELECT @o, item_id, 1, sell_price, cost_price FROM item WHERE item_id = 5;
INSERT INTO orderinfo (customer_id, date_placed, date_shipped, shipping, status)
SELECT c.customer_id, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 0 DAY), 150.00, 'shipped'
FROM customer c INNER JOIN users u ON u.user_id = c.user_id WHERE u.username = 'juan';
SET @o = LAST_INSERT_ID();
INSERT INTO orderline (orderinfo_id, item_id, quantity, unit_price, unit_cost) SELECT @o, item_id, 1, sell_price, cost_price FROM item WHERE item_id = 5;

INSERT INTO expense (category_id, description, amount, expense_date, created_by)
SELECT ec.category_id, d.descr, d.amt, DATE_SUB(CURDATE(), INTERVAL d.ago DAY), (SELECT user_id FROM users WHERE username = 'admin') FROM (
  SELECT 'Rent' cat, 85 ago, 'Shop rent' descr, 25000.00 amt UNION ALL
  SELECT 'Salaries' cat, 84 ago, 'Staff salaries' descr, 18000.00 amt UNION ALL
  SELECT 'Utilities' cat, 78 ago, 'Electricity and internet' descr, 3200.00 amt UNION ALL
  SELECT 'Supplies & packaging' cat, 70 ago, 'Gift boxes and bubble wrap' descr, 1800.00 amt UNION ALL
  SELECT 'Marketing' cat, 62 ago, 'Facebook ads' descr, 2500.00 amt UNION ALL
  SELECT 'Rent' cat, 55 ago, 'Shop rent' descr, 25000.00 amt UNION ALL
  SELECT 'Salaries' cat, 54 ago, 'Staff salaries' descr, 18000.00 amt UNION ALL
  SELECT 'Delivery / courier' cat, 45 ago, 'Courier pickup fees' descr, 1400.00 amt UNION ALL
  SELECT 'Repairs & maintenance' cat, 38 ago, 'Display case repair' descr, 2200.00 amt UNION ALL
  SELECT 'Utilities' cat, 30 ago, 'Electricity and internet' descr, 3400.00 amt UNION ALL
  SELECT 'Rent' cat, 24 ago, 'Shop rent' descr, 25000.00 amt UNION ALL
  SELECT 'Salaries' cat, 23 ago, 'Staff salaries' descr, 18000.00 amt UNION ALL
  SELECT 'Marketing' cat, 14 ago, 'Instagram promotion' descr, 3000.00 amt UNION ALL
  SELECT 'Supplies & packaging' cat, 9 ago, 'Receipt paper and labels' descr, 900.00 amt UNION ALL
  SELECT 'Delivery / courier' cat, 3 ago, 'Courier pickup fees' descr, 750.00 amt
) d INNER JOIN expense_category ec ON ec.name = d.cat COLLATE utf8mb4_unicode_ci;

-- Add sample receipts for delivered demo orders.
INSERT INTO receipt (orderinfo_id, method, issued_by, issued_at, emailed_at, printed_at)
SELECT o.orderinfo_id, IF(o.orderinfo_id % 2 = 0, 'email', 'print'), (SELECT user_id FROM users WHERE username = 'employee1'),
       o.date_delivered, IF(o.orderinfo_id % 2 = 0, o.date_delivered, NULL), IF(o.orderinfo_id % 2 = 0, NULL, o.date_delivered)
FROM orderinfo o
WHERE o.status = 'delivered' AND NOT EXISTS (SELECT 1 FROM receipt r WHERE r.orderinfo_id = o.orderinfo_id);
