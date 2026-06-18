<?php
// my_seller_requests.php - View all seller requests
session_start();

// Seller authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'seller') {
    header("Location: index.php");
    exit();
}

// Include database connection
include 'DBConn.php';

$user_id = $_SESSION['user_id'];

// Get user's requests using prepared statement
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests - Past Times</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: #f4f4f4;
            padding: 20px;
        }
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
        .container {
            max-width: 1100px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
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
            background: #0056b3;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #1e7e34;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background: #f8f9fa;
            padding: 10px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .badge {
            padding: 3px 10px;
            border-radius: 3px;
            font-size: 12px;
            display: inline-block;
        }
        .badge-pending {
            background: #ffc107;
            color: #000;
        }
        .badge-approved {
            background: #28a745;
            color: white;
        }
        .badge-rejected {
            background: #dc3545;
            color: white;
        }
        .request-image {
            max-width: 60px;
            max-height: 60px;
            border-radius: 3px;
        }
        .description-text {
            color: #666;
            font-size: 13px;
            margin-top: 5px;
        }
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
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        .empty-state .btn {
            margin-top: 15px;
        }
        .stats {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .stat-box {
            background: #f8f9fa;
            padding: 10px 20px;
            border-radius: 4px;
            border-left: 3px solid #007bff;
        }
        .stat-box .number {
            font-size: 20px;
            font-weight: bold;
        }
        .stat-box .label {
            font-size: 12px;
            color: #666;
        }
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            table {
                font-size: 13px;
            }
            th, td {
                padding: 8px 5px;
            }
            .request-image {
                max-width: 40px;
                max-height: 40px;
            }
            .top-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .stats {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <h2>Past Times</h2>
    <div>
        Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
        <a href="logout.php">Logout</a>
    </div>
</div>

<!-- Main Container -->
<div class="container">

    <!-- Stats -->
    <?php
    $total = 0;
    $pending = 0;
    $approved = 0;
    $rejected = 0;
    
    // Reset pointer to count
    mysqli_data_seek($result, 0);
    while($row = mysqli_fetch_assoc($result)) {
        $total++;
        if($row['status'] == 'pending') $pending++;
        elseif($row['status'] == 'approved') $approved++;
        elseif($row['status'] == 'rejected') $rejected++;
    }
    // Reset pointer for display
    mysqli_data_seek($result, 0);
    ?>
    <div class="stats">
        <div class="stat-box"><span class="number"><?php echo $total; ?></span> <span class="label">Total</span></div>
        <div class="stat-box" style="border-left-color: #ffc107;"><span class="number"><?php echo $pending; ?></span> <span class="label">Pending</span></div>
        <div class="stat-box" style="border-left-color: #28a745;"><span class="number"><?php echo $approved; ?></span> <span class="label">Approved</span></div>
        <div class="stat-box" style="border-left-color: #dc3545;"><span class="number"><?php echo $rejected; ?></span> <span class="label">Rejected</span></div>
    </div>

    <!-- Top Bar -->
    <div class="top-bar">
        <h3>My Seller Requests</h3>
        <a href="submit_seller_request.php" class="btn btn-success">+ New Request</a>
    </div>

    <!-- Table -->
    <?php if (mysqli_num_rows($result) > 0): ?>
        <table>
            <thead>
                <tr>
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
                <?php while($request = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td>#<?php echo $request['request_id']; ?></td>
                    <td><?php echo htmlspecialchars($request['clothing_name']); ?></td>
                    <td><?php echo htmlspecialchars($request['brand']); ?></td>
                    <td>$<?php echo number_format($request['price'], 2); ?></td>
                    <td>
                        <?php if (!empty($request['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($request['image_url']); ?>" class="request-image">
                        <?php else: ?>
                            <span style="color:#999;font-size:12px;">No image</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($request['status'] == 'pending'): ?>
                            <span class="badge badge-pending">Pending</span>
                        <?php elseif ($request['status'] == 'approved'): ?>
                            <span class="badge badge-approved">Approved</span>
                        <?php else: ?>
                            <span class="badge badge-rejected">Rejected</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                </tr>
                <?php if (!empty($request['description'])): ?>
                <tr>
                    <td colspan="7" style="background:#f9f9f9;padding:5px 10px;">
                        <span class="description-text"><strong>Description:</strong> <?php echo htmlspecialchars($request['description']); ?></span>
                    </td>
                </tr>
                <?php endif; ?>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">
            <p>You haven't submitted any seller requests yet.</p>
            <a href="submit_seller_request.php" class="btn btn-success">Sell Your Clothes Now</a>
        </div>
    <?php endif; ?>

    <!-- Navigation -->
    <div class="nav-links">
        <a href="dashboard.php">← Dashboard</a>
        <a href="submit_seller_request.php">+ New Request</a>
        <?php if ($approved > 0): ?>
            <a href="my_listings.php">View Listings</a>
        <?php endif; ?>
    </div>

</div>

</body>
</html>

<?php
// Close connection
mysqli_close($conn);
?>