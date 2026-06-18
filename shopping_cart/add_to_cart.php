<?php
session_start();

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $product_id = $_POST['product_id'] ?? '';
    $product_name = $_POST['product_name'] ?? '';
    $product_price = $_POST['product_price'] ?? 0;
    $product_image = $_POST['product_image'] ?? '';
    $quantity = $_POST['quantity'] ?? 1;

    if(!isset($_SESSION['cart'])){
        $_SESSION['cart'] = [];
    }

    $item = [
        "id" => $product_id,
        "name" => $product_name,
        "price" => $product_price,
        "image" => $product_image,
        "quantity" => $quantity
    ];

    $_SESSION['cart'][] = $item;

    header("Location: products.php");
    exit();
}

    
