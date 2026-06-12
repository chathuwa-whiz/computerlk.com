<?php
/**
 * Database Setup Script for Ecodez Store
 * Run this file once to create database tables and sample data
 */

// Database Configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'ecodestore';

// Create connection
$conn = new mysqli($host, $user, $pass);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "✅ Database created successfully or already exists<br>";
} else {
    die("❌ Error creating database: " . $conn->error);
}

// Select database
$conn->select_db($dbname);

// Create categories table
$sql = "CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    icon VARCHAR(50),
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Categories table created<br>";
} else {
    echo "❌ Error creating categories table: " . $conn->error . "<br>";
}

// Create products table
$sql = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    old_price DECIMAL(10, 2),
    stock INT DEFAULT 0,
    image VARCHAR(255),
    badge VARCHAR(20),
    rating DECIMAL(2, 1) DEFAULT 4.5,
    reviews_count INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Products table created<br>";
} else {
    echo "❌ Error creating products table: " . $conn->error . "<br>";
}

// Create orders table
$sql = "CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL,
    customer_id INT NULL,
    customer_name VARCHAR(100),
    customer_email VARCHAR(100),
    customer_phone VARCHAR(20),
    shipping_address TEXT,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'pending_payment', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_customer_id (customer_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Orders table created<br>";
} else {
    echo "❌ Error creating orders table: " . $conn->error . "<br>";
}

// Create order_items table
$sql = "CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Order items table created<br>";
} else {
    echo "❌ Error creating order items table: " . $conn->error . "<br>";
}

// Create admin_users table
$sql = "CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    full_name VARCHAR(100),
    role ENUM('admin', 'manager') DEFAULT 'admin',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Admin users table created<br>";
} else {
    echo "❌ Error creating admin users table: " . $conn->error . "<br>";
}

// Create customers table (for store login / Google login)
$sql = "CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255),
    name VARCHAR(100),
    phone VARCHAR(20),
    google_id VARCHAR(100),
    avatar VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_google_id (google_id),
    KEY idx_email (email)
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Customers table created<br>";
} else {
    echo "❌ Error creating customers table: " . $conn->error . "<br>";
}

// Customer delivery addresses (for logged-in users)
$sql = "CREATE TABLE IF NOT EXISTS customer_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    label VARCHAR(100) NOT NULL,
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255),
    city VARCHAR(100),
    postal_code VARCHAR(20),
    phone VARCHAR(20),
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_customer_id (customer_id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo "✅ Customer addresses table created<br>";
} else {
    echo "❌ Error creating customer_addresses table: " . $conn->error . "<br>";
}

// Add customer_id to orders if table already existed without it
@$conn->query("ALTER TABLE orders ADD COLUMN customer_id INT NULL AFTER order_number");
@$conn->query("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'pending_payment', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending'");

// Password reset tokens (for forgot password)
$sql = "CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_token (token),
    KEY idx_email (email)
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Password resets table created<br>";
} else {
    echo "❌ Error creating password_resets table: " . $conn->error . "<br>";
}

// Create testimonials table
$sql = "CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100),
    customer_location VARCHAR(100),
    rating INT DEFAULT 5,
    content TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Testimonials table created<br>";
} else {
    echo "❌ Error creating testimonials table: " . $conn->error . "<br>";
}

// Insert sample categories
$categories = [
    ['Charging Adapters', 'charging-adapters', 'lightning-charge', 'Fast and reliable charging solutions'],
    ['Hubs & Docks', 'hubs-docks', 'usb', 'Expand your connectivity options'],
    ['Cables', 'cables', 'cable-car', 'Durable and fast charging cables'],
    ['Power Banks', 'power-banks', 'battery-full', 'Stay powered on the go'],
    ['Wireless Chargers', 'wireless-chargers', 'wifi', 'Cable-free charging experience'],
    ['Headphones', 'headphones', 'headphones', 'Crystal clear audio experience'],
    ['Phone Cases', 'phone-cases', 'phone', 'Premium protection for your device'],
    ['Car Accessories', 'car-accessories', 'car-front', 'Stay connected while traveling']
];

