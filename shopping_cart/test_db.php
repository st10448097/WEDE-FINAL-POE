<?php
echo "<!DOCTYPE html>
<html>
<head>
    <title>ClothesStore Database Test</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .success { color: green; }
        .error { color: red; }
        .box { background: white; padding: 20px; margin: 20px 0; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #7cb9e8; color: white; }
    </style>
</head>
<body>
<h1>🔍 ClothesStore Database Diagnostic</h1>";

// Test connection
$conn = new mysqli("localhost", "root", "", "clothesstore");

if ($conn->connect_error) {
    die("<div class='error box'>❌ Connection failed: " . $conn->connect_error . "</div></body></html>");
}
echo "<div class='success box'>✅ Successfully connected to database: <strong>clothesstore</strong></div>";

// Show all tables
echo "<div class='box'><h3>📊 Tables in clothesstore database:</h3>";
$tables = $conn->query("SHOW TABLES");
if ($tables && $tables->num_rows > 0) {
    while ($row = $tables->fetch_array()) {
        echo "<strong>📁 Table: " . $row[0] . "</strong><br>";
        
        // Show columns for each table
        $columns = $conn->query("SHOW COLUMNS FROM " . $row[0]);
        if ($columns) {
            echo "<table>";
            echo "<tr><th>Column</th><th>Type</th><th>Key</th></tr>";
            while ($col = $columns->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $col['Field'] . "</td>";
                echo "<td>" . $col['Type'] . "</td>";
                echo "<td>" . ($col['Key'] ?: '-') . "</td>";
                echo "</tr>";
            }
            echo "</table><br>";
        }
    }
} else {
    echo "<div class='error'>❌ No tables found! Run createTable.php first.</div>";
}
echo "</div>";

// Check admin table specifically
echo "<div class='box'><h3>👤 Admin Table Check:</h3>";
$admin_check = $conn->query("SELECT * FROM admin");
if ($admin_check) {
    if ($admin_check->num_rows > 0) {
        echo "<div class='success'>✅ Admin table exists with " . $admin_check->num_rows . " admin(s)</div>";
        echo "<table><tr><th>ID</th><th>Name</th><th>Email</th><th>Password Hash</th></tr>";
        while ($admin = $admin_check->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $admin['admin_id'] . "</td>";
            echo "<td>" . $admin['name'] . "</td>";
            echo "<td>" . $admin['email'] . "</td>";
            echo "<td style='font-size: 0.8em;'>" . $admin['password'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>⚠️ Admin table exists but is EMPTY</div>";
    }
} else {
    echo "<div class='error'>❌ Admin table does NOT exist<br>";
    echo "Error: " . $conn->error . "</div>";
}
echo "</div>";

// Check user table
echo "<div class='box'><h3>👥 User Table Check:</h3>";
$user_check = $conn->query("SELECT * FROM user");
if ($user_check) {
    if ($user_check->num_rows > 0) {
        echo "<div class='success'>✅ user table exists with " . $user_check->num_rows . " user(s)</div>";
        echo "<table><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Verified</th></tr>";
        while ($user = $user_check->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $user['user_id'] . "</td>";
            echo "<td>" . $user['name'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td>" . $user['role'] . "</td>";
            echo "<td>" . ($user['verified'] ? '✅ Yes' : '❌ No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>⚠️ user table exists but is EMPTY</div>";
    }
} else {
    echo "<div class='error'>❌ user table does NOT exist</div>";
}
echo "</div>";

echo "<a href='createTable.php' style='background: #7cb9e8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Run createTable.php</a> ";
echo "<a href='index.php' style='background: #9b7ec4; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login</a>";

$conn->close();
echo "</body></html>";
?>