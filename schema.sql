-- Create Database
CREATE DATABASE IF NOT EXISTS homeclone_db;
USE homeclone_db;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    address TEXT,
    phone VARCHAR(20),
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories Table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products Table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    old_price DECIMAL(10,2),
    category_id INT,
    image VARCHAR(255),
    stock_quantity INT DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0,
    featured BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Wishlist Table
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    UNIQUE KEY unique_wishlist (user_id, product_id)
);

-- Cart Table
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    UNIQUE KEY unique_cart (user_id, product_id)
);

-- Orders Table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    billing_address TEXT NOT NULL,
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Order Items Table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Insert Sample Data
INSERT INTO categories (name, description, image) VALUES
('Living Room', 'Comfortable and stylish living room furniture', 'living-room.jpg'),
('Bedroom', 'Cozy bedroom sets and accessories', 'bedroom.jpg'),
('Dining Room', 'Elegant dining tables and chairs', 'dining-room.jpg'),
('Home Office', 'Productive home office solutions', 'home-office.jpg');

INSERT INTO products (name, description, price, old_price, category_id, image, stock_quantity, rating, featured) VALUES
('Modern Sofa Set', '3-seater modern sofa with premium fabric and comfortable cushions', 899.99, 1099.99, 1, 'sofa-set.jpg', 15, 4.5, TRUE),
('Leather Recliner', 'Premium leather recliner chair with massage function', 499.99, 599.99, 1, 'recliner.jpg', 8, 4.7, TRUE),
('King Size Bed', 'Solid wood king size bed with storage drawers', 1299.99, 1499.99, 2, 'king-bed.jpg', 5, 4.8, TRUE),
('Dining Table Set', '6-seater wooden dining table with upholstered chairs', 799.99, 899.99, 3, 'dining-set.jpg', 12, 4.4, FALSE),
('Office Desk', 'Ergonomic L-shaped office desk with cable management', 349.99, NULL, 4, 'office-desk.jpg', 20, 4.6, TRUE),
('Bookshelf', '5-tier wooden bookshelf with adjustable shelves', 199.99, 249.99, 4, 'bookshelf.jpg', 18, 4.3, FALSE),
('Coffee Table', 'Glass top coffee table with wooden legs', 149.99, 179.99, 1, 'coffee-table.jpg', 10, 4.2, FALSE),
('Wardrobe Cabinet', '3-door wardrobe with mirror and storage', 699.99, 799.99, 2, 'wardrobe.jpg', 6, 4.5, TRUE),
('TV Stand', 'Modern TV stand with storage compartments', 299.99, 349.99, 1, 'tv-stand.jpg', 14, 4.1, FALSE),
('Office Chair', 'Ergonomic office chair with lumbar support', 249.99, 299.99, 4, 'office-chair.jpg', 25, 4.7, TRUE),
('Night Stand', 'Bedside table with drawer and shelf', 89.99, 99.99, 2, 'night-stand.jpg', 22, 4.0, FALSE),
('Bar Stool', 'Adjustable height bar stool with backrest', 129.99, 149.99, 3, 'bar-stool.jpg', 16, 4.3, FALSE);

-- Insert Admin User (password: admin123)
INSERT INTO users (username, email, password, first_name, last_name, is_admin) VALUES
('admin', 'admin@homeclone.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', TRUE);

-- Insert Regular User (password: user123)
INSERT INTO users (username, email, password, first_name, last_name) VALUES
('john_doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Doe');