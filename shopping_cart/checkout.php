<?php
session_start();
?>
<form action="https://sandbox.payfast.co.za/eng/process" method="POST">

    <!--Merchant Details-->
    <input type="hidden" name="merchant_id" value="10000100">
    <input type="hidden" name="merchant_key" value="46f0cd694581a">

    <!--Payment Details-->
    <input type="hidden" name="amount" value="<?php echo $grand_total; ?>">
    <input type="hidden" name="item_name" value="Shopping Cart Order">

    <!--Customer Details-->
    <input type="hidden" name="name_first" value="Customer">
    <button type="submit">
    Pay Now
    </button>
</form>

//clear cart after checkout
<?php
unset($_SESSION['cart']);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Checkout</title>
    </head>
    <body>
        <h1>Thank you for your purchase!</h1>
        <a href="products.php">
            <button>Continue Shopping</button>
        </a>
    </body>
</html>