<?php
// seller_submit_request.php - Form for sellers to submit clothing items for admin approval
// Handles file uploads, form validation, and inserts requests into seller_requests table
session_start(); // Start session for user authentication
include 'DBConn.php'; // Include database connection

// Authentication check: Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Authorization check: Only allow users with 'seller' role to access this page
if ($_SESSION['user_role'] != 'seller') {
    header("Location: dashboard.php"); // Redirect non-sellers to dashboard
    exit();
}

// Initialize feedback message variables
$error = '';
$success = '';

// Process form submission when seller submits a clothing request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id']; // Get current seller's ID
    
    // Sanitize all text inputs to prevent SQL injection
    $clothing_name = mysqli_real_escape_string($conn, $_POST['clothing_name']);
    $brand = mysqli_real_escape_string($conn, $_POST['brand']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = floatval($_POST['price']); // Convert price to float
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $condition = mysqli_real_escape_string($conn, $_POST['condition']);
    
    // Handle image file upload
    $image_url = '';
    // Check if file was uploaded without errors
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        // Set target directory for seller item uploads
        $target_dir = "uploads/seller_items/";
        
        // Create the directory if it doesn't already exist
        // 0777 grants full read/write/execute permissions, 'true' enables recursive creation
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        // Whitelist of allowed file extensions for security
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        // Extract file extension from uploaded filename and convert to lowercase
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        // Validate that the file extension is in the allowed list
        if (in_array($file_extension, $allowed)) {
            // Generate unique filename using timestamp + unique ID to prevent collisions
            $filename = time() . "_" . uniqid() . "." . $file_extension;
            $target_file = $target_dir . $filename;
            
            // Move uploaded file from temporary location to target directory
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_url = $target_file; // Store file path for database
            } else {
                $error = "Failed to upload image.";
            }
        } else {
            $error = "Invalid file type. Only JPG, PNG, GIF allowed.";
        }
    }
    
    // Only proceed with database insertion if no errors occurred
    if (empty($error)) {
        // Prepare INSERT statement for seller_requests table
        $stmt = mysqli_prepare($conn, "INSERT INTO seller_requests (user_id, clothing_name, brand, description, image_url, price, category, condition_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        // Bind parameters: integer, 5 strings, double, string
        mysqli_stmt_bind_param($stmt, "issssdss", $user_id, $clothing_name, $brand, $description, $image_url, $price, $category, $condition);
        
        // Execute and check for success
        if (mysqli_stmt_execute($stmt)) {
            $success = "Your request has been submitted! Admin will review it and contact you.";
        } else {
            // Capture database error for debugging
            $error = "Error: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt); // Clean up prepared statement
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Sell Request - Past Times</title>
    <style>
        /* Universal reset for consistent cross-browser rendering */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        /* Full viewport background with brand gradient */
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        /* Header bar with same gradient, ensuring seamless appearance */
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        /* Left side: logo and store name */
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px; /* Space between logo and title */
        }
        /* Fixed size container for clock SVG logo */
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
        /* Right side: welcome text and navigation links */
        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        /* Navigation links with hover effect */
        .header-right a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .header-right a:hover {
            background: rgba(255,255,255,0.2); /* Semi-transparent hover */
        }
        /* Semi-transparent welcome text */
        .welcome-text {
            opacity: 0.9;
        }
        /* Centered content container with max-width */
        .container { max-width: 800px; margin: 40px auto; padding: 20px; }
        /* White card with shadow for the form */
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.2); }
        h2 { color: #764ba2; margin-bottom: 10px; }
        /* Subtitle with lighter text color */
        .subtitle { color: #666; margin-bottom: 20px; }
        /* Consistent spacing between form groups */
        .form-group { margin-bottom: 20px; }
        /* Bold form labels */
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        /* Full-width form inputs with light border */
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        /* Purple glow effect on focus for visual feedback */
        input:focus, select:focus, textarea:focus { outline: none; border-color: #764ba2; box-shadow: 0 0 5px rgba(118, 75, 162, 0.3); }
        /* Allow vertical resizing only for textarea */
        textarea { resize: vertical; min-height: 100px; }
        /* Purple submit button with hover transition */
        .btn { background: #764ba2; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; transition: background 0.3s; }
        .btn:hover { background: #5a3d82; } /* Darker purple on hover */
        /* Gray secondary button for cancel action */
        .btn-secondary { background: #6c757d; text-decoration: none; display: inline-block; text-align: center; padding: 12px 30px; border-radius: 5px; color: white; transition: background 0.3s; }
        .btn-secondary:hover { background: #5a6268; }
        /* Green success message with left border accent */
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #28a745; }
        .success a { color: #155724; font-weight: bold; }
        /* Red error message with left border accent */
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #dc3545; }
        /* Flex container for button alignment with wrapping */
        .button-group { display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap; }
        /* Image preview container, hidden by default until file selected */
        .image-preview { margin-top: 10px; max-width: 200px; display: none; }
        .image-preview img { width: 100%; border-radius: 5px; border: 2px solid #ddd; }
        /* Help text styling */
        small { color: #666; display: block; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="header-logo-icon">
                <!-- Simple clock SVG icon representing "Past Times" brand -->
                <svg width="45" height="45" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
                    <!-- Outer decorative ring -->
                    <circle cx="30" cy="30" r="28" fill="#ffffff" stroke="#ffffff" stroke-width="2" opacity="0.3"/>
                    <!-- Inner transparent fill -->
                    <circle cx="30" cy="30" r="24" fill="rgba(255,255,255,0.1)"/>
                    <!-- Clock numerals at cardinal positions -->
                    <text x="30" y="10" text-anchor="middle" font-size="8" fill="white" font-weight="bold">12</text>
                    <text x="48" y="33" text-anchor="middle" font-size="8" fill="white" font-weight="bold">3</text>
                    <text x="30" y="56" text-anchor="middle" font-size="8" fill="white" font-weight="bold">6</text>
                    <text x="12" y="33" text-anchor="middle" font-size="8" fill="white" font-weight="bold">9</text>
                    <!-- Hour marker ticks -->
                    <line x1="30" y1="8" x2="30" y2="13" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="52" y1="30" x2="47" y2="30" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="30" y1="52" x2="30" y2="47" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="8" y1="30" x2="13" y2="30" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <!-- Clock hands at classic 10:10 position -->
                    <line x1="30" y1="30" x2="30" y2="12" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                    <line x1="30" y1="30" x2="42" y2="20" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    <!-- Center pivot dot -->
                    <circle cx="30" cy="30" r="2.5" fill="white"/>
                </svg>
            </div>
            <h1>Past Times</h1>
        </div>
        <div class="header-right">
            <!-- Display logged-in seller's name -->
            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <!-- Navigation links -->
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>📦 Sell Your Clothes</h2>
            <p class="subtitle">Fill out the form below. Admin will review your request and contact you.</p>
            
            <!-- Display success message after successful submission -->
            <?php if ($success): ?>
                <div class="success">✅ <?php echo $success; ?> <a href="dashboard.php">Go to Dashboard →</a></div>
            <?php endif; ?>
            
            <!-- Display error message if validation or upload fails -->
            <?php if ($error): ?>
                <div class="error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Form with enctype for file upload support -->
            <form method="POST" enctype="multipart/form-data">
                <!-- Clothing name input -->
                <div class="form-group">
                    <label>Clothing Name *</label>
                    <input type="text" name="clothing_name" required placeholder="e.g., Vintage Levi's Jeans">
                </div>
                
                <!-- Brand name input -->
                <div class="form-group">
                    <label>Brand *</label>
                    <input type="text" name="brand" required placeholder="e.g., Nike, Zara, Gucci, Levi's">
                </div>
                
                <!-- Category dropdown -->
                <div class="form-group">
                    <label>Category</label>
                    <select name="category">
                        <option value="men">Men</option>
                        <option value="women">Women</option>
                        <option value="kids">Kids</option>
                        <option value="accessories">Accessories</option>
                    </select>
                </div>
                
                <!-- Condition dropdown with descriptive options -->
                <div class="form-group">
                    <label>Condition *</label>
                    <select name="condition" required>
                        <option value="new">New with tags</option>
                        <option value="like_new">Like New</option>
                        <option value="good">Good condition</option>
                        <option value="fair">Fair condition (visible wear)</option>
                    </select>
                </div>
                
                <!-- Price input with decimal support -->
                <div class="form-group">
                    <label>Price ($) *</label>
                    <!-- Step 0.01 allows cents, min 0 prevents negative values -->
                    <input type="number" name="price" step="0.01" min="0" required placeholder="0.00">
                </div>
                
                <!-- Description textarea -->
                <div class="form-group">
                    <label>Description *</label>
                    <textarea name="description" required placeholder="Describe the item: size, color, material, any defects, etc."></textarea>
                </div>
                
                <!-- File upload input with accepted file types restriction -->
                <div class="form-group">
                    <label>Upload Image *</label>
                    <!-- Accept attribute restricts file dialog to image types -->
                    <input type="file" name="image" accept="image/jpeg,image/png,image/jpg,image/gif" required onchange="previewImage(this)">
                    <small>Upload a clear photo of the item (Max 5MB)</small>
                    <!-- Hidden image preview container, shown when file is selected -->
                    <div class="image-preview" id="imagePreview">
                        <img id="previewImg" src="">
                    </div>
                </div>
                
                <!-- Action buttons -->
                <div class="button-group">
                    <button type="submit" class="btn">Submit Request</button>
                    <!-- Cancel returns to dashboard -->
                    <a href="dashboard.php" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Client-side image preview function
        // Reads the selected file and displays it as a thumbnail before upload
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const img = document.getElementById('previewImg');
            
            // Check if a file was selected
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                // When file is loaded, set the image source and show preview
                reader.onload = function(e) {
                    img.src = e.target.result; // Base64 encoded image data
                    preview.style.display = 'block'; // Make preview visible
                }
                reader.readAsDataURL(input.files[0]); // Read file as data URL
            }
        }
    </script>
</body>
</html>