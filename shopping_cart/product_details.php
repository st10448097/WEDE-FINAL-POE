<?php
// product_details.php - Individual product detail page with reviews system
// Displays product information, image, pricing, stock status, and customer reviews
error_reporting(E_ALL); // Enable all error reporting for debugging
ini_set('display_errors', 1); // Display errors on screen
session_start(); // Start session for user data and cart management
include 'DBConn.php'; // Include database connection

// Validate product ID from URL parameter
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: shop.php"); // Redirect to shop if no ID provided
    exit();
}

// Convert ID to integer for security and database compatibility
$product_id = intval($_GET['id']);

// Fetch the product record from clothing table
$stmt = mysqli_prepare($conn, "SELECT * FROM clothing WHERE clothing_id = ?");
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Redirect if product doesn't exist in database
if (mysqli_num_rows($result) == 0) {
    header("Location: shop.php");
    exit();
}

// Store product data for display
$product = mysqli_fetch_assoc($result);

// Check if reviews table exists in the database (optional feature)
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'reviews'");
$has_reviews_table = mysqli_num_rows($table_check) > 0;

// Fetch existing reviews only if reviews table is present
$reviews = null;
if ($has_reviews_table) {
    $review_stmt = mysqli_prepare($conn, "SELECT * FROM reviews WHERE product_id = ? ORDER BY created_at DESC");
    mysqli_stmt_bind_param($review_stmt, "i", $product_id);
    mysqli_stmt_execute($review_stmt);
    $reviews = mysqli_stmt_get_result($review_stmt);
}

// Handle review submission when form is posted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review']) && $has_reviews_table) {
    // Sanitize user input to prevent SQL injection
    $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
    $rating = intval($_POST['rating']); // Convert rating to integer
    $review_text = mysqli_real_escape_string($conn, $_POST['review']);
    
    // Insert new review into database
    $insert = mysqli_prepare($conn, "INSERT INTO reviews (product_id, customer_name, rating, review) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($insert, "isis", $product_id, $customer_name, $rating, $review_text);
    mysqli_stmt_execute($insert);
    
    // Redirect to prevent form resubmission on page refresh
    header("Location: product_details.php?id=" . $product_id);
    exit();
}

