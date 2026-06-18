<?php
// product_details.php
session_start();
include 'DBConn.php';

// Get product ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: shop.php");
    exit();
}

$product_id = intval($_GET['id']);

// Fetch product
$stmt = mysqli_prepare($conn, "SELECT * FROM clothing WHERE clothing_id = ?");
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: shop.php");
    exit();
}

$product = mysqli_fetch_assoc($result);

// Get reviews
$review_stmt = mysqli_prepare($conn, "SELECT * FROM reviews WHERE product_id = ? ORDER BY created_at DESC");
mysqli_stmt_bind_param($review_stmt, "i", $product_id);
mysqli_stmt_execute($review_stmt);
$reviews = mysqli_stmt_get_result($review_stmt);

// Handle review submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review'])) {
    $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
    $rating = intval($_POST['rating']);
    $review_text = mysqli_real_escape_string($conn, $_POST['review']);
    
    $insert = mysqli_prepare($conn, "INSERT INTO reviews (product_id, customer_name, rating, review) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($insert, "isis", $product_id, $customer_name, $rating, $review_text);
    mysqli_stmt_execute($insert);
    
    // Refresh page to show new review
    header("Location: product_details.php?id=" . $product_id);
    exit();
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($product['name']); ?> - Past Times</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        .header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; padding: 20px; 
            display: flex; justify-content: space-between; align-items: center; 
        }
        .header a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; }
        .header a:hover { background: rgba(255,255,255,0.2); }
        .container { max-width: 900px; margin: 40px auto; padding: 20px; }
        .product-detail { 
            background: white; padding: 30px; border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; 
        }
        .product-layout { display: flex; gap: 30px; flex-wrap: wrap; }
        .product-image { flex: 1; min-width: 300px; }
        .product-image img { width: 100%; border-radius: 10px; max-height: 400px; object-fit: cover; }
        .product-info { flex: 1; min-width: 300px; }
        .product-name { font-size: 24px; color: #764ba2; margin-bottom: 10px; }
        .product-price { font-size: 28px; color: #28a745; font-weight: bold; margin-bottom: 10px; }
        .product-stock { color: #666; margin-bottom: 15px; }
        .product-description { color: #555; line-height: 1.6; margin-bottom: 20px; }
        .add-to-cart { 
            background: #764ba2; color: white; padding: 12px 30px; border: none; 
            border-radius: 5px; font-size: 16px; cursor: pointer; 
        }
        .add-to-cart:hover { background: #5a3d82; }
        .quantity-input { padding: 10px; border: 1px solid #ddd; border-radius: 5px; width: 70px; margin-right: 10px; }
        
        .reviews-section { 
            background: white; padding: 30px; border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }
        .review { border-bottom: 1px solid #eee; padding: 15px 0; }
        .review-header { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .review-name { font-weight: bold; color: #764ba2; }
        .review-stars { color: #ffc107; }
        .review-text { color: #555; }
        .review-form { margin-top: 20px; padding-top: 20px; border-top: 2px solid #764ba2; }
        .review-form input, .review-form textarea, .review-form select { 
            width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; 
            border-radius: 5px; font-family: Arial, sans-serif; 
        }
        .btn { 
            background: #764ba2; color: white; padding: 10px 20px; border: none; 
            border-radius: 5px; cursor: pointer; 
        }
        .btn:hover { background: #5a3d82; }
        .back-link { display: inline-block; margin-top: 20px; color: #764ba2; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Past Times</h1>
        <div>
            <a href="shop.php">← Back to Shop</a>
            <a href="cart.php">🛒 Cart</a>
        </div>
    </div>
    
    <div class="container">
        <div class="product-detail">
            <div class="product-layout">
                <div class="product-image">
                    <img src="<?php echo htmlspecialchars($product['image_url'] ?: 'https://via.placeholder.com/400x400?text=No+Image'); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                </div>
                
                <div class="product-info">
                    <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                    <div class="product-price">R<?php echo number_format($product['price'], 2); ?></div>
                    <div class="product-stock">
                        <?php if ($product['stock'] > 0): ?>
                            ✅ In Stock (<?php echo $product['stock']; ?> available)
                        <?php else: ?>
                            ❌ Out of Stock
                        <?php endif; ?>
                    </div>
                    <div class="product-description">
                        <?php echo nl2br(htmlspecialchars($product['description'] ?: 'No description available.')); ?>
                    </div>
                    <div style="color: #666; margin-bottom: 15px;">
                        <strong>Category:</strong> <?php echo ucfirst($product['category']); ?>
                    </div>
                    
                    <?php if ($product['stock'] > 0): ?>
                    <form method="POST" action="add_to_cart.php">
                        <input type="hidden" name="product_id" value="<?php echo $product['clothing_id']; ?>">
                        <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
                        <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
                        <input type="hidden" name="product_image" value="<?php echo htmlspecialchars($product['image_url'] ?? ''); ?>">
                        
                        <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="quantity-input">
                        <button type="submit" class="add-to-cart">Add to Cart</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Reviews Section -->
        <div class="reviews-section">
            <h3 style="color: #764ba2; margin-bottom: 20px;">Customer Reviews</h3>
            
            <?php if (mysqli_num_rows($reviews) > 0): ?>
                <?php while($review = mysqli_fetch_assoc($reviews)): ?>
                    <div class="review">
                        <div class="review-header">
                            <span class="review-name"><?php echo htmlspecialchars($review['customer_name']); ?></span>
                            <span class="review-stars">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <?php echo $i <= $review['rating'] ? '★' : '☆'; ?>
                                <?php endfor; ?>
                            </span>
                        </div>
                        <div class="review-text"><?php echo htmlspecialchars($review['review']); ?></div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: #999;">No reviews yet. Be the first to review this product!</p>
            <?php endif; ?>
            
            <!-- Review Form -->
            <div class="review-form">
                <h4 style="color: #764ba2;">Write a Review</h4>
                <form method="POST">
                    <input type="text" name="customer_name" placeholder="Your Name" required>
                    <select name="rating" required>
                        <option value="">Select Rating</option>
                        <option value="5">★★★★★ (5)</option>
                        <option value="4">★★★★☆ (4)</option>
                        <option value="3">★★★☆☆ (3)</option>
                        <option value="2">★★☆☆☆ (2)</option>
                        <option value="1">★☆☆☆☆ (1)</option>
                    </select>
                    <textarea name="review" rows="4" placeholder="Write your review..." required></textarea>
                    <button type="submit" name="submit_review" class="btn">Submit Review</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>