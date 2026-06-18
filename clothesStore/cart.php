<?php
// cart.php - Shopping cart display page for users to review and manage their selected items
// Displays cart contents, allows quantity updates, item removal, and checkout initiation
session_start(); // Start session to access cart data
include 'DBConn.php'; // Include database connection (available for future use)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shopping Cart - Past Times</title>
    <style>
        /* Universal reset for consistent cross-browser rendering */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        /* Gradient header matching brand color scheme */
        .header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; padding: 20px; 
            display: flex; justify-content: space-between; align-items: center; 
        }
        /* Left side: logo and store name grouped together */
        .header-left { display: flex; align-items: center; gap: 15px; }
        /* Fixed size for the clock SVG logo container */
        .header-logo-icon { width: 45px; height: 45px; }
        /* Ensure SVG scales to fit its container */
        .header-logo-icon svg { width: 100%; height: auto; }
        .header h1 { font-size: 1.8em; }
        /* Navigation links in header with hover effect */
        .header a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: background 0.3s; }
        .header a:hover { background: rgba(255,255,255,0.2); } /* Semi-transparent hover */
        /* Centered content container with max-width */
        .container { max-width: 1000px; margin: 40px auto; padding: 20px; }
        /* White card with subtle shadow for cart content */
        .cart-card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #764ba2; margin-bottom: 25px; }
        /* Full-width table with collapsed borders */
        table { width: 100%; border-collapse: collapse; }
        /* Purple table header with white text */
        th { background: #764ba2; color: white; padding: 12px; text-align: left; }
        /* Table cells with vertical center alignment for mixed content */
        td { padding: 15px 12px; border-bottom: 1px solid #eee; vertical-align: middle; }
        /* Subtle row highlight on hover */
        tr:hover { background: #f8f9fa; }
        /* Product thumbnail image in cart */
        .cart-image { width: 80px; height: 80px; object-fit: cover; border-radius: 5px; }
        /* Quantity input field with centered text */
        .quantity-input { width: 60px; padding: 8px; border: 1px solid #ddd; border-radius: 5px; text-align: center; }
        /* Base button styles with smooth hover transition */
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; transition: background 0.3s; text-decoration: none; display: inline-block; }
        /* Blue update button for cart modifications */
        .btn-update { background: #007bff; color: white; }
        .btn-update:hover { background: #0056b3; }
        /* Red remove button, smaller than other buttons */
        .btn-remove { background: #dc3545; color: white; padding: 5px 10px; font-size: 12px; }
        .btn-remove:hover { background: #c82333; }
        /* Gray continue shopping button */
        .btn-continue { background: #6c757d; color: white; }
        .btn-continue:hover { background: #5a6268; }
        /* Green checkout button */
        .btn-checkout { background: #28a745; color: white; }
        .btn-checkout:hover { background: #218838; }
        /* Right-aligned grand total display with larger font */
        .grand-total { text-align: right; font-size: 1.3em; margin-top: 20px; color: #764ba2; }
        /* Centered empty cart placeholder */
        .empty-cart { text-align: center; padding: 50px; color: #999; }
        /* Flex container for button grouping, right-aligned with wrapping */
        .button-group { display: flex; gap: 10px; margin-top: 20px; justify-content: flex-end; flex-wrap: wrap; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="header-logo-icon">
                <!-- Simple clock SVG icon representing "Past Times" vintage brand -->
                <svg width="45" height="45" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
                    <!-- Outer decorative circle with transparency -->
                    <circle cx="30" cy="30" r="28" fill="#ffffff" stroke="#ffffff" stroke-width="2" opacity="0.3"/>
                    <!-- Inner fill circle -->
                    <circle cx="30" cy="30" r="24" fill="rgba(255,255,255,0.1)"/>
                    <!-- Clock numerals at cardinal positions -->
                    <text x="30" y="10" text-anchor="middle" font-size="8" fill="white" font-weight="bold">12</text>
                    <text x="48" y="33" text-anchor="middle" font-size="8" fill="white" font-weight="bold">3</text>
                    <text x="30" y="56" text-anchor="middle" font-size="8" fill="white" font-weight="bold">6</text>
                    <text x="12" y="33" text-anchor="middle" font-size="8" fill="white" font-weight="bold">9</text>
                    <!-- Hour hand (shorter, thicker) pointing upward -->
                    <line x1="30" y1="30" x2="30" y2="12" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                    <!-- Minute hand (longer, thinner) pointing to 2 o'clock position -->
                    <line x1="30" y1="30" x2="42" y2="20" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    <!-- Center pivot dot -->
                    <circle cx="30" cy="30" r="2.5" fill="white"/>
                </svg>
            </div>
            <h1>Past Times</h1>
        </div>
        <div>
            <!-- Link to return to shop and continue browsing -->
            <a href="shop.php">← Continue Shopping</a>
            <!-- Dashboard link only shown when user is logged in -->
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php">Dashboard</a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="container">
        <div class="cart-card">
            <h2>🛒 Your Shopping Cart</h2>
            
            <!-- Check if cart exists in session and contains items -->
            <?php if(isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
            <!-- Form submits to update_cart.php for quantity modifications -->
            <form method="POST" action="update_cart.php">
                <table>
                    <thead>
                        <tr>
                            <!-- Table column headers -->
                            <th>Image</th>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Initialize grand total accumulator for all cart items
                        $grand_total = 0;
                        // Loop through each item in the cart with its array index
                        foreach($_SESSION['cart'] as $index => $item): 
                            // Calculate line total: unit price multiplied by quantity
                            $total = $item['price'] * $item['quantity'];
                            // Accumulate running grand total
                            $grand_total += $total;
                        ?>
                        <tr>
                            <td>
                                <!-- Display product image with fallback placeholder if no image URL exists -->
                                <img src="<?php echo htmlspecialchars(!empty($item['image']) ? $item['image'] : 'https://via.placeholder.com/80x80?text=No+Image'); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-image">
                            </td>
                            <!-- Product name in bold, escaped for XSS protection -->
                            <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                            <!-- Unit price formatted with R currency symbol and 2 decimal places -->
                            <td>R<?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <!-- Quantity input field, named as array with index for update processing -->
                                <input type="number" name="quantities[<?php echo $index; ?>]" 
                                       value="<?php echo $item['quantity']; ?>" min="1" class="quantity-input">
                            </td>
                            <!-- Line total (price × quantity) displayed in bold -->
                            <td><strong>R<?php echo number_format($total, 2); ?></strong></td>
                            <td>
                                <!-- Remove button links to remove_item.php with item index -->
                                <a href="remove_item.php?index=<?php echo $index; ?>" class="btn btn-remove" 
                                   onclick="return confirm('Remove this item?')">Remove</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Display the accumulated grand total for all cart items -->
                <div class="grand-total">
                    <strong>Grand Total: R<?php echo number_format($grand_total, 2); ?></strong>
                </div>
                
                <!-- Button group for update cart action -->
                <div class="button-group">
                    <!-- Submit button triggers form action to update quantities -->
                    <button type="submit" name="update_cart" class="btn btn-update">Update Cart</button>
                </div>
            </form>
            
            <!-- Additional action buttons below the form -->
            <div class="button-group">
                <!-- Link back to shop for continued browsing -->
                <a href="shop.php" class="btn btn-continue">Continue Shopping</a>
                <!-- Proceed to checkout page to complete purchase -->
                <a href="checkout.php" class="btn btn-checkout">Proceed to Checkout</a>
            </div>
            <?php else: ?>
                <!-- Displayed when cart is empty or doesn't exist -->
                <div class="empty-cart">
                    <h3>Your cart is empty</h3>
                    <p>Browse our products and add items to your cart!</p>
                    <br>
                    <!-- Link to start shopping -->
                    <a href="shop.php" class="btn btn-continue">Start Shopping</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>