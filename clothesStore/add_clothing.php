<?php
// add_clothing.php - Admin interface for adding new clothing items to the database
session_start();

// Security check: Verify admin authentication before allowing access
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

// Initialize message variables for user feedback
$message = '';
$error = '';

// Process form data when submitted via POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize string inputs by removing leading/trailing whitespace
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    // Convert price to float for decimal handling (allows cents)
    $price = floatval($_POST['price']);
    // Convert stock to integer for whole number quantities
    $stock = intval($_POST['stock']);
    $description = trim($_POST['description']);
    $image_url = trim($_POST['image_url']);
    
    // Validate required fields and price positivity
    if (empty($name) || empty($category) || $price <= 0) {
        $error = "Please fill in all required fields (Name, Category, Price).";
    } else {
        // Prepare SQL statement with parameterized query to prevent SQL injection
        $sql = "INSERT INTO clothing (name, category, price, stock, description, image_url) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        // Bind parameters with type specification: s=string, d=double, i=integer
        mysqli_stmt_bind_param($stmt, "ssdiss", $name, $category, $price, $stock, $description, $image_url);
        
        // Execute the prepared statement and check for success
        if (mysqli_stmt_execute($stmt)) {
            $message = "Clothing item added successfully!";
            // Reset form fields to default values after successful insertion
            $name = $category = $description = $image_url = '';
            $price = 0;
            $stock = 0;
        } else {
            // Capture and display database error if insertion fails
            $error = "Error adding clothing: " . mysqli_error($conn);
        }
        // Clean up prepared statement resources
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Ensure proper rendering on mobile devices -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Clothing - Admin Panel</title>
    <style>
        /* Reset default browser styles for consistent rendering */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            /* Subtle gradient background combining light blue and light purple */
            background: linear-gradient(135deg, #e6f3ff, #f0e6ff);
            min-height: 100vh;
        }
        .header {
            /* Gradient header with blue to purple transition */
            background: linear-gradient(to right, #7cb9e8, #9b7ec4);
            color: white;
            padding: 20px;
            /* Flexbox for horizontal layout with space between elements */
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { font-size: 2em; }
        /* Center container with max-width for readability */
        .container { max-width: 800px; margin: 40px auto; padding: 20px; }
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            /* Subtle shadow for depth effect */
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            /* Purple border to match the theme */
            border: 2px solid #6b4e96;
        }
        h2 { color: #6b4e96; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; color: #4a3a6b; font-weight: bold; }
        /* Unified styling for all form input elements */
        input, select, textarea { 
            width: 100%; 
            padding: 10px; 
            border: 2px solid #d0c4e8; 
            border-radius: 5px; 
            font-size: 16px; 
        }
        /* Visual feedback when form elements are focused */
        input:focus, select:focus, textarea:focus { outline: none; border-color: #6b4e96; }
        /* Allow vertical resizing only for textarea */
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
        /* Darker shade on hover for interactive feedback */
        .btn:hover { background: #5a3d82; }
        /* Secondary button style for cancel action */
        .btn-secondary {
            background: #999;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-secondary:hover { background: #777; }
        /* Success message styling with green theme */
        .message {
            background: #e6ffe6;
            color: #006600;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #4CAF50;
        }
        /* Error message styling with red theme */
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
    </style>
</head>
<body>
    <div class="header">
        <h1>Past Times - Admin Panel</h1>
        <div>
            <!-- Display logged-in admin's name with XSS protection -->
            Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
            <!-- Logout link for session termination -->
            <a href="logout.php" style="color: white; text-decoration: none; margin-left: 20px;">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>Add New Clothing Item</h2>
            
            <!-- Display success message when item is added -->
            <?php if ($message): ?>
                <div class="message">✅ <?php echo $message; ?></div>
            <?php endif; ?>
            
            <!-- Display error message when validation or database operation fails -->
            <?php if ($error): ?>
                <div class="error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Form submits to same page for processing -->
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Name *</label>
                    <!-- Preserve user input if form validation fails -->
                    <input type="text" id="name" name="name" required value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <option value="">Select Category</option>
                        <!-- Preserve selected category after form submission -->
                        <option value="men" <?php echo (isset($category) && $category == 'men') ? 'selected' : ''; ?>>Men</option>
                        <option value="women" <?php echo (isset($category) && $category == 'women') ? 'selected' : ''; ?>>Women</option>
                        <option value="kids" <?php echo (isset($category) && $category == 'kids') ? 'selected' : ''; ?>>Kids</option>
                        <option value="accessories" <?php echo (isset($category) && $category == 'accessories') ? 'selected' : ''; ?>>Accessories</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="price">Price ($) *</label>
                    <!-- Step 0.01 allows decimal values for cents, min 0 prevents negative prices -->
                    <input type="number" id="price" name="price" step="0.01" min="0" required value="<?php echo isset($price) ? $price : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="stock">Stock Quantity</label>
                    <!-- Default value of 0 if no stock specified -->
                    <input type="number" id="stock" name="stock" min="0" value="<?php echo isset($stock) ? $stock : 0; ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <!-- Multiline text input for product description -->
                    <textarea id="description" name="description"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="image_url">Image URL</label>
                    <!-- Optional field with placeholder showing expected format -->
                    <input type="text" id="image_url" name="image_url" placeholder="https://example.com/image.jpg" value="<?php echo isset($image_url) ? htmlspecialchars($image_url) : ''; ?>">
                    <small style="color: #666;">Enter a URL for the product image (optional)</small>
                </div>
                
                <div class="button-group">
                    <!-- Primary submit button triggers form submission -->
                    <button type="submit" class="btn">Add Clothing Item</button>
                    <!-- Cancel button returns to admin dashboard without submitting -->
                    <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

<?php
// Close database connection to free resources
mysqli_close($conn);
?>