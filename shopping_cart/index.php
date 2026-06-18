<?php
// index.php - User Login
session_start();
include 'DBConn.php';

$error = '';
$sticky_email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $sticky_email = $email;
    
    $stmt = mysqli_prepare($conn, "SELECT * FROM user WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $hashed_input = md5($password);
        
        if ($hashed_input === $user['password']) {
            if ($user['verified'] == 1) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                echo "<div style='background: #4CAF50; color: white; padding: 10px; text-align: center;'>
                      <strong>User " . $user['name'] . " is logged in</strong>
                      </div>";
                header("refresh:2; url=dashboard.php");
                exit();
            } else {
                $error = "Your account is pending verification. Please wait for admin approval.";
            }
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found! Please register first.";
    }
    mysqli_stmt_close($stmt);
}
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Past Times - User Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; justify-content: center; align-items: center; }
        .login-container { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.2); width: 400px; }
        
        /* Logo Styles */
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            text-decoration: none;
        }
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
        .logo-text {
            font-size: 32px;
            font-weight: bold;
            color: #764ba2;
            letter-spacing: 2px;
        }
        .logo-text span {
            color: #667eea;
        }
        .logo-subtitle {
            color: #888;
            font-size: 14px;
            margin-top: 5px;
            letter-spacing: 3px;
        }
        
        h2 { color: #764ba2; margin-bottom: 20px; text-align: center; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #333; font-weight: bold; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        input:focus { outline: none; border-color: #764ba2; }
        button { width: 100%; padding: 12px; background: #764ba2; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; transition: background 0.3s; }
        button:hover { background: #5a3d82; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
        .links { text-align: center; margin-top: 20px; }
        .links a { color: #764ba2; text-decoration: none; margin: 0 10px; font-size: 14px; }
        .links a:hover { text-decoration: underline; }
        .divider { 
            border: none;
            border-top: 1px solid #e0e0e0;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Logo Section -->
        <div class="logo-container">
            <div class="logo">
                <div class="logo-icon">
                    <!-- Simple clock SVG icon -->
                    <svg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="30" cy="30" r="28" fill="#764ba2" stroke="#5a3d82" stroke-width="2"/>
                        <circle cx="30" cy="30" r="24" fill="white"/>
                        <!-- Clock numbers -->
                        <text x="30" y="10" text-anchor="middle" font-size="8" fill="#333" font-weight="bold">12</text>
                        <text x="48" y="33" text-anchor="middle" font-size="8" fill="#333" font-weight="bold">3</text>
                        <text x="30" y="56" text-anchor="middle" font-size="8" fill="#333" font-weight="bold">6</text>
                        <text x="12" y="33" text-anchor="middle" font-size="8" fill="#333" font-weight="bold">9</text>
                        <!-- Hour marks -->
                        <line x1="30" y1="8" x2="30" y2="13" stroke="#333" stroke-width="1.5"/>
                        <line x1="52" y1="30" x2="47" y2="30" stroke="#333" stroke-width="1.5"/>
                        <line x1="30" y1="52" x2="30" y2="47" stroke="#333" stroke-width="1.5"/>
                        <line x1="8" y1="30" x2="13" y2="30" stroke="#333" stroke-width="1.5"/>
                        <!-- Clock hands pointing to 10:10 -->
                        <line x1="30" y1="30" x2="30" y2="12" stroke="#333" stroke-width="2.5" stroke-linecap="round"/>
                        <line x1="30" y1="30" x2="42" y2="20" stroke="#333" stroke-width="2" stroke-linecap="round"/>
                        <!-- Center dot -->
                        <circle cx="30" cy="30" r="2.5" fill="#764ba2"/>
                    </svg>
                </div>
                <div class="logo-text">Past <span>Times</span></div>
            </div>
            <div class="logo-subtitle">VINTAGE CLOTHING STORE</div>
        </div>
        
        <h2>User Login</h2>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Email Address:</label>
                <input type="email" name="email" required 
                       value="<?php echo htmlspecialchars($sticky_email); ?>"
                       placeholder="Enter your email">
            </div>
            
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required 
                       placeholder="Enter your password">
            </div>
            
            <button type="submit">Login</button>
        </form>
        
        <hr class="divider">
        
        <div class="links">
            <a href="register.php">📝 Register New Account</a>
            <a href="admin_login.php">🔐 Admin Login</a>
        </div>
    </div>
</body>
</html>