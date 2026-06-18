<?php
// admin_dashboard.php
session_start();
include 'DBConn.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle Verify User
if (isset($_GET['verify']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = mysqli_prepare($conn, "UPDATE user SET verified = 1 WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    header("Location: admin_dashboard.php?msg=verified");
    exit();
}

// Handle Delete User
if (isset($_GET['delete_user']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = mysqli_prepare($conn, "DELETE FROM user WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    header("Location: admin_dashboard.php?msg=deleted");
    exit();
}

// Handle Edit User
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_user'])) {
    $id = (int)$_POST['user_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    
    $stmt = mysqli_prepare($conn, "UPDATE user SET name=?, email=?, phone=?, role=? WHERE user_id=?");
    mysqli_stmt_bind_param($stmt, "ssssi", $name, $email, $phone, $role, $id);
    mysqli_stmt_execute($stmt);
    header("Location: admin_dashboard.php?msg=updated");
    exit();
}

// Handle Add User
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = md5($_POST['password']);
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    $verified = isset($_POST['verified']) ? 1 : 0;
    
    $stmt = mysqli_prepare($conn, "INSERT INTO user (name, email, password, phone, role, verified) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssssi", $name, $email, $password, $phone, $role, $verified);
    mysqli_stmt_execute($stmt);
    header("Location: admin_dashboard.php?msg=added");
    exit();
}

// Get all users
$users = mysqli_query($conn, "SELECT * FROM user ORDER BY user_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Past Times</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .header-logo-icon {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
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
        .welcome-text {
            opacity: 0.9;
        }
        .container { max-width: 1200px; margin: 40px auto; padding: 20px; }
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        h2 { color: #764ba2; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #764ba2; color: white; }
        tr:hover { background: #f5f5f5; }
        .btn { padding: 5px 15px; border: none; border-radius: 3px; cursor: pointer; text-decoration: none; display: inline-block; margin: 2px; font-size: 12px; transition: all 0.3s; }
        .btn-verify { background: #28a745; color: white; }
        .btn-verify:hover { background: #218838; }
        .btn-delete { background: #dc3545; color: white; }
        .btn-delete:hover { background: #c82333; }
        .btn-edit { background: #ffc107; color: black; }
        .btn-edit:hover { background: #e0a800; }
        .btn-add { background: #007bff; color: white; }
        .btn-add:hover { background: #0069d9; }
        .message { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000; }
        .modal-content { background: white; padding: 30px; border-radius: 10px; width: 500px; max-height: 90vh; overflow-y: auto; }
        .modal:target { display: flex; }
        .close { float: right; text-decoration: none; font-size: 24px; color: #333; transition: color 0.3s; }
        .close:hover { color: #000; }
        input, select { width: 100%; padding: 8px; margin: 5px 0 15px 0; border: 1px solid #ddd; border-radius: 4px; }
        input:focus, select:focus { outline: none; border-color: #764ba2; box-shadow: 0 0 5px rgba(118, 75, 162, 0.3); }
        
        /* Navigation Tiles */
        .nav-tiles {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .nav-tile {
            background: linear-gradient(135deg, #764ba2, #5a3d8a);
            color: white;
            padding: 25px 20px;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 120px;
        }
        .nav-tile:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(118, 75, 162, 0.3);
        }
        .nav-tile .icon {
            font-size: 36px;
            margin-bottom: 10px;
        }
        .nav-tile .label {
            font-size: 16px;
            font-weight: bold;
        }
        .nav-tile .description {
            font-size: 12px;
            opacity: 0.8;
            margin-top: 5px;
        }
        .nav-tile.clothing { background: linear-gradient(135deg, #667eea, #764ba2); }
        .nav-tile.requests { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .nav-tile.messages { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .nav-tile.logout { background: linear-gradient(135deg, #fa709a, #fee140); }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header { flex-direction: column; gap: 15px; text-align: center; }
            .modal-content { width: 90%; }
            table { font-size: 14px; }
            th, td { padding: 8px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="header-logo-icon">
                <!-- Simple clock SVG icon -->
                <svg width="45" height="45" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="30" cy="30" r="28" fill="#ffffff" stroke="#ffffff" stroke-width="2" opacity="0.3"/>
                    <circle cx="30" cy="30" r="24" fill="rgba(255,255,255,0.1)"/>
                    <!-- Clock numbers -->
                    <text x="30" y="10" text-anchor="middle" font-size="8" fill="white" font-weight="bold">12</text>
                    <text x="48" y="33" text-anchor="middle" font-size="8" fill="white" font-weight="bold">3</text>
                    <text x="30" y="56" text-anchor="middle" font-size="8" fill="white" font-weight="bold">6</text>
                    <text x="12" y="33" text-anchor="middle" font-size="8" fill="white" font-weight="bold">9</text>
                    <!-- Hour marks -->
                    <line x1="30" y1="8" x2="30" y2="13" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="52" y1="30" x2="47" y2="30" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="30" y1="52" x2="30" y2="47" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="8" y1="30" x2="13" y2="30" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <!-- Clock hands pointing to 10:10 -->
                    <line x1="30" y1="30" x2="30" y2="12" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                    <line x1="30" y1="30" x2="42" y2="20" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    <!-- Center dot -->
                    <circle cx="30" cy="30" r="2.5" fill="white"/>
                </svg>
            </div>
            <h1>Past Times</h1>
        </div>
        <div>
            <span class="welcome-text">Welcome, <?php echo $_SESSION['admin_name']; ?></span>
        </div>
    </div>
    
    <div class="container">
        <?php if (isset($_GET['msg'])): ?>
            <div class="message">
                <?php 
                if ($_GET['msg'] == 'verified') echo "✅ User verified successfully!";
                if ($_GET['msg'] == 'deleted') echo "✅ User deleted successfully!";
                if ($_GET['msg'] == 'updated') echo "✅ User updated successfully!";
                if ($_GET['msg'] == 'added') echo "✅ User added successfully!";
                ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Customer Management</h2>
                <a href="#addUserModal" class="btn btn-add">+ Add New User</a>
            </div>
            
            <?php if (mysqli_num_rows($users) > 0): ?>
            <table>
                <thead>
                    <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php while($user = mysqli_fetch_assoc($users)): ?>
                    <tr>
                        <td><?php echo $user['user_id']; ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                        <td><?php echo ucfirst($user['role']); ?></td>
                        <td>
                            <?php if ($user['verified'] == 1): ?>
                                <span style="color: green;">✅ Verified</span>
                            <?php else: ?>
                                <span style="color: orange;">⏳ Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($user['verified'] == 0): ?>
                                <a href="?verify&id=<?php echo $user['user_id']; ?>" class="btn btn-verify">Verify</a>
                            <?php endif; ?>
                            <a href="#editModal<?php echo $user['user_id']; ?>" class="btn btn-edit">Edit</a>
                            <a href="?delete_user&id=<?php echo $user['user_id']; ?>" class="btn btn-delete" onclick="return confirm('Delete this user?')">Delete</a>
                        </td>
                    </tr>
                    
                    <!-- Edit Modal -->
                    <div id="editModal<?php echo $user['user_id']; ?>" class="modal">
                        <div class="modal-content">
                            <a href="#" class="close">&times;</a>
                            <h3>Edit User</h3>
                            <form method="POST">
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                <label>Name:</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                <label>Email:</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                <label>Phone:</label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                                <label>Role:</label>
                                <select name="role">
                                    <option value="buyer" <?php echo $user['role'] == 'buyer' ? 'selected' : ''; ?>>Buyer</option>
                                    <option value="seller" <?php echo $user['role'] == 'seller' ? 'selected' : ''; ?>>Seller</option>
                                </select>
                                <button type="submit" name="edit_user" class="btn btn-edit">Update</button>
                            </form>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>No users found in database.</p>
            <?php endif; ?>
        </div>
        
        <!-- Navigation Tiles -->
        <div class="nav-tiles">
            <a href="admin_clothing.php" class="nav-tile clothing">
                <div class="icon">👕</div>
                <div class="label">Manage Clothing</div>
                <div class="description">View and manage clothing items</div>
            </a>
            
            <a href="admin_requests.php" class="nav-tile requests">
                <div class="icon">📋</div>
                <div class="label">Seller Requests</div>
                <div class="description">Review seller applications</div>
            </a>
            
            <a href="admin_messages.php" class="nav-tile messages">
                <div class="icon">💬</div>
                <div class="label">Messages</div>
                <div class="description">View user messages</div>
            </a>
            
            <a href="logout.php" class="nav-tile logout">
                <div class="icon">🚪</div>
                <div class="label">Logout</div>
                <div class="description">Sign out of your account</div>
            </a>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <a href="#" class="close">&times;</a>
            <h3>Add New User</h3>
            <form method="POST">
                <label>Name:</label>
                <input type="text" name="name" required>
                <label>Email:</label>
                <input type="email" name="email" required>
                <label>Password:</label>
                <input type="password" name="password" required>
                <label>Phone:</label>
                <input type="text" name="phone" required>
                <label>Role:</label>
                <select name="role">
                    <option value="buyer">Buyer</option>
                    <option value="seller">Seller</option>
                </select>
                <label>
                    <input type="checkbox" name="verified" value="1"> Verified
                </label>
                <button type="submit" name="add_user" class="btn btn-add">Add User</button>
            </form>
        </div>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>