<?php
// edit_clothing.php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Connect to database
$conn = mysqli_connect("localhost", "root", "", "clothesstore");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$message = '';
$error = '';
$clothing = null;

// Get clothing ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$clothing_id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch existing clothing data
$sql = "SELECT * FROM clothing WHERE clothing_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $clothing_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: admin_dashboard.php");
    exit();
}

$clothing = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $description = trim($_POST['description']);
    $image_url = trim($_POST['image_url']);
    
    // Validate inputs
    if (empty($name) || empty($category) || $price <= 0) {
        $error = "Please fill in all required fields (Name, Category, Price).";
    } else {
        // Update database
        $update_sql = "UPDATE clothing SET name=?, category=?, price=?, stock=?, description=?, image_url=? WHERE clothing_id=?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "ssdissi", $name, $category, $price, $stock, $description, $image_url, $clothing_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $message = "Clothing item updated successfully!";
            // Refresh clothing data
            $clothing['name'] = $name;
            $clothing['category'] = $category;
            $clothing['price'] = $price;
            $clothing['stock'] = $stock;
            $clothing['description'] = $description;
            $clothing['image_url'] = $image_url;
        } else {
            $error = "Error updating clothing: " . mysqli_error($conn);
        }
        mysqli_stmt_close($update_stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Clothing - Admin Panel</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: linear-gradient(135deg, #e6f3ff, #f0e6ff);
            min-height: 100vh;
        }
        .header {
            background: linear-gradient(to right, #7cb9e8, #9b7ec4);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { font-size: 2em; }
        .container { max-width: 800px; margin: 40px auto; padding: 20px; }
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border: 2px solid #6b4e96;
        }
        h2 { color: #6b4e96; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; color: #4a3a6b; font-weight: bold; }
        input, select, textarea { 
            width: 100%; 
            padding: 10px; 
            border: 2px solid #d0c4e8; 
            border-radius: 5px; 
            font-size: 16px; 
        }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #6b4e96; }
        textarea { resize: vertical; min-height: 100px; }
        .btn {
            background: #6b4e96;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover { background: #5a3d82; }
        .btn-secondary {
            background: #999;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-secondary:hover { background: #777; }
        .message {
            background: #e6ffe6;
            color: #006600;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #4CAF50;
        }
        .error {
            background: #ffe6e6;
            color: #cc0000;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #cc0000;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .current-image {
            margin-top: 10px;
            padding: 10px;
            background: #f0f0f0;
            border-radius: 5px;
        }
        .current-image img {
            max-width: 100px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Past Times - Admin Panel</h1>
        <div>
            Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
            <a href="logout.php" style="color: white; text-decoration: none; margin-left: 20px;">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>Edit Clothing Item</h2>
            
            <?php if ($message): ?>
                <div class="message">✅ <?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Name *</label>
                    <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($clothing['name']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <option value="">Select Category</option>
                        <option value="men" <?php echo ($clothing['category'] == 'men') ? 'selected' : ''; ?>>Men</option>
                        <option value="women" <?php echo ($clothing['category'] == 'women') ? 'selected' : ''; ?>>Women</option>
                        <option value="kids" <?php echo ($clothing['category'] == 'kids') ? 'selected' : ''; ?>>Kids</option>
                        <option value="accessories" <?php echo ($clothing['category'] == 'accessories') ? 'selected' : ''; ?>>Accessories</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="price">Price ($) *</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required value="<?php echo $clothing['price']; ?>">
                </div>
                
                <div class="form-group">
                    <label for="stock">Stock Quantity</label>
                    <input type="number" id="stock" name="stock" min="0" value="<?php echo $clothing['stock']; ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"><?php echo htmlspecialchars($clothing['description']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="image_url">Image URL</label>
                    <input type="text" id="image_url" name="image_url" placeholder="https://example.com/image.jpg" value="<?php echo htmlspecialchars($clothing['image_url']); ?>">
                    <?php if ($clothing['image_url']): ?>
                        <div class="current-image">
                            <strong>Current Image:</strong><br>
                            <img src="<?php echo htmlspecialchars($clothing['image_url']); ?>" alt="Current">
                        </div>
                    <?php endif; ?>
                    <small style="color: #666;">Enter a URL for the product image (optional)</small>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn">Update Clothing Item</button>
                    <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

<?php
mysqli_close($conn);
?>