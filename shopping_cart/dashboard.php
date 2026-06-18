<?php
// dashboard.php - Role-based user dashboard for buyers and sellers
// Displays personalized content, statistics, and quick actions based on user role
session_start(); // Start session to access user authentication data
include 'DBConn.php'; // Include database connection

// Authentication check: Redirect to login page if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Extract user information from session variables for convenience
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];

// Retrieve complete user profile data from database
$stmt = mysqli_prepare($conn, "SELECT * FROM user WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Initialize counters for dashboard statistics
$pending_requests = 0;
$approved_requests = 0;
$rejected_requests = 0;
$total_items = 0;
$unread_messages = 0;

// Seller-specific statistics: Count requests by status
if ($user_role == 'seller') {
    // Get counts grouped by request status for this seller
    $stmt = mysqli_prepare($conn, "SELECT status, COUNT(*) as count FROM seller_requests WHERE user_id = ? GROUP BY status");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $stats = mysqli_stmt_get_result($stmt);
    // Map status counts to corresponding variables
    while ($stat = mysqli_fetch_assoc($stats)) {
        if ($stat['status'] == 'pending') $pending_requests = $stat['count'];
        if ($stat['status'] == 'approved') $approved_requests = $stat['count'];
        if ($stat['status'] == 'rejected') $rejected_requests = $stat['count'];
    }
    
    // Count total approved items (effectively items sold in the store)
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM seller_requests WHERE user_id = ? AND status = 'approved'");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $total_items = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
}

// Count unread messages for notification badge
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as unread FROM message WHERE receiver_id = ? AND is_read = 0");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$unread_messages = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['unread'];

// Count items currently in shopping cart (or 0 if cart doesn't exist)
$cart_count = count($_SESSION['cart'] ?? []);

// Fetch recent messages for the conversation preview
$recent_messages = [];
$stmt = mysqli_prepare($conn, "SELECT m.*, 
    -- Determine sender display name: 'Me' for own messages, otherwise user's name
    CASE WHEN m.sender_id = ? THEN 'Me' ELSE u.name END as sender_name
    FROM message m
    JOIN user u ON m.sender_id = u.user_id
    -- Show messages where user is either sender or receiver
    WHERE m.receiver_id = ? OR m.sender_id = ?
    ORDER BY m.time_sent DESC LIMIT 5"); // Most recent 5 messages
mysqli_stmt_bind_param($stmt, "iii", $user_id, $user_id, $user_id);
mysqli_stmt_execute($stmt);
$recent_messages = mysqli_stmt_get_result($stmt);

// Fetch recent seller requests for sellers only
$recent_requests = [];
if ($user_role == 'seller') {
    $stmt = mysqli_prepare($conn, "SELECT * FROM seller_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $recent_requests = mysqli_stmt_get_result($stmt);
}

// Fetch available clothing items for buyers to browse
$available_clothing = [];
$stmt = mysqli_prepare($conn, "SELECT * FROM clothing WHERE stock > 0 ORDER BY created_at DESC LIMIT 8");
mysqli_stmt_execute($stmt);
$available_clothing = mysqli_stmt_get_result($stmt);

// Check if orders table exists and fetch recent orders for buyers
$recent_orders = [];
$has_orders_table = mysqli_query($conn, "SHOW TABLES LIKE 'orders'");
if (mysqli_num_rows($has_orders_table) > 0) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE buyer_id = ? ORDER BY order_date DESC LIMIT 5");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $recent_orders = mysqli_stmt_get_result($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Responsive viewport for mobile devices -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Past Times</title>
    <style>
        /* Universal reset for consistent cross-browser rendering */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        /* Left header section: logo and title */
        .header-left { display: flex; align-items: center; gap: 15px; }
        .header-logo-icon { width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; }
        .header-logo-icon svg { width: 100%; height: auto; }
        .header h1 { font-size: 1.8em; display: flex; align-items: center; gap: 10px; }
        /* Role badge displayed next to store name */
        .header h1 span { font-size: 0.6em; background: rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 20px; margin-left: 10px; }
        /* User menu area with navigation links */
        .user-menu { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
        .user-menu .role-badge { background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 20px; font-size: 0.9em; }
        .user-menu a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: background 0.3s; }
        .user-menu a:hover { background: rgba(255,255,255,0.2); } /* Semi-transparent hover */
        
        /* Container */
        .container { max-width: 1400px; margin: 0 auto; padding: 30px; }
        
        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .welcome-banner h2 { font-size: 1.8em; margin-bottom: 10px; }
        .welcome-banner p { opacity: 0.9; } /* Slightly faded subtitle */
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); /* Responsive grid */
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s; /* Smooth lift animation */
        }
        .stat-card:hover { transform: translateY(-5px); } /* Lift effect on hover */
        .stat-card .icon { font-size: 2.5em; margin-bottom: 10px; }
        .stat-card .number { font-size: 2em; font-weight: bold; color: #764ba2; }
        .stat-card .label { color: #666; margin-top: 5px; }
        
        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        /* Cards */
        .card {
            background: white;
            border-radius: 15px;
            overflow: hidden; /* Ensures border-radius clips content */
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .card-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 2px solid #764ba2; /* Purple accent border */
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-header h3 { color: #764ba2; }
        .card-header a { color: #764ba2; text-decoration: none; font-size: 0.9em; }
        .card-body { padding: 20px; }
        
        /* Tables */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .data-table th { color: #666; font-weight: 600; font-size: 0.9em; }
        .data-table tr:hover { background: #f8f9fa; } /* Row highlight */
        
        /* Product Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); /* Auto-fill responsive grid */
            gap: 15px;
        }
        .product-card {
            background: #f8f9fa;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s;
            cursor: pointer;
        }
        .product-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .product-card .product-image {
            height: 150px;
            background: #ddd; /* Placeholder background */
            display: flex;
            align-items: center;
            justify-content: center;
            background-size: cover;
            background-position: center;
        }
        .product-card .product-info { padding: 12px; }
        .product-card .product-name { font-weight: bold; margin-bottom: 5px; }
        .product-card .product-price { color: #764ba2; font-weight: bold; }
        .product-card .product-stock { font-size: 0.8em; color: #666; }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75em;
            font-weight: 600;
        }
        .badge-pending { background: #ffc107; color: #333; } /* Yellow/orange for pending */
        .badge-approved { background: #28a745; color: white; } /* Green for approved */
        .badge-rejected { background: #dc3545; color: white; } /* Red for rejected */
        .badge-success { background: #28a745; color: white; }
        .badge-warning { background: #ffc107; color: #333; }
        
        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap; /* Allow wrapping on smaller screens */
            margin-top: 20px;
        }
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: #764ba2;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.3s;
        }
        .action-btn:hover { background: #5a3d82; }
        .action-btn.secondary { background: #6c757d; } /* Gray secondary button */
        .action-btn.secondary:hover { background: #5a6268; }
        .action-btn.shop { background: #28a745; } /* Green shop button */
        .action-btn.shop:hover { background: #218838; }
        
        /* Message List */
        .message-item {
            padding: 12px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .message-item.unread { background: #e3f2fd; } /* Light blue highlight for unread */
        .message-item:last-child { border-bottom: none; }
        .message-sender { font-weight: bold; }
        .message-time { font-size: 0.75em; color: #666; }
        
        /* Responsive adjustments for mobile */
        @media (max-width: 768px) {
            .container { padding: 15px; }
            .dashboard-grid { grid-template-columns: 1fr; } /* Single column */
            .stats-grid { grid-template-columns: repeat(2, 1fr); } /* Two columns for stats */
            .header { flex-direction: column; gap: 15px; text-align: center; }
        }
        
        /* Empty state placeholder */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        /* Button to view all items */
        .btn-view-all {
            display: inline-block;
            margin-top: 15px;
            padding: 8px 15px;
            background: #764ba2;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="header-logo-icon">
                <!-- Simple clock SVG icon representing "Past Times" brand -->
                <svg width="45" height="45" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="30" cy="30" r="28" fill="#ffffff" stroke="#ffffff" stroke-width="2" opacity="0.3"/>
                    <circle cx="30" cy="30" r="24" fill="rgba(255,255,255,0.1)"/>
                    <text x="30" y="10" text-anchor="middle" font-size="8" fill="white" font-weight="bold">12</text>
                    <text x="48" y="33" text-anchor="middle" font-size="8" fill="white" font-weight="bold">3</text>
                    <text x="30" y="56" text-anchor="middle" font-size="8" fill="white" font-weight="bold">6</text>
                    <text x="12" y="33" text-anchor="middle" font-size="8" fill="white" font-weight="bold">9</text>
                    <line x1="30" y1="8" x2="30" y2="13" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="52" y1="30" x2="47" y2="30" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="30" y1="52" x2="30" y2="47" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="8" y1="30" x2="13" y2="30" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="30" y1="30" x2="30" y2="12" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                    <line x1="30" y1="30" x2="42" y2="20" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    <circle cx="30" cy="30" r="2.5" fill="white"/>
                </svg>
            </div>
            <!-- Display store name with role-specific portal label -->
            <h1>Past Times <span><?php echo ucfirst($user_role); ?> Portal</span></h1>
        </div>
        <div class="user-menu">
            <!-- User identity and role badges -->
            <span class="role-badge">👤 <?php echo htmlspecialchars($user_name); ?></span>
            <span class="role-badge">🎯 <?php echo ucfirst($user_role); ?></span>
            <!-- Navigation links with cart count -->
            <a href="shop.php">🛍️ Shop</a>
            <a href="cart.php">🛒 Cart (<?php echo $cart_count; ?>)</a>
            <a href="logout.php">🚪 Logout</a>
        </div>
    </div>
    
    <div class="container">
        <!-- Welcome Banner with personalized greeting -->
        <div class="welcome-banner">
            <h2>Welcome back, <?php echo htmlspecialchars($user_name); ?>! 👋</h2>
            <p>Your personalized dashboard for Past Times clothing store.</p>
        </div>
        
        <!-- Statistics Cards - Content varies by user role -->
        <div class="stats-grid">
            <?php if ($user_role == 'seller'): ?>
                <!-- Seller stats: Pending requests count -->
                <div class="stat-card">
                    <div class="icon">📦</div>
                    <div class="number"><?php echo $pending_requests; ?></div>
                    <div class="label">Pending Requests</div>
                </div>
                <!-- Seller stats: Approved items count -->
                <div class="stat-card">
                    <div class="icon">✅</div>
                    <div class="number"><?php echo $approved_requests; ?></div>
                    <div class="label">Approved Items</div>
                </div>
                <!-- Seller stats: Total items sold -->
                <div class="stat-card">
                    <div class="icon">💰</div>
                    <div class="number"><?php echo $total_items; ?></div>
                    <div class="label">Items Sold</div>
                </div>
            <?php else: ?>
                <!-- Buyer stats: Available items in shop -->
                <div class="stat-card">
                    <div class="icon">🛍️</div>
                    <div class="number"><?php echo mysqli_num_rows($available_clothing); ?></div>
                    <div class="label">Available Items</div>
                </div>
                <!-- Buyer stats: Items in cart -->
                <div class="stat-card">
                    <div class="icon">🛒</div>
                    <div class="number"><?php echo $cart_count; ?></div>
                    <div class="label">Cart Items</div>
                </div>
            <?php endif; ?>
            <!-- Shared stats: Unread messages count -->
            <div class="stat-card">
                <div class="icon">💬</div>
                <div class="number"><?php echo $unread_messages; ?></div>
                <div class="label">Unread Messages</div>
            </div>
            <!-- Placeholder rating stat (hardcoded for demo) -->
            <div class="stat-card">
                <div class="icon">⭐</div>
                <div class="number">4.8</div>
                <div class="label">Rating</div>
            </div>
        </div>
        
        <!-- Quick Action Buttons - Role-specific shortcuts -->
        <div class="quick-actions">
            <a href="shop.php" class="action-btn shop">🛍️ Browse Shop</a>
            <a href="cart.php" class="action-btn">🛒 View Cart (<?php echo $cart_count; ?>)</a>
            <?php if ($user_role == 'seller'): ?>
                <!-- Seller-specific actions -->
                <a href="seller_submit_request.php" class="action-btn">📦 + Sell Your Clothes</a>
                <a href="my_seller_requests.php" class="action-btn">📋 My Requests</a>
            <?php endif; ?>
            <!-- Dynamic messages link based on role -->
            <a href="<?php echo ($user_role == 'seller') ? 'seller_messages.php' : 'admin_messages.php'; ?>" class="action-btn">💬 Messages</a>
        </div>
        
        <div class="dashboard-grid">
            <!-- Recent Messages Card -->
            <div class="card">
                <div class="card-header">
                    <h3>💬 Recent Messages</h3>
                    <a href="<?php echo ($user_role == 'seller') ? 'seller_messages.php' : 'admin_messages.php'; ?>">View All →</a>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($recent_messages) > 0): ?>
                        <!-- Loop through recent messages -->
                        <?php while($msg = mysqli_fetch_assoc($recent_messages)): ?>
                            <div class="message-item <?php echo ($msg['receiver_id'] == $user_id && $msg['is_read'] == 0) ? 'unread' : ''; ?>">
                                <div>
                                    <div class="message-sender"><?php echo htmlspecialchars($msg['sender_name']); ?></div>
                                    <!-- Truncate message preview to 60 characters -->
                                    <div class="message-text" style="font-size: 0.9em; color: #666;">
                                        <?php echo substr(htmlspecialchars($msg['message_text']), 0, 60); ?>...
                                    </div>
                                </div>
                                <!-- Format message date as abbreviated month and day -->
                                <div class="message-time"><?php echo date('M d', strtotime($msg['time_sent'])); ?></div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">No messages yet. Start a conversation!</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Role-Specific Content -->
            <?php if ($user_role == 'seller'): ?>
                <!-- Seller: Recent Sell Requests -->
                <div class="card">
                    <div class="card-header">
                        <h3>📋 Recent Sell Requests</h3>
                        <a href="my_seller_requests.php">View All →</a>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($recent_requests) > 0): ?>
                            <table class="data-table">
                                <thead>
                                    <tr><th>Item</th><th>Price</th><th>Status</th><th>Date</th></tr>
                                </thead>
                                <tbody>
                                    <?php while($req = mysqli_fetch_assoc($recent_requests)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($req['clothing_name']); ?></td>
                                        <td>R<?php echo number_format($req['price'], 2); ?></td>
                                        <td>
                                            <!-- Dynamic badge class based on status -->
                                            <span class="badge badge-<?php echo $req['status']; ?>">
                                                <?php echo ucfirst($req['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d', strtotime($req['created_at'])); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>You haven't submitted any sell requests yet.</p>
                                <a href="seller_submit_request.php" class="btn-view-all">Start Selling →</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Seller Tips Card - Educational content for sellers -->
                <div class="card">
                    <div class="card-header">
                        <h3>💡 Seller Tips</h3>
                    </div>
                    <div class="card-body">
                        <ul style="margin-left: 20px; color: #666; line-height: 1.8;">
                            <li>📸 Upload clear, well-lit photos of your items</li>
                            <li>📝 Provide accurate descriptions including size, condition, and brand</li>
                            <li>💰 Price your items competitively</li>
                            <li>💬 Respond to admin messages promptly</li>
                            <li>✨ Approved items will appear in the shop</li>
                        </ul>
                    </div>
                </div>
            <?php else: ?>
                <!-- Buyer: Available Products (New Arrivals) -->
                <div class="card">
                    <div class="card-header">
                        <h3>🛍️ New Arrivals</h3>
                        <a href="shop.php">View All →</a>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($available_clothing) > 0): ?>
                            <div class="products-grid">
                                <!-- Loop through available clothing items -->
                                <?php while($item = mysqli_fetch_assoc($available_clothing)): ?>
                                    <!-- Each product card links to product details page -->
                                    <a href="product_details.php?id=<?php echo $item['clothing_id']; ?>" 
                                       style="text-decoration: none; color: inherit; display: block;">
                                        <div class="product-card">
                                            <div class="product-image" style="background-image: url('<?php echo htmlspecialchars($item['image_url'] ?: 'images/placeholder.jpg'); ?>'); background-size: cover;">
                                                <!-- Show placeholder text if no image available -->
                                                <?php if(!$item['image_url']): ?>
                                                    <span style="color: #999;">No Image</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="product-info">
                                                <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                                <div class="product-price">R<?php echo number_format($item['price'], 2); ?></div>
                                                <div class="product-stock">Stock: <?php echo $item['stock']; ?></div>
                                            </div>
                                        </div>
                                    </a>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">No products available at the moment. Check back soon!</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Buyer: Recent Orders (only if orders table exists) -->
                <div class="card">
                    <div class="card-header">
                        <h3>📦 Recent Orders</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($has_orders_table && mysqli_num_rows($recent_orders) > 0): ?>
                            <table class="data-table">
                                <thead><tr><th>Order #</th><th>Date</th><th>Total</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php while($order = mysqli_fetch_assoc($recent_orders)): ?>
                                    <tr>
                                        <td>#<?php echo $order['order_id']; ?></td>
                                        <td><?php echo date('M d', strtotime($order['order_date'])); ?></td>
                                        <td>R<?php echo number_format($order['total_price'], 2); ?></td>
                                        <td><span class="badge badge-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>You haven't placed any orders yet.</p>
                                <a href="shop.php" class="btn-view-all">Start Shopping →</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Account Information Card - Always visible at bottom -->
        <div class="card" style="margin-top: 20px;">
            <div class="card-header">
                <h3>👤 Account Information</h3>
                <a href="edit_profile.php">Edit Profile →</a>
            </div>
            <div class="card-body">
                <table class="data-table">
                    <tr><th style="width: 150px;">Name</th><td><?php echo htmlspecialchars($user['name']); ?></td></tr>
                    <tr><th>Email</th><td><?php echo htmlspecialchars($user['email']); ?></td></tr>
                    <tr><th>Phone</th><td><?php echo htmlspecialchars($user['phone']); ?></td></tr>
                    <tr><th>Role</th><td><?php echo ucfirst($user['role']); ?></td></tr>
                    <!-- Display verification status with color coding -->
                    <tr><th>Account Status</th><td><?php echo $user['verified'] ? '<span style="color: green;">✅ Verified</span>' : '<span style="color: orange;">⏳ Pending Verification</span>'; ?></td></tr>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
<?php 
// Close database connection to free server resources
mysqli_close($conn); 
?>