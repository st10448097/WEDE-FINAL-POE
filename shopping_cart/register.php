<?php
session_start();

if(isset($_GET['index'])) {
    $index = intval($_GET['index']);
    
    // Remove item from cart
    if(isset($_SESSION['cart'][$index])) {
        unset($_SESSION['cart'][$index]);
        // Reorganize array indexes
        $_SESSION['cart'] = array_values($_SESSION['cart']);
    }
}

header("Location: cart.php");
exit();
?>