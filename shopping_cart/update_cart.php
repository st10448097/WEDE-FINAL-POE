<?php
session_start();

if(isset($_POST['update_cart']) && isset($_POST['quantities'])) {
    foreach($_POST['quantities'] as $index => $quantity) {
        if(isset($_SESSION['cart'][$index])) {
            $_SESSION['cart'][$index]['quantity'] = max(1, (int)$quantity);
        }
    }
}

header("Location: cart.php");
exit();
?>