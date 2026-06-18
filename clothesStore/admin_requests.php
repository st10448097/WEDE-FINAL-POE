```php
<?php
// admin_requests.php - Admin interface for managing seller requests
// Allows admin to approve, reject, and communicate with sellers about their submissions
session_start(); // Start session for admin authentication
include 'DBConn.php'; // Include database connection

// Security check: Redirect unauthorized users to login page
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle Approve Request - Converts seller request into an actual store item
if (isset($_GET['approve']) && isset($_GET['id'])) {
    $request_id = intval($_GET['id']); // Cast to integer for security
    
    // Retrieve the full request details from seller_requests table
    $stmt = mysqli_prepare($conn, "SELECT * FROM seller_requests WHERE request_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $request_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $request = mysqli_fetch_assoc($result);
    
    // Only proceed if request record exists
    if ($request) {
        // Insert the seller's item into the main clothing inventory table
        // Stock starts at 1 unit by default
        $insert = mysqli_prepare($conn, "INSERT INTO clothing (name, category, price, description, image_url, stock) VALUES (?, ?, ?, ?, ?, 1)");
        mysqli_stmt_bind_param($insert, "ssdss", $request['clothing_name'], $request['category'], $request['price'], $request['description'], $request['image_url']);
        mysqli_stmt_execute($insert);
        
        // Update the request status to 'approved' in seller_requests table
        $update = mysqli_prepare($conn, "UPDATE seller_requests SET status = 'approved' WHERE request_id = ?");
        mysqli_stmt_bind_param($update, "i", $request_id);
        mysqli_stmt_execute($update);
        
        // Automatically send approval notification message to the seller
        $message_text = "Your request to sell '{$request['clothing_name']}' has been APPROVED! The item has been added to our store.";
        $msg_stmt = mysqli_prepare($conn, "INSERT INTO message (sender_id, receiver_id, message_text, time_sent) VALUES (?, ?, ?, NOW())");
        $admin_id = $_SESSION['admin_id']; // Current admin as sender
        mysqli_stmt_bind_param($msg_stmt, "iis", $admin_id, $request['user_id'], $message_text);
        mysqli_stmt_execute($msg_stmt);
        
        // Redirect with success message to prevent form resubmission
        header("Location: admin_requests.php?msg=approved");
        exit();
    }
}

// Handle Reject Request - Denies seller's item submission
if (isset($_GET['reject']) && isset($_GET['id'])) {
    $request_id = intval($_GET['id']);
    
    // Fetch request details for the notification message
    $stmt = mysqli_prepare($conn, "SELECT * FROM seller_requests WHERE request_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $request_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $request = mysqli_fetch_assoc($result);
    
    if ($request) {
        // Update status to 'rejected' instead of deleting to maintain history
        $update = mysqli_prepare($conn, "UPDATE seller_requests SET status = 'rejected' WHERE request_id = ?");
        mysqli_stmt_bind_param($update, "i", $request_id);
        mysqli_stmt_execute($update);
        
        // Send rejection notification message to the seller
        $message_text = "Your request to sell '{$request['clothing_name']}' has been REJECTED. Please contact admin for more information.";
        $msg_stmt = mysqli_prepare($conn, "INSERT INTO message (sender_id, receiver_id, message_text, time_sent) VALUES (?, ?, ?, NOW())");
        $admin_id = $_SESSION['admin_id'];
        mysqli_stmt_bind_param($msg_stmt, "iis", $admin_id, $request['user_id'], $message_text);
        mysqli_stmt_execute($msg_stmt);
        
        header("Location: admin_requests.php?msg=rejected");
        exit();
    }
}

// Handle Send Message - Admin sends custom message to a seller
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_message'])) {
    $receiver_id = intval($_POST['receiver_id']);
    // Sanitize message text to prevent SQL injection
    $message_text = mysqli_real_escape_string($conn, $_POST['message_text']);
    $admin_id = $_SESSION['admin_id'];
    
    // Insert message with current timestamp
    $stmt = mysqli_prepare($conn, "INSERT INTO message (sender_id, receiver_id, message_text, time_sent) VALUES (?, ?, ?, NOW())");
    mysqli_stmt_bind_param($stmt, "iis", $admin_id, $receiver_id, $message_text);
    mysqli_stmt_execute($stmt);
    header("Location: admin_requests.php?msg=sent");
    exit();
}

// Retrieve all seller requests with user information via JOIN
// Orders pending requests first, then by creation date (newest first)
$requests = mysqli_query($conn, "SELECT r.*, u.name as user_name, u.email 
    FROM seller_requests r 
    JOIN user u ON r.user_id = u.user_id 
    ORDER BY CASE WHEN r.status = 'pending' THEN 0 ELSE 1 END, r.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Seller Requests - Admin</title>
    <style>
        /* Global reset for consistent browser rendering */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        /* Gradient header matching brand color scheme */
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        /* Left side: logo and store name */
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px; /* Space between logo and title */
        }
        /* Fixed size clock logo container */
        .header-logo-icon {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .header-logo-icon svg {
            width: 100%;
            height: auto;
        }
        .header h1 { 
            font-size: 1.8em; 
            display: flex;
            align-items: center;
            gap: 10px;
        }
        /* Semi-transparent welcome message for visual depth */
        .welcome-text {
            opacity: 0.9;
        }
        /* Centered content container with max-width */
        .container { max-width: 1200px; margin: 40px auto; padding: 20px; }
        /* White card with shadow for content sections */
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        h2 { color: #764ba2; margin-bottom: 20px; }
        /* Full-width table with collapsed borders */
        table { width: 100%; border-collapse: collapse; }
        /* Table cells with padding and bottom border, vertical alignment at top for multi-line content */
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; vertical-align: top; }
        /* Purple header row with white text */
        th { background: #764ba2; color: white; }
        /* Highlight row on hover */
        tr:hover { background: #f5f5f5; }
        /* Base button styles with smooth transitions */
        .btn { padding: 5px 15px; border: none; border-radius: 3px; cursor: pointer; text-decoration: none; display: inline-block; margin: 2px; font-size: 12px; transition: all 0.3s; }
        /* Green approve button */
        .btn-approve { background: #28a745; color: white; }
        .btn-approve:hover { background: #218838; }
        /* Red reject button */
        .btn-reject { background: #dc3545; color: white; }
        .btn-reject:hover { background: #c82333; }
        /* Teal message button for communication actions */
        .btn-message { background: #17a2b8; color: white; }
        .btn-message:hover { background: #138496; }
        /* Gray back button for navigation */
        .btn-back { background: #6c757d; color: white; }
        .btn-back:hover { background: #5a6268; }
        /* Green success notification banner */
        .message { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        /* Status badges with rounded pill shape */
        .badge-pending { background: #ffc107; color: #333; padding: 3px 10px; border-radius: 12px; font-size: 12px; }
        .badge-approved { background: #28a745; color: white; padding: 3px 10px; border-radius: 12px; font-size: 12px; }
        .badge-rejected { background: #dc3545; color: white; padding: 3px 10px; border-radius: 12px; font-size: 12px; }
        /* Thumbnail image for request items */
        .request-image { width: 60px; height: 60px; object-fit: cover; border-radius: 5px; }
        /* Modal overlay - hidden by default, covers full viewport */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000; }
        /* Modal content box */
        .modal-content { background: white; padding: 30px; border-radius: 10px; width: 500px; }
        /* Show modal when targeted by URL hash */
        .modal:target { display: flex; }
        /* Close button in top-right of modal */
        .close { float: right; text-decoration: none; font-size: 24px; color: #333; transition: color 0.3s; }
        .close:hover { color: #000; }
        /* Textarea styling for message composition */
        textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; min-height: 100px; font-family: Arial, sans-serif; }
        /* Purple focus glow on textarea */
        textarea:focus { outline: none; border-color: #764ba2; box-shadow: 0 0 5px rgba(118, 75, 162, 0.3); }
        /* Statistics display area */
        .stats { display: flex; gap: 20px; margin-bottom: 20px; }
        /* Individual stat boxes with light background */
        .stat-box { background: #f8f9fa; padding: 15px; border-radius: 5px; flex: 1; text-align: center; }
        /* Large number display in stat boxes */
        .stat-number { font-size: 2em; font-weight: bold; color: #764ba2; }
        
        /* Navigation Tiles */
        /* Responsive grid for quick navigation */
        .nav-tiles {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); /* Auto-adjusting columns */
            gap: 20px;
            margin-top: 30px;
        }
        /* Individual tile with gradient background */
        .nav-tile {
            background: linear-gradient(135deg, #764ba2, #5a3d8a);
            color: white;
            padding: 25px 20px;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            transition: transform 0.3s, box-shadow 0.3s; /* Smooth hover animation */
            display: flex;
            flex-direction: column; /* Stack icon, label, description */
            align-items: center;
            justify-content: center;
            min-height: 120px;
        }
        /* Lift effect on hover with purple shadow */
        .nav-tile:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(118, 75, 162, 0.3);
        }
        /* Large emoji icon */
        .nav-tile .icon {
            font-size: 36px;
            margin-bottom: 10px;
        }
        /* Bold label */
        .nav-tile .label {
            font-size: 16px;
            font-weight: bold;
        }
        /* Subdued description text */
        .nav-tile .description {
            font-size: 12px;
            opacity: 0.8;
            margin-top: 5px;
        }
        /* Unique gradient colors for different tile destinations */
        .nav-tile.dashboard { background: linear-gradient(135deg, #667eea, #764ba2); }
        .nav-tile.clothing { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .nav-tile.messages { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .nav-tile.logout { background: linear-gradient(135deg, #fa709a, #fee140); }
        
        /* Responsive adjustments for mobile devices */
        @media (max-width: 768px) {
            .header { flex-direction: column; gap: 15px; text-align: center; }
            .modal-content { width: 90%; } /* Full-width modals on mobile */
            table { font-size: 14px; }
            th, td { padding: 8px; }
            .stats { flex-direction: column; } /* Stack stats vertically */
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="header-logo-icon">
                <!-- Simple clock SVG icon representing "Past Times" brand -->
                <svg width="45" height="45" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
                    <!-- Outer decorative circle -->
                    <circle cx="30" cy="30" r="28" fill="#ffffff" stroke="#ffffff" stroke-width="2" opacity="0.3"/>
                    <!-- Inner transparent fill -->
                    <circle cx="30" cy="30" r="24" fill="rgba(255,255,255,0.1)"/>
                    <!-- Clock face numerals -->
                    <text x="30" y="10" text-anchor="middle" font-size="8" fill="white" font-weight="bold">12</text>
                    <text x="48" y="33" text-anchor="middle" font-size="8" fill="white" font-weight="bold">3</text>
                    <text x="30" y="56" text-anchor="middle" font-size="8" fill="white" font-weight="bold">6</text>
                    <text x="12" y="33" text-anchor="middle" font-size="8" fill="white" font-weight="bold">9</text>
                    <!-- Hour marker ticks -->
                    <line x1="30" y1="8" x2="30" y2="13" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="52" y1="30" x2="47" y2="30" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="30" y1="52" x2="30" y2="47" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="8" y1="30" x2="13" y2="30" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <!-- Clock hands at classic 10:10 position -->
                    <line x1="30" y1="30" x2="30" y2="12" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                    <line x1="30" y1="30" x2="42" y2="20" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    <!-- Center pivot dot -->
                    <circle cx="30" cy="30" r="2.5" fill="white"/>
                </svg>
            </div>
            <h1>Past Times</h1>
        </div>
        <div>
            <!-- Display current admin username -->
            <span class="welcome-text">Welcome, <?php echo $_SESSION['admin_name']; ?></span>
        </div>
    </div>
    
    <div class="container">
        <!-- Display success/notification messages based on action -->
        <?php if (isset($_GET['msg'])): ?>
            <div class="message">
                <?php 
                // Map action codes to detailed success messages
                if ($_GET['msg'] == 'approved') echo "✅ Request approved! Item added to clothing store. Seller has been notified.";
                if ($_GET['msg'] == 'rejected') echo "✅ Request rejected. Seller has been notified.";
                if ($_GET['msg'] == 'sent') echo "✅ Message sent successfully!";
                ?>
            </div>
        <?php endif; ?>
        
        <?php
        // Calculate statistics for the dashboard overview
        // Count pending requests
        $pending = mysqli_query($conn, "SELECT COUNT(*) as count FROM seller_requests WHERE status='pending'");
        $pending_count = mysqli_fetch_assoc($pending)['count'];
        // Count approved requests
        $approved = mysqli_query($conn, "SELECT COUNT(*) as count FROM seller_requests WHERE status='approved'");
        $approved_count = mysqli_fetch_assoc($approved)['count'];
        ?>
        
        <!-- Statistics overview boxes -->
        <div class="stats">
            <div class="stat-box">
                <div class="stat-number"><?php echo $pending_count; ?></div>
                <div>Pending Requests</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $approved_count; ?></div>
                <div>Approved Items</div>
            </div>
        </div>
        
        <div class="card">
            <h2>📋 Seller Requests</h2>
            
            <!-- Conditional display: show table only if requests exist -->
            <?php if (mysqli_num_rows($requests) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <!-- Table column headers for request information -->
                            <th>ID</th>
                            <th>Seller</th>
                            <th>Item</th>
                            <th>Brand</th>
                            <th>Price</th>
                            <th>Condition</th>
                            <th>Image</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loop through all seller requests -->
                        <?php while($req = mysqli_fetch_assoc($requests)): ?>
                        <tr>
                            <!-- Request ID -->
                            <td><?php echo $req['request_id']; ?></td>
                            <!-- Seller name and email -->
                            <td>
                                <strong><?php echo htmlspecialchars($req['user_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($req['email']); ?></small>
                            </td>
                            <!-- Item name proposed by seller -->
                            <td><?php echo htmlspecialchars($req['clothing_name']); ?></td>
                            <!-- Brand information -->
                            <td><?php echo htmlspecialchars($req['brand']); ?></td>
                            <!-- Price formatted with dollar sign and 2 decimal places -->
                            <td>$<?php echo number_format($req['price'], 2); ?></td>
                            <!-- Item condition with fallback to 'Good' -->
                            <td><?php echo ucfirst($req['condition_status'] ?? 'Good'); ?></td>
                            <!-- Item image thumbnail -->
                            <td>
                                <?php if ($req['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($req['image_url']); ?>" class="request-image">
                                <?php else: ?>
                                    No image
                                <?php endif; ?>
                            </td>
                            <!-- Truncated description for table display -->
                            <td style="max-width: 200px;"><?php echo substr(htmlspecialchars($req['description']), 0, 100); ?>...</td>
                            <!-- Status badge -->
                            <td>
                                <?php if ($req['status'] == 'pending'): ?>
                                    <span class="badge-pending">Pending</span>
                                <?php elseif ($req['status'] == 'approved'): ?>
                                    <span class="badge-approved">Approved</span>
                                <?php else: ?>
                                    <span class="badge-rejected">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <!-- Action buttons -->
                            <td>
                                <!-- Only show approve/reject for pending requests -->
                                <?php if ($req['status'] == 'pending'): ?>
                                    <!-- Approve button with confirmation dialog to prevent accidental clicks -->
                                    <a href="?approve&id=<?php echo $req['request_id']; ?>" class="btn btn-approve" onclick="return confirm('Approve this request? It will be added to the store.')">✓ Approve</a>
                                    <!-- Reject button with confirmation dialog -->
                                    <a href="?reject&id=<?php echo $req['request_id']; ?>" class="btn btn-reject" onclick="return confirm('Reject this request?')">✗ Reject</a>
                                <?php endif; ?>
                                <!-- Message button available for all statuses, opens modal for that specific user -->
                                <a href="#messageModal<?php echo $req['user_id']; ?>" class="btn btn-message">💬 Message</a>
                            </td>
                        </tr>
                        
                        <!-- Message Modal - uniquely identified by user ID for CSS :target -->
                        <div id="messageModal<?php echo $req['user_id']; ?>" class="modal">
                            <div class="modal-content">
                                <!-- Close button removes hash from URL, hiding modal -->
                                <a href="#" class="close">&times;</a>
                                <h3>Message to <?php echo htmlspecialchars($req['user_name']); ?></h3>
                                <!-- Form submits to same page with send_message flag -->
                                <form method="POST">
                                    <!-- Hidden field identifying the recipient user -->
                                    <input type="hidden" name="receiver_id" value="<?php echo $req['user_id']; ?>">
                                    <div class="form-group">
                                        <label>Message:</label>
                                        <!-- Textarea for composing custom message to seller -->
                                        <textarea name="message_text" required placeholder="Type your message here..."></textarea>
                                    </div>
                                    <!-- Submit button triggers message insertion -->
                                    <button type="submit" name="send_message" class="btn btn-message">Send Message</button>
                                </form>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <!-- Fallback message when no requests exist -->
                <p>No seller requests found.</p>
            <?php endif; ?>
        </div>
        
        <!-- Navigation Tiles - Quick access to other admin sections -->
        <div class="nav-tiles">
            <!-- Dashboard tile -->
            <a href="admin_dashboard.php" class="nav-tile dashboard">
                <div class="icon">📊</div>
                <div class="label">Dashboard</div>
                <div class="description">Return to admin dashboard</div>
            </a>
            
            <!-- Clothing management tile -->
            <a href="admin_clothing.php" class="nav-tile clothing">
                <div class="icon">👕</div>
                <div class="label">Manage Clothing</div>
                <div class="description">View and manage clothing items</div>
            </a>
            
            <!-- Messages tile -->
            <a href="admin_messages.php" class="nav-tile messages">
                <div class="icon">💬</div>
                <div class="label">Messages</div>
                <div class="description">View user messages</div>
            </a>
            
            <!-- Logout tile -->
            <a href="logout.php" class="nav-tile logout">
                <div class="icon">🚪</div>
                <div class="label">Logout</div>
                <div class="description">Sign out of your account</div>
            </a>
        </div>
    </div>
</body>
</html>