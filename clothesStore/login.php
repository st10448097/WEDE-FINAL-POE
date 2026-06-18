<?php
// login.php - Alternative user login handler using object-oriented MySQLi
// Authenticates users, checks verification status, and redirects to dashboard
session_start(); // Start session to persist user authentication data
include 'DBConn.php'; // Include database connection

// Initialize feedback message variables
$error = '';    // Stores error messages for failed login attempts
$success = '';  // Stores success message (used before redirect)

// Process login form when submitted via POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve user credentials from form submission
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Prepare statement using object-oriented MySQLi syntax
    // Searches for user by email (unique identifier in database)
    $stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
    $stmt->bind_param("s", $email); // Bind email parameter as string type
    $stmt->execute();               // Execute the prepared query
    $result = $stmt->get_result();  // Get the result set
    
    // Check if any user was found with the provided email
    if ($result->num_rows > 0) {
        // Fetch the user record as an associative array
        $user = $result->fetch_assoc();
        
        // Compare submitted password directly with stored value
        // Note: This assumes passwords are stored as plain MD5 hashes or plaintext
        // For production, use password_verify() with bcrypt hashing instead
        if ($password === $user['password']) {
            // Check if the account has been verified by an administrator
            if ($user['verified'] == 1) {
                // Store essential user data in session for persistent login
                $_SESSION['user_id'] = $user['user_id'];     // Unique user identifier
                $_SESSION['user_name'] = $user['name'];      // User's display name
                $_SESSION['user_role'] = $user['role'];      // 'buyer' or 'seller' role
                
                // Set success message (briefly visible before redirect)
                $success = "User " . $user['name'] . " is logged in";
                
                // Redirect to role-based dashboard after successful authentication
                header("Location: dashboard.php");
                exit(); // Terminate script to prevent further execution after redirect
            } else {
                // Account exists but requires admin approval before access
                $error = "Your account is pending verification by an administrator.";
            }
        } else {
            // Password does not match the stored password for this email
            $error = "Invalid password!";
        }
    } else {
        // No user account found with the provided email address
        $error = "User not found!";
    }
    // Close the prepared statement to free database resources
    $stmt->close();
}
?>