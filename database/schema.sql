CREATE DATABASE IF NOT EXISTS tqseet_db;
USE tqseet_db;

-- 1. USERS
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

-- 2. MERCHANTS
CREATE TABLE merchants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    store_name VARCHAR(150),
    description TEXT,
    commission_rate DECIMAL(5,2) DEFAULT 0.05, -- Upgraded: 0.05 = 5%
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. PRODUCTS
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    merchant_id INT,
    name VARCHAR(150),
    description TEXT,
    price DECIMAL(10,2),
    image VARCHAR(255),
    stock INT DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (merchant_id) REFERENCES merchants(id) ON DELETE CASCADE
);

-- 4. IDENTITY VERIFICATIONS (KYC)
CREATE TABLE user_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    cin VARCHAR(50),
    cin_image VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    verified_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 5. FINANCIAL PROFILES (Credit Scoring)
CREATE TABLE user_financials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    profession VARCHAR(100),
    salary DECIMAL(10,2),
    salary_proof VARCHAR(255), -- PDF/Image proof
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    credit_limit DECIMAL(10,2) DEFAULT 0.00, -- Automated: Salary * 1.5
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 6. ORDERS (Professional Marketplace Model)
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT,
    total_price DECIMAL(10,2),
    commission DECIMAL(10,2),        -- What the platform earns
    merchant_earning DECIMAL(10,2),  -- What the store earns
    status ENUM('active', 'paid', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- 7. INSTALLMENTS
CREATE TABLE installments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    amount DECIMAL(10,2),
    due_date DATE,
    status ENUM('pending', 'paid', 'overdue') DEFAULT 'pending',
    paid_at TIMESTAMP NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- 8. OTP CODES (Cross-Device 2FA Verification)
CREATE TABLE otp_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);