CREATE DATABASE IF NOT EXISTS ecommerce_store;
USE ecommerce_store;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(255) DEFAULT 'default-avatar.png',
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    image VARCHAR(255) DEFAULT 'default-product.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert admin user (username: honore123, password: hono@123)
INSERT INTO users (username, email, password, role) 
VALUES ('honore123', 'admin@ecommerce.com', '$2y$10$YourHashedPasswordHere', 'admin');

-- Insert sample products
INSERT INTO products (name, description, price, category, image) VALUES
('Classic Running Shoes', 'Comfortable running shoes with breathable mesh', 49.99, 'shoes', 'running-shoes.jpg'),
('Premium Sneakers', 'Stylish sneakers for everyday wear', 59.99, 'shoes', 'sneakers.jpg'),
('Casual T-Shirt', '100% cotton comfortable t-shirt', 19.99, 'clothing', 'tshirt.jpg'),
('Denim Jeans', 'Classic fit denim jeans', 39.99, 'clothing', 'jeans.jpg'),
('Winter Jacket', 'Warm and stylish winter jacket', 89.99, 'clothing', 'jacket.jpg'),
('Sports Shoes', 'Professional sports shoes', 69.99, 'shoes', 'sports-shoes.jpg');