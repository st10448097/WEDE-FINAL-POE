<?php
session_start();
include "DBConn.php";

$sql="SELECT * FROM products";
$result=mysqli_query($conn,$sql);
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Products</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 1000px;
                margin: 0 auto;
                padding: 20px;
                background: #f7f7f7;
            }
            .products-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 20px;
            }
            .product-card {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
                padding: 16px;
            }
            .product-card img {
                width: 100%;
                height: auto;
                display: block;
                border-radius: 8px;
                margin-bottom: 12px;
            }
            .product-card h3 {
                margin: 0 0 8px;
                font-size: 1.2rem;
            }
            .product-card p {
                margin: 0 0 12px;
            }
            .product-card form {
                display: grid;
                gap: 10px;
            }
            .product-card button {
                border: none;
                background: #007bff;
                color: #fff;
                padding: 10px 14px;
                border-radius: 6px;
                cursor: pointer;
            }
            .product-card button:hover {
                background: #0056d6;
            }
            .product-card input[type="number"] {
                width: 100%;
                padding: 8px;
                border: 1px solid #ccc;
                border-radius: 6px;
            }
            .top-actions {
                margin-bottom: 24px;
            }
        </style>
    </head>
    <body>
        <div class="top-actions">
            <h1>Products</h1>
            <a href="cart.php">View Cart</a>
        </div>

        <div class="products-grid">
        <?php
        while($row=mysqli_fetch_assoc($result)){
            $productImage = !empty($row['product_image']) ? $row['product_image'] : 'https://via.placeholder.com/350x350?text=Clothing';
        ?>
        <div class="product-card">
            <img src="<?php echo htmlspecialchars($productImage); ?>" alt="<?php echo htmlspecialchars($row['product_name']); ?>">
            <h3><?php echo htmlspecialchars($row['product_name']); ?></h3>
            <p>Price: R<?php echo htmlspecialchars($row['product_price']); ?></p>
            <form method="post" action="add_to_cart.php">

            <!--Hidden fields store product info-->
                <input type="hidden" name="product_id"
                 value="<?php echo htmlspecialchars($row['product_id']); ?>">

                <input type="hidden" name="product_name" 
                value="<?php echo htmlspecialchars($row['product_name']); ?>">

                <input type="hidden" name="product_price" 
                value="<?php echo htmlspecialchars($row['product_price']); ?>">

                <input type="hidden" name="product_image" 
                value="<?php echo htmlspecialchars($productImage); ?>">

                Quantity:
                <input type="number" name="quantity" value="1" min="1">

                <button type="submit">Add to Cart</button>
            </form>
        </div>
        <?php
        }
        ?>
        </div>
    </body>
</html>
