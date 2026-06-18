```php
<?php
// my_seller_requests.php - Seller's personal view of their submitted clothing requests
// Displays all requests with status tracking, statistics, and navigation options
session_start();

// Authentication check: Only allow logged-in sellers to access this page
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'seller') {
    header("Location: index.php"); // Redirect non-sellers to login
    exit();
}

// Include database connection
include 'DBConn.php';

$user_id = $_SESSION['user_id']; // Get current seller's ID from session

// Fetch all requests for this seller, ordered by newest first
$sql = "SELECT * FROM seller_requests WHERE user_id = ? ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Responsive viewport for mobile devices -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests - Past Times</title>
    <style>
        /* Universal reset for consistent cross-browser rendering */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: #f4f4f4;
            padding: 20px;
        }
        /* Dark header bar with navigation */
        .header {
            background: #333;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .header a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
        }
        .header a:hover {
            text-decoration: underline;
        }
        /* Main content container with white background */
        .container {
            max-width: 1100px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        /* Top bar with heading and action button */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap; /* Allow wrapping on small screens */
            gap: 10px;
        }
        /* Base button style */
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background: #0056b3; /* Darker blue on hover */
        }
        /* Green success button variant */
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #1e7e34;
        }
        /* Full-width table with collapsed borders */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        /* Light gray header row with bottom border */
        th {
            background: #f8f9fa;
            padding: 10px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }
        /* Table cell styling with vertical center alignment */
        td {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        /* Highlight row on hover */
        tr:hover {
            background: #f8f9fa;
        }
        /* Status badge base styling */
        .badge {
            padding: 3px 10px;
            border-radius: 3px;
            font-size: 12px;
            display: inline-block;
        }
        /* Yellow badge for pending status */
        .badge-pending {
            background: #ffc107;
            color: #000;
        }
        /* Green badge for approved status */
        .badge-approved {
            background: #28a745;
            color: white;
        }
        /* Red badge for rejected status */
        .badge-rejected {
            background: #dc3545;
            color: white;
        }
        /* Thumbnail image constraints */
        .request-image {
            max-width: 60px;
            max-height: 60px;
            border-radius: 3px;
        }
        /* Description text displayed below item in collapsed row */
        .description-text {
            color: #666;
            font-size: 13px;
            margin-top: 5px;
        }
        /* Bottom navigation links section */
        .nav-links {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }
        .nav-links a {
            margin-right: 15px;
            color: #007bff;
            text-decoration: none;
        }
        .nav-links a:hover {
            text-decoration: underline;
        }
        /* Empty state placeholder when no requests exist */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        .empty-state .btn {
            margin-top: 15px;
        }
        /* Statistics overview bar */
        .stats {
            display: flex;
            gap: 15px;
            flex-wrap: wrap; /* Wrap on small screens */
            margin-bottom: 20px;
        }
        /* Individual stat box with left border accent */
        .stat-box {
            background: #f8f9fa;
            padding: 10px 20px;
            border-radius: 4px;
            border-left: 3px solid #007bff; /* Blue accent border */
        }
        /* Large number display in stat box */
        .stat-box .number {
            font-size: 20px;
            font-weight: bold;
        }
        /* Smaller label text below number */
        .stat-box .label {
            font-size: 12px;
            color: #666;
        }
        /* Responsive adjustments for mobile screens */
        @media (max-width: 768px) {
            .header {
                flex-direction: column; /* Stack header vertically */
                gap: 10px;
                text-align: center;
            }
            table {
                font-size: 13px; /* Smaller text on mobile */
            }
            th, td {
                padding: 8px 5px; /* Reduced padding */
            }
            .request-image {
                max-width: 40px;
                max-height: 40px; /* Smaller thumbnails */
            }
            .top-bar {
                flex-direction: column; /* Stack top bar */
                align-items: stretch;
            }
            .stats {
                flex-direction: column; /* Stack stats vertically */
            }
        }
    </style>
</head>
<body>

<!-- Header with store name and user info -->
<div class="header">
    <h2>Past Times</h2>
    <div>
        <!-- Display logged-in seller's name with fallback -->
        Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
        <a href="logout.php">Logout</a>
    </div>
</div>

<!-- Main Container -->
<div class="container">

    <!-- Statistics counters calculated by iterating through results -->
    <?php
    $total = 0;
    $pending = 0;
    $approved = 0;
    $rejected = 0;
    
    // Reset result pointer to beginning for counting
    mysqli_data_seek($result, 0);
    // Loop through results to tally status counts
    while($row = mysqli_fetch_assoc($result)) {
        $total++;
        if($row['status'] == 'pending') $pending++;
        elseif($row['status'] == 'approved') $approved++;
        elseif($row['status'] == 'rejected') $rejected++;
    }
    // Reset pointer again for the display table below
    mysqli_data_seek($result, 0);
    ?>
    <!-- Statistics summary boxes -->
    <div class="stats">
        <div class="stat-box"><span class="number"><?php echo $total; ?></span> <span class="label">Total</span></div>
        <!-- Color-coded border accents match status badge colors -->
        <div class="stat-box" style="border-left-color: #ffc107;"><span class="number"><?php echo $pending; ?></span> <span class="label">Pending</span></div>
        <div class="stat-box" style="border-left-color: #28a745;"><span class="number"><?php echo $approved; ?></span> <span class="label">Approved</span></div>
        <div class="stat-box" style="border-left-color: #dc3545;"><span class="number"><?php echo $rejected; ?></span> <span class="label">Rejected</span></div>
    </div>

    <!-- Top Bar with heading and new request button -->
    <div class="top-bar">
        <h3>My Seller Requests</h3>
        <!-- Green button to submit a new seller request -->
        <a href="submit_seller_request.php" class="btn btn-success">+ New Request</a>
    </div>

    <!-- Requests Table - only displayed if requests exist -->
    <?php if (mysqli_num_rows($result) > 0): ?>
        <table>
            <thead>
                <tr>
                    <!-- Table column headers -->
                    <th>ID</th>
                    <th>Item</th>
                    <th>Brand</th>
                    <th>Price</th>
                    <th>Image</th>
                    <th>Status</th>
                    <th>Submitted</th>
                </tr>
            </thead>
            <tbody>
                <!-- Loop through each seller request -->
                <?php while($request = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <!-- Request ID with hash prefix -->
                    <td>#<?php echo $request['request_id']; ?></td>
                    <!-- Item name with XSS protection -->
                    <td><?php echo htmlspecialchars($request['clothing_name']); ?></td>
                    <!-- Brand name -->
                    <td><?php echo htmlspecialchars($request['brand']); ?></td>
                    <!-- Price formatted with dollar sign and 2 decimal places -->
                    <td>$<?php echo number_format($request['price'], 2); ?></td>
                    <!-- Item image thumbnail with fallback for missing images -->
                    <td>
                        <?php if (!empty($request['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($request['image_url']); ?>" class="request-image">
                        <?php else: ?>
                            <span style="color:#999;font-size:12px;">No image</span>
                        <?php endif; ?>
                    </td>
                    <!-- Status badge with color coding -->
                    <td>
                        <?php if ($request['status'] == 'pending'): ?>
                            <span class="badge badge-pending">Pending</span>
                        <?php elseif ($request['status'] == 'approved'): ?>
                            <span class="badge badge-approved">Approved</span>
                        <?php else: ?>
                            <span class="badge badge-rejected">Rejected</span>
                        <?php endif; ?>
                    </td>
                    <!-- Formatted submission date -->
                    <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                </tr>
                <!-- Show description in a collapsed row below the main row if it exists -->
                <?php if (!empty($request['description'])): ?>
                <tr>
                    <!-- Span across all 7 columns for full-width description -->
                    <td colspan="7" style="background:#f9f9f9;padding:5px 10px;">
                        <span class="description-text"><strong>Description:</strong> <?php echo htmlspecialchars($request['description']); ?></span>
                    </td>
                </tr>
                <?php endif; ?>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <!-- Empty state when no requests have been submitted -->
        <div class="empty-state">
            <p>You haven't submitted any seller requests yet.</p>
            <a href="submit_seller_request.php" class="btn btn-success">Sell Your Clothes Now</a>
        </div>
    <?php endif; ?>

    <!-- Navigation links at bottom of page -->
    <div class="nav-links">
        <a href="dashboard.php">← Dashboard</a>
        <a href="submit_seller_request.php">+ New Request</a>
        <!-- Only show "View Listings" link if seller has approved items -->
        <?php if ($approved > 0): ?>
            <a href="my_listings.php">View Listings</a>
        <?php endif; ?>
    </div>

</div>

</body>
</html>

<?php
// Close database connection to free server resources
mysqli_close($conn);
?>
