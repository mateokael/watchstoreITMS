# Database design notes (for Unit 1)

Tables: users, customer, item, stock, item_image, orderinfo, orderline, review, wishlist, password_resets, expense_category, expense.

## Normalization (target: 2NF to 3NF)
- **1NF:** every column holds one value; no repeating groups (photos live in `item_image`, order lines in `orderline`).
- **2NF:** `orderline` has a composite key (orderinfo_id, item_id). Its non-key columns (quantity, unit_price, unit_cost) depend on the whole key. Item details (brand, model) are NOT repeated there; they stay in `item`.
- **3NF:** no non-key column depends on another non-key column:
  - expense category name is stored once in `expense_category`, and `expense` only holds `category_id`.
  - stock quantity lives in `stock`, not in `item`.
  - customer contact details live in `customer`, login details in `users`.
- **Deliberate snapshots:** `orderline.unit_price` and `unit_cost` copy the price and cost at the time of sale. This is not a normalization mistake: the sale must not change when the item price changes later.
- **One known gap:** `customer` stores `town` and `zipcode` together, and a zip code normally determines the town. That is a transitive dependency, so strictly this one table is 2NF. It is easy to defend (customers type a free-text address), or fix with a `location(zipcode, town)` table if your teacher wants strict 3NF.

## Relationships
users 1-1 customer; customer 1-many orderinfo; orderinfo 1-many orderline; item 1-many orderline; item 1-1 stock; item 1-many item_image; expense_category 1-many expense; users 1-many expense (created_by).
