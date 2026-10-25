-- Run on an existing database after its schema has the current order columns.
-- New installations already create this view in database.sql.
USE db_watchstoreproj;

DROP VIEW IF EXISTS v_order_totals;
DROP VIEW IF EXISTS v_order_details;

-- One row per order line. The application calculates totals from the raw values.
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
