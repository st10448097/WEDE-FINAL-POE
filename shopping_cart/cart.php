<?php
session_start();
include 'DBConn.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shopping Cart - Past Times</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        .header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; padding: 20px; 
            display: flex; justify-content: space-between; align-items: center; 
        }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .header-logo-icon { width: 45px; height: 45px; }
        .header-logo-icon svg { width: 100%; height: auto; }
        .header h1 { font-size: 1.8em; }
        .header a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: background 0.3s; }
        .header a:hover { background: rgba(255,255,255,0.2); }
        .container { max-width: 1000px; margin: 40px auto; padding: 20px; }
        .cart-card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #764ba2; margin-bottom: 25px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #764ba2; color: white; padding: 12px; text-align: left; }
        td { padding: 15px 12px; border-bottom: 1px solid #eee; vertical-align: middle; }
        tr:hover { background: #f8f9fa; }
        .cart-image { width: 80px; height: 80px; object-fit: cover; border-radius: 5px; }
        .quantity-input { width: 60px; padding: 8px; border: 1px solid #ddd; border-radius: 5px; text-align: center; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; transition: background 0.3s; text-decoration: none; display: inline-block; }
        .btn-update { background: #007bff; color: white; }
        .btn-update:hover { background: #0056b3; }
        .btn-remove { background: #dc3545; color: white; padding: 5px 10px; font-size: 12px; }
        .btn-remove:hover { background: #c82333; }
        .btn-continue { background: #6c757d; color: white; }
        .btn-continue:hover { background: #5a6268; }
        .btn-checkout { background: #28a745; color: white; }
        .btn-checkout:hover { background: #218838; }
        .grand-total { text-align: right; font-size: 1.3em; margin-top: 20px; color: #764ba2; }
        .empty-cart { text-align: center; padding: 50px; color: #999; }
        .button-group { display: flex; gap: 10px; margin-top: 20px; justify-content: flex-end; flex-wrap: wrap; }
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
                    <line x1="30" y1="30" x2="30" y2="12" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                    <line x1="30" y1="30" x2="42" y2="20" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    <circle cx="30" cy="30" r="2.5" fill="white"/>
                </svg>
            </div>
            <h1>Past Times</h1>
        </div>
        <div>
            <a href="shop.php">← Continue Shopping</a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php">Dashboard</a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="container">
        <div class="cart-card">
            <h2>🛒 Your Shopping Cart</h2>
            
            <?php if(isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
            <form method="POST" action="update_cart.php">
                <table>
                    <thead>
                        <tr>
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
                        $grand_total = 0;
                        foreach($_SESSION['cart'] as $index => $item): 
                            $total = $item['price'] * $item['quantity'];
                            $grand_total += $total;
                        ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars(!empty($item['image']) ? $item['image'] : 'https://via.placeholder.com/80x80?text=No+Image'); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-image">
                            </td>
                            <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                            <td>R<?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <input type="number" name="quantities[<?php echo $index; ?>]" 
                                       value="<?php echo $item['quantity']; ?>" min="1" class="quantity-input">
                            </td>
                            <td><strong>R<?php echo number_format($total, 2); ?></strong></td>
                            <td>
                                <a href="remove_item.php?index=<?php echo $index; ?>" class="btn btn-remove" 
                                   onclick="return confirm('Remove this item?')">Remove</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="grand-total">
                    <strong>Grand Total: R<?php echo number_format($grand_total, 2); ?></strong>
                </div>
                
                <div class="button-group">
                    <button type="submit" name="update_cart" class="btn btn-update">Update Cart</button>
                </div>
            </form>
            
            <div class="button-group">
                <a href="shop.php" class="btn btn-continue">Continue Shopping</a>
                <a href="checkout.php" class="btn btn-checkout">Proceed to Checkout</a>
            </div>
            <?php else: ?>
                <div class="empty-cart">
                    <h3>Your cart is empty</h3>
                    <p>Browse our products and add items to your cart!</p>
                    <br>
                    <a href="shop.php" class="btn btn-continue">Start Shopping</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>