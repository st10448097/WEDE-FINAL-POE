<?php
// admin_clothing.php - Admin CRUD for clothing items
session_start();
include 'DBConn.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle Add Clothing
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_clothing'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $image_url = mysqli_real_escape_string($conn, $_POST['image_url']);
    
    $stmt = mysqli_prepare($conn, "INSERT INTO clothing (name, category, price, stock, description, image_url) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssdiss", $name, $category, $price, $stock, $description, $image_url);
    mysqli_stmt_execute($stmt);
    header("Location: admin_clothing.php?msg=added");
    exit();
}

// Handle Update Clothing
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_clothing'])) {
    $id = intval($_POST['clothing_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $image_url = mysqli_real_escape_string($conn, $_POST['image_url']);
    
    $stmt = mysqli_prepare($conn, "UPDATE clothing SET name=?, category=?, price=?, stock=?, description=?, image_url=? WHERE clothing_id=?");
    mysqli_stmt_bind_param($stmt, "ssdissi", $name, $category, $price, $stock, $description, $image_url, $id);
    mysqli_stmt_execute($stmt);
    header("Location: admin_clothing.php?msg=updated");
    exit();
}

// Handle Delete Clothing
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = mysqli_prepare($conn, "DELETE FROM clothing WHERE clothing_id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    header("Location: admin_clothing.php?msg=deleted");
    exit();
}

// Get all clothing items
$clothing = mysqli_query($conn, "SELECT * FROM clothing ORDER BY clothing_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Clothing - Admin Panel</title>
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
        .btn-add { background: #28a745; color: white; }
        .btn-add:hover { background: #218838; }
        .btn-edit { background: #ffc107; color: black; }
        .btn-edit:hover { background: #e0a800; }
        .btn-delete { background: #dc3545; color: white; }
        .btn-delete:hover { background: #c82333; }
        .btn-back { background: #6c757d; color: white; }
        .btn-back:hover { background: #5a6268; }
        .message { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #764ba2; box-shadow: 0 0 5px rgba(118, 75, 162, 0.3); }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000; }
        .modal-content { background: white; padding: 30px; border-radius: 10px; width: 500px; max-height: 80vh; overflow-y: auto; }
        .modal:target { display: flex; }
        .close { float: right; text-decoration: none; font-size: 24px; color: #333; transition: color 0.3s; }
        .close:hover { color: #000; }
        .clothing-image { width: 50px; height: 50px; object-fit: cover; border-radius: 5px; }
        
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
        .nav-tile.dashboard { background: linear-gradient(135deg, #667eea, #764ba2); }
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
                if ($_GET['msg'] == 'added') echo "✅ Clothing item added successfully!";
                if ($_GET['msg'] == 'updated') echo "✅ Clothing item updated successfully!";
                if ($_GET['msg'] == 'deleted') echo "✅ Clothing item deleted successfully!";
                ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Clothing Inventory</h2>
                <a href="#addClothingModal" class="btn btn-add">+ Add New Clothing</a>
            </div>
            
            <?php if (mysqli_num_rows($clothing) > 0): ?>
            <table>
                <thead>
                    <tr><th>ID</th><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Description</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php while($item = mysqli_fetch_assoc($clothing)): ?>
                    <tr>
                        <td><?php echo $item['clothing_id']; ?></td>
                        <td>
                            <?php if ($item['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="clothing-image">
                            <?php else: ?>
                                No Image
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo ucfirst($item['category']); ?></td>
                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                        <td><?php echo $item['stock']; ?></td>
                        <td><?php echo substr(htmlspecialchars($item['description']), 0, 50); ?>...</td>
                        <td>
                            <a href="#editModal<?php echo $item['clothing_id']; ?>" class="btn btn-edit">Edit</a>
                            <a href="?delete&id=<?php echo $item['clothing_id']; ?>" class="btn btn-delete" onclick="return confirm('Delete this item?')">Delete</a>
                        </td>
                    </tr>
                    
                    <!-- Edit Modal -->
                    <div id="editModal<?php echo $item['clothing_id']; ?>" class="modal">
                        <div class="modal-content">
                            <a href="#" class="close">&times;</a>
                            <h3>Edit Clothing Item</h3>
                            <form method="POST">
                                <input type="hidden" name="clothing_id" value="<?php echo $item['clothing_id']; ?>">
                                <div class="form-group">
                                    <label>Name:</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($item['name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Category:</label>
                                    <select name="category" required>
                                        <option value="men" <?php echo $item['category'] == 'men' ? 'selected' : ''; ?>>Men</option>
                                        <option value="women" <?php echo $item['category'] == 'women' ? 'selected' : ''; ?>>Women</option>
                                        <option value="kids" <?php echo $item['category'] == 'kids' ? 'selected' : ''; ?>>Kids</option>
                                        <option value="accessories" <?php echo $item['category'] == 'accessories' ? 'selected' : ''; ?>>Accessories</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Price:</label>
                                    <input type="number" step="0.01" name="price" value="<?php echo $item['price']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Stock:</label>
                                    <input type="number" name="stock" value="<?php echo $item['stock']; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Description:</label>
                                    <textarea name="description"><?php echo htmlspecialchars($item['description']); ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Image URL:</label>
                                    <input type="text" name="image_url" value="<?php echo htmlspecialchars($item['image_url']); ?>">
                                </div>
                                <button type="submit" name="update_clothing" class="btn btn-edit">Update</button>
                            </form>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>No clothing items found.</p>
            <?php endif; ?>
        </div>
        
        <!-- Navigation Tiles -->
        <div class="nav-tiles">
            <a href="admin_dashboard.php" class="nav-tile dashboard">
                <div class="icon">📊</div>
                <div class="label">Dashboard</div>
                <div class="description">Return to admin dashboard</div>
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
    
    <!-- Add Clothing Modal -->
    <div id="addClothingModal" class="modal">
        <div class="modal-content">
            <a href="#" class="close">&times;</a>
            <h3>Add New Clothing Item</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Name:</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Category:</label>
                    <select name="category" required>
                        <option value="men">Men</option>
                        <option value="women">Women</option>
                        <option value="kids">Kids</option>
                        <option value="accessories">Accessories</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Price:</label>
                    <input type="number" step="0.01" name="price" required>
                </div>
                <div class="form-group">
                    <label>Stock:</label>
                    <input type="number" name="stock" value="0">
                </div>
                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="description"></textarea>
                </div>
                <div class="form-group">
                    <label>Image URL:</label>
                    <input type="text" name="image_url" placeholder="https://example.com/image.jpg">
                </div>
                <button type="submit" name="add_clothing" class="btn btn-add">Add Item</button>
            </form>
        </div>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>