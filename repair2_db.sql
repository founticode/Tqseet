USE tqseet_db;

-- Step 1: Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Step 2: Discard all tablespaces (detaches the corrupted .ibd files)
ALTER TABLE installments DISCARD TABLESPACE;
ALTER TABLE orders DISCARD TABLESPACE;
ALTER TABLE payment_links DISCARD TABLESPACE;
ALTER TABLE payment_methods DISCARD TABLESPACE;
ALTER TABLE products DISCARD TABLESPACE;
ALTER TABLE settlements DISCARD TABLESPACE;
ALTER TABLE user_financials DISCARD TABLESPACE;
ALTER TABLE user_verifications DISCARD TABLESPACE;
ALTER TABLE otp_codes DISCARD TABLESPACE;
ALTER TABLE merchants DISCARD TABLESPACE;
ALTER TABLE users DISCARD TABLESPACE;

-- Step 3: Drop all tables (now safe since tablespaces are discarded)
DROP TABLE IF EXISTS installments;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS payment_links;
DROP TABLE IF EXISTS payment_methods;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS settlements;
DROP TABLE IF EXISTS user_financials;
DROP TABLE IF EXISTS user_verifications;
DROP TABLE IF EXISTS otp_codes;
DROP TABLE IF EXISTS merchants;
DROP TABLE IF EXISTS users;

-- Step 4: Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Step 5: Recreate all tables cleanly

-- USERS
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    phone VARCHAR(20) UNIQUE,
    role ENUM('user','merchant','admin') DEFAULT 'user',
    is_verified BOOLEAN DEFAULT FALSE,
    profile_pic VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- MERCHANTS
CREATE TABLE merchants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    store_name VARCHAR(150),
    description TEXT,
    commission_rate DECIMAL(5,2) DEFAULT 0.05,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- SETTLEMENTS (before orders)
CREATE TABLE settlements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    merchant_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (merchant_id) REFERENCES merchants(id) ON DELETE CASCADE
);

-- PRODUCTS
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    merchant_id INT,
    name VARCHAR(150),
    description TEXT,
    price DECIMAL(10,2),
    image VARCHAR(255),
    stock INT DEFAULT 10,
    is_payment_link BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (merchant_id) REFERENCES merchants(id) ON DELETE CASCADE
);

-- ORDERS
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT,
    total_price DECIMAL(10,2),
    commission DECIMAL(10,2),
    merchant_earning DECIMAL(10,2),
    settlement_id INT DEFAULT NULL,
    status ENUM('pending', 'active', 'paid', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_orders_settlements FOREIGN KEY (settlement_id) REFERENCES settlements(id) ON DELETE SET NULL
);

-- INSTALLMENTS
CREATE TABLE installments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    amount DECIMAL(10,2),
    due_date DATE,
    status ENUM('unpaid', 'paid', 'overdue') DEFAULT 'unpaid',
    paid_at TIMESTAMP NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- IDENTITY VERIFICATIONS (KYC)
CREATE TABLE user_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    cin VARCHAR(50),
    cin_image VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    verified_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- FINANCIAL PROFILES
CREATE TABLE user_financials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    profession VARCHAR(100),
    salary DECIMAL(10,2),
    salary_proof VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    credit_limit DECIMAL(10,2) DEFAULT 0.00,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- OTP CODES
CREATE TABLE otp_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- PAYMENT METHODS
CREATE TABLE payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    card_brand VARCHAR(50),
    last_four VARCHAR(4) NOT NULL,
    expiry VARCHAR(5) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- PAYMENT LINKS
CREATE TABLE payment_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    merchant_id INT NOT NULL,
    link_hash VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('active', 'paid', 'expired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (merchant_id) REFERENCES merchants(id) ON DELETE CASCADE
);
