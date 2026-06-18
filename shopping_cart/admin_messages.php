<?php
// admin_messages.php - Admin messaging interface for communicating with sellers and buyers
// Enables admin to view conversations and reply to users directly
session_start(); // Start session for admin authentication
include 'DBConn.php'; // Include database connection

// Security check: Redirect to login if admin is not authenticated
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Process sending a reply message to a user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reply_message'])) {
    // Convert receiver ID to integer for security
    $receiver_id = intval($_POST['receiver_id']);
    // Sanitize message text to prevent SQL injection
    $message_text = mysqli_real_escape_string($conn, $_POST['message_text']);
    // Get current admin ID from session as sender
    $admin_id = $_SESSION['admin_id'];
    
    // Insert new message with current timestamp using MySQL NOW() function
    $stmt = mysqli_prepare($conn, "INSERT INTO message (sender_id, receiver_id, message_text, time_sent) VALUES (?, ?, ?, NOW())");
    // Bind parameters: admin_id (integer), receiver_id (integer), message_text (string)
    mysqli_stmt_bind_param($stmt, "iis", $admin_id, $receiver_id, $message_text);
    mysqli_stmt_execute($stmt);
    // Redirect to prevent form resubmission, with success indicator
    header("Location: admin_messages.php?msg=sent");
    exit();
}

// Fetch all users who are sellers or buyers (not admins)
// Ordered alphabetically by name for easy browsing
$users = mysqli_query($conn, "SELECT user_id, name, email, role FROM user WHERE role IN ('seller', 'buyer') ORDER BY name");

