<?php
// admin_dashboard.php - Main admin interface for user management
// Provides CRUD operations for user accounts and verification system
session_start(); // Start session to access admin authentication
include 'DBConn.php'; // Include database connection

// Authentication check - redirect non-admin users to login page
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle Verify User - Admin approves pending user accounts
// Triggered via GET parameter from verify button link
if (isset($_GET['verify']) && isset($_GET['id'])) {
    $id = (int)$_GET['id']; // Cast to integer for security against injection
    // Update user verified status to 1 (approved)
    $stmt = mysqli_prepare($conn, "UPDATE user SET verified = 1 WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    header("Location: admin_dashboard.php?msg=verified");
    exit();
}

// Handle Delete User - Removes user account from database
if (isset($_GET['delete_user']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = mysqli_prepare($conn, "DELETE FROM user WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    header("Location: admin_dashboard.php?msg=deleted");
    exit();
}

// Handle Edit User - Updates existing user information
// Distinguishes form submission by checking for edit_user button name
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_user'])) {
    $id = (int)$_POST['user_id'];
    // Note: Inputs are not sanitized here - should use mysqli_real_escape_string for production
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    
    // Update all user fields for specified user ID
    $stmt = mysqli_prepare($conn, "UPDATE user SET name=?, email=?, phone=?, role=? WHERE user_id=?");
    // All string parameters except final integer ID
    mysqli_stmt_bind_param($stmt, "ssssi", $name, $email, $phone, $role, $id);
    mysqli_stmt_execute($stmt);
    header("Location: admin_dashboard.php?msg=updated");
    exit();
}

// Handle Add User - Creates new user account
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    // MD5 hashing for password (note: MD5 is not cryptographically secure for production use)
    $password = md5($_POST['password']);
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    // Convert checkbox value to 1 or 0 for database storage
    $verified = isset($_POST['verified']) ? 1 : 0;
    
    $stmt = mysqli_prepare($conn, "INSERT INTO user (name, email, password, phone, role, verified) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssssi", $name, $email, $password, $phone, $role, $verified);
    mysqli_stmt_execute($stmt);
    header("Location: admin_dashboard.php?msg=added");
    exit();
}

// Fetch all users ordered by newest registrations first
$users = mysqli_query($conn, "SELECT * FROM user ORDER BY user_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Past Times</title>
    <style>
        /* Global reset for consistent cross-browser styling */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        /* Gradient header with brand colors - purple to blue transition */
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        /* Flex container for logo and title alignment */
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px; /* Spacing between logo and title */
        }
        /* Fixed size container for the clock SVG logo */
        .header-logo-icon {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        /* Ensure SVG scales properly within container */
        .header-logo-icon svg {
            width: 100%;
            height: auto;
        }
        .header h1 { 
            font-size: 1.8em; 
            display: flex;
            align-items: center;
            gap: 10px;
        }
        /* Semi-transparent text for subtle visual hierarchy */
        .welcome-text {
            opacity: 0.9;
        }
        /* Centered content container with max-width for readability */
        .container { max-width: 1200px; margin: 40px auto; padding: 20px; }
        /* White card with shadow for content separation */
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        h2 { color: #764ba2; margin-bottom: 20px; }
        /* Full-width table with merged borders */
        table { width: 100%; border-collapse: collapse; }
        /* Table cell spacing and bottom border for rows */
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        /* Purple header row with white text */
        th { background: #764ba2; color: white; }
        /* Highlight row on hover for better readability */
        tr:hover { background: #f5f5f5; }
        /* Base button styles with smooth hover transition */
        .btn { padding: 5px 15px; border: none; border-radius: 3px; cursor: pointer; text-decoration: none; display: inline-block; margin: 2px; font-size: 12px; transition: all 0.3s; }
        /* Green verify button for approval actions */
        .btn-verify { background: #28a745; color: white; }
        .btn-verify:hover { background: #218838; }
        /* Red delete button for destructive actions */
        .btn-delete { background: #dc3545; color: white; }
        .btn-delete:hover { background: #c82333; }
        /* Yellow edit button with dark text for contrast */
        .btn-edit { background: #ffc107; color: black; }
        .btn-edit:hover { background: #e0a800; }
        /* Blue add button for create actions */
        .btn-add { background: #007bff; color: white; }
        .btn-add:hover { background: #0069d9; }
        /* Green success notification banner */
        .message { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        /* Modal overlay - hidden by default, covers full viewport */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000; }
        /* Modal white content box with scroll for overflow content */
        .modal-content { background: white; padding: 30px; border-radius: 10px; width: 500px; max-height: 90vh; overflow-y: auto; }
        /* CSS-only modal toggle - shows modal when URL hash matches its ID */
        .modal:target { display: flex; }
        /* Close button positioned top-right of modal */
        .close { float: right; text-decoration: none; font-size: 24px; color: #333; transition: color 0.3s; }
        .close:hover { color: #000; }
        /* Form input styling with light border */
        input, select { width: 100%; padding: 8px; margin: 5px 0 15px 0; border: 1px solid #ddd; border-radius: 4px; }
        /* Purple glow effect on focus for visual feedback */
        input:focus, select:focus { outline: none; border-color: #764ba2; box-shadow: 0 0 5px rgba(118, 75, 162, 0.3); }
        
        /* Navigation Tiles */
        /* Responsive grid for quick navigation cards */
        .nav-tiles {
            display: grid;
            /* Automatically fits columns with minimum 200px width */
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        /* Individual tile with purple gradient and white text */
        .nav-tile {
            background: linear-gradient(135deg, #764ba2, #5a3d8a);
            color: white;
            padding: 25px 20px;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            transition: transform 0.3s, box-shadow 0.3s; /* Smooth animation */
            display: flex;
            flex-direction: column; /* Stack items vertically */
            align-items: center;
            justify-content: center;
            min-height: 120px;
        }
        /* Lift effect on hover with purple shadow */
        .nav-tile:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(118, 75, 162, 0.3);
        }
        /* Large emoji icon display */
        .nav-tile .icon {
            font-size: 36px;
            margin-bottom: 10px;
        }
        /* Bold label text */
        .nav-tile .label {
            font-size: 16px;
            font-weight: bold;
        }
        /* Subdued description text */
        .nav-tile .description {
            font-size: 12px;
            opacity: 0.8;
            margin-top: 5px;
        }
        /* Unique gradient colors to distinguish different tile destinations */
        .nav-tile.clothing { background: linear-gradient(135deg, #667eea, #764ba2); }
        .nav-tile.requests { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .nav-tile.messages { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .nav-tile.logout { background: linear-gradient(135deg, #fa709a, #fee140); }
        
        /* Responsive design adjustments for mobile devices */
        @media (max-width: 768px) {
            /* Stack header elements vertically on small screens */
            .header { flex-direction: column; gap: 15px; text-align: center; }
            /* Full-width modals on mobile */
            .modal-content { width: 90%; }
            /* Smaller table text for mobile screens */
            table { font-size: 14px; }
            th, td { padding: 8px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="header-logo-icon">
                <!-- Simple clock SVG icon representing "Past Times" vintage theme -->
                <svg width="45" height="45" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
                    <!-- Outer decorative circle -->
                    <circle cx="30" cy="30" r="28" fill="#ffffff" stroke="#ffffff" stroke-width="2" opacity="0.3"/>
                    <!-- Inner transparent circle -->
                    <circle cx="30" cy="30" r="24" fill="rgba(255,255,255,0.1)"/>
                    <!-- Clock numbers at cardinal positions -->
                    <text x="30" y="10" text-anchor="middle" font-size="8" fill="white" font-weight="bold">12</text>
                    <text x="48" y="33" text-anchor="middle" font-size="8" fill="white" font-weight="bold">3</text>
                    <text x="30" y="56" text-anchor="middle" font-size="8" fill="white" font-weight="bold">6</text>
                    <text x="12" y="33" text-anchor="middle" font-size="8" fill="white" font-weight="bold">9</text>
                    <!-- Hour marker lines at 12, 3, 6, and 9 positions -->
                    <line x1="30" y1="8" x2="30" y2="13" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="52" y1="30" x2="47" y2="30" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="30" y1="52" x2="30" y2="47" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="8" y1="30" x2="13" y2="30" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <!-- Clock hands set to classic 10:10 position (aesthetically pleasing) -->
                    <line x1="30" y1="30" x2="30" y2="12" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                    <line x1="30" y1="30" x2="42" y2="20" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    <!-- Center pivot dot -->
                    <circle cx="30" cy="30" r="2.5" fill="white"/>
                </svg>
            </div>
            <h1>Past Times</h1>
        </div>
        <div>
            <!-- Display logged-in admin's username -->
            <span class="welcome-text">Welcome, <?php echo $_SESSION['admin_name']; ?></span>
        </div>
    </div>
    
    <div class="container">
        <!-- Display success/notification messages based on URL parameter -->
        <?php if (isset($_GET['msg'])): ?>
            <div class="message">
                <?php 
                // Map message codes to user-friendly notifications with emojis
                if ($_GET['msg'] == 'verified') echo "✅ User verified successfully!";
                if ($_GET['msg'] == 'deleted') echo "✅ User deleted successfully!";
                if ($_GET['msg'] == 'updated') echo "✅ User updated successfully!";
                if ($_GET['msg'] == 'added') echo "✅ User added successfully!";
                ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <!-- Flex container aligns heading left and add button right -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Customer Management</h2>
                <!-- Link triggers Add User modal via CSS :target selector -->
                <a href="#addUserModal" class="btn btn-add">+ Add New User</a>
            </div>
            
            <!-- Conditional rendering: show table only if users exist in database -->
            <?php if (mysqli_num_rows($users) > 0): ?>
            <table>
                <thead>
                    <!-- Table column definitions -->
                    <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <!-- Iterate through all users from database query -->
                    <?php while($user = mysqli_fetch_assoc($users)): ?>
                    <tr>
                        <td><?php echo $user['user_id']; ?></td>
                        <!-- Escape user data to prevent XSS attacks -->
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                        <!-- Capitalize first letter of role for display -->
                        <td><?php echo ucfirst($user['role']); ?></td>
                        <td>
                            <!-- Display verification status with color-coded indicators -->
                            <?php if ($user['verified'] == 1): ?>
                                <span style="color: green;">✅ Verified</span>
                            <?php else: ?>
                                <span style="color: orange;">⏳ Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <!-- Only show verify button for unverified users -->
                            <?php if ($user['verified'] == 0): ?>
                                <a href="?verify&id=<?php echo $user['user_id']; ?>" class="btn btn-verify">Verify</a>
                            <?php endif; ?>
                            <!-- Edit link opens user-specific modal -->
                            <a href="#editModal<?php echo $user['user_id']; ?>" class="btn btn-edit">Edit</a>
                            <!-- Delete link with JavaScript confirmation dialog to prevent accidental deletion -->
                            <a href="?delete_user&id=<?php echo $user['user_id']; ?>" class="btn btn-delete" onclick="return confirm('Delete this user?')">Delete</a>
                        </td>
                    </tr>
                    
                    <!-- Edit Modal - uniquely identified by user ID for CSS targeting -->
                    <div id="editModal<?php echo $user['user_id']; ?>" class="modal">
                        <div class="modal-content">
                            <!-- Close button removes hash from URL, hiding modal -->
                            <a href="#" class="close">&times;</a>
                            <h3>Edit User</h3>
                            <!-- Form submits to same page with edit_user flag -->
                            <form method="POST">
                                <!-- Hidden field carries user ID for database update -->
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                <label>Name:</label>
                                <!-- Pre-populate fields with current user data -->
                                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                <label>Email:</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                <label>Phone:</label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                                <label>Role:</label>
                                <select name="role">
                                    <!-- Mark current role as selected in dropdown -->
                                    <option value="buyer" <?php echo $user['role'] == 'buyer' ? 'selected' : ''; ?>>Buyer</option>
                                    <option value="seller" <?php echo $user['role'] == 'seller' ? 'selected' : ''; ?>>Seller</option>
                                </select>
                                <!-- Submit button named edit_user to distinguish from add form -->
                                <button type="submit" name="edit_user" class="btn btn-edit">Update</button>
                            </form>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
                <!-- Fallback message when no users exist in database -->
                <p>No users found in database.</p>
            <?php endif; ?>
        </div>
        
        <!-- Navigation Tiles - Quick access to other admin sections -->
        <div class="nav-tiles">
            <!-- Clothing management tile -->
            <a href="admin_clothing.php" class="nav-tile clothing">
                <div class="icon">👕</div>
                <div class="label">Manage Clothing</div>
                <div class="description">View and manage clothing items</div>
            </a>
            
            <!-- Seller requests review tile -->
            <a href="admin_requests.php" class="nav-tile requests">
                <div class="icon">📋</div>
                <div class="label">Seller Requests</div>
                <div class="description">Review seller applications</div>
            </a>
            
            <!-- User messages tile -->
            <a href="admin_messages.php" class="nav-tile messages">
                <div class="icon">💬</div>
                <div class="label">Messages</div>
                <div class="description">View user messages</div>
            </a>
            
            <!-- Logout tile to end admin session -->
            <a href="logout.php" class="nav-tile logout">
                <div class="icon">🚪</div>
                <div class="label">Logout</div>
                <div class="description">Sign out of your account</div>
            </a>
        </div>
    </div>
    
    <!-- Add User Modal - single modal for creating new user accounts -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <!-- Close button hides modal -->
            <a href="#" class="close">&times;</a>
            <h3>Add New User</h3>
            <!-- Form submits to same page with add_user flag -->
            <form method="POST">
                <label>Name:</label>
                <input type="text" name="name" required>
                <label>Email:</label>
                <!-- Email type provides basic browser validation -->
                <input type="email" name="email" required>
                <label>Password:</label>
                <!-- Password type masks input characters -->
                <input type="password" name="password" required>
                <label>Phone:</label>
                <input type="text" name="phone" required>
                <label>Role:</label>
                <select name="role">
                    <!-- Default role options for new users -->
                    <option value="buyer">Buyer</option>
                    <option value="seller">Seller</option>
                </select>
                <!-- Optional checkbox to pre-verify user during creation -->
                <label>
                    <input type="checkbox" name="verified" value="1"> Verified
                </label>
                <!-- Submit button named add_user to distinguish from edit form -->
                <button type="submit" name="add_user" class="btn btn-add">Add User</button>
            </form>
        </div>
    </div>
</body>
</html>
<?php 
// Close database connection to free server resources
mysqli_close($conn); 
?>