<?php
// admin_diagnostic.php - Database diagnostic and setup tool for admin system
// Run this file directly in browser to verify or create required database tables
$conn = mysqli_connect("localhost", "root", "", "clothesstore");

// Terminate if database connection cannot be established
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "<h1>Admin Table Diagnostic</h1>";

// Check if admin table exists in the database
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'admin'");
if (mysqli_num_rows($tableCheck) > 0) {
    echo "<p style='color:green'>✅ Admin table exists</p>";
    
    // Display the column structure of the admin table for verification
    echo "<h3>Table Structure:</h3>";
    $columns = mysqli_query($conn, "SHOW COLUMNS FROM admin");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th></tr>";
    while ($col = mysqli_fetch_assoc($columns)) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td></tr>";
    }
    echo "</table>";
    
    // Retrieve and display all existing admin user records
    echo "<h3>Admin Records:</h3>";
    $admins = mysqli_query($conn, "SELECT * FROM admin");
    
    if (mysqli_num_rows($admins) > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Password (Stored)</th><th>Test MD5 of 'admin123'</th></tr>";
        while ($admin = mysqli_fetch_assoc($admins)) {
            // Generate MD5 hash of default password for comparison
            $testHash = md5('admin123');
            // Check if stored password matches expected MD5 hash
            $match = ($admin['password'] === $testHash) ? "✅ MATCH" : "❌ NO MATCH";
            echo "<tr>";
            echo "<td>{$admin['admin_id']}</td>";
            echo "<td>{$admin['name']}</td>";
            echo "<td>{$admin['email']}</td>";
            // Display stored hash in monospace font for readability
            echo "<td style='font-family:monospace;'>{$admin['password']}</td>";
            echo "<td style='font-family:monospace;'>$testHash<br>$match</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Perform login test using default credentials to verify authentication works
        echo "<h3>Testing Login:</h3>";
        $testEmail = "admin@example.com";
        $testPassword = "admin123";
        $testHash = md5($testPassword);
        
        // Build test query to simulate login authentication
        $testQuery = "SELECT * FROM admin WHERE email = '$testEmail' AND password = '$testHash'";
        $testResult = mysqli_query($conn, $testQuery);
        
        // Display the exact query being executed for debugging purposes
        echo "<p>Query: <code>$testQuery</code></p>";
        
        if (mysqli_num_rows($testResult) > 0) {
            // Login simulation succeeded - credentials match
            echo "<p style='color:green;font-size:20px;'>✅ LOGIN TEST SUCCESSFUL!</p>";
        } else {
            // Login simulation failed - diagnose the specific issue
            echo "<p style='color:red;font-size:20px;'>❌ LOGIN TEST FAILED!</p>";
            
            // Second test: Check if email exists at all (ignoring password)
            $testQuery2 = "SELECT * FROM admin WHERE email = '$testEmail'";
            $testResult2 = mysqli_query($conn, $testQuery2);
            
            if (mysqli_num_rows($testResult2) > 0) {
                // Email exists but password hash doesn't match expected value
                $row = mysqli_fetch_assoc($testResult2);
                echo "<p>Email found but password doesn't match!</p>";
                echo "<p>Stored hash: {$row['password']}</p>";
                echo "<p>Hash of 'admin123': $testHash</p>";
                echo "<p>They are different! Your stored password is NOT the MD5 hash of 'admin123'.</p>";
            } else {
                // Email not found in database at all
                echo "<p>Email 'admin@example.com' not found in database!</p>";
            }
        }
    } else {
        // Admin table exists but contains no records
        echo "<p style='color:red'>No admin records found!</p>";
        
        // Automatically create default admin account
        echo "<h3>Creating default admin...</h3>";
        // Insert default admin with MD5 hashed password
        $insertSql = "INSERT INTO admin (name, email, password) VALUES ('Admin User', 'admin@example.com', '" . md5('admin123') . "')";
        if (mysqli_query($conn, $insertSql)) {
            // Success - display default login credentials
            echo "<p style='color:green'>✅ Admin created! Email: admin@example.com, Password: admin123</p>";
        } else {
            // Database error during insertion
            echo "<p style='color:red'>Error creating admin: " . mysqli_error($conn) . "</p>";
        }
    }
} else {
    // Admin table does not exist - needs to be created
    echo "<p style='color:red'>❌ Admin table does NOT exist!</p>";
    echo "<p>Creating admin table...</p>";
    
    // Define table structure with appropriate columns and constraints
    $createTable = "CREATE TABLE admin (
        admin_id INT AUTO_INCREMENT PRIMARY KEY,  -- Unique identifier for each admin
        name VARCHAR(100) NOT NULL,               -- Admin display name
        email VARCHAR(100) NOT NULL UNIQUE,       -- Login email (must be unique)
        password VARCHAR(255) NOT NULL,            -- Stored password hash
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP  -- Auto-set creation timestamp
    )";
    
    if (mysqli_query($conn, $createTable)) {
        echo "<p style='color:green'>✅ Admin table created!</p>";
        
        // Insert default admin account after table creation
        $insertSql = "INSERT INTO admin (name, email, password) VALUES ('Admin User', 'admin@example.com', '" . md5('admin123') . "')";
        mysqli_query($conn, $insertSql);
        // Provide default credentials for first login
        echo "<p style='color:green'>✅ Default admin created! Email: admin@example.com, Password: admin123</p>";
    } else {
        // Display MySQL error if table creation fails
        echo "<p style='color:red'>Error: " . mysqli_error($conn) . "</p>";
    }
}

