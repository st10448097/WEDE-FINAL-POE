<?php
// createTable.php - Drops and recreates user table from userData.txt
include 'DBConn.php';

echo "<h1>Creating user table from userData.txt</h1>";

// Drop user table if exists
mysqli_query($conn, "DROP TABLE IF EXISTS user");
echo "<p>✅ Dropped existing user table</p>";

// Create user table (matching your structure)
$sql = "CREATE TABLE user (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role VARCHAR(20) DEFAULT buyer',
    verified INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "<p>✅ user table created successfully</p>";
} else {
    die("Error creating table: " . mysqli_error($conn));
}

// Load data from userData.txt
if (file_exists("userData.txt")) {
    $file = fopen("userData.txt", "r");
    if ($file) {
        $count = 0;
        while (($line = fgets($file)) !== false) {
            $line = trim($line);
            if (!empty($line)) {
                $data = explode(",", $line);
                $name = mysqli_real_escape_string($conn, $data[0]);
                $email = mysqli_real_escape_string($conn, $data[1]);
                $password = mysqli_real_escape_string($conn, $data[2]);
                $phone = mysqli_real_escape_string($conn, $data[3]);
                $role = mysqli_real_escape_string($conn, $data[4]);
                $verified = (int)$data[5];
                
                $insert = "INSERT INTO user (name, email, password, phone, role, verified) 
                          VALUES ('$name', '$email', '$password', '$phone', '$role', $verified)";
                
                if (mysqli_query($conn, $insert)) {
                    echo "<p>✅ Added: $name ($role)</p>";
                    $count++;
                } else {
                    echo "<p>❌ Error adding $name: " . mysqli_error($conn) . "</p>";
                }
            }
        }
        fclose($file);
        echo "<h2>✅ Total $count users loaded from userData.txt!</h2>";
    } else {
        echo "<p>❌ Cannot open userData.txt</p>";
    }
} else {
    echo "<p>❌ userData.txt not found!</p>";
}

mysqli_close($conn);
echo "<br><a href='index.php'>Go to Login Page →</a>";
?>