foreach ($categories as $category) {
    $sql = "INSERT IGNORE INTO categories (name, slug, icon, description) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $category[0], $category[1], $category[2], $category[3]);
    $stmt->execute();
}
echo "✅ Sample categories inserted<br>";

// Insert sample products
$products = [
    [1, '20W Fast Charger', '20w-fast-charger', 'Type-C PD Fast Charging Adapter with 1m cable', 2500.00, 3000.00, 50, 'HOT', 4.5, 23],
    [2, '4-Port USB Hub', '4-port-usb-hub', 'High-speed 4-port USB 3.0 Hub for laptops', 3800.00, 4500.00, 35, 'NEW', 5.0, 45],
    [3, '20,000mAh Power Bank', '20000mah-power-bank', 'Fast charging power bank with dual output', 8500.00, 9500.00, 25, '', 4.0, 67],
    [4, 'Wireless Charger', 'wireless-charger', '15W fast wireless charging pad', 4200.00, 5000.00, 40, '', 4.8, 89],
    [5, 'Type-C Braided Cable', 'type-c-braided-cable', '2m braided Type-C cable', 1200.00, 1500.00, 100, 'SALE', 4.2, 156],
    [6, 'USB-C Hub Pro', 'usb-c-hub-pro', '7-in-1 USB-C Hub with HDMI', 5800.00, 6500.00, 30, 'HOT', 4.7, 78],
    [7, 'Headphones Wireless', 'headphones-wireless', 'Noise cancelling wireless headphones', 15000.00, 18000.00, 20, 'NEW', 4.6, 34],
    [8, 'iPhone Case Premium', 'iphone-case-premium', 'Premium protective case with stand', 2500.00, 3000.00, 80, '', 4.3, 112]
];

foreach ($products as $product) {
    $sql = "INSERT IGNORE INTO products (category_id, name, slug, description, price, old_price, stock, badge, rating, reviews_count) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssdddsdi", $product[0], $product[1], $product[2], $product[3], $product[4], $product[5], $product[6], $product[7], $product[8], $product[9]);
    $stmt->execute();
}
echo "✅ Sample products inserted<br>";

// Insert sample testimonials
$testimonials = [
    ['Kamal Perera', 'Colombo', 5, 'Ecodez is always a quality product. I ordered a charging adapter from the website. It arrived in two days. The product quality is excellent!'],
    ['Nimali Fernando', 'Kandy', 5, 'Friendly staff, quick delivery. I\'ve been using Ecodez products for quite some time. Never disappointed me! Keep it up team!'],
    ['Tharindu Silva', 'Gampaha', 4, 'Just received accessories and USB hub. I\'m super happy with the quality. Shipping was only 3 days. Items were packed really well. Great job!'],
    ['Thisara Janith', 'Kurunegala', 5, 'Good service with quick delivery. Products are genuine and work perfectly. Highly recommended!'],
    ['Naduni Perera', 'Moratuwa', 4, 'Great product quality and excellent customer service. Will definitely buy again!']
];

foreach ($testimonials as $testimonial) {
    $sql = "INSERT IGNORE INTO testimonials (customer_name, customer_location, rating, content) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssis", $testimonial[0], $testimonial[1], $testimonial[2], $testimonial[3]);
    $stmt->execute();
}
echo "✅ Sample testimonials inserted<br>";

// Insert default admin user (username: admin, password: admin123)
$admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "INSERT IGNORE INTO admin_users (username, password, email, full_name) VALUES ('admin', ?, 'admin@ecodestore.lk', 'Administrator')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $admin_pass);
$stmt->execute();
echo "✅ Default admin user created (username: admin, password: admin123)<br>";

echo "<br>========================================<br>";
echo "🎉 Database setup completed successfully!<br>";
echo "========================================<br>";
echo "<br>Next steps:<br>";
echo "1. Delete this file (database_setup.php) for security<br>";
echo "2. Visit your website: http://localhost/ecodestore<br>";
echo "<br>";

// Close connection
$conn->close();
?>