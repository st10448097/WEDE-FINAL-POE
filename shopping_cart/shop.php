<?php
// shop.php - Browse all clothing items with Add to Cart
session_start();
include 'DBConn.php';

// Get all clothing items
$clothing = mysqli_query($conn, "SELECT * FROM clothing WHERE stock > 0 ORDER BY created_at DESC");
$cart_count = count($_SESSION['cart'] ?? []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shop - Past Times</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .header-logo-icon { width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; }
        .header-logo-icon svg { width: 100%; height: auto; }
        .header h1 { font-size: 1.8em; display: flex; align-items: center; gap: 10px; }
        .header-right { display: flex; align-items: center; gap: 15px; }
        .header-right a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: background 0.3s; }
        .header-right a:hover { background: rgba(255,255,255,0.2); }
        .cart-btn { background: rgba(255,255,255,0.2); padding: 10px 20px; border-radius: 25px; font-weight: bold; }
        .cart-count { background: #ffc107; color: #333; padding: 2px 8px; border-radius: 50%; font-size: 0.8em; margin-left: 5px; }
        .container { max-width: 1200px; margin: 40px auto; padding: 20px; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .product-card { 
            background: white; border-radius: 10px; overflow: hidden; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: transform 0.3s; 
        }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        .product-image { 
            height: 250px; background: #ddd; background-size: cover; 
            background-position: center; display: flex; align-items: center; justify-content: center; 
        }
        .product-info { padding: 15px; }
        .product-name { font-weight: bold; font-size: 1.1em; margin-bottom: 8px; color: #333; }
        .product-price { color: #764ba2; font-weight: bold; font-size: 1.3em; margin-bottom: 8px; }
        .product-category { font-size: 0.8em; color: #666; margin-bottom: 10px; }
        .product-stock { font-size: 0.85em; color: #28a745; margin-bottom: 10px; }
        .add-to-cart-form { display: flex; gap: 8px; align-items: center; }
        .quantity-input { width: 60px; padding: 8px; border: 1px solid #ddd; border-radius: 5px; text-align: center; }
        .btn-add { 
            flex: 1; background: #764ba2; color: white; padding: 10px; border: none; 
            border-radius: 5px; cursor: pointer; font-weight: bold; transition: background 0.3s; 
        }
        .btn-add:hover { background: #5a3d82; }
        .btn-view { 
            display: block; text-align: center; margin-top: 8px; color: #764ba2; 
            text-decoration: none; font-size: 0.9em; 
        }
        .btn-view:hover { text-decoration: underline; }
        .welcome-text { opacity: 0.9; }
        h2 { color: #764ba2; margin-bottom: 25px; }
        .empty-shop { text-align: center; padding: 50px; color: #999; }
        @media (max-width: 768px) { 
            .header { flex-direction: column; gap: 15px; }
            .products-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
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
            <?php if(isset($_SESSION['user_id'])): ?>
                <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="dashboard.php">Dashboard</a>
            <?php else: ?>
                <a href="index.php">Login</a>
            <?php endif; ?>
            <a href="cart.php" class="cart-btn">🛒 Cart <span class="cart-count"><?php echo $cart_count; ?></span></a>
        </div>
    </div>
    
    <div class="container">
        <h2>🛍️ Shop All Products</h2>
        
        <?php if (mysqli_num_rows($clothing) > 0): ?>
        <div class="products-grid">
            <?php while($item = mysqli_fetch_assoc($clothing)): ?>
                <div class="product-card">
                    <div class="product-image" style="background-image: url('<?php echo htmlspecialchars($item['image_url'] ?: 'https://via.placeholder.com/300x300?text=No+Image'); ?>');">
                        <?php if(!$item['image_url']): ?>
                            <span style="color: #999;">No Image</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="product-price">R<?php echo number_format($item['price'], 2); ?></div>
                        <div class="product-category"><?php echo ucfirst($item['category']); ?></div>
                        <div class="product-stock">✅ In Stock (<?php echo $item['stock']; ?>)</div>
                        
                        <form method="POST" action="add_to_cart.php" class="add-to-cart-form">
                            <input type="hidden" name="product_id" value="<?php echo $item['clothing_id']; ?>">
                            <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($item['name']); ?>">
                            <input type="hidden" name="product_price" value="<?php echo $item['price']; ?>">
                            <input type="hidden" name="product_image" value="<?php echo htmlspecialchars($item['image_url'] ?? ''); ?>">
                            <input type="number" name="quantity" value="1" min="1" max="<?php echo $item['stock']; ?>" class="quantity-input">
                            <button type="submit" class="btn-add">Add to Cart</button>
                        </form>
                        
                        <a href="product_details.php?id=<?php echo $item['clothing_id']; ?>" class="btn-view">View Details →</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
            <div class="empty-shop">
                <p>No products available at the moment. Check back soon!</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>