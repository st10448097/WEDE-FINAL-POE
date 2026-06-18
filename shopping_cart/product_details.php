<?php
// product_details.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'DBConn.php';

// Get product ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: shop.php");
    exit();
}

$product_id = intval($_GET['id']);

// Fetch product from clothing table
$stmt = mysqli_prepare($conn, "SELECT * FROM clothing WHERE clothing_id = ?");
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: shop.php");
    exit();
}

$product = mysqli_fetch_assoc($result);

// Check if reviews table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'reviews'");
$has_reviews_table = mysqli_num_rows($table_check) > 0;

// Get reviews if table exists
$reviews = null;
if ($has_reviews_table) {
    $review_stmt = mysqli_prepare($conn, "SELECT * FROM reviews WHERE product_id = ? ORDER BY created_at DESC");
    mysqli_stmt_bind_param($review_stmt, "i", $product_id);
    mysqli_stmt_execute($review_stmt);
    $reviews = mysqli_stmt_get_result($review_stmt);
}

// Handle review submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review']) && $has_reviews_table) {
    $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
    $rating = intval($_POST['rating']);
    $review_text = mysqli_real_escape_string($conn, $_POST['review']);
    
    $insert = mysqli_prepare($conn, "INSERT INTO reviews (product_id, customer_name, rating, review) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($insert, "isis", $product_id, $customer_name, $rating, $review_text);
    mysqli_stmt_execute($insert);
    
    // Refresh page to show new review
    header("Location: product_details.php" . $product_id);
    exit();
}

