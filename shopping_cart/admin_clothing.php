<?php
// admin_clothing.php - Admin CRUD interface for managing clothing inventory
// Provides full Create, Read, Update, Delete functionality for clothing items
session_start(); // Start session to access admin authentication data
include 'DBConn.php'; // Include database connection file

// Security check: Redirect to login if admin is not authenticated
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle Add Clothing form submission
// Checks both POST method and specific submit button to distinguish from update form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_clothing'])) {
    // Sanitize all string inputs to prevent SQL injection
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    // Convert price to float for proper decimal storage
    $price = floatval($_POST['price']);
    // Convert stock to integer for whole number quantities
    $stock = intval($_POST['stock']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $image_url = mysqli_real_escape_string($conn, $_POST['image_url']);
    
    // Use prepared statement for secure database insertion
    $stmt = mysqli_prepare($conn, "INSERT INTO clothing (name, category, price, stock, description, image_url) VALUES (?, ?, ?, ?, ?, ?)");
    // Bind parameters with type specification: s=string, d=double, i=integer
    mysqli_stmt_bind_param($stmt, "ssdiss", $name, $category, $price, $stock, $description, $image_url);
    mysqli_stmt_execute($stmt);
    // Redirect with success message to prevent form resubmission
    header("Location: admin_clothing.php?msg=added");
    exit();
}

