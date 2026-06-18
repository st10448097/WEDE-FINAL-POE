<?php
session_start();

if(isset($_GET['index'])){
    $index=$_GET['index'];

    //remove item from cart
    unset($_SESSION['cart'][$index]);

    //reorganize array indexes
    $_SESSION['cart']=array_values($_SESSION['cart']);

    //redirect to cart page
    header("Location: cart.php");


}


?>