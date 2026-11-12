CREATE DATABASE IF NOT EXISTS gadgethub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gadgethub;

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE brands (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  country VARCHAR(100) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE suppliers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  contact_person VARCHAR(120) NULL,
  email VARCHAR(150) NULL,
  phone VARCHAR(40) NULL,
  address VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sku VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(160) NOT NULL,
  category_id INT NOT NULL,
  brand_id INT NOT NULL,
  supplier_id INT NULL,
  description TEXT NULL,
  price DECIMAL(12,2) NOT NULL DEFAULT 0,
  stock INT NOT NULL DEFAULT 0,
  image_url VARCHAR(500) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_product_category FOREIGN KEY (category_id) REFERENCES categories(id),
  CONSTRAINT fk_product_brand FOREIGN KEY (brand_id) REFERENCES brands(id),
  CONSTRAINT fk_product_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
);

CREATE TABLE sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(12,2) NOT NULL,
  total DECIMAL(12,2) NOT NULL,
  customer_name VARCHAR(150) NULL,
  sold_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sale_product FOREIGN KEY (product_id) REFERENCES products(id)
);

INSERT INTO categories (name, description) VALUES
('Smartphones','Mobile phones and smartphones'),('Tablets','Tablets and iPads'),('Laptops','Personal and business laptops'),('Accessories','Chargers, cases, audio and other accessories');

INSERT INTO brands (name,country) VALUES
('Apple','United States'),('Samsung','South Korea'),('Xiaomi','China'),('OPPO','China'),('Google','United States'),('OnePlus','China');

INSERT INTO suppliers (name,contact_person,email,phone,address) VALUES
('TechSource Distribution','Mark Reyes','sales@techsource.example','09171234567','Quezon City, Philippines'),
('Digital Central Trading','Anne Cruz','hello@digitalcentral.example','09181234567','Manila, Philippines');

INSERT INTO products (sku,name,category_id,brand_id,supplier_id,description,price,stock,image_url) VALUES
('APL-IP15-128','iPhone 15 128GB',1,1,1,'Dynamic Island, 48MP camera, A16 Bionic chip.',42990,12,'https://images.unsplash.com/photo-1695048133142-1a20484d2569?w=600'),
('SAM-S24-256','Samsung Galaxy S24 256GB',1,2,1,'Flagship Android smartphone with Galaxy AI.',45990,8,'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?w=600'),
('XIA-14-256','Xiaomi 14 256GB',1,3,2,'Leica camera system and flagship performance.',39999,5,'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=600'),
('APL-IPAD-AIR','iPad Air',2,1,1,'Lightweight tablet for work and creativity.',37990,3,'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=600'),
('GOO-PIX-8','Google Pixel 8',1,5,2,'Google AI features and excellent camera.',32990,0,'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=600');