// Get cart item count for header display (defaults to 0 if cart doesn't exist)
$cart_count = count($_SESSION['cart'] ?? []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Responsive viewport for mobile devices -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Dynamic page title with product name -->
    <title><?php echo htmlspecialchars($product['name']); ?> - Past Times</title>
    <style>
        /* Universal reset for consistent cross-browser rendering */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        /* Gradient header matching brand color scheme */
        .header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; padding: 15px 20px; 
            display: flex; justify-content: space-between; align-items: center; 
            flex-wrap: wrap; gap: 10px; /* Wrap on small screens */
        }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .header h1 { font-size: 1.8em; }
        /* Right side navigation area */
        .header-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        /* Navigation links with hover effect */
        .header a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: background 0.3s; }
        .header a:hover { background: rgba(255,255,255,0.2); } /* Semi-transparent hover */
        /* Cart count badge with circular shape */
        .cart-badge { background: #ffc107; color: #333; padding: 2px 8px; border-radius: 50%; font-weight: bold; font-size: 0.8em; }
        /* Main content container with max-width */
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        /* Breadcrumb navigation trail */
        .breadcrumb { margin-bottom: 20px; color: #666; }
        .breadcrumb a { color: #764ba2; text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }
        /* Product detail card with shadow */
        .product-detail { 
            background: white; padding: 30px; border-radius: 10px; 
            box-shadow: 0 2px 15px rgba(0,0,0,0.1); margin-bottom: 30px; 
        }
        /* Two-column layout for image and info */
        .product-layout { display: flex; gap: 30px; flex-wrap: wrap; }
        /* Left column: product image */
        .product-image-section { flex: 1; min-width: 300px; }
        .product-image-section img { 
            width: 100%; border-radius: 10px; max-height: 450px; object-fit: cover; 
            border: 1px solid #eee;
        }
        /* Placeholder when no image is available */
        .no-image { 
            width: 100%; height: 300px; background: #f0f0f0; border-radius: 10px; 
            display: flex; align-items: center; justify-content: center; color: #999; 
        }
        /* Right column: product information */
        .product-info-section { flex: 1; min-width: 300px; }
        /* Category label with pill shape */
        .product-category { 
            display: inline-block; background: #e8e0f0; color: #764ba2; 
            padding: 5px 12px; border-radius: 20px; font-size: 0.85em; margin-bottom: 10px; 
        }
        .product-name { font-size: 26px; color: #333; margin-bottom: 10px; }
        /* Large green price display */
        .product-price { font-size: 32px; color: #28a745; font-weight: bold; margin-bottom: 10px; }
        .product-stock { margin-bottom: 15px; font-size: 0.95em; }
        /* Color-coded stock status */
        .in-stock { color: #28a745; }
        .out-of-stock { color: #dc3545; }
        /* Description with top and bottom borders */
        .product-description { 
            color: #555; line-height: 1.7; margin-bottom: 20px; 
            padding: 15px 0; border-top: 1px solid #eee; border-bottom: 1px solid #eee; 
        }
        /* Product metadata styling */
        .product-meta { color: #666; font-size: 0.9em; margin-bottom: 20px; }
        /* Add to cart section with gray background */
        .add-to-cart-section { 
            background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 15px; 
        }
        /* Flex layout for cart form elements */
        .cart-form { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .quantity-label { font-weight: bold; color: #333; }
        /* Quantity input with larger size */
        .quantity-input { 
            width: 70px; padding: 12px; border: 2px solid #ddd; border-radius: 5px; 
            text-align: center; font-size: 16px; 
        }
        /* Base button style */
        .btn { 
            padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; 
            font-size: 16px; font-weight: bold; text-decoration: none; display: inline-block;
        }
        /* Purple add to cart button that expands to fill space */
        .btn-add-cart { background: #764ba2; color: white; flex: 1; }
        .btn-add-cart:hover { background: #5a3d82; }
        /* Disabled button for out-of-stock items */
        .btn-disabled { background: #ccc; color: #666; cursor: not-allowed; flex: 1; }
        /* Reviews section card */
        .reviews-section { 
            background: white; padding: 30px; border-radius: 10px; 
            box-shadow: 0 2px 15px rgba(0,0,0,0.1); 
        }
        .reviews-section h2 { color: #764ba2; margin-bottom: 20px; }
        /* Individual review card with bottom border */
        .review-card { border-bottom: 1px solid #eee; padding: 15px 0; }
        /* Review header with name and stars */
        .review-header { display: flex; justify-content: space-between; margin-bottom: 8px; flex-wrap: wrap; gap: 5px; }
        .review-name { font-weight: bold; color: #764ba2; }
        .review-date { font-size: 0.85em; color: #999; }
        /* Gold star rating display */
        .review-stars { color: #ffc107; font-size: 1.1em; }
        .review-text { color: #555; line-height: 1.6; }
        /* Empty state for no reviews */
        .no-reviews { text-align: center; color: #999; padding: 20px; }
        /* Review submission form section */
        .review-form-section { margin-top: 25px; padding-top: 25px; border-top: 2px solid #764ba2; }
        .review-form-section h3 { color: #764ba2; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        /* Form inputs with consistent styling */
        .form-group input, .form-group select, .form-group textarea { 
            width: 100%; padding: 10px; border: 1px solid #ddd; 
            border-radius: 5px; font-family: Arial, sans-serif; font-size: 15px;
        }
        /* Full-width submit button */
        .btn-submit { background: #764ba2; color: white; width: 100%; }
        .btn-submit:hover { background: #5a3d82; }
        /* Back to shop link */
        .btn-back { display: inline-block; margin-top: 20px; color: #764ba2; text-decoration: none; font-weight: bold; }
        .btn-back:hover { text-decoration: underline; }
        /* Responsive adjustments for mobile */
        @media (max-width: 768px) {
            .header { flex-direction: column; text-align: center; }
            .product-layout { flex-direction: column; } /* Stack columns vertically */
            .cart-form { flex-direction: column; } /* Stack cart form */
            .btn-add-cart, .btn-disabled { width: 100%; } /* Full-width buttons */
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <h1>Past Times</h1>
        </div>
        <div class="header-right">
            <!-- Navigation links with cart count badge -->
            <a href="shop.php">🛍️ Shop</a>
            <a href="cart.php">🛒 Cart <span class="cart-badge"><?php echo $cart_count; ?></span></a>
            <!-- Conditional dashboard/login link based on authentication status -->
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php">📊 Dashboard</a>
            <?php else: ?>
                <a href="index.php">🔐 Login</a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="container">
        <!-- Breadcrumb navigation showing current page location -->
        <div class="breadcrumb">
            <a href="shop.php">Shop</a> → <?php echo htmlspecialchars($product['name']); ?>
        </div>
        
        <div class="product-detail">
            <div class="product-layout">
                <!-- Product Image Section -->
                <div class="product-image-section">
                    <?php if (!empty($product['image_url'])): ?>
                        <!-- Display product image with alt text for accessibility -->
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php else: ?>
                        <!-- Placeholder when no image URL is available -->
                        <div class="no-image">No Image Available</div>
                    <?php endif; ?>
                </div>
                
                <!-- Product Information Section -->
                <div class="product-info-section">
                    <!-- Category badge -->
                    <span class="product-category"><?php echo ucfirst($product['category']); ?></span>
                    <!-- Product name as heading -->
                    <h1 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h1>
                    <!-- Price with R currency symbol -->
                    <div class="product-price">R<?php echo number_format($product['price'], 2); ?></div>
                    
                    <!-- Stock availability status -->
                    <div class="product-stock">
                        <?php if ($product['stock'] > 0): ?>
                            <span class="in-stock">✅ In Stock</span> - <?php echo $product['stock']; ?> available
                        <?php else: ?>
                            <span class="out-of-stock">❌ Out of Stock</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Product description with line break conversion -->
                    <div class="product-description">
                        <h4 style="margin-bottom: 10px; color: #333;">Description</h4>
                        <!-- Convert newlines to <br> tags, show fallback if description is empty -->
                        <?php echo nl2br(htmlspecialchars($product['description'] ?: 'No description available.')); ?>
                    </div>
                    
                    <!-- Additional product metadata -->
                    <div class="product-meta">
                        <span><strong>Category:</strong> <?php echo ucfirst($product['category']); ?></span>
                    </div>
                    
                    <!-- Add to Cart Section -->
                    <div class="add-to-cart-section">
                        <?php if ($product['stock'] > 0): ?>
                            <!-- Form submits to add_to_cart.php with product details -->
                            <form method="POST" action="add_to_cart.php" class="cart-form">
                                <!-- Hidden fields to pass product data to cart -->
                                <input type="hidden" name="product_id" value="<?php echo $product['clothing_id']; ?>">
                                <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
                                <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
                                <input type="hidden" name="product_image" value="<?php echo htmlspecialchars($product['image_url'] ?? ''); ?>">
                                
                                <!-- Quantity selector with min 1 and max stock limit -->
                                <span class="quantity-label">Qty:</span>
                                <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="quantity-input">
                                <!-- Submit button adds item to session cart -->
                                <button type="submit" class="btn btn-add-cart">🛒 Add to Cart</button>
                            </form>
                        <?php else: ?>
                            <!-- Disabled button when product is out of stock -->
                            <button class="btn btn-disabled" disabled>Out of Stock</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Customer Reviews Section -->
        <div class="reviews-section">
            <h2>⭐ Customer Reviews</h2>
            
            <!-- Display existing reviews if table exists and reviews are present -->
            <?php if ($has_reviews_table && $reviews && mysqli_num_rows($reviews) > 0): ?>
                <!-- Loop through all reviews for this product -->
                <?php while($review = mysqli_fetch_assoc($reviews)): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <span class="review-name"><?php echo htmlspecialchars($review['customer_name']); ?></span>
                            <!-- Render star rating using filled/empty star characters -->
                            <span class="review-stars">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <?php echo $i <= $review['rating'] ? '★' : '☆'; ?>
                                <?php endfor; ?>
                            </span>
                        </div>
                        <!-- Review text with line break conversion -->
                        <div class="review-text"><?php echo nl2br(htmlspecialchars($review['review'])); ?></div>
                        <!-- Formatted review date -->
                        <div class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></div>
                    </div>
                <?php endwhile; ?>
            <?php elseif (!$has_reviews_table): ?>
                <!-- Message when reviews table doesn't exist yet -->
                <div class="no-reviews">Reviews system is not available yet.</div>
            <?php else: ?>
                <!-- Message when no reviews have been submitted -->
                <div class="no-reviews">No reviews yet. Be the first to review this product!</div>
            <?php endif; ?>
            
            <!-- Review submission form (only if reviews table exists) -->
            <?php if ($has_reviews_table): ?>
            <div class="review-form-section">
                <h3>✍️ Write a Review</h3>
                <!-- Form submits to same page for review insertion -->
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Your Name *</label>
                        <!-- Pre-fill name if user is logged in, with XSS protection -->
                        <input type="text" name="customer_name" required value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Rating *</label>
                        <!-- Star rating dropdown with descriptive labels -->
                        <select name="rating" required>
                            <option value="">Select rating</option>
                            <option value="5">★★★★★ - Excellent</option>
                            <option value="4">★★★★☆ - Good</option>
                            <option value="3">★★★☆☆ - Average</option>
                            <option value="2">★★☆☆☆ - Poor</option>
                            <option value="1">★☆☆☆☆ - Terrible</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Your Review *</label>
                        <!-- Textarea for review content with placeholder -->
                        <textarea name="review" rows="4" required placeholder="Share your experience..."></textarea>
                    </div>
                    <!-- Submit button with name attribute for form identification -->
                    <button type="submit" name="submit_review" class="btn btn-submit">Submit Review</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Back to shop navigation link -->
        <a href="shop.php" class="btn-back">← Back to Shop</a>
    </div>
</body>
</html>
<?php 
// Close database connection to free server resources
mysqli_close($conn); 
?>