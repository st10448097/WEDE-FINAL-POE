<?php
session_start();
include 'DBConn.php';

// Calculate grand total
$grand_total = 0;
if(isset($_SESSION['cart'])) {
    foreach($_SESSION['cart'] as $item) {
        $grand_total += $item['price'] * $item['quantity'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - Past Times</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        .header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; padding: 20px; 
            display: flex; justify-content: space-between; align-items: center; 
        }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .header h1 { font-size: 1.8em; }
        .header a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; }
        .header a:hover { background: rgba(255,255,255,0.2); }
        .container { max-width: 800px; margin: 40px auto; padding: 20px; }
        .checkout-card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #764ba2; margin-bottom: 25px; }
        .order-summary { margin-bottom: 30px; }
        .order-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
        .order-total { display: flex; justify-content: space-between; padding: 15px 0; font-size: 1.2em; font-weight: bold; }
        .btn { padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; }
        .btn-pay { background: #28a745; color: white; }
        .btn-pay:hover { background: #218838; }
        .btn-cancel { background: #6c757d; color: white; }
        .btn-cancel:hover { background: #5a6268; }
        .button-group { display: flex; gap: 10px; margin-top: 20px; }
        .success-message { 
            background: #d4edda; color: #155724; padding: 30px; border-radius: 10px; 
            text-align: center; border: 2px solid #28a745; 
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <h1>Past Times</h1>
        </div>
        <div>
            <a href="cart.php">← Back to Cart</a>
            <a href="shop.php">Continue Shopping</a>
        </div>
    </div>
    
    <div class="container">
        <div class="checkout-card">
            <?php if(isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
                <h2>💳 Checkout</h2>
                
                <div class="order-summary">
                    <h3 style="color: #666; margin-bottom: 15px;">Order Summary</h3>
                    <?php foreach($_SESSION['cart'] as $item): ?>
                        <div class="order-item">
                            <span><?php echo htmlspecialchars($item['name']); ?> (x<?php echo $item['quantity']; ?>)</span>
                            <span>R<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="order-total">
                        <span>Total Amount:</span>
                        <span>R<?php echo number_format($grand_total, 2); ?></span>
                    </div>
                </div>
                
                <!-- PayFast Payment Form -->
                <form action="https://sandbox.payfast.co.za/eng/process" method="POST">
                    <!-- Merchant Details -->
                    <input type="hidden" name="merchant_id" value="10000100">
                    <input type="hidden" name="merchant_key" value="46f0cd694581a">
                    
                    <!-- Payment Details -->
                    <input type="hidden" name="amount" value="<?php echo number_format($grand_total, 2, '.', ''); ?>">
                    <input type="hidden" name="item_name" value="Past Times Order">
                    
                    <!-- Customer Details -->
                    <input type="hidden" name="name_first" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Customer'); ?>">
                    
                    <!-- Return URLs -->
                    <input type="hidden" name="return_url" value="http://localhost/checkout.php?payment=success">
                    <input type="hidden" name="cancel_url" value="http://localhost/cart.php">
                    
                    <div class="button-group">
                        <button type="submit" class="btn btn-pay">Pay with PayFast</button>
                        <a href="cart.php" class="btn btn-cancel">Cancel</a>
                    </div>
                </form>
                
            <?php elseif(isset($_GET['payment']) && $_GET['payment'] == 'success'): ?>
                <div class="success-message">
                    <h2>✅ Thank You for Your Purchase!</h2>
                    <p>Your payment has been processed successfully.</p>
                    <br>
                    <a href="shop.php" class="btn btn-pay">Continue Shopping</a>
                </div>
                <?php unset($_SESSION['cart']); ?>
            <?php else: ?>
                <div style="text-align: center; padding: 40px;">
                    <h3>Your cart is empty</h3>
                    <br>
                    <a href="shop.php" class="btn btn-pay">Shop Now</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>