<?php

include "db.php";

$product_id=$_POST['product_id'];
$customer_name=$_POST['customer_name'];
$rating=$_POST['rating'];
$review=$_POST['review'];

$stmt=$conn->prepare(
    "INSERT INTO reviews
     (product_id, customer_name, rating, review)
      VALUES (?, ?, ?, ?)");

$stmt->bind_param(
    "isis",
    $product_id,
    $customer_name,
    $rating,
    $review);   
    
$stmt->execute();
header("Location: products.php");
?>    
