<?php
// seller_submit_request.php - Sellers submit requests to sell clothes
session_start();
include 'DBConn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check if user is a seller
if ($_SESSION['user_role'] != 'seller') {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $clothing_name = mysqli_real_escape_string($conn, $_POST['clothing_name']);
    $brand = mysqli_real_escape_string($conn, $_POST['brand']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = floatval($_POST['price']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $condition = mysqli_real_escape_string($conn, $_POST['condition']);
    
    // Handle image upload
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "uploads/seller_items/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_extension, $allowed)) {
            $filename = time() . "_" . uniqid() . "." . $file_extension;
            $target_file = $target_dir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_url = $target_file;
            } else {
                $error = "Failed to upload image.";
            }
        } else {
            $error = "Invalid file type. Only JPG, PNG, GIF allowed.";
        }
    }
    
    if (empty($error)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO seller_requests (user_id, clothing_name, brand, description, image_url, price, category, condition_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "issssdss", $user_id, $clothing_name, $brand, $description, $image_url, $price, $category, $condition);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = "Your request has been submitted! Admin will review it and contact you.";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Sell Request - Past Times</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
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
        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .header-right a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .header-right a:hover {
            background: rgba(255,255,255,0.2);
        }
        .welcome-text {
            opacity: 0.9;
        }
        .container { max-width: 800px; margin: 40px auto; padding: 20px; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.2); }
        h2 { color: #764ba2; margin-bottom: 10px; }
        .subtitle { color: #666; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #764ba2; box-shadow: 0 0 5px rgba(118, 75, 162, 0.3); }
        textarea { resize: vertical; min-height: 100px; }
        .btn { background: #764ba2; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; transition: background 0.3s; }
        .btn:hover { background: #5a3d82; }
        .btn-secondary { background: #6c757d; text-decoration: none; display: inline-block; text-align: center; padding: 12px 30px; border-radius: 5px; color: white; transition: background 0.3s; }
        .btn-secondary:hover { background: #5a6268; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #28a745; }
        .success a { color: #155724; font-weight: bold; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #dc3545; }
        .button-group { display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap; }
        .image-preview { margin-top: 10px; max-width: 200px; display: none; }
        .image-preview img { width: 100%; border-radius: 5px; border: 2px solid #ddd; }
        small { color: #666; display: block; margin-top: 5px; }
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
        <div class="header-right">
            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>📦 Sell Your Clothes</h2>
            <p class="subtitle">Fill out the form below. Admin will review your request and contact you.</p>
            
            <?php if ($success): ?>
                <div class="success">✅ <?php echo $success; ?> <a href="dashboard.php">Go to Dashboard →</a></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Clothing Name *</label>
                    <input type="text" name="clothing_name" required placeholder="e.g., Vintage Levi's Jeans">
                </div>
                
                <div class="form-group">
                    <label>Brand *</label>
                    <input type="text" name="brand" required placeholder="e.g., Nike, Zara, Gucci, Levi's">
                </div>
                
                <div class="form-group">
                    <label>Category</label>
                    <select name="category">
                        <option value="men">Men</option>
                        <option value="women">Women</option>
                        <option value="kids">Kids</option>
                        <option value="accessories">Accessories</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Condition *</label>
                    <select name="condition" required>
                        <option value="new">New with tags</option>
                        <option value="like_new">Like New</option>
                        <option value="good">Good condition</option>
                        <option value="fair">Fair condition (visible wear)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Price ($) *</label>
                    <input type="number" name="price" step="0.01" min="0" required placeholder="0.00">
                </div>
                
                <div class="form-group">
                    <label>Description *</label>
                    <textarea name="description" required placeholder="Describe the item: size, color, material, any defects, etc."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Upload Image *</label>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/jpg,image/gif" required onchange="previewImage(this)">
                    <small>Upload a clear photo of the item (Max 5MB)</small>
                    <div class="image-preview" id="imagePreview">
                        <img id="previewImg" src="">
                    </div>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn">Submit Request</button>
                    <a href="dashboard.php" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const img = document.getElementById('previewImg');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>