<?php
session_start();

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $product_id = $_POST['product_id'] ?? '';
    $product_name = $_POST['product_name'] ?? '';
    $product_price = floatval($_POST['product_price'] ?? 0);
    $product_image = $_POST['product_image'] ?? '';
    $quantity = max(1, intval($_POST['quantity'] ?? 1));

    if(!isset($_SESSION['cart'])){
        $_SESSION['cart'] = [];
    }

    // Check if product already in cart - update quantity instead
    $found = false;
    foreach($_SESSION['cart'] as $key => &$item) {
        if($item['id'] == $product_id) {
            $item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }
    
    if(!$found) {
        $_SESSION['cart'][] = [
            "id" => $product_id,
            "name" => $product_name,
            "price" => $product_price,
            "image" => $product_image,
            "quantity" => $quantity
        ];
    }

    // Redirect back to shop
    header("Location: shop.php");
    exit();
} else {
    // If someone tries to access directly without POST
    header("Location: shop.php");
    exit();
}
?>