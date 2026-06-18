<?php
// admin_seller_requests.php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Connect to database
$conn = mysqli_connect("localhost", "root", "", "clothesstore");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$message = '';
$error = '';

// Handle approval
if (isset($_GET['approve']) && isset($_GET['id'])) {
    $request_id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Get request details
    $getRequest = "SELECT * FROM seller_requests WHERE request_id = '$request_id'";
    $result = mysqli_query($conn, $getRequest);
    $request = mysqli_fetch_assoc($result);
    
    if ($request) {
        // Insert into clothing table
        $insert = "INSERT INTO clothing (name, category, price, description, image_url, stock) 
                   VALUES ('{$request['clothing_name']}', '{$request['category']}', '{$request['price']}', 
                           '{$request['description']}', '{$request['image_url']}', 1)";
        
        if (mysqli_query($conn, $insert)) {
            // Update request status
            $update = "UPDATE seller_requests SET status = 'approved' WHERE request_id = '$request_id'";
            mysqli_query($conn, $update);
            $message = "Request approved and added to clothing store!";
        } else {
            $error = "Error adding to clothing: " . mysqli_error($conn);
        }
    }
}

// Handle rejection
if (isset($_GET['reject']) && isset($_GET['id'])) {
    $request_id = mysqli_real_escape_string($conn, $_GET['id']);
    $update = "UPDATE seller_requests SET status = 'rejected' WHERE request_id = '$request_id'";
    
    if (mysqli_query($conn, $update)) {
        $message = "Request rejected successfully!";
    } else {
        $error = "Error rejecting request: " . mysqli_error($conn);
    }
}

// Get all seller requests
$sql = "SELECT r.*, u.name as user_name, u.email 
        FROM seller_requests r 
        JOIN user u ON r.user_id = u.user_id 
        ORDER BY 
            CASE WHEN r.status = 'pending' THEN 0 ELSE 1 END,
            r.created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Requests - Admin Panel</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: linear-gradient(135deg, #e6f3ff, #f0e6ff);
            min-height: 100vh;
        }
        .header {
            background: linear-gradient(to right, #7cb9e8, #9b7ec4);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { font-size: 2em; }
        .container { max-width: 1200px; margin: 40px auto; padding: 20px; }
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border: 2px solid #6b4e96;
        }
        h2 { color: #6b4e96; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th {
            background: linear-gradient(to right, #7cb9e8, #9b7ec4);
            color: white;
            padding: 12px;
            text-align: left;
        }
        td { padding: 12px; border-bottom: 1px solid #ddd; vertical-align: top; }
        tr:hover { background-color: #f5f5f5; }
        .btn {
            padding: 5px 15px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 2px;
            font-size: 0.9em;
        }
        .btn-approve { background: #4CAF50; color: white; }
        .btn-reject { background: #ff9800; color: white; }
        .btn-back { background: #6b4e96; color: white; }
        .badge-pending {
            background: #ff9800;
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            display: inline-block;
        }
        .badge-approved {
            background: #4CAF50;
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            display: inline-block;
        }
        .badge-rejected {
            background: #f44336;
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            display: inline-block;
        }
        .request-image {
            max-width: 80px;
            max-height: 80px;
            border-radius: 5px;
        }
        .message {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            flex: 1;
            text-align: center;
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #6b4e96;
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        .description-cell {
            max-width: 300px;
            font-size: 13px;
            color: #555;
        }
        .nav-links {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
        }
        .nav-links a {
            color: #6b4e96;
            text-decoration: none;
            margin: 0 15px;
        }
        .nav-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Past Times - Seller Requests Manager</h1>
        <div>
            Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
            <a href="logout.php" style="color: white; text-decoration: none; margin-left: 20px;">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>📋 Manage Seller Requests</h2>
                <a href="admin_dashboard.php" class="btn btn-back">← Back to Dashboard</a>
            </div>
            
            <?php if ($message): ?>
                <div class="message">✅ <?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php
            // Calculate stats
            $pending_count = 0;
            $approved_count = 0;
            $rejected_count = 0;
            $all_requests = [];
            if (mysqli_num_rows($result) > 0) {
                mysqli_data_seek($result, 0);
                while($row = mysqli_fetch_assoc($result)) {
                    if ($row['status'] == 'pending') $pending_count++;
                    elseif ($row['status'] == 'approved') $approved_count++;
                    elseif ($row['status'] == 'rejected') $rejected_count++;
                    $all_requests[] = $row;
                }
            }
            ?>
            
            <div class="stats">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $pending_count; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $approved_count; ?></div>
                    <div class="stat-label">Approved</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $rejected_count; ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>
            
            <?php if (count($all_requests) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Seller</th>
                            <th>Item Details</th>
                            <th>Price</th>
                            <th>Image</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($all_requests as $request): ?>
                        <tr>
                            <td><?php echo $request['request_id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($request['user_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($request['email']); ?></small><br>
                                <small><?php echo date('M d, Y', strtotime($request['created_at'])); ?></small>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($request['clothing_name']); ?></strong><br>
                                <strong>Brand:</strong> <?php echo htmlspecialchars($request['brand']); ?><br>
                                <div class="description-cell">
                                    <strong>Desc:</strong> <?php echo substr(htmlspecialchars($request['description']), 0, 100); ?>
                                    <?php if(strlen($request['description']) > 100) echo '...'; ?>
                                </div>
                                <?php if($request['category']): ?>
                                    <strong>Category:</strong> <?php echo ucfirst($request['category']); ?>
                                <?php endif; ?>
                             </td>
                            <td>$<?php echo number_format($request['price'], 2); ?></td>
                            <td>
                                <?php if ($request['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($request['image_url']); ?>" class="request-image" alt="Item">
                                <?php else: ?>
                                    <span style="color: #999;">No image</span>
                                <?php endif; ?>
                             </td>
                            <td>
                                <?php if ($request['status'] == 'pending'): ?>
                                    <span class="badge-pending">⏳ Pending</span>
                                <?php elseif ($request['status'] == 'approved'): ?>
                                    <span class="badge-approved">✅ Approved</span>
                                <?php else: ?>
                                    <span class="badge-rejected">❌ Rejected</span>
                                <?php endif; ?>
                             </td>
                            <td>
                                <?php if ($request['status'] == 'pending'): ?>
                                    <a href="?approve&id=<?php echo $request['request_id']; ?>" 
                                       class="btn btn-approve" 
                                       onclick="return confirm('Approve this request? It will be added to the clothing store.')">
                                       ✓ Approve
                                    </a>
                                    <a href="?reject&id=<?php echo $request['request_id']; ?>" 
                                       class="btn btn-reject" 
                                       onclick="return confirm('Reject this request?')">
                                       ✗ Reject
                                    </a>
                                <?php else: ?>
                                    <span style="color: #999;">-</span>
                                <?php endif; ?>
                             </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No seller requests found.</p>
            <?php endif; ?>
            
            <div class="nav-links">
                <a href="admin_dashboard.php">← Go to Main Dashboard</a>
                <a href="add_clothing.php">+ Add Clothing Directly</a>
            </div>
        </div>
    </div>
</body>
</html>

<?php
mysqli_close($conn);
?>