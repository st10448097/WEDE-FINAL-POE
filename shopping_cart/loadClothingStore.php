<?php
// loadClothingStore.php - Creates all tables if they don't exist
include 'DBConn.php';

echo "<h1>Setting up ClothingStore Database (11 Tables)</h1>";

// 1. user table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS user (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role VARCHAR(20) DEFAULT 'buyer',
    verified INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
echo "<p>✅ user table ready</p>";

// 2. admin table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
echo "<p>✅ admin table ready</p>";

// 3. clothing table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS clothing (
    clothing_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    category VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    description TEXT,
    image_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
echo "<p>✅ clothing table ready</p>";

// 4. seller table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS seller (
    seller_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    seller_name VARCHAR(100),
    profile_image VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
)");
echo "<p>✅ seller table ready</p>";

// 5. seller_requests table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS seller_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    clothing_name VARCHAR(200) NOT NULL,
    brand VARCHAR(100),
    description TEXT,
    image_url VARCHAR(500),
    price DECIMAL(10,2),
    category VARCHAR(50),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
)");
echo "<p>✅ seller_requests table ready</p>";

// 6. seller_clothing table (junction table)
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS seller_clothing (
    seller_clothing_id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    clothing_id INT NOT NULL,
    FOREIGN KEY (seller_id) REFERENCES seller(seller_id) ON DELETE CASCADE,
    FOREIGN KEY (clothing_id) REFERENCES clothing(clothing_id) ON DELETE CASCADE
)");
echo "<p>✅ seller_clothing table ready</p>";

// 7. address table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS address (
    address_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    street VARCHAR(255),
    city VARCHAR(100),
    province VARCHAR(100),
    country VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
)");
echo "<p>✅ address table ready</p>";

// 8. product table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS product (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    title VARCHAR(100),
    description TEXT,
    price DECIMAL(10,2),
    size VARCHAR(20),
    `condition` VARCHAR(50),
    category VARCHAR(50),
    date_posted DATETIME,
    FOREIGN KEY (seller_id) REFERENCES seller(seller_id) ON DELETE CASCADE
)");
echo "<p>✅ product table ready</p>";

// 9. message table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS message (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message_text TEXT,
    time_sent DATETIME,
    FOREIGN KEY (sender_id) REFERENCES user(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES user(user_id) ON DELETE CASCADE
)");
echo "<p>✅ message table ready</p>";

// 10. orders table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    order_date DATETIME,
    total_price DECIMAL(10,2),
    status VARCHAR(50),
    FOREIGN KEY (buyer_id) REFERENCES user(user_id) ON DELETE CASCADE
)");
echo "<p>✅ orders table ready</p>";

// 11. order table (if separate)
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `order` (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    order_date DATETIME,
    total_price DECIMAL(10,2),
    status VARCHAR(50),
    FOREIGN KEY (buyer_id) REFERENCES user(user_id) ON DELETE CASCADE
)");
echo "<p>✅ order table ready</p>";

// Load sample data if tables are empty
$check = mysqli_query($conn, "SELECT COUNT(*) as count FROM user");
$row = mysqli_fetch_assoc($check);
if ($row['count'] == 0 && file_exists("userData.txt")) {
    $file = fopen("userData.txt", "r");
    while (($line = fgets($file)) !== false) {
        $line = trim($line);
        if (!empty($line)) {
            $data = explode(",", $line);
            mysqli_query($conn, "INSERT INTO user (name, email, password, phone, role, verified) 
                                VALUES ('{$data[0]}', '{$data[1]}', '{$data[2]}', '{$data[3]}', '{$data[4]}', {$data[5]})");
        }
    }
    fclose($file);
    echo "<p>✅ Sample users loaded from userData.txt</p>";
}

// Insert default admin if not exists
$adminCheck = mysqli_query($conn, "SELECT * FROM admin WHERE email='admin@example.com'");
if (mysqli_num_rows($adminCheck) == 0) {
    mysqli_query($conn, "INSERT INTO admin (name, email, password) VALUES 
                        ('Admin User', 'admin@example.com', MD5('admin123'))");
    echo "<p>✅ Default admin created (admin@example.com / admin123)</p>";
}

// Insert sample clothing if empty
$clothCheck = mysqli_query($conn, "SELECT COUNT(*) as count FROM clothing");
$clothRow = mysqli_fetch_assoc($clothCheck);
if ($clothRow['count'] == 0) {
    mysqli_query($conn, "INSERT INTO clothing (name, category, price, stock, description) VALUES
        ('Classic White T-Shirt', 'men', 19.99, 50, 'Comfortable cotton t-shirt'),
        ('Denim Jacket', 'men', 59.99, 30, 'Stylish blue denim jacket'),
        ('Floral Summer Dress', 'women', 49.99, 25, 'Beautiful floral print dress'),
        ('Kids Hoodie', 'kids', 29.99, 40, 'Warm hoodie for kids'),
        ('Leather Belt', 'accessories', 24.99, 100, 'Genuine leather belt')");
    echo "<p>✅ Sample clothing added</p>";
}

mysqli_close($conn);
echo "<h2>✅ All 11 tables are ready!</h2>";
echo "<a href='index.php'>Go to Login →</a>";
?>