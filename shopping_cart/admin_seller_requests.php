<?php
// admin_seller_requests.php - Admin interface for reviewing and managing seller product submissions
// Allows admin to approve or reject items that sellers want to list in the store
session_start();

// Authentication check: Only allow logged-in admins to access this page
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Establish database connection to clothesstore database
$conn = mysqli_connect("localhost", "root", "", "clothesstore");

// Terminate script if database connection fails
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Initialize feedback message variables for user notification
$message = '';
$error = '';

// Handle approval of a seller's clothing request
// Triggered when admin clicks "Approve" button via GET parameter
if (isset($_GET['approve']) && isset($_GET['id'])) {
    // Sanitize the request ID to prevent SQL injection
    $request_id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Retrieve the full request details before processing
    $getRequest = "SELECT * FROM seller_requests WHERE request_id = '$request_id'";
    $result = mysqli_query($conn, $getRequest);
    $request = mysqli_fetch_assoc($result);
    
    // Only proceed if the request record was found
    if ($request) {
        // Insert approved item into the main clothing inventory table
        // Sets initial stock to 1 unit
        $insert = "INSERT INTO clothing (name, category, price, description, image_url, stock) 
                   VALUES ('{$request['clothing_name']}', '{$request['category']}', '{$request['price']}', 
                           '{$request['description']}', '{$request['image_url']}', 1)";
        
        // Check if insertion was successful before updating status
        if (mysqli_query($conn, $insert)) {
            // Update the request status from 'pending' to 'approved'
            $update = "UPDATE seller_requests SET status = 'approved' WHERE request_id = '$request_id'";
            mysqli_query($conn, $update);
            $message = "Request approved and added to clothing store!";
        } else {
            // Capture database error if insertion fails
            $error = "Error adding to clothing: " . mysqli_error($conn);
        }
    }
}

// Handle rejection of a seller's clothing request
// Triggered when admin clicks "Reject" button via GET parameter
if (isset($_GET['reject']) && isset($_GET['id'])) {
    $request_id = mysqli_real_escape_string($conn, $_GET['id']);
    // Update status to 'rejected' without deleting the record (maintains history)
    $update = "UPDATE seller_requests SET status = 'rejected' WHERE request_id = '$request_id'";
    
    if (mysqli_query($conn, $update)) {
        $message = "Request rejected successfully!";
    } else {
        $error = "Error rejecting request: " . mysqli_error($conn);
    }
}

