-- Run on an existing database; database.sql already creates these views on a fresh install.
USE db_watchstoreproj;

DROP VIEW IF EXISTS v_order_totals;
DROP VIEW IF EXISTS v_order_details;

-- One row per order line.
CREATE VIEW v_order_details AS
SELECT o.orderinfo_id, o.date_placed, o.date_shipped, o.date_delivered, o.status, o.shipping, o.receipt_pref,
       c.customer_id, c.user_id, c.title, c.fname, c.lname, c.addressline, c.town, c.zipcode, c.phone,
       u.username, u.email,
       i.item_id, i.brand, i.model, i.img_path,
       ol.quantity, ol.unit_price, ol.unit_cost,
       ol.quantity * ol.unit_price AS line_total
FROM orderinfo o
INNER JOIN customer  c  ON c.customer_id  = o.customer_id
INNER JOIN users     u  ON u.user_id      = c.user_id
INNER JOIN orderline ol ON ol.orderinfo_id = o.orderinfo_id
INNER JOIN item      i  ON i.item_id      = ol.item_id;

-- One row per order with totals.
CREATE VIEW v_order_totals AS
SELECT d.orderinfo_id, d.date_placed, d.date_shipped, d.date_delivered, d.status, d.shipping, d.receipt_pref,
       d.customer_id, d.user_id, d.title, d.fname, d.lname, d.addressline, d.town, d.zipcode, d.phone,
       d.username, d.email,
       COUNT(*)            AS line_count,
       SUM(d.quantity)     AS units,
       SUM(d.line_total)   AS subtotal,
       SUM(d.line_total) + d.shipping AS grand_total
FROM v_order_details d
GROUP BY d.orderinfo_id, d.date_placed, d.date_shipped, d.date_delivered, d.status, d.shipping, d.receipt_pref,
         d.customer_id, d.user_id, d.title, d.fname, d.lname, d.addressline, d.town, d.zipcode, d.phone,
         d.username, d.email;