// Check if clothing table exists separately from admin table
$clothingTableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'clothing'");
if (mysqli_num_rows($clothingTableCheck) == 0) {
    // Clothing table missing - create it
    echo "<h3>Creating clothing table...</h3>";
    $createClothing = "CREATE TABLE clothing (
        clothing_id INT AUTO_INCREMENT PRIMARY KEY,   -- Unique product identifier
        name VARCHAR(200) NOT NULL,                   -- Product name
        category VARCHAR(50) NOT NULL,                 -- Product category (men/women/kids/accessories)
        price DECIMAL(10,2) NOT NULL,                  -- Price with 2 decimal places for cents
        stock INT DEFAULT 0,                           -- Inventory quantity, defaults to 0
        description TEXT,                              -- Product description (can be long text)
        image_url VARCHAR(500),                        -- URL path to product image
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP -- Auto-set creation timestamp
    )";
    
    if (mysqli_query($conn, $createClothing)) {
        echo "<p style='color:green'>✅ Clothing table created!</p>";
        
        // Insert sample clothing items to populate the new table with demo data
        $sampleItems = [
            ["Classic White T-Shirt", "men", 19.99, 50, "Classic cotton t-shirt for everyday wear", "https://via.placeholder.com/150"],
            ["Denim Jacket", "men", 59.99, 30, "Stylish denim jacket for all seasons", "https://via.placeholder.com/150"],
            ["Floral Summer Dress", "women", 49.99, 25, "Beautiful floral print summer dress", "https://via.placeholder.com/150"],
            ["Kids Hoodie", "kids", 29.99, 40, "Comfortable hoodie for kids", "https://via.placeholder.com/150"],
            ["Leather Belt", "accessories", 24.99, 100, "Genuine leather belt", "https://via.placeholder.com/150"]
        ];
        
        // Insert each sample item into the clothing table
        foreach ($sampleItems as $item) {
            $insertSql = "INSERT INTO clothing (name, category, price, stock, description, image_url) VALUES (
                '{$item[0]}', '{$item[1]}', {$item[2]}, {$item[3]}, '{$item[4]}', '{$item[5]}'
            )";
            mysqli_query($conn, $insertSql);
        }
        echo "<p style='color:green'>✅ Sample clothing items added!</p>";
    } else {
        // Display error if clothing table creation fails
        echo "<p style='color:red'>Error creating clothing table: " . mysqli_error($conn) . "</p>";
    }
} else {
    // Clothing table already exists - no action needed
    echo "<p style='color:green'>✅ Clothing table exists</p>";
}

// Close database connection to free resources
mysqli_close($conn);
// Navigation link to proceed to admin login page
echo "<br><a href='admin_login.php'>Go to Admin Login →</a>";
?>