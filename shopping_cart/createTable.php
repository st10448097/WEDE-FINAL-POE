```php
<?php
// createTable.php - Database table initialization and data import script
// Drops and recreates the user table, then populates it from a text file
include 'DBConn.php'; // Include database connection

echo "<h1>Creating user table from userData.txt</h1>";

// Remove existing user table to start fresh (prevents duplicate data)
mysqli_query($conn, "DROP TABLE IF EXISTS user");
echo "<p>✅ Dropped existing user table</p>";

// Define the user table structure with all required columns
$sql = "CREATE TABLE user (
    user_id INT AUTO_INCREMENT PRIMARY KEY,  -- Unique identifier for each user, auto-increments
    name VARCHAR(100) NOT NULL,              -- User's full name (required)
    email VARCHAR(100) UNIQUE NOT NULL,      -- Email address, must be unique (required)
    password VARCHAR(255) NOT NULL,          -- Password stored as hash (required)
    phone VARCHAR(20),                       -- Contact phone number (optional)
    role VARCHAR(20) DEFAULT 'buyer',        -- User role: 'buyer' or 'seller', defaults to buyer
    verified INT DEFAULT 0,                  -- Verification status: 0 = pending, 1 = verified
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP  -- Auto-set account creation timestamp
)";

// Execute table creation and verify success
if (mysqli_query($conn, $sql)) {
    echo "<p>✅ user table created successfully</p>";
} else {
    // Terminate script if table creation fails (critical error)
    die("Error creating table: " . mysqli_error($conn));
}

// Check if the data source file exists before attempting to read
if (file_exists("userData.txt")) {
    // Open the text file for reading
    $file = fopen("userData.txt", "r");
    if ($file) {
        $count = 0; // Counter for successfully imported users
        // Read file line by line until end of file
        while (($line = fgets($file)) !== false) {
            // Remove whitespace and newline characters from the line
            $line = trim($line);
            // Skip empty lines in the file
            if (!empty($line)) {
                // Split comma-separated values into array
                // Expected format: name,email,password,phone,role,verified
                $data = explode(",", $line);
                // Sanitize each field to prevent SQL injection
                $name = mysqli_real_escape_string($conn, $data[0]);
                $email = mysqli_real_escape_string($conn, $data[1]);
                $password = mysqli_real_escape_string($conn, $data[2]);
                $phone = mysqli_real_escape_string($conn, $data[3]);
                $role = mysqli_real_escape_string($conn, $data[4]);
                // Convert verified value to integer (0 or 1)
                $verified = (int)$data[5];
                
                // Insert user data into the newly created table
                $insert = "INSERT INTO user (name, email, password, phone, role, verified) 
                          VALUES ('$name', '$email', '$password', '$phone', '$role', $verified)";
                
                // Execute insert and provide feedback for each record
                if (mysqli_query($conn, $insert)) {
                    echo "<p>✅ Added: $name ($role)</p>";
                    $count++; // Increment success counter
                } else {
                    // Display error for failed inserts (e.g., duplicate email)
                    echo "<p>❌ Error adding $name: " . mysqli_error($conn) . "</p>";
                }
            }
        }
        // Close the file handle to free system resources
        fclose($file);
        // Display total count of successfully imported users
        echo "<h2>✅ Total $count users loaded from userData.txt!</h2>";
    } else {
        // File exists but cannot be opened (permission issue)
        echo "<p>❌ Cannot open userData.txt</p>";
    }
} else {
    // Data file not found in the expected location
    echo "<p>❌ userData.txt not found!</p>";
}

// Close database connection to free resources
mysqli_close($conn);
// Navigation link to proceed to the login page
echo "<br><a href='index.php'>Go to Login Page →</a>";
?>