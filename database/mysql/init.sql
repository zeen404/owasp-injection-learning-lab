-- =========================================================
--  OWASP A05 Injection Lab — MySQL Initialization
-- =========================================================

CREATE DATABASE IF NOT EXISTS labdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE labdb;

-- Users table (target for SQL Injection demo)
CREATE TABLE IF NOT EXISTS users (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50)  NOT NULL,
  password VARCHAR(100) NOT NULL,
  email    VARCHAR(100) NOT NULL,
  role     VARCHAR(20)  NOT NULL DEFAULT 'user',
  created  DATETIME     DEFAULT CURRENT_TIMESTAMP
);

-- Products table (target for UNION-based injection)
CREATE TABLE IF NOT EXISTS products (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  description TEXT,
  price       DECIMAL(10,2),
  category    VARCHAR(50)
);

-- Secrets table (จะถูกดึงออกผ่าน UNION injection)
CREATE TABLE IF NOT EXISTS secrets (
  id      INT AUTO_INCREMENT PRIMARY KEY,
  secret  VARCHAR(255) NOT NULL,
  note    VARCHAR(255)
);

-- Seed Users
INSERT INTO users (username, password, email, role) VALUES
  ('admin',   'supersecret123!', 'admin@lab.local',  'admin'),
  ('alice',   'alice_pass',      'alice@lab.local',  'user'),
  ('bob',     'bob_pass',        'bob@lab.local',    'user'),
  ('charlie', 'charlie_pass',    'charlie@lab.local','user');

-- Seed Products
INSERT INTO products (name, description, price, category) VALUES
  ('Widget A', 'A standard widget',    9.99, 'widgets'),
  ('Widget B', 'A premium widget',    29.99, 'widgets'),
  ('Gadget X', 'A powerful gadget',   49.99, 'gadgets'),
  ('Gadget Y', 'An advanced gadget', 199.99, 'gadgets');

-- Seed Secrets (FLAG!)
INSERT INTO secrets (secret, note) VALUES
  ('FLAG{sql_injection_pwned_db!}', 'Congrats! You extracted the secret via SQL injection.');

GRANT ALL PRIVILEGES ON labdb.* TO 'labuser'@'%';
FLUSH PRIVILEGES;
