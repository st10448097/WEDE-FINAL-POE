<?php
// submit_review.php - Processes review submissions from the products page
// Inserts customer reviews into the reviews table and redirects back to products

include "DBConn.php"; // Include database connection file

// Retrieve form data from POST request
$product_id = $_POST['product_id'];       // ID of the product being reviewed
$customer_name = $_POST['customer_name']; // Name of the customer submitting the review
$rating = $_POST['rating'];               // Star rating (1-5)
$review = $_POST['review'];               // Review text content

// Prepare SQL statement to prevent SQL injection attacks
// Uses parameterized query with placeholders for all user-supplied values
$stmt = $conn->prepare(
    "INSERT INTO reviews
     (product_id, customer_name, rating, review)
      VALUES (?, ?, ?, ?)");

// Bind parameters with their respective data types
// "isis" specifies: integer, string, integer, string
$stmt->bind_param(
    "isis",
    $product_id,    // Integer: product ID
    $customer_name, // String: customer name
    $rating,        // Integer: star rating value
    $review);       // String: review text content
    
// Execute the prepared statement to insert the review into the database
$stmt->execute();

// Redirect user back to the products listing page after successful submission
// Note: No success/error handling is implemented - review is assumed to succeed
header("Location: products.php");
?>