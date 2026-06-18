<?php
// test_db.php - Database diagnostic and inspection tool
// Displays all tables, their structures, and sample data for verification purposes
echo "<!DOCTYPE html>
<html>
<head>
    <title>ClothesStore Database Test</title>
    <style>
        /* Base body styling with light gray background */
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        /* Green text for success messages */
        .success { color: green; }
        /* Red text for error messages */
        .error { color: red; }
        /* White card-style container for each section */
        .box { background: white; padding: 20px; margin: 20px 0; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        /* Collapsed border table for data display */
        table { border-collapse: collapse; width: 100%; }
        /* Table cell styling with padding and light borders */
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        /* Blue header row with white text */
        th { background: #7cb9e8; color: white; }
    </style>
</head>
<body>
<h1>🔍 ClothesStore Database Diagnostic</h1>";

// Test database connection using object-oriented MySQLi
$conn = new mysqli("localhost", "root", "", "clothesstore");

// Terminate script with error message if connection fails
if ($conn->connect_error) {
    die("<div class='error box'>❌ Connection failed: " . $conn->connect_error . "</div></body></html>");
}
// Confirm successful connection to the clothesstore database
echo "<div class='success box'>✅ Successfully connected to database: <strong>clothesstore</strong></div>";

// Show all tables in the database with their column structures
echo "<div class='box'><h3>📊 Tables in clothesstore database:</h3>";
// Query to list all tables in the current database
$tables = $conn->query("SHOW TABLES");
if ($tables && $tables->num_rows > 0) {
    // Iterate through each table found in the database
    while ($row = $tables->fetch_array()) {
        echo "<strong>📁 Table: " . $row[0] . "</strong><br>";
        
        // Retrieve and display column information for each table
        $columns = $conn->query("SHOW COLUMNS FROM " . $row[0]);
        if ($columns) {
            echo "<table>";
            // Table header showing column name, data type, and key status
            echo "<tr><th>Column</th><th>Type</th><th>Key</th></tr>";
            while ($col = $columns->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $col['Field'] . "</td>"; // Column name
                echo "<td>" . $col['Type'] . "</td>";   // Data type (e.g., INT, VARCHAR)
                echo "<td>" . ($col['Key'] ?: '-') . "</td>"; // Key status (PRI, UNI, etc.) or dash
                echo "</tr>";
            }
            echo "</table><br>";
        }
    }
} else {
    // Message displayed when no tables exist in the database
    echo "<div class='error'>❌ No tables found! Run createTable.php first.</div>";
}
echo "</div>";

// Check admin table specifically - shows all admin accounts
echo "<div class='box'><h3>👤 Admin Table Check:</h3>";
$admin_check = $conn->query("SELECT * FROM admin");
if ($admin_check) {
    if ($admin_check->num_rows > 0) {
        // Admin table exists and contains records
        echo "<div class='success'>✅ Admin table exists with " . $admin_check->num_rows . " admin(s)</div>";
        echo "<table><tr><th>ID</th><th>Name</th><th>Email</th><th>Password Hash</th></tr>";
        // Display each admin record (password hash is shown for debugging)
        while ($admin = $admin_check->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $admin['admin_id'] . "</td>";    // Admin ID
            echo "<td>" . $admin['name'] . "</td>";          // Admin display name
            echo "<td>" . $admin['email'] . "</td>";         // Admin email (login credential)
            echo "<td style='font-size: 0.8em;'>" . $admin['password'] . "</td>"; // Stored password hash
            echo "</tr>";
        }
        echo "</table>";
    } else {
        // Admin table structure exists but contains no records
        echo "<div class='error'>⚠️ Admin table exists but is EMPTY</div>";
    }
} else {
    // Admin table was not created - displays MySQL error for debugging
    echo "<div class='error'>❌ Admin table does NOT exist<br>";
    echo "Error: " . $conn->error . "</div>";
}
echo "</div>";

// Check user table - shows all registered user accounts
echo "<div class='box'><h3>👥 User Table Check:</h3>";
$user_check = $conn->query("SELECT * FROM user");
if ($user_check) {
    if ($user_check->num_rows > 0) {
        // User table exists and contains registered users
        echo "<div class='success'>✅ user table exists with " . $user_check->num_rows . " user(s)</div>";
        echo "<table><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Verified</th></tr>";
        // Display each user record with verification status indicator
        while ($user = $user_check->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $user['user_id'] . "</td>";                                    // User ID
            echo "<td>" . $user['name'] . "</td>";                                       // User's full name
            echo "<td>" . $user['email'] . "</td>";                                      // User email
            echo "<td>" . $user['role'] . "</td>";                                       // Account role (buyer/seller)
            echo "<td>" . ($user['verified'] ? '✅ Yes' : '❌ No') . "</td>";            // Verification status
            echo "</tr>";
        }
        echo "</table>";
    } else {
        // User table structure exists but no users are registered
        echo "<div class='error'>⚠️ user table exists but is EMPTY</div>";
    }
} else {
    // User table query failed - table likely doesn't exist
    echo "<div class='error'>❌ user table does NOT exist</div>";
}
echo "</div>";

// Navigation links for common next actions
// Link to create tables and populate sample data
echo "<a href='createTable.php' style='background: #7cb9e8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Run createTable.php</a> ";
// Link to return to the login page
echo "<a href='index.php' style='background: #9b7ec4; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login</a>";

// Close database connection to free resources
$conn->close();
echo "</body></html>";
?>