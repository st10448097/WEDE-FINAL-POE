<?php
session_start();
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Shopping Cart</title>
    </head>
    <body>
        <h1>Your Shopping Cart</h1>
        <form method="POST" action="update_cart.php">
        <table border="1" cellpadding="10">
            <tr>
                <th>Image</th>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Total</th>
                <th>Action</th>
            </tr>
            <?php
            $grand_total=0;
            if(isset($_SESSION['cart'])&& !empty($_SESSION['cart'])){
                foreach($_SESSION['cart'] as $index=>$item){
                    $total=$item['price']*$item['quantity'];
                    $grand_total+=$total;
            ?>
            <tr>
                <td>
                    <img src="<?php echo htmlspecialchars(!empty($item['image']) ? $item['image'] : 'https://via.placeholder.com/100x100?text=No+Image'); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="max-width:100px; height:auto;">
                </td>
                <!--Product-->
                <td>
                    <?php echo htmlspecialchars($item['name']); ?>
                </td>

                <!--Price-->
                <td>
                    R<?php echo htmlspecialchars($item['price']); ?>
                </td>

                <!--Quantity-->
                <td>
                    <input
                    type="number"
                    name="quantities[<?php echo $index; ?>]"
                    value="<?php echo htmlspecialchars($item['quantity']); ?>"
                     min="1">
                </td>

                <!--Total-->
                <td>
                    R<?php echo htmlspecialchars($total); ?>
                </td>
                <!--Remove Button-->
                <td>
                    <a href="remove_item.php?index=<?php echo $index; ?>">
                        Remove
                    </a>
                </td>
            </tr>
            <?php
                }
            }
            ?>
        </table>
        <h3>Grand Total: R<?php echo $grand_total; ?></h3>
        <br>
        <!--update cart button-->
        <button type="submit" name="update_cart">
            Update Cart
        </button>
        </form>
        <br><br>
        <!--Continue shopping-->
        <a href="products.php">
            <button>Continue Shopping</button>
        </a>
        <!--Checkout-->
        <a href="checkout.php">
            <button>Checkout</button>
        </a>

    </body>
</html>