// Retrieve all seller requests joined with user information
// Orders by status (pending first), then by creation date (newest first)
$sql = "SELECT r.*, u.name as user_name, u.email 
        FROM seller_requests r 
        JOIN user u ON r.user_id = u.user_id 
        ORDER BY 
            CASE WHEN r.status = 'pending' THEN 0 ELSE 1 END,  -- Prioritize pending requests
            r.created_at DESC";  //Show newest first within each status group
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Ensure proper scaling on mobile devices -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Requests - Admin Panel</title>
    <style>
        /* Universal reset for consistent cross-browser rendering */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            /* Subtle gradient background with light blue and light purple */
            background: linear-gradient(135deg, #e6f3ff, #f0e6ff);
            min-height: 100vh;
        }
        .header {
            /* Gradient header with blue to purple horizontal transition */
            background: linear-gradient(to right, #7cb9e8, #9b7ec4);
            color: white;
            padding: 20px;
            /* Flex layout with space between title and user info */
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { font-size: 2em; }
        /* Centered content container with max-width for large screens */
        .container { max-width: 1200px; margin: 40px auto; padding: 20px; }
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            /* Subtle shadow for depth effect */
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            /* Purple border matching the theme */
            border: 2px solid #6b4e96;
        }
        h2 { color: #6b4e96; margin-bottom: 20px; }
        /* Full-width table with collapsed borders for clean appearance */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th {
            /* Gradient header row matching the main header */
            background: linear-gradient(to right, #7cb9e8, #9b7ec4);
            color: white;
            padding: 12px;
            text-align: left;
        }
        /* Table cells with padding and separator lines, top alignment for multi-line content */
        td { padding: 12px; border-bottom: 1px solid #ddd; vertical-align: top; }
        /* Highlight table row on hover for better readability */
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
        /* Green button for approval actions */
        .btn-approve { background: #4CAF50; color: white; }
        /* Orange button for rejection actions */
        .btn-reject { background: #ff9800; color: white; }
        /* Purple button for navigation back to dashboard */
        .btn-back { background: #6b4e96; color: white; }
        /* Badge styles for status indicators - pill-shaped labels */
        .badge-pending {
            background: #ff9800;  /* Orange for pending */
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            display: inline-block;
        }
        .badge-approved {
            background: #4CAF50;  /* Green for approved */
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            display: inline-block;
        }
        .badge-rejected {
            background: #f44336;  /* Red for rejected */
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            display: inline-block;
        }
        /* Item image thumbnail constraints */
        .request-image {
            max-width: 80px;
            max-height: 80px;
            border-radius: 5px;
        }
        /* Green success message banner with left border accent */
        .message {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        /* Red error message banner with left border accent */
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        /* Statistics overview area with flex layout */
        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        /* Individual stat boxes with light gray background */
        .stat-box {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            flex: 1;  /* Equal width distribution */
            text-align: center;
        }
        /* Large number display in stat boxes */
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #6b4e96;
        }
        /* Subtle label text below stat numbers */
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        /* Constrain description column width to prevent table overflow */
        .description-cell {
            max-width: 300px;
            font-size: 13px;
            color: #555;
        }
        /* Navigation links section at bottom of card */
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
            <!-- Display logged-in admin name with XSS protection -->
            Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
            <!-- Logout link ends admin session -->
            <a href="logout.php" style="color: white; text-decoration: none; margin-left: 20px;">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <!-- Flex container for heading and back button alignment -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>📋 Manage Seller Requests</h2>
                <a href="admin_dashboard.php" class="btn btn-back">← Back to Dashboard</a>
            </div>
            
            <!-- Display success message when present -->
            <?php if ($message): ?>
                <div class="message">✅ <?php echo $message; ?></div>
            <?php endif; ?>
            
            <!-- Display error message when present -->
            <?php if ($error): ?>
                <div class="error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php
            // Calculate statistics by iterating through all requests
            $pending_count = 0;
            $approved_count = 0;
            $rejected_count = 0;
            $all_requests = [];  // Store all requests for display
            if (mysqli_num_rows($result) > 0) {
                // Reset result pointer to beginning for counting
                mysqli_data_seek($result, 0);
                // Count requests by status and store in array
                while($row = mysqli_fetch_assoc($result)) {
                    if ($row['status'] == 'pending') $pending_count++;
                    elseif ($row['status'] == 'approved') $approved_count++;
                    elseif ($row['status'] == 'rejected') $rejected_count++;
                    $all_requests[] = $row;  // Store for later display
                }
            }
            ?>
            
            <!-- Statistics overview boxes showing request counts by status -->
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
            
            <!-- Display table only if requests exist -->
            <?php if (count($all_requests) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <!-- Table column headers -->
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
                        <!-- Loop through all seller requests -->
                        <?php foreach($all_requests as $request): ?>
                        <tr>
                            <!-- Request ID -->
                            <td><?php echo $request['request_id']; ?></td>
                            <!-- Seller information: name, email, and submission date -->
                            <td>
                                <strong><?php echo htmlspecialchars($request['user_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($request['email']); ?></small><br>
                                <!-- Format date as abbreviated month, day, year -->
                                <small><?php echo date('M d, Y', strtotime($request['created_at'])); ?></small>
                            </td>
                            <!-- Item details: name, brand, description, category -->
                            <td>
                                <strong><?php echo htmlspecialchars($request['clothing_name']); ?></strong><br>
                                <strong>Brand:</strong> <?php echo htmlspecialchars($request['brand']); ?><br>
                                <div class="description-cell">
                                    <strong>Desc:</strong> <?php echo substr(htmlspecialchars($request['description']), 0, 100); ?>
                                    <!-- Add ellipsis if description exceeds 100 characters -->
                                    <?php if(strlen($request['description']) > 100) echo '...'; ?>
                                </div>
                                <!-- Display category if available -->
                                <?php if($request['category']): ?>
                                    <strong>Category:</strong> <?php echo ucfirst($request['category']); ?>
                                <?php endif; ?>
                             </td>
                            <!-- Price formatted with dollar sign and 2 decimal places -->
                            <td>$<?php echo number_format($request['price'], 2); ?></td>
                            <!-- Item image thumbnail with fallback for missing images -->
                            <td>
                                <?php if ($request['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($request['image_url']); ?>" class="request-image" alt="Item">
                                <?php else: ?>
                                    <span style="color: #999;">No image</span>
                                <?php endif; ?>
                             </td>
                            <!-- Status badge with color-coded indicator -->
                            <td>
                                <?php if ($request['status'] == 'pending'): ?>
                                    <span class="badge-pending">⏳ Pending</span>
                                <?php elseif ($request['status'] == 'approved'): ?>
                                    <span class="badge-approved">✅ Approved</span>
                                <?php else: ?>
                                    <span class="badge-rejected">❌ Rejected</span>
                                <?php endif; ?>
                             </td>
                            <!-- Action buttons - only show approve/reject for pending requests -->
                            <td>
                                <?php if ($request['status'] == 'pending'): ?>
                                    <!-- Approve button with JavaScript confirmation dialog -->
                                    <a href="?approve&id=<?php echo $request['request_id']; ?>" 
                                       class="btn btn-approve" 
                                       onclick="return confirm('Approve this request? It will be added to the clothing store.')">
                                       ✓ Approve
                                    </a>
                                    <!-- Reject button with JavaScript confirmation dialog -->
                                    <a href="?reject&id=<?php echo $request['request_id']; ?>" 
                                       class="btn btn-reject" 
                                       onclick="return confirm('Reject this request?')">
                                       ✗ Reject
                                    </a>
                                <?php else: ?>
                                    <!-- Placeholder dash for already processed requests -->
                                    <span style="color: #999;">-</span>
                                <?php endif; ?>
                             </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <!-- Fallback message when no requests exist in database -->
                <p>No seller requests found.</p>
            <?php endif; ?>
            
            <!-- Bottom navigation links for quick access to other pages -->
            <div class="nav-links">
                <a href="admin_dashboard.php">← Go to Main Dashboard</a>
                <a href="add_clothing.php">+ Add Clothing Directly</a>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// Close database connection to free server resources
mysqli_close($conn);
?>