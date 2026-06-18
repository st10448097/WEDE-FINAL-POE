<?php
// checkout.php - Payment checkout page integrating with PayFast payment gateway
// Displays order summary and handles payment processing and success confirmation
session_start(); // Start session to access cart and user data
include 'DBConn.php'; // Include database connection

// Calculate the grand total by summing all cart item line totals
$grand_total = 0;
if(isset($_SESSION['cart'])) {
    // Iterate through each cart item, multiplying price by quantity
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
        /* Universal reset for consistent cross-browser rendering */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        /* Gradient header matching brand color scheme */
        .header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; padding: 20px; 
            display: flex; justify-content: space-between; align-items: center; 
        }
        /* Left side header content container */
        .header-left { display: flex; align-items: center; gap: 15px; }
        .header h1 { font-size: 1.8em; }
        /* Navigation links in header with hover effect */
        .header a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; }
        .header a:hover { background: rgba(255,255,255,0.2); } /* Semi-transparent white on hover */
        /* Centered content container with max-width */
        .container { max-width: 800px; margin: 40px auto; padding: 20px; }
        /* White card with shadow for checkout content */
        .checkout-card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #764ba2; margin-bottom: 25px; }
        /* Order summary section containing item list */
        .order-summary { margin-bottom: 30px; }
        /* Individual order item row with space-between layout */
        .order-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
        /* Grand total row with larger font and bold text */
        .order-total { display: flex; justify-content: space-between; padding: 15px 0; font-size: 1.2em; font-weight: bold; }
        /* Base button styles */
        .btn { padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; }
        /* Green payment button */
        .btn-pay { background: #28a745; color: white; }
        .btn-pay:hover { background: #218838; }
        /* Gray cancel/back button */
        .btn-cancel { background: #6c757d; color: white; }
        .btn-cancel:hover { background: #5a6268; }
        /* Flex container for button alignment */
        .button-group { display: flex; gap: 10px; margin-top: 20px; }
        /* Success message styling after completed payment */
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
            <!-- Navigation link to return to cart -->
            <a href="cart.php">← Back to Cart</a>
            <!-- Navigation link to continue browsing products -->
            <a href="shop.php">Continue Shopping</a>
        </div>
    </div>
    
    <div class="container">
        <div class="checkout-card">
            <!-- Display checkout form only if cart has items -->
            <?php if(isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
                <h2>💳 Checkout</h2>
                
                <!-- Order summary section showing all cart items -->
                <div class="order-summary">
                    <h3 style="color: #666; margin-bottom: 15px;">Order Summary</h3>
                    <!-- Loop through each item in the cart for the summary -->
                    <?php foreach($_SESSION['cart'] as $item): ?>
                        <div class="order-item">
                            <!-- Display product name with quantity in parentheses -->
                            <span><?php echo htmlspecialchars($item['name']); ?> (x<?php echo $item['quantity']; ?>)</span>
                            <!-- Display line total (price × quantity) with R currency -->
                            <span>R<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <!-- Grand total row at bottom of order summary -->
                    <div class="order-total">
                        <span>Total Amount:</span>
                        <!-- Display calculated grand total -->
                        <span>R<?php echo number_format($grand_total, 2); ?></span>
                    </div>
                </div>
                
                <!-- PayFast Payment Form - submits to PayFast sandbox environment for testing -->
                <form action="https://sandbox.payfast.co.za/eng/process" method="POST">
                    <!-- Merchant Details - PayFast sandbox test credentials -->
                    <input type="hidden" name="merchant_id" value="10000100">
                    <input type="hidden" name="merchant_key" value="46f0cd694581a">
                    
                    <!-- Payment Details -->
                    <!-- Format amount with 2 decimal places, no thousands separator for PayFast -->
                    <input type="hidden" name="amount" value="<?php echo number_format($grand_total, 2, '.', ''); ?>">
                    <!-- Generic order description displayed on PayFast payment page -->
                    <input type="hidden" name="item_name" value="Past Times Order">
                    
                    <!-- Customer Details -->
                    <!-- Use logged-in user's name or fallback to 'Customer' -->
                    <input type="hidden" name="name_first" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Customer'); ?>">
                    
                    <!-- Return URLs for payment flow control -->
                    <!-- Success URL: returns to checkout with payment=success parameter -->
                    <input type="hidden" name="return_url" value="http://localhost/checkout.php?payment=success">
                    <!-- Cancel URL: returns to cart if customer cancels payment -->
                    <input type="hidden" name="cancel_url" value="http://localhost/cart.php">
                    
                    <!-- Action buttons for payment or cancellation -->
                    <div class="button-group">
                        <!-- Submit button sends all hidden fields to PayFast -->
                        <button type="submit" class="btn btn-pay">Pay with PayFast</button>
                        <!-- Cancel returns to cart without proceeding with payment -->
                        <a href="cart.php" class="btn btn-cancel">Cancel</a>
                    </div>
                </form>
                
            <?php elseif(isset($_GET['payment']) && $_GET['payment'] == 'success'): ?>
                <!-- Success message displayed after PayFast redirects back with payment=success -->
                <div class="success-message">
                    <h2>✅ Thank You for Your Purchase!</h2>
                    <p>Your payment has been processed successfully.</p>
                    <br>
                    <!-- Button to continue shopping after successful purchase -->
                    <a href="shop.php" class="btn btn-pay">Continue Shopping</a>
                </div>
                <!-- Clear the cart from session after successful payment -->
                <?php unset($_SESSION['cart']); ?>
            <?php else: ?>
                <!-- Displayed when accessing checkout with an empty cart -->
                <div style="text-align: center; padding: 40px;">
                    <h3>Your cart is empty</h3>
                    <br>
                    <!-- Link to start shopping -->
                    <a href="shop.php" class="btn btn-pay">Shop Now</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>