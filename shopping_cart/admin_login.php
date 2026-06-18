```php
<?php
// admin_login.php - Admin authentication page
// Handles admin login form submission and session creation
session_start(); // Start session to store admin authentication data
include 'DBConn.php'; // Include database connection file

// Initialize error message variable as empty string
$error = '';

// Process login form when submitted via POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve email from form input
    $email = $_POST['email'];
    // Hash password with MD5 to match stored hash in database
    // Note: MD5 is used for consistency with existing database records
    $password = md5($_POST['password']);
    
    // Prepare parameterized query to prevent SQL injection
    // Verifies both email and password match a record in admin table
    $stmt = mysqli_prepare($conn, "SELECT * FROM admin WHERE email = ? AND password = ?");
    // Bind both parameters as strings (type "ss")
    mysqli_stmt_bind_param($stmt, "ss", $email, $password);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Check if exactly one matching admin record was found
    if (mysqli_num_rows($result) > 0) {
        // Fetch admin user data from result set
        $admin = mysqli_fetch_assoc($result);
        // Store admin identifier and display name in session for persistence
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_name'] = $admin['name'];
        // Redirect to admin dashboard after successful authentication
        header("Location: admin_dashboard.php");
        exit(); // Terminate script to prevent further execution
    } else {
        // Set error message for invalid credentials
        $error = "Invalid admin credentials";
    }
    // Clean up prepared statement resources
    mysqli_stmt_close($stmt);
}
// Close database connection before rendering HTML
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - Past Times</title>
    <style>
        /* Universal reset for consistent rendering across browsers */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        /* Full viewport background with matching purple-blue gradient */
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; justify-content: center; align-items: center; }
        /* White card container centered on screen */
        .login-container { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.2); width: 400px; }
        /* Purple heading matching brand color scheme */
        h2 { color: #764ba2; margin-bottom: 20px; text-align: center; }
        /* Consistent spacing between form field groups */
        .form-group { margin-bottom: 15px; }
        /* Bold labels for form fields */
        label { display: block; margin-bottom: 5px; color: #333; font-weight: bold; }
        /* Full-width inputs with light gray border */
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        /* Full-width purple submit button with pointer cursor */
        button { width: 100%; padding: 12px; background: #764ba2; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
        /* Red-tinted error message box for invalid login attempts */
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        /* Centered navigation links below form */
        .links { text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Admin Login</h2>
        <!-- Display error message when login credentials are invalid -->
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Information box displaying default demo credentials -->
        <div style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #2196f3;">
            <strong>📋 Default Admin Credentials (for demonstration):</strong><br>
            Email: <strong>admin@example.com</strong><br>
            Password: <strong>admin123</strong>
            <br><br>
            <!-- Security note recommending password change -->
            <small style="color: #666;">* These are the default credentials. Change them after first login.</small>
        </div>

        <!-- Login form submits to same page for processing -->
        <form method="POST">
            <div class="form-group">
                <label>Email:</label>
                <!-- Email type enables browser-level email validation -->
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password:</label>
                <!-- Password type masks input for security -->
                <input type="password" name="password" required>
            </div>
            <!-- Submit button triggers form processing -->
            <button type="submit">Login</button>
        </form>
        <!-- Navigation link to return to main user site -->
        <div class="links">
            <a href="index.php">← Back to User Login</a>
        </div>
    </div>
</body>
</html>