// Get cart count
$cart_count = count($_SESSION['cart'] ?? []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Past Times</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        
        /* Header */
        .header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; padding: 15px 20px; 
            display: flex; justify-content: space-between; align-items: center; 
            flex-wrap: wrap; gap: 10px;
        }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .header-logo-icon { width: 45px; height: 45px; }
        .header-logo-icon svg { width: 100%; height: auto; }
        .header h1 { font-size: 1.8em; display: flex; align-items: center; gap: 10px; }
        .header-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .header a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: background 0.3s; }
        .header a:hover { background: rgba(255,255,255,0.2); }
        .cart-badge { background: #ffc107; color: #333; padding: 2px 8px; border-radius: 50%; font-weight: bold; font-size: 0.8em; }
        
        /* Container */
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        
        /* Breadcrumb */
        .breadcrumb { margin-bottom: 20px; color: #666; }
        .breadcrumb a { color: #764ba2; text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }
        
        /* Product Detail Card */
        .product-detail { 
            background: white; padding: 30px; border-radius: 10px; 
            box-shadow: 0 2px 15px rgba(0,0,0,0.1); margin-bottom: 30px; 
        }
        .product-layout { display: flex; gap: 30px; flex-wrap: wrap; }
        .product-image-section { flex: 1; min-width: 300px; }
        .product-image-section img { 
            width: 100%; border-radius: 10px; max-height: 450px; object-fit: cover; 
            border: 1px solid #eee;
        }
        .no-image { 
            width: 100%; height: 300px; background: #f0f0f0; border-radius: 10px; 
            display: flex; align-items: center; justify-content: center; color: #999; 
        }
        .product-info-section { flex: 1; min-width: 300px; }
        .product-category { 
            display: inline-block; background: #e8e0f0; color: #764ba2; 
            padding: 5px 12px; border-radius: 20px; font-size: 0.85em; margin-bottom: 10px; 
        }
        .product-name { font-size: 26px; color: #333; margin-bottom: 10px; line-height: 1.3; }
        .product-price { font-size: 32px; color: #28a745; font-weight: bold; margin-bottom: 10px; }
        .product-stock { margin-bottom: 15px; font-size: 0.95em; }
        .in-stock { color: #28a745; }
        .out-of-stock { color: #dc3545; }
        .product-description { 
            color: #555; line-height: 1.7; margin-bottom: 20px; 
            padding: 15px 0; border-top: 1px solid #eee; border-bottom: 1px solid #eee; 
        }
        .product-meta { color: #666; font-size: 0.9em; margin-bottom: 20px; }
        .product-meta span { margin-right: 20px; }
        
        /* Add to Cart Form */
        .add-to-cart-section { 
            background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 15px; 
        }
        .cart-form { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .quantity-label { font-weight: bold; color: #333; }
        .quantity-input { 
            width: 70px; padding: 12px; border: 2px solid #ddd; border-radius: 5px; 
            text-align: center; font-size: 16px; 
        }
        .quantity-input:focus { outline: none; border-color: #764ba2; }
        .btn { 
            padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; 
            font-size: 16px; font-weight: bold; text-decoration: none; display: inline-block;
            transition: background 0.3s; 
        }
        .btn-add-cart { background: #764ba2; color: white; flex: 1; }
        .btn-add-cart:hover { background: #5a3d82; }
        .btn-disabled { background: #ccc; color: #666; cursor: not-allowed; flex: 1; }
        
        /* Reviews Section */
        .reviews-section { 
            background: white; padding: 30px; border-radius: 10px; 
            box-shadow: 0 2px 15px rgba(0,0,0,0.1); 
        }
        .reviews-section h2 { color: #764ba2; margin-bottom: 20px; }
        .review-card { 
            border-bottom: 1px solid #eee; padding: 15px 0; 
        }
        .review-card:last-child { border-bottom: none; }
        .review-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 5px; }
        .review-name { font-weight: bold; color: #764ba2; }
        .review-date { font-size: 0.85em; color: #999; }
        .review-stars { color: #ffc107; font-size: 1.1em; }
        .review-text { color: #555; line-height: 1.6; }
        .no-reviews { text-align: center; color: #999; padding: 20px; }
        
        /* Review Form */
        .review-form-section { 
            margin-top: 25px; padding-top: 25px; border-top: 2px solid #764ba2; 
        }
        .review-form-section h3 { color: #764ba2; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        .form-group input, .form-group select, .form-group textarea { 
            width: 100%; padding: 10px; border: 1px solid #ddd; 
            border-radius: 5px; font-family: Arial, sans-serif; font-size: 15px;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { 
            outline: none; border-color: #764ba2; box-shadow: 0 0 5px rgba(118, 75, 162, 0.3);
        }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .btn-submit { background: #764ba2; color: white; width: 100%; }
        .btn-submit:hover { background: #5a3d82; }
        .btn-back { 
            display: inline-block; margin-top: 20px; color: #764ba2; text-decoration: none; 
            font-weight: bold;
        }
        .btn-back:hover { text-decoration: underline; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header { flex-direction: column; text-align: center; }
            .product-layout { flex-direction: column; }
            .product-image-section { min-width: auto; }
            .product-info-section { min-width: auto; }
            .cart-form { flex-direction: column; }
            .btn-add-cart, .btn-disabled { width: 100%; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="header-logo-icon">
                <svg width="45" height="45" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="30" cy="30" r="28" fill="#ffffff" stroke="#ffffff" stroke-width="2" opacity="0.3"/>
                    <circle cx="30" cy="30" r="24" fill="rgba(255,255,255,0.1)"/>
                    <text x="30" y="10" text-anchor="middle" font-size="8" fill="white" font-weight="bold">12</text>
                    <text x="48" y="33" text-anchor="middle" font-size="8" fill="white" font-weight="bold">3</text>
                    <text x="30" y="56" text-anchor="middle" font-size="8" fill="white" font-weight="bold">6</text>
                    <text x="12" y="33" text-anchor="middle" font-size="8" fill="white" font-weight="bold">9</text>
                    <line x1="30" y1="8" x2="30" y2="13" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="52" y1="30" x2="47" y2="30" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="30" y1="52" x2="30" y2="47" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="8" y1="30" x2="13" y2="30" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="30" y1="30" x2="30" y2="12" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                    <line x1="30" y1="30" x2="42" y2="20" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    <circle cx="30" cy="30" r="2.5" fill="white"/>
                </svg>
            </div>
            <h1>Past Times</h1>
        </div>
        <div class="header-right">
            <a href="shop.php">🛍️ Shop</a>
            <a href="cart.php">🛒 Cart <span class="cart-badge"><?php echo $cart_count; ?></span></a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php">📊 Dashboard</a>
                <span style="opacity: 0.9;">👤 <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <?php else: ?>
                <a href="index.php">🔐 Login</a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="shop.php">Shop</a> → <?php echo htmlspecialchars($product['name']); ?>
        </div>
        
        <!-- Product Details -->
        <div class="product-detail">
            <div class="product-layout">
                <!-- Product Image -->
                <div class="product-image-section">
                    <?php if (!empty($product['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php else: ?>
                        <div class="no-image">
                            <span>No Image Available</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Product Info -->
                <div class="product-info-section">
                    <span class="product-category"><?php echo ucfirst($product['category']); ?></span>
                    <h1 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h1>
                    <div class="product-price">R<?php echo number_format($product['price'], 2); ?></div>
                    
                    <div class="product-stock">
                        <?php if ($product['stock'] > 0): ?>
                            <span class="in-stock">✅ In Stock</span> - <?php echo $product['stock']; ?> available
                        <?php else: ?>
                            <span class="out-of-stock">❌ Out of Stock</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-description">
                        <h4 style="margin-bottom: 10px; color: #333;">Description</h4>
                        <?php echo nl2br(htmlspecialchars($product['description'] ?: 'No description available.')); ?>
                    </div>
                    
                    <div class="product-meta">
                        <span><strong>Product ID:</strong> #<?php echo $product['clothing_id']; ?></span>
                        <span><strong>Category:</strong> <?php echo ucfirst($product['category']); ?></span>
                    </div>
                    
                    <!-- Add to Cart -->
                    <div class="add-to-cart-section">
                        <?php if ($product['stock'] > 0): ?>
                            <form method="POST" action="add_to_cart.php" class="cart-form">
                                <input type="hidden" name="product_id" value="<?php echo $product['clothing_id']; ?>">
                                <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
                                <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
                                <input type="hidden" name="product_image" value="<?php echo htmlspecialchars($product['image_url'] ?? ''); ?>">
                                
                                <span class="quantity-label">Qty:</span>
                                <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="quantity-input">
                                <button type="submit" class="btn btn-add-cart">🛒 Add to Cart</button>
                            </form>
                        <?php else: ?>
                            <button class="btn btn-disabled" disabled>Out of Stock</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Reviews Section -->
        <div class="reviews-section">
            <h2>⭐ Customer Reviews</h2>
            
            <?php if ($has_reviews_table && $reviews && mysqli_num_rows($reviews) > 0): ?>
                <?php while($review = mysqli_fetch_assoc($reviews)): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div>
                                <span class="review-name"><?php echo htmlspecialchars($review['customer_name']); ?></span>
                                <span class="review-date"> - <?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                            </div>
                            <span class="review-stars">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <?php echo $i <= $review['rating'] ? '★' : '☆'; ?>
                                <?php endfor; ?>
                            </span>
                        </div>
                        <div class="review-text"><?php echo nl2br(htmlspecialchars($review['review'])); ?></div>
                    </div>
                <?php endwhile; ?>
            <?php elseif (!$has_reviews_table): ?>
                <div class="no-reviews">
                    <p>Reviews system is not available yet.</p>
                </div>
            <?php else: ?>
                <div class="no-reviews">
                    <p>No reviews yet. Be the first to review this product!</p>
                </div>
            <?php endif; ?>
            
            <!-- Review Form -->
            <?php if ($has_reviews_table): ?>
            <div class="review-form-section">
                <h3>✍️ Write a Review</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="customer_name">Your Name *</label>
                        <input type="text" id="customer_name" name="customer_name" required 
                               value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>" 
                               placeholder="Enter your name">
                    </div>
                    
                    <div class="form-group">
                        <label for="rating">Rating *</label>
                        <select id="rating" name="rating" required>
                            <option value="">Select rating</option>
                            <option value="5">★★★★★ - Excellent</option>
                            <option value="4">★★★★☆ - Good</option>
                            <option value="3">★★★☆☆ - Average</option>
                            <option value="2">★★☆☆☆ - Poor</option>
                            <option value="1">★☆☆☆☆ - Terrible</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="review">Your Review *</label>
                        <textarea id="review" name="review" required 
                                  placeholder="Share your experience with this product..."></textarea>
                    </div>
                    
                    <button type="submit" name="submit_review" class="btn btn-submit">Submit Review</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Back Button -->
        <a href="shop.php" class="btn-back">← Back to Shop</a>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>