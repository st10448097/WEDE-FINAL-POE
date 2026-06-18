<?php
// register.php - User registration page for Past Times clothing store
// Handles new user account creation with validation and duplicate email checking
session_start(); // Start session for potential auto-login after registration
include 'DBConn.php'; // Include database connection

// Initialize variables for form fields and error/success messages
$error = '';
$success = '';
$sticky_name = '';
$sticky_email = '';
$sticky_phone = '';

// Process registration form when submitted via POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form inputs
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    $role = $_POST['role']; // 'buyer' or 'seller'
    
    // Preserve form inputs for sticky form behavior on error
    $sticky_name = $name;
    $sticky_email = $email;
    $sticky_phone = $phone;
    
    // === Validation Checks ===
    
    // Check all required fields are filled
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all required fields.";
    }
    // Validate email format using PHP's built-in filter
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    }
    // Check password minimum length for security
    elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    }
    // Verify password and confirmation match
    elseif ($password !== $confirm_password) {
        $error = "Passwords do not match. Please try again.";
    }
    // If all validation passes, proceed with registration
    else {
        // Check if email already exists in database to prevent duplicates
        $check_stmt = mysqli_prepare($conn, "SELECT user_id FROM user WHERE email = ?");
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        // If email is already registered, show error
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $error = "An account with this email already exists. Please login or use a different email.";
        } else {
            // Hash password using MD5 for storage (matches existing system)
            // Note: For production, use password_hash() with bcrypt for better security
            $hashed_password = md5($password);
            
            // Insert new user into database with verified = 0 (pending admin approval)
            $insert_stmt = mysqli_prepare($conn, "INSERT INTO user (name, email, password, phone, role, verified) VALUES (?, ?, ?, ?, ?, 0)");
            mysqli_stmt_bind_param($insert_stmt, "sssss", $name, $email, $hashed_password, $phone, $role);
            
            // Execute insertion and check for success
            if (mysqli_stmt_execute($insert_stmt)) {
                // Registration successful - show success message
                $success = "Registration successful! Your account is pending verification by an administrator. You will be able to login once approved.";
                // Clear sticky form fields after successful registration
                $sticky_name = '';
                $sticky_email = '';
                $sticky_phone = '';
            } else {
                // Handle database insertion errors
                $error = "Registration failed. Please try again later. Error: " . mysqli_error($conn);
            }
            mysqli_stmt_close($insert_stmt); // Clean up prepared statement
        }
        mysqli_stmt_close($check_stmt); // Clean up check statement
    }
}
mysqli_close($conn); // Close database connection before rendering HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Past Times</title>
    <style>
        /* Universal reset for consistent cross-browser rendering */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        /* Full viewport background with brand gradient, scrollable if needed */
        body { 
            font-family: Arial, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            padding: 20px;
        }
        /* Registration card container */
        .register-container { 
            background: white; 
            padding: 40px; 
            border-radius: 10px; 
            box-shadow: 0 0 20px rgba(0,0,0,0.2); 
            width: 100%;
            max-width: 450px; /* Maximum width for readability */
        }
        
        /* Logo Styles */
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0; /* Separator between logo and form */
        }
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px; /* Space between icon and text */
            text-decoration: none;
        }
        /* Clock SVG icon container */
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
        /* Brand name with two-tone purple/blue styling */
        .logo-text {
            font-size: 32px;
            font-weight: bold;
            color: #764ba2; /* Purple for "Past" */
            letter-spacing: 2px;
        }
        .logo-text span {
            color: #667eea; /* Blue for "Times" */
        }
        /* Vintage-themed subtitle with wide letter spacing */
        .logo-subtitle {
            color: #888;
            font-size: 14px;
            margin-top: 5px;
            letter-spacing: 3px;
        }
        
        /* Form heading */
        h2 { color: #764ba2; margin-bottom: 20px; text-align: center; }
        /* Consistent spacing between form groups */
        .form-group { margin-bottom: 15px; }
        /* Bold form labels */
        label { display: block; margin-bottom: 5px; color: #333; font-weight: bold; }
        /* Required field indicator */
        label .required { color: #dc3545; }
        /* Full-width inputs with light border */
        input, select { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            font-size: 16px; 
        }
        /* Purple border highlight on focus */
        input:focus, select:focus { outline: none; border-color: #764ba2; }
        /* Full-width purple submit button with hover transition */
        button { 
            width: 100%; 
            padding: 12px; 
            background: #764ba2; 
            color: white; 
            border: none; 
            border-radius: 5px; 
            font-size: 16px; 
            cursor: pointer; 
            transition: background 0.3s; 
            margin-top: 10px;
        }
        button:hover { background: #5a3d82; } /* Darker purple on hover */
        /* Red-tinted error message box */
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            padding: 10px; 
            border-radius: 5px; 
            margin-bottom: 20px; 
            border: 1px solid #f5c6cb; 
            font-size: 14px;
        }
        /* Green success message box */
        .success { 
            background: #d4edda; 
            color: #155724; 
            padding: 10px; 
            border-radius: 5px; 
            margin-bottom: 20px; 
            border: 1px solid #c3e6cb; 
            font-size: 14px;
        }
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
        /* Help text for password requirements */
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        /* Role selection radio button group */
        .role-group {
            display: flex;
            gap: 20px;
            margin-top: 5px;
        }
        .role-option {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .role-option input[type="radio"] {
            width: auto; /* Override full-width for radio buttons */
        }
        .role-option label {
            margin-bottom: 0;
            font-weight: normal;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="register-container">
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
            <!-- Vintage clothing store subtitle -->
            <div class="logo-subtitle">VINTAGE CLOTHING STORE</div>
        </div>
        
        <h2>Create Account</h2>
        
        <!-- Display error message when validation fails -->
        <?php if ($error): ?>
            <div class="error">❌ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Display success message after successful registration -->
        <?php if ($success): ?>
            <div class="success">✅ <?php echo $success; ?></div>
        <?php endif; ?>
        
        <!-- Registration form submits to same page for processing -->
        <form method="POST" action="">
            <!-- Full Name field -->
            <div class="form-group">
                <label for="name">Full Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" required 
                       value="<?php echo htmlspecialchars($sticky_name); ?>"
                       placeholder="Enter your full name">
            </div>
            
            <!-- Email field with browser validation -->
            <div class="form-group">
                <label for="email">Email Address <span class="required">*</span></label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo htmlspecialchars($sticky_email); ?>"
                       placeholder="Enter your email address">
            </div>
            
            <!-- Password field with minimum length -->
            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <input type="password" id="password" name="password" required 
                       placeholder="Create a password (min. 6 characters)"
                       minlength="6">
                <div class="help-text">Password must be at least 6 characters long.</div>
            </div>
            
            <!-- Confirm password field to prevent typos -->
            <div class="form-group">
                <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" required 
                       placeholder="Re-enter your password">
            </div>
            
            <!-- Phone number field (optional) -->
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone" 
                       value="<?php echo htmlspecialchars($sticky_phone); ?>"
                       placeholder="Enter your phone number (optional)">
            </div>
            
            <!-- Role selection: Buyer or Seller -->
            <div class="form-group">
                <label>Account Type <span class="required">*</span></label>
                <div class="role-group">
                    <!-- Buyer option (default selected) -->
                    <div class="role-option">
                        <input type="radio" id="role_buyer" name="role" value="buyer" checked>
                        <label for="role_buyer">🛍️ Buyer</label>
                    </div>
                    <!-- Seller option -->
                    <div class="role-option">
                        <input type="radio" id="role_seller" name="role" value="seller">
                        <label for="role_seller">📦 Seller</label>
                    </div>
                </div>
                <div class="help-text">Buyers purchase items. Sellers can list their clothes for sale (requires admin approval).</div>
            </div>
            
            <!-- Submit button to create account -->
            <button type="submit">Create Account</button>
        </form>
        
        <!-- Divider between form and navigation links -->
        <hr class="divider">
        
        <!-- Navigation links -->
        <div class="links">
            <!-- Link to login page for existing users -->
            <a href="index.php">🔐 Already have an account? Login</a>
            <br><br>
            <!-- Link to admin login portal -->
            <a href="admin_login.php">👤 Admin Login</a>
        </div>
    </div>
</body>
</html>