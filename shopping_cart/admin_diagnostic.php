<?php
// admin_diagnostic.php - Run this file to check/setup the admin table
$conn = mysqli_connect("localhost", "root", "", "clothesstore");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "<h1>Admin Table Diagnostic</h1>";

// Check if admin table exists
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'admin'");
if (mysqli_num_rows($tableCheck) > 0) {
    echo "<p style='color:green'>✅ Admin table exists</p>";
    
    // Show table structure
    echo "<h3>Table Structure:</h3>";
    $columns = mysqli_query($conn, "SHOW COLUMNS FROM admin");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th></tr>";
    while ($col = mysqli_fetch_assoc($columns)) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td></tr>";
    }
    echo "</table>";
    
    // Show all admin records
    echo "<h3>Admin Records:</h3>";
    $admins = mysqli_query($conn, "SELECT * FROM admin");
    
    if (mysqli_num_rows($admins) > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Password (Stored)</th><th>Test MD5 of 'admin123'</th></tr>";
        while ($admin = mysqli_fetch_assoc($admins)) {
            $testHash = md5('admin123');
            $match = ($admin['password'] === $testHash) ? "✅ MATCH" : "❌ NO MATCH";
            echo "<tr>";
            echo "<td>{$admin['admin_id']}</td>";
            echo "<td>{$admin['name']}</td>";
            echo "<td>{$admin['email']}</td>";
            echo "<td style='font-family:monospace;'>{$admin['password']}</td>";
            echo "<td style='font-family:monospace;'>$testHash<br>$match</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Test login with admin123
        echo "<h3>Testing Login:</h3>";
        $testEmail = "admin@example.com";
        $testPassword = "admin123";
        $testHash = md5($testPassword);
        
        $testQuery = "SELECT * FROM admin WHERE email = '$testEmail' AND password = '$testHash'";
        $testResult = mysqli_query($conn, $testQuery);
        
        echo "<p>Query: <code>$testQuery</code></p>";
        
        if (mysqli_num_rows($testResult) > 0) {
            echo "<p style='color:green;font-size:20px;'>✅ LOGIN TEST SUCCESSFUL!</p>";
        } else {
            echo "<p style='color:red;font-size:20px;'>❌ LOGIN TEST FAILED!</p>";
            
            // Try without password
            $testQuery2 = "SELECT * FROM admin WHERE email = '$testEmail'";
            $testResult2 = mysqli_query($conn, $testQuery2);
            
            if (mysqli_num_rows($testResult2) > 0) {
                $row = mysqli_fetch_assoc($testResult2);
                echo "<p>Email found but password doesn't match!</p>";
                echo "<p>Stored hash: {$row['password']}</p>";
                echo "<p>Hash of 'admin123': $testHash</p>";
                echo "<p>They are different! Your stored password is NOT the MD5 hash of 'admin123'.</p>";
            } else {
                echo "<p>Email 'admin@example.com' not found in database!</p>";
            }
        }
    } else {
        echo "<p style='color:red'>No admin records found!</p>";
        
        // Insert default admin
        echo "<h3>Creating default admin...</h3>";
        $insertSql = "INSERT INTO admin (name, email, password) VALUES ('Admin User', 'admin@example.com', '" . md5('admin123') . "')";
        if (mysqli_query($conn, $insertSql)) {
            echo "<p style='color:green'>✅ Admin created! Email: admin@example.com, Password: admin123</p>";
        } else {
            echo "<p style='color:red'>Error creating admin: " . mysqli_error($conn) . "</p>";
        }
    }
} else {
    echo "<p style='color:red'>❌ Admin table does NOT exist!</p>";
    echo "<p>Creating admin table...</p>";
    
    $createTable = "CREATE TABLE admin (
        admin_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($conn, $createTable)) {
        echo "<p style='color:green'>✅ Admin table created!</p>";
        
        // Insert default admin
        $insertSql = "INSERT INTO admin (name, email, password) VALUES ('Admin User', 'admin@example.com', '" . md5('admin123') . "')";
        mysqli_query($conn, $insertSql);
        echo "<p style='color:green'>✅ Default admin created! Email: admin@example.com, Password: admin123</p>";
    } else {
        echo "<p style='color:red'>Error: " . mysqli_error($conn) . "</p>";
    }
}

// Check if clothing table exists
$clothingTableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'clothing'");
if (mysqli_num_rows($clothingTableCheck) == 0) {
    echo "<h3>Creating clothing table...</h3>";
    $createClothing = "CREATE TABLE clothing (
        clothing_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        category VARCHAR(50) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        stock INT DEFAULT 0,
        description TEXT,
        image_url VARCHAR(500),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($conn, $createClothing)) {
        echo "<p style='color:green'>✅ Clothing table created!</p>";
        
        // Insert sample clothing items
        $sampleItems = [
            ["Classic White T-Shirt", "men", 19.99, 50, "Classic cotton t-shirt for everyday wear", "https://via.placeholder.com/150"],
            ["Denim Jacket", "men", 59.99, 30, "Stylish denim jacket for all seasons", "https://via.placeholder.com/150"],
            ["Floral Summer Dress", "women", 49.99, 25, "Beautiful floral print summer dress", "https://via.placeholder.com/150"],
            ["Kids Hoodie", "kids", 29.99, 40, "Comfortable hoodie for kids", "https://via.placeholder.com/150"],
            ["Leather Belt", "accessories", 24.99, 100, "Genuine leather belt", "https://via.placeholder.com/150"]
        ];
        
        foreach ($sampleItems as $item) {
            $insertSql = "INSERT INTO clothing (name, category, price, stock, description, image_url) VALUES (
                '{$item[0]}', '{$item[1]}', {$item[2]}, {$item[3]}, '{$item[4]}', '{$item[5]}'
            )";
            mysqli_query($conn, $insertSql);
        }
        echo "<p style='color:green'>✅ Sample clothing items added!</p>";
    } else {
        echo "<p style='color:red'>Error creating clothing table: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color:green'>✅ Clothing table exists</p>";
}

mysqli_close($conn);
echo "<br><a href='admin_login.php'>Go to Admin Login →</a>";
?>