<?php
// edit_clothing.php - Admin interface for editing existing clothing items
// Retrieves current item data, displays editable form, and processes updates
session_start();

// Authentication check: Only allow logged-in admin users
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Establish database connection to clothesstore database
$conn = mysqli_connect("localhost", "root", "", "clothesstore");

// Terminate script if database connection fails
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Initialize variables for feedback messages and clothing data
$message = '';
$error = '';
$clothing = null;

// Validate that clothing ID is provided in URL parameters
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect to dashboard if no ID specified
    header("Location: admin_dashboard.php");
    exit();
}

// Sanitize the clothing ID from URL to prevent SQL injection
$clothing_id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch existing clothing data for the specified ID
$sql = "SELECT * FROM clothing WHERE clothing_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $clothing_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Redirect if no matching clothing item found
if (mysqli_num_rows($result) == 0) {
    header("Location: admin_dashboard.php");
    exit();
}

// Store fetched clothing data for form pre-population
$clothing = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt); // Free prepared statement resources

// Process form submission when user updates the item
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form inputs
    $name = trim($_POST['name']); // Remove leading/trailing whitespace
    $category = trim($_POST['category']);
    $price = floatval($_POST['price']); // Convert to float for decimal handling
    $stock = intval($_POST['stock']); // Convert to integer for whole numbers
    $description = trim($_POST['description']);
    $image_url = trim($_POST['image_url']);
    
    // Validate required fields and price positivity
    if (empty($name) || empty($category) || $price <= 0) {
        $error = "Please fill in all required fields (Name, Category, Price).";
    } else {
        // Prepare update statement with all fields plus WHERE clause for specific item
        $update_sql = "UPDATE clothing SET name=?, category=?, price=?, stock=?, description=?, image_url=? WHERE clothing_id=?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        // Bind parameters: 3 strings, 1 double, 2 integers, 1 integer for WHERE
        mysqli_stmt_bind_param($update_stmt, "ssdissi", $name, $category, $price, $stock, $description, $image_url, $clothing_id);
        
        // Execute update and check for success
        if (mysqli_stmt_execute($update_stmt)) {
            $message = "Clothing item updated successfully!";
            // Update the local clothing array to reflect changes in the form
            $clothing['name'] = $name;
            $clothing['category'] = $category;
            $clothing['price'] = $price;
            $clothing['stock'] = $stock;
            $clothing['description'] = $description;
            $clothing['image_url'] = $image_url;
        } else {
            // Capture database error message if update fails
            $error = "Error updating clothing: " . mysqli_error($conn);
        }
        mysqli_stmt_close($update_stmt); // Clean up statement resources
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Responsive viewport for mobile devices -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Clothing - Admin Panel</title>
    <style>
        /* Universal reset for consistent cross-browser rendering */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            /* Subtle gradient background with light blue and light purple */
            background: linear-gradient(135deg, #e6f3ff, #f0e6ff);
            min-height: 100vh;
        }
        .header {
            /* Gradient header with blue to purple horizontal transition */
            background: linear-gradient(to right, #7cb9e8, #9b7ec4);
            color: white;
            padding: 20px;
            /* Flex layout: title left, user info right */
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { font-size: 2em; }
        /* Centered content container with max-width */
        .container { max-width: 800px; margin: 40px auto; padding: 20px; }
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            /* Subtle shadow for depth */
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            /* Purple border matching theme */
            border: 2px solid #6b4e96;
        }
        h2 { color: #6b4e96; margin-bottom: 20px; }
        /* Spacing between form input groups */
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; color: #4a3a6b; font-weight: bold; }
        /* Unified input styling */
        input, select, textarea { 
            width: 100%; 
            padding: 10px; 
            border: 2px solid #d0c4e8; 
            border-radius: 5px; 
            font-size: 16px; 
        }
        /* Purple border on focus for visual feedback */
        input:focus, select:focus, textarea:focus { outline: none; border-color: #6b4e96; }
        /* Allow vertical resizing only for textareas */
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
        .btn:hover { background: #5a3d82; } /* Darker shade on hover */
        /* Secondary button style for cancel action */
        .btn-secondary {
            background: #999;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-secondary:hover { background: #777; }
        /* Green success message banner */
        .message {
            background: #e6ffe6;
            color: #006600;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #4CAF50;
        }
        /* Red error message banner */
        .error {
            background: #ffe6e6;
            color: #cc0000;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #cc0000;
        }
        /* Flex container for button alignment */
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        /* Preview of current product image */
        .current-image {
            margin-top: 10px;
            padding: 10px;
            background: #f0f0f0;
            border-radius: 5px;
        }
        .current-image img {
            max-width: 100px; /* Constrain preview size */
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Past Times - Admin Panel</h1>
        <div>
            <!-- Display logged-in admin name with XSS protection -->
            Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
            <!-- Logout link to end session -->
            <a href="logout.php" style="color: white; text-decoration: none; margin-left: 20px;">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>Edit Clothing Item</h2>
            
            <!-- Display success message when update is successful -->
            <?php if ($message): ?>
                <div class="message">✅ <?php echo $message; ?></div>
            <?php endif; ?>
            
            <!-- Display error message when validation or update fails -->
            <?php if ($error): ?>
                <div class="error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Form submits to same page for processing -->
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Name *</label>
                    <!-- Pre-populated with current clothing name, escaped for security -->
                    <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($clothing['name']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <option value="">Select Category</option>
                        <!-- Mark current category as selected using ternary operator -->
                        <option value="men" <?php echo ($clothing['category'] == 'men') ? 'selected' : ''; ?>>Men</option>
                        <option value="women" <?php echo ($clothing['category'] == 'women') ? 'selected' : ''; ?>>Women</option>
                        <option value="kids" <?php echo ($clothing['category'] == 'kids') ? 'selected' : ''; ?>>Kids</option>
                        <option value="accessories" <?php echo ($clothing['category'] == 'accessories') ? 'selected' : ''; ?>>Accessories</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="price">Price ($) *</label>
                    <!-- Step 0.01 allows decimal cents, min 0 prevents negative values -->
                    <input type="number" id="price" name="price" step="0.01" min="0" required value="<?php echo $clothing['price']; ?>">
                </div>
                
                <div class="form-group">
                    <label for="stock">Stock Quantity</label>
                    <input type="number" id="stock" name="stock" min="0" value="<?php echo $clothing['stock']; ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <!-- Pre-populated textarea with current description -->
                    <textarea id="description" name="description"><?php echo htmlspecialchars($clothing['description']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="image_url">Image URL</label>
                    <!-- Pre-populated image URL field -->
                    <input type="text" id="image_url" name="image_url" placeholder="https://example.com/image.jpg" value="<?php echo htmlspecialchars($clothing['image_url']); ?>">
                    <!-- Display current image thumbnail if URL exists -->
                    <?php if ($clothing['image_url']): ?>
                        <div class="current-image">
                            <strong>Current Image:</strong><br>
                            <!-- Show existing image with XSS-safe URL -->
                            <img src="<?php echo htmlspecialchars($clothing['image_url']); ?>" alt="Current">
                        </div>
                    <?php endif; ?>
                    <small style="color: #666;">Enter a URL for the product image (optional)</small>
                </div>
                
                <div class="button-group">
                    <!-- Submit button triggers form processing -->
                    <button type="submit" class="btn">Update Clothing Item</button>
                    <!-- Cancel returns to dashboard without saving -->
                    <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

<?php
// Close database connection to free server resources
mysqli_close($conn);
?>