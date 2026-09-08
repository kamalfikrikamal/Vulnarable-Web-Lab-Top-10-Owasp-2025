-- VulnShop seed data (INTENTIONALLY INSECURE - training only)
CREATE DATABASE IF NOT EXISTS vulnshop;
USE vulnshop;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(50) NOT NULL,
  role VARCHAR(20) DEFAULT 'user',
  email VARCHAR(100)
);

INSERT INTO users (username, password, role, email) VALUES
('admin', 'S3cr3tAdminPass!', 'admin', 'admin@vulnshop.local'),
('alice', 'alice123', 'user', 'alice@vulnshop.local'),
('bob', 'bobpass99', 'user', 'bob@vulnshop.local');

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  description TEXT,
  price DECIMAL(10,2),
  category VARCHAR(50)
);

INSERT INTO products (name, description, price, category) VALUES
('Wireless Mouse', 'Ergonomic wireless mouse', 19.99, 'electronics'),
('Mechanical Keyboard', 'RGB mechanical keyboard', 59.99, 'electronics'),
('Coffee Mug', 'Ceramic mug 350ml', 8.50, 'home'),
('Notebook', 'A5 lined notebook', 3.25, 'office'),
('Desk Lamp', 'LED desk lamp', 24.00, 'home');

CREATE TABLE feedback (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT,
  comment TEXT
);

INSERT INTO feedback (product_id, comment) VALUES
(1, 'Great mouse, very responsive!'),
(2, 'Love the RGB keyboard.'),
(3, 'Nice mug, good size.');

-- Used by the second-order injection lab
CREATE TABLE profiles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(255),
  bio VARCHAR(255)
);

-- Used by Lab 8 (stacked queries). Dedicated table so DROP/INSERT demo payloads never touch
-- the shared users/products/profiles tables used by the other labs. The lab page itself also
-- runs a CREATE TABLE IF NOT EXISTS on every load, so this is self-healing if dropped.
CREATE TABLE IF NOT EXISTS notes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  text VARCHAR(255)
);

-- Used by Lab 10 (SQLi via INSERT / registration form). Dedicated table, separate from the
-- real `users` table, so this lab's demo registrations never collide with it.
CREATE TABLE IF NOT EXISTS reg_demo_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50),
  bio VARCHAR(255)
);
