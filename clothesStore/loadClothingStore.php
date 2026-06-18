<?php
// loadClothingStore.php - Database initialization script
// Creates all 11 required tables if they don't exist and populates with sample data
include 'DBConn.php'; // Include database connection

echo "<h1>Setting up ClothingStore Database (11 Tables)</h1>";

// 1. user table - Stores registered user accounts (buyers and sellers)
// Primary authentication and profile table for the application
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS user (
    user_id INT AUTO_INCREMENT PRIMARY KEY,  -- Unique identifier for each user
    name VARCHAR(100) NOT NULL,              -- User's full name
    email VARCHAR(100) UNIQUE NOT NULL,      -- Email address (must be unique for login)
    password VARCHAR(255) NOT NULL,          -- Password stored as MD5 hash
    phone VARCHAR(20),                       -- Contact phone number (optional)
    role VARCHAR(20) DEFAULT 'buyer',        -- User role: 'buyer' or 'seller'
    verified INT DEFAULT 0,                  -- Account verification status (0=pending, 1=verified)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP  -- Auto-set account creation date
)");
echo "<p>✅ user table ready</p>";

// 2. admin table - Stores administrator accounts for system management
// Separate from user table for security and role isolation
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,  -- Unique identifier for each admin
    name VARCHAR(100) NOT NULL,               -- Admin display name
    email VARCHAR(100) UNIQUE NOT NULL,       -- Admin login email (must be unique)
    password VARCHAR(255) NOT NULL,            -- Admin password stored as hash
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP  -- Auto-set creation timestamp
)");
echo "<p>✅ admin table ready</p>";

// 3. clothing table - Main inventory of clothing items available for sale
// Central product catalog for the entire store
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS clothing (
    clothing_id INT AUTO_INCREMENT PRIMARY KEY,  -- Unique product identifier
    name VARCHAR(200) NOT NULL,                  -- Product display name
    category VARCHAR(50) NOT NULL,               -- Product category (men/women/kids/accessories)
    price DECIMAL(10,2) NOT NULL,                -- Price with 2 decimal places for cents
    stock INT DEFAULT 0,                         -- Available inventory quantity
    description TEXT,                            -- Detailed product description
    image_url VARCHAR(500),                      -- URL path to product image
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP  -- Auto-set listing date
)");
echo "<p>✅ clothing table ready</p>";

// 4. seller table - Extended profile information for seller users
// Links to user table and stores seller-specific data
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS seller (
    seller_id INT AUTO_INCREMENT PRIMARY KEY,  -- Unique seller identifier
    user_id INT NOT NULL,                      -- Reference to user table (who owns this seller profile)
    seller_name VARCHAR(100),                  -- Seller's store/display name
    profile_image VARCHAR(255),                -- URL to seller's profile image
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE  -- Delete seller profile if user is removed
)");
echo "<p>✅ seller table ready</p>";

// 5. seller_requests table - Tracks seller submissions for new clothing items
// Admin reviews these requests to approve or reject items for the store
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS seller_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,  -- Unique request identifier
    user_id INT NOT NULL,                       -- Seller who submitted the request
    clothing_name VARCHAR(200) NOT NULL,        -- Proposed item name
    brand VARCHAR(100),                         -- Brand of the clothing item
    description TEXT,                           -- Item description from seller
    image_url VARCHAR(500),                     -- URL to item photo
    price DECIMAL(10,2),                        -- Seller's proposed price
    category VARCHAR(50),                       -- Proposed category
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',  -- Review status
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Submission timestamp
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE  -- Remove requests if user deleted
)");
echo "<p>✅ seller_requests table ready</p>";

// 6. seller_clothing table - Junction table linking sellers to their clothing items
// Many-to-many relationship between sellers and clothing inventory
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS seller_clothing (
    seller_clothing_id INT AUTO_INCREMENT PRIMARY KEY,  -- Unique junction record ID
    seller_id INT NOT NULL,                             -- Reference to seller
    clothing_id INT NOT NULL,                           -- Reference to clothing item
    FOREIGN KEY (seller_id) REFERENCES seller(seller_id) ON DELETE CASCADE,     -- Cascade delete
    FOREIGN KEY (clothing_id) REFERENCES clothing(clothing_id) ON DELETE CASCADE  -- Cascade delete
)");
echo "<p>✅ seller_clothing table ready</p>";

// 7. address table - Stores shipping/billing addresses for users
// Users can have addresses stored for checkout convenience
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS address (
    address_id INT AUTO_INCREMENT PRIMARY KEY,  -- Unique address identifier
    user_id INT NOT NULL,                       -- User who owns this address
    street VARCHAR(255),                        -- Street address
    city VARCHAR(100),                          -- City name
    province VARCHAR(100),                      -- State/province
    country VARCHAR(100),                       -- Country name
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE  -- Remove addresses if user deleted
)");
echo "<p>✅ address table ready</p>";