// Handle Update Clothing form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_clothing'])) {
    // Convert ID to integer for security (prevents injection in numeric field)
    $id = intval($_POST['clothing_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $image_url = mysqli_real_escape_string($conn, $_POST['image_url']);
    
    // Update query with WHERE clause to target specific clothing item
    $stmt = mysqli_prepare($conn, "UPDATE clothing SET name=?, category=?, price=?, stock=?, description=?, image_url=? WHERE clothing_id=?");
    // Note the additional "i" type for the WHERE clause ID parameter
    mysqli_stmt_bind_param($stmt, "ssdissi", $name, $category, $price, $stock, $description, $image_url, $id);
    mysqli_stmt_execute($stmt);
    header("Location: admin_clothing.php?msg=updated");
    exit();
}

// Handle Delete Clothing via URL parameters
// Uses GET method since delete links don't submit forms
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = intval($_GET['id']); // Ensure ID is integer for security
    $stmt = mysqli_prepare($conn, "DELETE FROM clothing WHERE clothing_id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    header("Location: admin_clothing.php?msg=deleted");
    exit();
}

// Retrieve all clothing items ordered by newest first
$clothing = mysqli_query($conn, "SELECT * FROM clothing ORDER BY clothing_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Clothing - Admin Panel</title>
    <style>
        /* Universal reset for consistent cross-browser rendering */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        /* Header with gradient purple-blue background spanning full width */
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        /* Left side of header containing logo and title */
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px; /* Space between logo and title */
        }
        /* Fixed dimensions for the clock logo icon container */
        .header-logo-icon {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        /* Ensure SVG scales to fit container */
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
        /* Slightly transparent welcome text for visual hierarchy */
        .welcome-text {
            opacity: 0.9;
        }
        /* Center content with maximum width for large screens */
        .container { max-width: 1200px; margin: 40px auto; padding: 20px; }
        /* White card with subtle shadow for content sections */
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        h2 { color: #764ba2; margin-bottom: 20px; }
        /* Full-width table with collapsed borders for clean appearance */
        table { width: 100%; border-collapse: collapse; }
        /* Table cell padding and bottom border for row separation */
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        /* Header row with purple background and white text */
        th { background: #764ba2; color: white; }
        /* Hover effect to highlight table rows */
        tr:hover { background: #f5f5f5; }
        /* Base button styles with smooth transition for hover effects */
        .btn { padding: 5px 15px; border: none; border-radius: 3px; cursor: pointer; text-decoration: none; display: inline-block; margin: 2px; font-size: 12px; transition: all 0.3s; }
        /* Green add button for positive actions */
        .btn-add { background: #28a745; color: white; }
        .btn-add:hover { background: #218838; }
        /* Yellow edit button with dark text for contrast */
        .btn-edit { background: #ffc107; color: black; }
        .btn-edit:hover { background: #e0a800; }
        /* Red delete button for destructive actions */
        .btn-delete { background: #dc3545; color: white; }
        .btn-delete:hover { background: #c82333; }
        /* Gray back button for neutral navigation */
        .btn-back { background: #6c757d; color: white; }
        .btn-back:hover { background: #5a6268; }
        /* Green success message banner */
        .message { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        /* Spacing for form input groups */
        .form-group { margin-bottom: 15px; }
        /* Bold labels for form fields */
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        /* Full-width form inputs with light border */
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        /* Purple glow effect on form element focus for visual feedback */
        input:focus, select:focus, textarea:focus { outline: none; border-color: #764ba2; box-shadow: 0 0 5px rgba(118, 75, 162, 0.3); }
        /* Modal overlay - hidden by default, covers entire viewport */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000; }
        /* Modal content box with scroll for overflow */
        .modal-content { background: white; padding: 30px; border-radius: 10px; width: 500px; max-height: 80vh; overflow-y: auto; }
        /* Show modal when targeted by URL hash (CSS-only modal toggle) */
        .modal:target { display: flex; }
        /* Close button positioned in top-right corner */
        .close { float: right; text-decoration: none; font-size: 24px; color: #333; transition: color 0.3s; }
        .close:hover { color: #000; }
        /* Thumbnail image styling for product images in table */
        .clothing-image { width: 50px; height: 50px; object-fit: cover; border-radius: 5px; }
        
        /* Navigation Tiles */
        /* CSS Grid layout for responsive navigation cards */
        .nav-tiles {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); /* Automatically adjusts columns */
            gap: 20px;
            margin-top: 30px;
        }
        /* Individual navigation tile with purple gradient and white text */
        .nav-tile {
            background: linear-gradient(135deg, #764ba2, #5a3d8a);
            color: white;
            padding: 25px 20px;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            transition: transform 0.3s, box-shadow 0.3s; /* Smooth hover animation */
            display: flex;
            flex-direction: column; /* Stack icon, label, description vertically */
            align-items: center;
            justify-content: center;
            min-height: 120px;
        }
        /* Lift effect on tile hover */
        .nav-tile:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(118, 75, 162, 0.3); /* Purple shadow */
        }
        /* Large emoji icon in tile */
        .nav-tile .icon {
            font-size: 36px;
            margin-bottom: 10px;
        }
        /* Bold label text */
        .nav-tile .label {
            font-size: 16px;
            font-weight: bold;
        }
        /* Smaller, subtle description text */
        .nav-tile .description {
            font-size: 12px;
            opacity: 0.8;
            margin-top: 5px;
        }
        /* Unique gradient colors for each navigation tile */
        .nav-tile.dashboard { background: linear-gradient(135deg, #667eea, #764ba2); }
        .nav-tile.requests { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .nav-tile.messages { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .nav-tile.logout { background: linear-gradient(135deg, #fa709a, #fee140); }
        
        /* Responsive design for mobile devices */
        @media (max-width: 768px) {
            .header { flex-direction: column; gap: 15px; text-align: center; }
            .modal-content { width: 90%; } /* Full-width modals on small screens */
            table { font-size: 14px; } /* Smaller table text */
            th, td { padding: 8px; } /* Reduced padding */
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="header-logo-icon">
                <!-- Simple clock SVG icon representing "Past Times" brand -->
                <svg width="45" height="45" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
                    <!-- Outer circle with slight transparency -->
                    <circle cx="30" cy="30" r="28" fill="#ffffff" stroke="#ffffff" stroke-width="2" opacity="0.3"/>
                    <!-- Inner circle fill -->
                    <circle cx="30" cy="30" r="24" fill="rgba(255,255,255,0.1)"/>
                    <!-- Clock numbers at 12, 3, 6, and 9 positions -->
                    <text x="30" y="10" text-anchor="middle" font-size="8" fill="white" font-weight="bold">12</text>
                    <text x="48" y="33" text-anchor="middle" font-size="8" fill="white" font-weight="bold">3</text>
                    <text x="30" y="56" text-anchor="middle" font-size="8" fill="white" font-weight="bold">6</text>
                    <text x="12" y="33" text-anchor="middle" font-size="8" fill="white" font-weight="bold">9</text>
                    <!-- Hour tick marks at cardinal directions -->
                    <line x1="30" y1="8" x2="30" y2="13" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="52" y1="30" x2="47" y2="30" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="30" y1="52" x2="30" y2="47" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="8" y1="30" x2="13" y2="30" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <!-- Clock hands set to 10:10 (classic clock display time) -->
                    <line x1="30" y1="30" x2="30" y2="12" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                    <line x1="30" y1="30" x2="42" y2="20" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    <!-- Center dot at clock pivot point -->
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
                // Map message codes to user-friendly notifications
                if ($_GET['msg'] == 'added') echo "✅ Clothing item added successfully!";
                if ($_GET['msg'] == 'updated') echo "✅ Clothing item updated successfully!";
                if ($_GET['msg'] == 'deleted') echo "✅ Clothing item deleted successfully!";
                ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <!-- Flex container to align heading and add button on same line -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Clothing Inventory</h2>
                <!-- Link opens Add Clothing modal via CSS :target selector -->
                <a href="#addClothingModal" class="btn btn-add">+ Add New Clothing</a>
            </div>
            
            <!-- Only display table if clothing items exist in database -->
            <?php if (mysqli_num_rows($clothing) > 0): ?>
            <table>
                <thead>
                    <!-- Table column headers -->
                    <tr><th>ID</th><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Description</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <!-- Loop through each clothing item from database query -->
                    <?php while($item = mysqli_fetch_assoc($clothing)): ?>
                    <tr>
                        <td><?php echo $item['clothing_id']; ?></td>
                        <td>
                            <!-- Display thumbnail if image URL exists, otherwise show placeholder text -->
                            <?php if ($item['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="clothing-image">
                            <?php else: ?>
                                No Image
                            <?php endif; ?>
                        </td>
                        <!-- Escape output to prevent XSS attacks -->
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <!-- Capitalize first letter of category for display -->
                        <td><?php echo ucfirst($item['category']); ?></td>
                        <!-- Format price with 2 decimal places and dollar sign -->
                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                        <td><?php echo $item['stock']; ?></td>
                        <!-- Truncate description to 50 characters for table display -->
                        <td><?php echo substr(htmlspecialchars($item['description']), 0, 50); ?>...</td>
                        <td>
                            <!-- Edit link opens item-specific modal using unique ID -->
                            <a href="#editModal<?php echo $item['clothing_id']; ?>" class="btn btn-edit">Edit</a>
                            <!-- Delete link with JavaScript confirmation dialog -->
                            <a href="?delete&id=<?php echo $item['clothing_id']; ?>" class="btn btn-delete" onclick="return confirm('Delete this item?')">Delete</a>
                        </td>
                    </tr>
                    
                    <!-- Edit Modal - unique for each clothing item using its ID -->
                    <div id="editModal<?php echo $item['clothing_id']; ?>" class="modal">
                        <div class="modal-content">
                            <!-- Close button returns to main page (removes hash from URL) -->
                            <a href="#" class="close">&times;</a>
                            <h3>Edit Clothing Item</h3>
                            <!-- Form submits to same page with update_clothing flag -->
                            <form method="POST">
                                <!-- Hidden field to identify which item to update -->
                                <input type="hidden" name="clothing_id" value="<?php echo $item['clothing_id']; ?>">
                                <div class="form-group">
                                    <label>Name:</label>
                                    <!-- Pre-fill form with existing values for editing -->
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($item['name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Category:</label>
                                    <select name="category" required>
                                        <!-- Mark current category as selected in dropdown -->
                                        <option value="men" <?php echo $item['category'] == 'men' ? 'selected' : ''; ?>>Men</option>
                                        <option value="women" <?php echo $item['category'] == 'women' ? 'selected' : ''; ?>>Women</option>
                                        <option value="kids" <?php echo $item['category'] == 'kids' ? 'selected' : ''; ?>>Kids</option>
                                        <option value="accessories" <?php echo $item['category'] == 'accessories' ? 'selected' : ''; ?>>Accessories</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Price:</label>
                                    <!-- Step 0.01 allows decimal cents in price -->
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
                                <!-- Submit button named update_clothing to distinguish from add form -->
                                <button type="submit" name="update_clothing" class="btn btn-edit">Update</button>
                            </form>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
                <!-- Displayed when no clothing items exist in database -->
                <p>No clothing items found.</p>
            <?php endif; ?>
        </div>
        
        <!-- Navigation Tiles - quick access to other admin sections -->
        <div class="nav-tiles">
            <!-- Dashboard tile navigates to main admin dashboard -->
            <a href="admin_dashboard.php" class="nav-tile dashboard">
                <div class="icon">📊</div>
                <div class="label">Dashboard</div>
                <div class="description">Return to admin dashboard</div>
            </a>
            
            <!-- Seller Requests tile for reviewing applications -->
            <a href="admin_requests.php" class="nav-tile requests">
                <div class="icon">📋</div>
                <div class="label">Seller Requests</div>
                <div class="description">Review seller applications</div>
            </a>
            
            <!-- Messages tile for viewing user communications -->
            <a href="admin_messages.php" class="nav-tile messages">
                <div class="icon">💬</div>
                <div class="label">Messages</div>
                <div class="description">View user messages</div>
            </a>
            
            <!-- Logout tile ends admin session -->
            <a href="logout.php" class="nav-tile logout">
                <div class="icon">🚪</div>
                <div class="label">Logout</div>
                <div class="description">Sign out of your account</div>
            </a>
        </div>
    </div>
    
    <!-- Add Clothing Modal - single modal for creating new items -->
    <div id="addClothingModal" class="modal">
        <div class="modal-content">
            <!-- Close button hides modal by removing URL hash -->
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
                        <!-- Product category options for classification -->
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
                    <!-- Default stock value of 0 for new items -->
                    <input type="number" name="stock" value="0">
                </div>
                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="description"></textarea>
                </div>
                <div class="form-group">
                    <label>Image URL:</label>
                    <!-- Placeholder shows expected URL format -->
                    <input type="text" name="image_url" placeholder="https://example.com/image.jpg">
                </div>
                <!-- Submit button named add_clothing to distinguish from update form -->
                <button type="submit" name="add_clothing" class="btn btn-add">Add Item</button>
            </form>
        </div>
    </div>
</body>
</html>
<?php 
// Close database connection to free server resources
mysqli_close($conn); 
?>