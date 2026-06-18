<?php
// add_to_cart.php - Handles adding products to the shopping cart session
// Start or resume the current session to access cart data
session_start();

// Only process cart additions when form is submitted via POST method
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    // Retrieve product details from POST data with fallback defaults
    $product_id = $_POST['product_id'] ?? '';
    $product_name = $_POST['product_name'] ?? '';
    // Convert price string to float for calculations, default to 0 if missing
    $product_price = floatval($_POST['product_price'] ?? 0);
    $product_image = $_POST['product_image'] ?? '';
    // Ensure quantity is at least 1, even if invalid value submitted
    $quantity = max(1, intval($_POST['quantity'] ?? 1));

    // Initialize cart array in session if it doesn't already exist
    if(!isset($_SESSION['cart'])){
        $_SESSION['cart'] = [];
    }

    // Check if product already in cart - update quantity instead of adding duplicate
    $found = false;
    // Use reference (&) to modify the array item directly within the loop
    foreach($_SESSION['cart'] as $key => &$item) {
        // Match products by their unique ID
        if($item['id'] == $product_id) {
            // Increment existing item's quantity instead of creating new entry
            $item['quantity'] += $quantity;
            $found = true;
            break; // Exit loop once match is found
        }
    }
    
    // Only add new item to cart if product wasn't already present
    if(!$found) {
        // Append new product data as associative array to cart
        $_SESSION['cart'][] = [
            "id" => $product_id,
            "name" => $product_name,
            "price" => $product_price,
            "image" => $product_image,
            "quantity" => $quantity
        ];
    }

    // Redirect back to shop page after processing cart addition
    header("Location: shop.php");
    exit(); // Terminate script to prevent further execution after redirect
} else {
    // If someone tries to access this page directly without submitting the form
    header("Location: shop.php");
    exit(); // Redirect to shop page for non-POST requests
}
?>