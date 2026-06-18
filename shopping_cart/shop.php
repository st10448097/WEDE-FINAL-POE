<?php
// shop.php - Main product browsing page displaying all in-stock clothing items
// Shows a responsive grid of product cards with add-to-cart functionality
session_start(); // Start session for cart and user data access
include 'DBConn.php'; // Include database connection

// Fetch all clothing items that have stock available, newest first
$clothing = mysqli_query($conn, "SELECT * FROM clothing WHERE stock > 0 ORDER BY created_at DESC");
// Count items currently in cart for header badge (defaults to 0 if cart doesn't exist)
$cart_count = count($_SESSION['cart'] ?? []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shop - Past Times</title>
    <style>
        /* Universal reset for consistent cross-browser rendering */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        /* Gradient header matching brand color scheme */
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        /* Left side: logo and store name */
        .header-left { display: flex; align-items: center; gap: 15px; }
        .header-logo-icon { width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; }
        .header-logo-icon svg { width: 100%; height: auto; }
        .header h1 { font-size: 1.8em; display: flex; align-items: center; gap: 10px; }
        /* Right side: user info and navigation */
        .header-right { display: flex; align-items: center; gap: 15px; }
        /* Navigation links with hover effect */
        .header-right a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: background 0.3s; }
        .header-right a:hover { background: rgba(255,255,255,0.2); } /* Semi-transparent hover */
        /* Cart button with pill shape and background */
        .cart-btn { background: rgba(255,255,255,0.2); padding: 10px 20px; border-radius: 25px; font-weight: bold; }
        /* Circular cart count badge */
        .cart-count { background: #ffc107; color: #333; padding: 2px 8px; border-radius: 50%; font-size: 0.8em; margin-left: 5px; }
        /* Centered content container with max-width */
        .container { max-width: 1200px; margin: 40px auto; padding: 20px; }
        /* Responsive grid for product cards, auto-fills with minimum 250px columns */
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        
        /* Product card wrapper with shadow and hover animation */
        .product-card-wrapper {
            background: white; 
            border-radius: 10px; 
            overflow: hidden; /* Ensures border-radius clips child elements */
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            transition: transform 0.3s; /* Smooth lift animation */
            display: flex;
            flex-direction: column; /* Stack children vertically */
        }
        /* Lift effect on hover with enhanced shadow */
        .product-card-wrapper:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 5px 20px rgba(0,0,0,0.15); 
        }
        /* Top section: product image and info, clickable to view details */
        .product-info-top {
            padding: 15px 15px 5px 15px;
            flex: 1; /* Takes remaining space */
        }
        /* Bottom section: add to cart form */
        .product-info-bottom {
            padding: 5px 15px 15px 15px;
        }
        /* Product image with cover sizing and center positioning */
        .product-image { 
            height: 250px; 
            background: #ddd; /* Fallback background color */
            background-size: cover; 
            background-position: center; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            cursor: pointer; /* Indicates clickable area */
        }
        /* Product name styling */
        .product-name { 
            font-weight: bold; 
            font-size: 1.1em; 
            margin-bottom: 8px; 
            color: #333; 
        }
        /* Product price in brand purple with larger font */
        .product-price { 
            color: #764ba2; 
            font-weight: bold; 
            font-size: 1.3em; 
            margin-bottom: 8px; 
        }
        /* Category label with subdued styling */
        .product-category { 
            font-size: 0.8em; 
            color: #666; 
            margin-bottom: 10px; 
        }
        /* Stock indicator in green */
        .product-stock { 
            font-size: 0.85em; 
            color: #28a745; 
            margin-bottom: 10px; 
        }
        /* Flex layout for quantity and add button */
        .add-to-cart-form { 
            display: flex; 
            gap: 8px; 
            align-items: center; 
        }
        /* Quantity input with centered text */
        .quantity-input { 
            width: 60px; 
            padding: 8px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            text-align: center; 
        }
        /* Add to cart button, expands to fill remaining space */
        .btn-add { 
            flex: 1; 
            background: #764ba2; 
            color: white; 
            padding: 10px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-weight: bold; 
            transition: background 0.3s; 
        }
        .btn-add:hover { 
            background: #5a3d82; /* Darker purple on hover */
        }
        /* Semi-transparent welcome text */
        .welcome-text { opacity: 0.9; }
        h2 { color: #764ba2; margin-bottom: 25px; }
        /* Empty state when no products available */
        .empty-shop { text-align: center; padding: 50px; color: #999; }
        /* Responsive adjustments for mobile */
        @media (max-width: 768px) { 
            .header { flex-direction: column; gap: 15px; } /* Stack header vertically */
            .products-grid { grid-template-columns: 1fr; } /* Single column on mobile */
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="header-logo-icon">
                <!-- Simple clock SVG icon representing "Past Times" brand -->
                <svg width="45" height="45" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
                    <!-- Outer decorative circle -->
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
            <!-- Conditional display based on login status -->
            <?php if(isset($_SESSION['user_id'])): ?>
                <!-- Show welcome message and dashboard link for logged-in users -->
                <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="dashboard.php">Dashboard</a>
            <?php else: ?>
                <!-- Show login link for guests -->
                <a href="index.php">Login</a>
            <?php endif; ?>
            <!-- Cart button always visible with item count badge -->
            <a href="cart.php" class="cart-btn">🛒 Cart <span class="cart-count"><?php echo $cart_count; ?></span></a>
        </div>
    </div>
    
    <div class="container">
        <h2>🛍️ Shop All Products</h2>
        
        <!-- Display product grid only if items with stock exist -->
        <?php if (mysqli_num_rows($clothing) > 0): ?>
        <div class="products-grid">
            <!-- Loop through each available clothing item -->
            <?php while($item = mysqli_fetch_assoc($clothing)): ?>
                <div class="product-card-wrapper">
                    <!-- Clicking image or product info navigates to product details page -->
                    <a href="product_details.php?id=<?php echo $item['clothing_id']; ?>" 
                       style="text-decoration: none; color: inherit;">
                        <!-- Product image with background-image for consistent sizing -->
                        <div class="product-image" style="background-image: url('<?php echo htmlspecialchars($item['image_url'] ?: 'https://via.placeholder.com/300x300?text=No+Image'); ?>');">
                            <!-- Fallback text shown if no image URL exists -->
                            <?php if(!$item['image_url']): ?>
                                <span style="color: #999;">No Image</span>
                            <?php endif; ?>
                        </div>
                        <!-- Product information section (clickable) -->
                        <div class="product-info-top">
                            <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <!-- Price formatted with R currency symbol -->
                            <div class="product-price">R<?php echo number_format($item['price'], 2); ?></div>
                            <!-- Category with first letter capitalized -->
                            <div class="product-category"><?php echo ucfirst($item['category']); ?></div>
                            <!-- Stock availability indicator -->
                            <div class="product-stock">✅ In Stock (<?php echo $item['stock']; ?>)</div>
                        </div>
                    </a>
                    
                    <!-- Add to cart form is separate from the clickable area -->
                    <div class="product-info-bottom">
                        <!-- Form submits to add_to_cart.php with product details -->
                        <form method="POST" action="add_to_cart.php" class="add-to-cart-form">
                            <!-- Hidden fields pass product data to cart handler -->
                            <input type="hidden" name="product_id" value="<?php echo $item['clothing_id']; ?>">
                            <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($item['name']); ?>">
                            <input type="hidden" name="product_price" value="<?php echo $item['price']; ?>">
                            <input type="hidden" name="product_image" value="<?php echo htmlspecialchars($item['image_url'] ?? ''); ?>">
                            <!-- Quantity selector with min 1 and max set to available stock -->
                            <input type="number" name="quantity" value="1" min="1" max="<?php echo $item['stock']; ?>" class="quantity-input">
                            <!-- Submit button adds item to session cart -->
                            <button type="submit" class="btn-add">Add to Cart</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
            <!-- Empty state displayed when no in-stock products are available -->
            <div class="empty-shop">
                <p>No products available at the moment. Check back soon!</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php 
// Close database connection to free server resources
mysqli_close($conn); 
?>