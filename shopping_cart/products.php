```php
<?php
// products.php - Product listing page displaying all items from the products table
// Shows a grid of product cards with images, prices, and add-to-cart functionality
session_start(); // Start session to enable cart functionality
include "DBConn.php"; // Include database connection file

// Fetch all products from the database
$sql = "SELECT * FROM products";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Products</title>
        <style>
            /* Base body styling with centered content and max-width */
            body {
                font-family: Arial, sans-serif;
                max-width: 1000px;
                margin: 0 auto; /* Center the content horizontally */
                padding: 20px;
                background: #f7f7f7;
            }
            /* Responsive CSS Grid for product cards */
            .products-grid {
                display: grid;
                /* Automatically fit columns with minimum 280px width */
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 20px; /* Spacing between product cards */
            }
            /* Individual product card styling */
            .product-card {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 10px;
                overflow: hidden; /* Ensures border-radius clips child elements */
                box-shadow: 0 4px 12px rgba(0,0,0,0.05); /* Subtle shadow for depth */
                padding: 16px;
            }
            /* Product image fills card width */
            .product-card img {
                width: 100%;
                height: auto;
                display: block;
                border-radius: 8px;
                margin-bottom: 12px;
            }
            /* Product name styling */
            .product-card h3 {
                margin: 0 0 8px;
                font-size: 1.2rem;
            }
            /* Product price styling */
            .product-card p {
                margin: 0 0 12px;
            }
            /* Form uses grid layout for consistent spacing */
            .product-card form {
                display: grid;
                gap: 10px; /* Space between form elements */
            }
            /* Add to cart button with blue background */
            .product-card button {
                border: none;
                background: #007bff;
                color: #fff;
                padding: 10px 14px;
                border-radius: 6px;
                cursor: pointer; /* Shows hand cursor on hover */
            }
            /* Darker blue on hover for interactive feedback */
            .product-card button:hover {
                background: #0056d6;
            }
            /* Quantity input field styling */
            .product-card input[type="number"] {
                width: 100%;
                padding: 8px;
                border: 1px solid #ccc;
                border-radius: 6px;
            }
            /* Top actions section with margin below */
            .top-actions {
                margin-bottom: 24px;
            }
        </style>
    </head>
    <body>
        <!-- Page header with navigation to cart -->
        <div class="top-actions">
            <h1>Products</h1>
            <!-- Link to view shopping cart -->
            <a href="cart.php">View Cart</a>
        </div>

        <!-- Product grid container -->
        <div class="products-grid">
        <?php
        // Loop through each product record from the database
        while($row = mysqli_fetch_assoc($result)){
            // Use product image if available, otherwise show placeholder image
            $productImage = !empty($row['product_image']) ? $row['product_image'] : 'https://via.placeholder.com/350x350?text=Clothing';
        ?>
        <!-- Individual product card -->
        <div class="product-card">
            <!-- Display product image with alt text for accessibility -->
            <img src="<?php echo htmlspecialchars($productImage); ?>" alt="<?php echo htmlspecialchars($row['product_name']); ?>">
            <!-- Product name -->
            <h3><?php echo htmlspecialchars($row['product_name']); ?></h3>
            <!-- Product price with R currency symbol -->
            <p>Price: R<?php echo htmlspecialchars($row['product_price']); ?></p>
            <!-- Form submits product data to add_to_cart.php -->
            <form method="post" action="add_to_cart.php">

            <!-- Hidden fields store product info for cart processing -->
                <!-- Product ID for identification in cart -->
                <input type="hidden" name="product_id"
                 value="<?php echo htmlspecialchars($row['product_id']); ?>">

                <!-- Product name for display in cart -->
                <input type="hidden" name="product_name" 
                value="<?php echo htmlspecialchars($row['product_name']); ?>">

                <!-- Product price for calculation in cart -->
                <input type="hidden" name="product_price" 
                value="<?php echo htmlspecialchars($row['product_price']); ?>">

                <!-- Product image URL for display in cart -->
                <input type="hidden" name="product_image" 
                value="<?php echo htmlspecialchars($productImage); ?>">

                <!-- Quantity label and input field -->
                Quantity:
                <!-- Default quantity of 1, minimum 1 to prevent invalid values -->
                <input type="number" name="quantity" value="1" min="1">

                <!-- Submit button triggers adding item to session cart -->
                <button type="submit">Add to Cart</button>
            </form>
        </div>
        <?php
        } // End of while loop
        ?>
        </div>
    </body>
</html>