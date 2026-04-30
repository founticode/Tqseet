CREATE DATABASE IF NOT EXISTS tqseet_db;
USE tqseet_db;

-- USERS
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    phone VARCHAR(20) UNIQUE,
    role ENUM('user','merchant','admin') DEFAULT 'user',
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- MERCHANTS
CREATE TABLE merchants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    commission_rate DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- PRODUCTS
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150),
    description TEXT,
    price DECIMAL(10,2),
    image VARCHAR(255),
    merchant_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (merchant_id) REFERENCES merchants(id)
);

-- ORDERS
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT,
    total_price DECIMAL(10,2),
    commission DECIMAL(10,2),
    merchant_earning DECIMAL(10,2),
    status ENUM('pending','paid','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- INSTALLMENTS
CREATE TABLE installments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    amount DECIMAL(10,2),
    due_date DATE,
    status ENUM('paid','unpaid') DEFAULT 'unpaid',
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

-- USER VERIFICATION
CREATE TABLE user_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    cin VARCHAR(20),
    cin_image VARCHAR(255),
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- USER FINANCIALS
CREATE TABLE user_financials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    profession VARCHAR(100),
    salary DECIMAL(10,2),
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- OTP
CREATE TABLE otp_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    code VARCHAR(10),
    expires_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id)
);