<?php
// index.php - User login page for the Past Times clothing store
// Authenticates users, checks verification status, and redirects to role-based dashboard
session_start(); // Start session to store user data after successful login
include 'DBConn.php'; // Include database connection

// Initialize error message and sticky email variables
$error = '';
$sticky_email = ''; // Preserves email input if login fails

// Process login form when submitted via POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $sticky_email = $email; // Store email to repopulate form on error
    
    // Prepare query to find user by email (email is unique in database)
    $stmt = mysqli_prepare($conn, "SELECT * FROM user WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Check if a user with this email exists
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        // Hash the submitted password with MD5 to compare with stored hash
        $hashed_input = md5($password);
        
        // Verify password matches stored hash
        if ($hashed_input === $user['password']) {
            // Check if the account has been verified by an admin
            if ($user['verified'] == 1) {
                // Store user details in session for persistent login
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role']; // 'buyer' or 'seller'
                // Display success message and redirect after 2 seconds
                echo "<div style='background: #4CAF50; color: white; padding: 10px; text-align: center;'>
                      <strong>User " . $user['name'] . " is logged in</strong>
                      </div>";
                header("refresh:2; url=dashboard.php"); // Delayed redirect for user feedback
                exit();
            } else {
                // Account exists but hasn't been approved by admin yet
                $error = "Your account is pending verification. Please wait for admin approval.";
            }
        } else {
            // Password doesn't match the stored hash
            $error = "Invalid password!";
        }
    } else {
        // No user found with the provided email address
        $error = "User not found! Please register first.";
    }
    mysqli_stmt_close($stmt); // Clean up prepared statement
}
mysqli_close($conn); // Close database connection before rendering HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Past Times - User Login</title>
    <style>
        /* Universal reset for consistent cross-browser rendering */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        /* Full viewport background with brand gradient */
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; justify-content: center; align-items: center; }
        /* White card container centered on screen */
        .login-container { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.2); width: 400px; }
        
        /* Logo Styles */
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0; /* Separator line below logo */
        }
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px; /* Space between icon and text */
            text-decoration: none;
        }
        /* Fixed size container for the clock SVG logo */
        .logo-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo-icon img {
            width: 100%;
            height: auto;
        }
        /* Brand name with purple and blue two-tone effect */
        .logo-text {
            font-size: 32px;
            font-weight: bold;
            color: #764ba2; /* Purple for "Past" */
            letter-spacing: 2px;
        }
        .logo-text span {
            color: #667eea; /* Blue for "Times" */
        }
        /* Subtitle with vintage styling */
        .logo-subtitle {
            color: #888;
            font-size: 14px;
            margin-top: 5px;
            letter-spacing: 3px; /* Wide letter spacing for vintage feel */
        }
        
        h2 { color: #764ba2; margin-bottom: 20px; text-align: center; }
        /* Consistent spacing between form groups */
        .form-group { margin-bottom: 15px; }
        /* Bold labels for form fields */
        label { display: block; margin-bottom: 5px; color: #333; font-weight: bold; }
        /* Full-width inputs with light border */
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        /* Purple border highlight on focus */
        input:focus { outline: none; border-color: #764ba2; }
        /* Full-width purple submit button with hover transition */
        button { width: 100%; padding: 12px; background: #764ba2; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; transition: background 0.3s; }
        button:hover { background: #5a3d82; } /* Darker purple on hover */
        /* Red-tinted error message box */
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
        /* Centered navigation links below form */
        .links { text-align: center; margin-top: 20px; }
        .links a { color: #764ba2; text-decoration: none; margin: 0 10px; font-size: 14px; }
        .links a:hover { text-decoration: underline; }
        /* Horizontal divider line */
        .divider { 
            border: none;
            border-top: 1px solid #e0e0e0;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Logo Section with clock icon and brand name -->
        <div class="logo-container">
            <div class="logo">
                <div class="logo-icon">
                    <!-- Clock SVG icon representing "Past Times" vintage brand -->
                    <svg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
                        <!-- Outer circle with purple fill and dark border -->
                        <circle cx="30" cy="30" r="28" fill="#764ba2" stroke="#5a3d82" stroke-width="2"/>
                        <!-- Inner white circle for clock face -->
                        <circle cx="30" cy="30" r="24" fill="white"/>
                        <!-- Clock numerals at 12, 3, 6, and 9 positions -->
                        <text x="30" y="10" text-anchor="middle" font-size="8" fill="#333" font-weight="bold">12</text>
                        <text x="48" y="33" text-anchor="middle" font-size="8" fill="#333" font-weight="bold">3</text>
                        <text x="30" y="56" text-anchor="middle" font-size="8" fill="#333" font-weight="bold">6</text>
                        <text x="12" y="33" text-anchor="middle" font-size="8" fill="#333" font-weight="bold">9</text>
                        <!-- Hour marker ticks -->
                        <line x1="30" y1="8" x2="30" y2="13" stroke="#333" stroke-width="1.5"/>
                        <line x1="52" y1="30" x2="47" y2="30" stroke="#333" stroke-width="1.5"/>
                        <line x1="30" y1="52" x2="30" y2="47" stroke="#333" stroke-width="1.5"/>
                        <line x1="8" y1="30" x2="13" y2="30" stroke="#333" stroke-width="1.5"/>
                        <!-- Clock hands at classic 10:10 position -->
                        <line x1="30" y1="30" x2="30" y2="12" stroke="#333" stroke-width="2.5" stroke-linecap="round"/>
                        <line x1="30" y1="30" x2="42" y2="20" stroke="#333" stroke-width="2" stroke-linecap="round"/>
                        <!-- Center dot in brand purple -->
                        <circle cx="30" cy="30" r="2.5" fill="#764ba2"/>
                    </svg>
                </div>
                <!-- "Past" in purple, "Times" in blue -->
                <div class="logo-text">Past <span>Times</span></div>
            </div>
            <!-- Vintage-themed subtitle -->
            <div class="logo-subtitle">VINTAGE CLOTHING STORE</div>
        </div>
        
        <h2>User Login</h2>
        
        <!-- Display error message when login fails -->
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Login form submits to same page for processing -->
        <form method="POST" action="">
            <div class="form-group">
                <label>Email Address:</label>
                <!-- Email input with sticky value (preserved on failed login) -->
                <input type="email" name="email" required 
                       value="<?php echo htmlspecialchars($sticky_email); ?>"
                       placeholder="Enter your email">
            </div>
            
            <div class="form-group">
                <label>Password:</label>
                <!-- Password field (value not preserved for security) -->
                <input type="password" name="password" required 
                       placeholder="Enter your password">
            </div>
            
            <!-- Submit button triggers authentication -->
            <button type="submit">Login</button>
        </form>
        
        <!-- Divider between form and navigation links -->
        <hr class="divider">
        
        <div class="links">
            <!-- Link to new user registration page -->
            <a href="register.php">📝 Register New Account</a>
            <!-- Link to admin login portal -->
            <a href="admin_login.php">🔐 Admin Login</a>
        </div>
    </div>
</body>
</html>