// 8. product table - Alternative product listing table for sellers
// Stores detailed product information posted by sellers
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS product (
    product_id INT AUTO_INCREMENT PRIMARY KEY,  -- Unique product identifier
    seller_id INT NOT NULL,                     -- Seller who listed this product
    title VARCHAR(100),                         -- Product title
    description TEXT,                           -- Product description
    price DECIMAL(10,2),                        -- Product price
    size VARCHAR(20),                           -- Available size
    `condition` VARCHAR(50),                    -- Item condition (new, used, etc.) - backticks for reserved word
    category VARCHAR(50),                       -- Product category
    date_posted DATETIME,                       -- Listing date and time
    FOREIGN KEY (seller_id) REFERENCES seller(seller_id) ON DELETE CASCADE  -- Remove products if seller deleted
)");
echo "<p>✅ product table ready</p>";

// 9. message table - Stores communications between users
// Supports buyer-seller and admin-user messaging
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS message (
    message_id INT AUTO_INCREMENT PRIMARY KEY,  -- Unique message identifier
    sender_id INT NOT NULL,                     -- User who sent the message
    receiver_id INT NOT NULL,                   -- User who receives the message
    message_text TEXT,                          -- Message content
    time_sent DATETIME,                         -- Timestamp when message was sent
    FOREIGN KEY (sender_id) REFERENCES user(user_id) ON DELETE CASCADE,    -- Cascade if sender deleted
    FOREIGN KEY (receiver_id) REFERENCES user(user_id) ON DELETE CASCADE   -- Cascade if receiver deleted
)");
echo "<p>✅ message table ready</p>";

// 10. orders table - Records completed purchases by buyers
// Stores order summary information for buyer purchase history
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,  -- Unique order identifier
    buyer_id INT NOT NULL,                    -- Buyer who placed the order
    order_date DATETIME,                      -- Date and time of order
    total_price DECIMAL(10,2),                -- Total order amount
    status VARCHAR(50),                       -- Order status (pending, shipped, delivered, etc.)
    FOREIGN KEY (buyer_id) REFERENCES user(user_id) ON DELETE CASCADE  -- Remove orders if buyer deleted
)");
echo "<p>✅ orders table ready</p>";

// 11. order table - Alternative orders table (duplicate structure for compatibility)
// Uses backticks because "order" is a MySQL reserved word
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `order` (
    order_id INT AUTO_INCREMENT PRIMARY KEY,  -- Unique order identifier
    buyer_id INT NOT NULL,                    -- Buyer who placed the order
    order_date DATETIME,                      -- Order date and time
    total_price DECIMAL(10,2),                -- Total order amount
    status VARCHAR(50),                       -- Order status
    FOREIGN KEY (buyer_id) REFERENCES user(user_id) ON DELETE CASCADE  -- Cascade on user deletion
)");
echo "<p>✅ order table ready</p>";

// Load sample data from text file if user table is empty
$check = mysqli_query($conn, "SELECT COUNT(*) as count FROM user");
$row = mysqli_fetch_assoc($check);
if ($row['count'] == 0 && file_exists("userData.txt")) {
    // Open and read the user data file line by line
    $file = fopen("userData.txt", "r");
    while (($line = fgets($file)) !== false) {
        $line = trim($line); // Remove whitespace and newlines
        if (!empty($line)) {
            // Parse comma-separated values: name,email,password,phone,role,verified
            $data = explode(",", $line);
            mysqli_query($conn, "INSERT INTO user (name, email, password, phone, role, verified) 
                                VALUES ('{$data[0]}', '{$data[1]}', '{$data[2]}', '{$data[3]}', '{$data[4]}', {$data[5]})");
        }
    }
    fclose($file); // Close file handle
    echo "<p>✅ Sample users loaded from userData.txt</p>";
}

// Insert default admin account if it doesn't already exist
$adminCheck = mysqli_query($conn, "SELECT * FROM admin WHERE email='admin@example.com'");
if (mysqli_num_rows($adminCheck) == 0) {
    // Create default admin with known credentials for initial access
    mysqli_query($conn, "INSERT INTO admin (name, email, password) VALUES 
                        ('Admin User', 'admin@example.com', MD5('admin123'))");
    echo "<p>✅ Default admin created (admin@example.com / admin123)</p>";
}

// Insert sample clothing items if the clothing table is empty
$clothCheck = mysqli_query($conn, "SELECT COUNT(*) as count FROM clothing");
$clothRow = mysqli_fetch_assoc($clothCheck);
if ($clothRow['count'] == 0) {
    // Add 5 sample products across different categories for initial store display
    mysqli_query($conn, "INSERT INTO clothing (name, category, price, stock, description) VALUES
        ('Classic White T-Shirt', 'men', 19.99, 50, 'Comfortable cotton t-shirt'),
        ('Denim Jacket', 'men', 59.99, 30, 'Stylish blue denim jacket'),
        ('Floral Summer Dress', 'women', 49.99, 25, 'Beautiful floral print dress'),
        ('Kids Hoodie', 'kids', 29.99, 40, 'Warm hoodie for kids'),
        ('Leather Belt', 'accessories', 24.99, 100, 'Genuine leather belt')");
    echo "<p>✅ Sample clothing added</p>";
}

// Close database connection
mysqli_close($conn);
// Confirmation message with all tables created
echo "<h2>✅ All 11 tables are ready!</h2>";
// Navigation link to proceed to login page
echo "<a href='index.php'>Go to Login →</a>";
?>