// Get all existing conversations grouped by user
// Uses subqueries to retrieve the most recent message and timestamp for each user
$conversations = mysqli_query($conn, "SELECT 
    u.user_id, u.name, u.email, u.role,
    -- Subquery: Get the last message text between admin and this user
    (SELECT message_text FROM message WHERE (sender_id = u.user_id OR receiver_id = u.user_id) AND (sender_id = 1 OR receiver_id = 1) ORDER BY time_sent DESC LIMIT 1) as last_message,
    -- Subquery: Get the timestamp of the last message
    (SELECT time_sent FROM message WHERE (sender_id = u.user_id OR receiver_id = u.user_id) AND (sender_id = 1 OR receiver_id = 1) ORDER BY time_sent DESC LIMIT 1) as last_time
    FROM user u 
    -- Only include users who have at least one message in the system
    WHERE u.user_id IN (SELECT DISTINCT sender_id FROM message UNION SELECT DISTINCT receiver_id FROM message)
    ORDER BY last_time DESC"); // Show most recent conversations first

// Initialize variables for selected user and their messages
$selected_user = null;
$messages = null;

// Load conversation when admin clicks on a specific user
if (isset($_GET['chat_user'])) {
    $user_id = intval($_GET['chat_user']); // Cast to integer for security
    
    // Fetch the selected user's details
    $stmt = mysqli_prepare($conn, "SELECT * FROM user WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $selected_user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    
    // Retrieve all messages exchanged between admin and the selected user
    // Hardcoded admin_id = 1; should be replaced with actual admin ID in production
    $admin_id = 1; // Adjust this value based on your admin user ID
    $msg_stmt = mysqli_prepare($conn, "SELECT m.*, 
        -- Determine sender display name: 'Admin' or user's actual name
        CASE WHEN m.sender_id = ? THEN 'Admin' ELSE u.name END as sender_name
        FROM message m
        JOIN user u ON m.sender_id = u.user_id
        -- Get messages where admin sent to user OR user sent to admin
        WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.time_sent ASC"); // Chronological order for conversation flow
    // Bind admin_id for CASE statement and both directions of conversation
    mysqli_stmt_bind_param($msg_stmt, "iiiii", $admin_id, $user_id, $admin_id, $admin_id, $user_id);
    mysqli_stmt_execute($msg_stmt);
    $messages = mysqli_stmt_get_result($msg_stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages - Admin Panel</title>
    <style>
        /* Universal box-sizing reset for consistent layout calculations */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        /* Gradient header matching brand purple-blue theme */
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        /* Left side: logo and title grouped together */
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px; /* Spacing between logo and store name */
        }
        /* Fixed size container for the clock SVG logo */
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
        /* Right side: welcome text and navigation buttons */
        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        /* Semi-transparent text for subtle visual hierarchy */
        .welcome-text {
            opacity: 0.9;
        }
        /* Main content container with max-width for readability */
        .container { max-width: 1200px; margin: 40px auto; padding: 20px; }
        /* Two-column layout: users list sidebar and chat area */
        .message-container { display: flex; gap: 20px; min-height: 500px; }
        /* Left sidebar: scrollable list of users */
        .users-list { width: 30%; background: white; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); overflow: hidden; }
        /* Right panel: chat interface with flex column layout */
        .chat-area { width: 70%; background: white; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); display: flex; flex-direction: column; }
        /* Individual user item in sidebar - clickable to open conversation */
        .user-item { padding: 15px; border-bottom: 1px solid #ddd; cursor: pointer; transition: background 0.3s; }
        .user-item:hover { background: #f0f0f0; } /* Highlight on hover */
        /* Active state for currently selected user */
        .user-item.active { background: #764ba2; color: white; }
        .user-name { font-weight: bold; }
        .user-role { font-size: 12px; color: #666; } /* Smaller, subdued role text */
        .user-item.active .user-role { color: #ddd; } /* Lighter role text when active */
        /* Truncated last message preview with ellipsis */
        .last-message { font-size: 12px; color: #888; margin-top: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        /* Chat header showing selected user's name and role */
        .chat-header { padding: 20px; border-bottom: 1px solid #ddd; background: #f8f9fa; }
        /* Scrollable message display area */
        .chat-messages { flex: 1; padding: 20px; overflow-y: auto; min-height: 400px; max-height: 500px; }
        /* Individual message wrapper - flex for alignment */
        .message { margin-bottom: 15px; display: flex; }
        /* Admin messages aligned to the right */
        .message.admin { justify-content: flex-end; }
        /* Message bubble with rounded corners */
        .message-bubble { max-width: 70%; padding: 10px 15px; border-radius: 10px; }
        /* User message bubble: light gray background, left-aligned */
        .message.user .message-bubble { background: #e9ecef; color: #333; }
        /* Admin message bubble: purple background, right-aligned */
        .message.admin .message-bubble { background: #764ba2; color: white; }
        /* Timestamp displayed below message text */
        .message-time { font-size: 10px; margin-top: 5px; color: #888; }
        /* Input area fixed at bottom of chat */
        .chat-input { padding: 20px; border-top: 1px solid #ddd; background: #f8f9fa; }
        /* Flex row layout for textarea and send button */
        .chat-input form { display: flex; gap: 10px; }
        /* Message input field with fixed height */
        .chat-input textarea { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px; resize: none; height: 60px; font-family: Arial, sans-serif; }
        /* Purple glow on focus for textarea */
        .chat-input textarea:focus { outline: none; border-color: #764ba2; box-shadow: 0 0 5px rgba(118, 75, 162, 0.3); }
        /* Green success notification banner */
        .success-message { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        h2 { color: #764ba2; margin-bottom: 20px; }
        /* Centered placeholder text when no messages exist */
        .no-messages { text-align: center; color: #888; padding: 50px; }
        
        /* Improved Button Styles */
        /* Send button with gradient purple background and pill shape */
        .btn-send {
            background: linear-gradient(135deg, #764ba2, #5a3d8a);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px; /* Pill-shaped button */
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease; /* Smooth hover animation */
            box-shadow: 0 4px 15px rgba(118, 75, 162, 0.3); /* Purple shadow */
            letter-spacing: 0.5px;
            min-width: 120px;
            height: 60px; /* Match textarea height */
        }
        /* Lift effect and enhanced shadow on hover */
        .btn-send:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(118, 75, 162, 0.4);
            background: linear-gradient(135deg, #8a5fc7, #6a4a9e);
        }
        /* Press effect - button returns to original position */
        .btn-send:active {
            transform: translateY(0px);
            box-shadow: 0 2px 10px rgba(118, 75, 162, 0.3);
        }
        
        /* Top navigation buttons */
        .nav-buttons {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        /* Pill-shaped navigation buttons with semi-transparent background */
        .nav-btn {
            color: white;
            text-decoration: none;
            padding: 8px 18px;
            border-radius: 20px; /* Rounded pill shape */
            background: rgba(255, 255, 255, 0.15); /* Subtle white overlay */
            transition: all 0.3s ease;
            font-size: 14px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        /* Brighter background and lift effect on hover */
        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        /* Logout button with red tint */
        .nav-btn.logout {
            background: rgba(220, 53, 69, 0.3); /* Red tint */
            border-color: rgba(220, 53, 69, 0.4);
        }
        .nav-btn.logout:hover {
            background: rgba(220, 53, 69, 0.5); /* Darker red on hover */
        }
        
        /* Responsive layout for mobile devices */
        @media (max-width: 768px) {
            /* Stack header vertically on small screens */
            .header { flex-direction: column; gap: 15px; text-align: center; }
            /* Stack users list above chat area */
            .message-container { flex-direction: column; }
            .users-list { width: 100%; }
            .chat-area { width: 100%; }
            /* Wrap navigation buttons */
            .header-right { flex-wrap: wrap; justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="header-logo-icon">
                <!-- Simple clock SVG icon representing "Past Times" vintage brand -->
                <svg width="45" height="45" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
                    <!-- Outer decorative ring -->
                    <circle cx="30" cy="30" r="28" fill="#ffffff" stroke="#ffffff" stroke-width="2" opacity="0.3"/>
                    <!-- Inner transparent fill -->
                    <circle cx="30" cy="30" r="24" fill="rgba(255,255,255,0.1)"/>
                    <!-- Clock numerals at 12, 3, 6, and 9 o'clock positions -->
                    <text x="30" y="10" text-anchor="middle" font-size="8" fill="white" font-weight="bold">12</text>
                    <text x="48" y="33" text-anchor="middle" font-size="8" fill="white" font-weight="bold">3</text>
                    <text x="30" y="56" text-anchor="middle" font-size="8" fill="white" font-weight="bold">6</text>
                    <text x="12" y="33" text-anchor="middle" font-size="8" fill="white" font-weight="bold">9</text>
                    <!-- Hour tick marks -->
                    <line x1="30" y1="8" x2="30" y2="13" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="52" y1="30" x2="47" y2="30" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="30" y1="52" x2="30" y2="47" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="8" y1="30" x2="13" y2="30" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <!-- Hour and minute hands at classic 10:10 position -->
                    <line x1="30" y1="30" x2="30" y2="12" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                    <line x1="30" y1="30" x2="42" y2="20" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    <!-- Center pivot point -->
                    <circle cx="30" cy="30" r="2.5" fill="white"/>
                </svg>
            </div>
            <h1>Past Times</h1>
        </div>
        <div class="header-right">
            <!-- Display logged-in admin username -->
            <span class="welcome-text">Welcome, <?php echo $_SESSION['admin_name']; ?></span>
            <div class="nav-buttons">
                <!-- Quick navigation buttons to other admin sections -->
                <a href="admin_dashboard.php" class="nav-btn">📊 Dashboard</a>
                <a href="admin_clothing.php" class="nav-btn">👕 Clothing</a>
                <a href="admin_requests.php" class="nav-btn">📋 Requests</a>
                <!-- Logout button with distinct red styling -->
                <a href="logout.php" class="nav-btn logout">🚪 Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Display success message when a reply is sent -->
        <?php if (isset($_GET['msg'])): ?>
            <div class="success-message">✅ Message sent successfully!</div>
        <?php endif; ?>
        
        <div class="message-container">
            <!-- Users List - Left sidebar showing all users -->
            <div class="users-list">
                <!-- Sidebar header -->
                <div style="padding: 15px; background: #764ba2; color: white; font-weight: bold;">
                    💬 Conversations
                </div>
                <!-- Loop through all users and create clickable list items -->
                <?php while($user = mysqli_fetch_assoc($users)): ?>
                    <!-- Each user item is a link that loads their conversation -->
                    <a href="?chat_user=<?php echo $user['user_id']; ?>" style="text-decoration: none; color: inherit;">
                        <div class="user-item <?php echo (isset($_GET['chat_user']) && $_GET['chat_user'] == $user['user_id']) ? 'active' : ''; ?>">
                            <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                            <div class="user-role"><?php echo ucfirst($user['role']); ?> | <?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
            
            <!-- Chat Area - Right panel for conversation display -->
            <div class="chat-area">
                <!-- Only show chat interface if a user is selected -->
                <?php if ($selected_user): ?>
                    <!-- Chat header with selected user information -->
                    <div class="chat-header">
                        <h3>Chat with <?php echo htmlspecialchars($selected_user['name']); ?></h3>
                        <small><?php echo ucfirst($selected_user['role']); ?> | <?php echo htmlspecialchars($selected_user['email']); ?></small>
                    </div>
                    
                    <!-- Scrollable message history container -->
                    <div class="chat-messages" id="chatMessages">
                        <!-- Check if messages exist for this conversation -->
                        <?php if ($messages && mysqli_num_rows($messages) > 0): ?>
                            <!-- Loop through all messages in chronological order -->
                            <?php while($msg = mysqli_fetch_assoc($messages)): ?>
                                <!-- Apply 'admin' or 'user' class for alignment styling -->
                                <div class="message <?php echo ($msg['sender_name'] == 'Admin') ? 'admin' : 'user'; ?>">
                                    <div class="message-bubble">
                                        <!-- Display message text with line break conversion -->
                                        <div><?php echo nl2br(htmlspecialchars($msg['message_text'])); ?></div>
                                        <!-- Format and display message timestamp -->
                                        <div class="message-time"><?php echo date('M d, H:i', strtotime($msg['time_sent'])); ?></div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <!-- Placeholder when conversation has no messages yet -->
                            <div class="no-messages">No messages yet. Start a conversation!</div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Message input area fixed at bottom -->
                    <div class="chat-input">
                        <form method="POST">
                            <!-- Hidden field to identify the recipient user -->
                            <input type="hidden" name="receiver_id" value="<?php echo $selected_user['user_id']; ?>">
                            <!-- Textarea for composing message with placeholder instructions -->
                            <textarea name="message_text" placeholder="Type your message here... (Discuss item conditions, delivery, etc.)" required></textarea>
                            <!-- Send button with icon -->
                            <button type="submit" name="reply_message" class="btn-send">📤 Send</button>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Placeholder shown when no user is selected -->
                    <div class="no-messages" style="padding: 100px;">
                        <h3>💬 Select a user to start messaging</h3>
                        <p style="margin-top: 10px;">Communicate with sellers about their items or with buyers about orders</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-scroll chat to the bottom to show most recent messages
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    </script>
</body>
</html>