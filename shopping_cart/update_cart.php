<?php
// update_cart.php - Handles cart quantity updates from the cart page
// Processes the update form submission and redirects back to cart display
session_start(); // Start session to access cart data

// Check if the form was submitted with the update button and quantities array
if(isset($_POST['update_cart']) && isset($_POST['quantities'])) {
    // Loop through each submitted quantity by its array index
    foreach($_POST['quantities'] as $index => $quantity) {
        // Verify the cart item at this index exists before updating
        if(isset($_SESSION['cart'][$index])) {
            // Update quantity, ensuring minimum value of 1
            // Cast to integer for safety and use max() to prevent negative/zero values
            $_SESSION['cart'][$index]['quantity'] = max(1, (int)$quantity);
        }
    }
}

// Redirect back to the cart page to display updated quantities
header("Location: cart.php");
exit(); // Terminate script after redirect
?>