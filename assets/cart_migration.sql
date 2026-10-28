-- Run once on an existing database to enable persistent user carts.
USE db_watchstoreproj;

CREATE TABLE IF NOT EXISTS cart (
  user_id   INT NOT NULL,
  item_id   INT NOT NULL,
  quantity  INT NOT NULL,
  PRIMARY KEY (user_id, item_id),
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (item_id) REFERENCES item(item_id) ON DELETE CASCADE
) ENGINE=InnoDB;
