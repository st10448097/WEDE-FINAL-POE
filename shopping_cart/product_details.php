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
    
    header("Location: product_details.php?id=" . $product_id);
    exit();
}

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
        .header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; padding: 15px 20px; 
            display: flex; justify-content: space-between; align-items: center; 
            flex-wrap: wrap; gap: 10px;
        }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .header h1 { font-size: 1.8em; }
        .header-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .header a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: background 0.3s; }
        .header a:hover { background: rgba(255,255,255,0.2); }
        .cart-badge { background: #ffc107; color: #333; padding: 2px 8px; border-radius: 50%; font-weight: bold; font-size: 0.8em; }
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .breadcrumb { margin-bottom: 20px; color: #666; }
        .breadcrumb a { color: #764ba2; text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }
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
        .product-name { font-size: 26px; color: #333; margin-bottom: 10px; }
        .product-price { font-size: 32px; color: #28a745; font-weight: bold; margin-bottom: 10px; }
        .product-stock { margin-bottom: 15px; font-size: 0.95em; }
        .in-stock { color: #28a745; }
        .out-of-stock { color: #dc3545; }
        .product-description { 
            color: #555; line-height: 1.7; margin-bottom: 20px; 
            padding: 15px 0; border-top: 1px solid #eee; border-bottom: 1px solid #eee; 
        }
        .product-meta { color: #666; font-size: 0.9em; margin-bottom: 20px; }
        .add-to-cart-section { 
            background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 15px; 
        }
        .cart-form { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .quantity-label { font-weight: bold; color: #333; }
        .quantity-input { 
            width: 70px; padding: 12px; border: 2px solid #ddd; border-radius: 5px; 
            text-align: center; font-size: 16px; 
        }
        .btn { 
            padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; 
            font-size: 16px; font-weight: bold; text-decoration: none; display: inline-block;
        }
        .btn-add-cart { background: #764ba2; color: white; flex: 1; }
        .btn-add-cart:hover { background: #5a3d82; }
        .btn-disabled { background: #ccc; color: #666; cursor: not-allowed; flex: 1; }
        .reviews-section { 
            background: white; padding: 30px; border-radius: 10px; 
            box-shadow: 0 2px 15px rgba(0,0,0,0.1); 
        }
        .reviews-section h2 { color: #764ba2; margin-bottom: 20px; }
        .review-card { border-bottom: 1px solid #eee; padding: 15px 0; }
        .review-header { display: flex; justify-content: space-between; margin-bottom: 8px; flex-wrap: wrap; gap: 5px; }
        .review-name { font-weight: bold; color: #764ba2; }
        .review-date { font-size: 0.85em; color: #999; }
        .review-stars { color: #ffc107; font-size: 1.1em; }
        .review-text { color: #555; line-height: 1.6; }
        .no-reviews { text-align: center; color: #999; padding: 20px; }
        .review-form-section { margin-top: 25px; padding-top: 25px; border-top: 2px solid #764ba2; }
        .review-form-section h3 { color: #764ba2; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        .form-group input, .form-group select, .form-group textarea { 
            width: 100%; padding: 10px; border: 1px solid #ddd; 
            border-radius: 5px; font-family: Arial, sans-serif; font-size: 15px;
        }
        .btn-submit { background: #764ba2; color: white; width: 100%; }
        .btn-submit:hover { background: #5a3d82; }
        .btn-back { display: inline-block; margin-top: 20px; color: #764ba2; text-decoration: none; font-weight: bold; }
        .btn-back:hover { text-decoration: underline; }
        @media (max-width: 768px) {
            .header { flex-direction: column; text-align: center; }
            .product-layout { flex-direction: column; }
            .cart-form { flex-direction: column; }
            .btn-add-cart, .btn-disabled { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <h1>Past Times</h1>
        </div>
        <div class="header-right">
            <a href="shop.php">🛍️ Shop</a>
            <a href="cart.php">🛒 Cart <span class="cart-badge"><?php echo $cart_count; ?></span></a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php">📊 Dashboard</a>
            <?php else: ?>
                <a href="index.php">🔐 Login</a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="container">
        <div class="breadcrumb">
            <a href="shop.php">Shop</a> → <?php echo htmlspecialchars($product['name']); ?>
        </div>
        
        <div class="product-detail">
            <div class="product-layout">
                <div class="product-image-section">
                    <?php if (!empty($product['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php else: ?>
                        <div class="no-image">No Image Available</div>
                    <?php endif; ?>
                </div>
                
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
                        <span><strong>Category:</strong> <?php echo ucfirst($product['category']); ?></span>
                    </div>
                    
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
        
        <div class="reviews-section">
            <h2>⭐ Customer Reviews</h2>
            
            <?php if ($has_reviews_table && $reviews && mysqli_num_rows($reviews) > 0): ?>
                <?php while($review = mysqli_fetch_assoc($reviews)): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <span class="review-name"><?php echo htmlspecialchars($review['customer_name']); ?></span>
                            <span class="review-stars">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <?php echo $i <= $review['rating'] ? '★' : '☆'; ?>
                                <?php endfor; ?>
                            </span>
                        </div>
                        <div class="review-text"><?php echo nl2br(htmlspecialchars($review['review'])); ?></div>
                        <div class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></div>
                    </div>
                <?php endwhile; ?>
            <?php elseif (!$has_reviews_table): ?>
                <div class="no-reviews">Reviews system is not available yet.</div>
            <?php else: ?>
                <div class="no-reviews">No reviews yet. Be the first to review this product!</div>
            <?php endif; ?>
            
            <?php if ($has_reviews_table): ?>
            <div class="review-form-section">
                <h3>✍️ Write a Review</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Your Name *</label>
                        <input type="text" name="customer_name" required value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Rating *</label>
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
                        <textarea name="review" rows="4" required placeholder="Share your experience..."></textarea>
                    </div>
                    <button type="submit" name="submit_review" class="btn btn-submit">Submit Review</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        
        <a href="shop.php" class="btn-back">← Back to Shop</a>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>