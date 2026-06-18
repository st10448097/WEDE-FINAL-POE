<?php
// seller_messages.php - Seller messaging interface for communicating with admin
// Allows sellers to send messages and view conversation history with administrators
session_start(); // Start session for user authentication
include 'DBConn.php'; // Include database connection

// Authentication check: Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id']; // Get current seller's ID from session

// Process sending a message to admin
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_message'])) {
    // Sanitize message text to prevent SQL injection
    $message_text = mysqli_real_escape_string($conn, $_POST['message_text']);
    $admin_id = 1; // Hardcoded admin ID - all seller messages go to admin #1
    
    // Insert new message with current timestamp using MySQL NOW() function
    $stmt = mysqli_prepare($conn, "INSERT INTO message (sender_id, receiver_id, message_text, time_sent) VALUES (?, ?, ?, NOW())");
    // Bind parameters: sender (current user), receiver (admin), message text
    mysqli_stmt_bind_param($stmt, "iis", $user_id, $admin_id, $message_text);
    mysqli_stmt_execute($stmt);
    // Redirect to prevent form resubmission with success indicator
    header("Location: seller_messages.php?msg=sent");
    exit();
}

// Retrieve all messages exchanged between this seller and admin
$admin_id = 1; // Target admin user ID for conversation
$stmt = mysqli_prepare($conn, "SELECT m.*, 
    -- Determine sender display name: 'Me' for seller's own messages, 'Admin' for admin messages
    CASE WHEN m.sender_id = ? THEN 'Me' ELSE 'Admin' END as sender_name
    FROM message m
    -- Get messages where seller sent to admin OR admin sent to seller
    WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY m.time_sent ASC"); // Chronological order for conversation flow
// Bind parameters: user ID for CASE statement, then both directions of conversation
mysqli_stmt_bind_param($stmt, "iiiii", $user_id, $user_id, $admin_id, $admin_id, $user_id);
mysqli_stmt_execute($stmt);
$messages = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages - Seller Panel</title>
    <style>
        /* Universal reset for consistent cross-browser rendering */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        /* Gradient header matching brand purple-blue theme */
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        /* Left side: logo and store name */
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px; /* Space between logo and title */
        }
        /* Fixed size container for clock SVG logo */
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
        /* Right side: welcome text and navigation */
        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        /* Navigation links with hover effect */
        .header-right a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .header-right a:hover {
            background: rgba(255,255,255,0.2); /* Semi-transparent white on hover */
        }
        /* Semi-transparent welcome text */
        .welcome-text {
            opacity: 0.9;
        }
        /* Centered content container with max-width */
        .container { max-width: 800px; margin: 40px auto; padding: 20px; }
        /* White chat container with rounded corners and shadow */
        .chat-container { background: white; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); overflow: hidden; }
        /* Purple chat header bar */
        .chat-header { padding: 20px; background: #764ba2; color: white; }
        /* Scrollable message area with minimum and maximum height */
        .chat-messages { padding: 20px; min-height: 400px; max-height: 500px; overflow-y: auto; }
        /* Individual message wrapper with flex for alignment */
        .message { margin-bottom: 15px; display: flex; }
        /* Seller's own messages aligned to the right */
        .message.me { justify-content: flex-end; }
        /* Message bubble with rounded corners, max-width prevents overly wide messages */
        .message-bubble { max-width: 70%; padding: 10px 15px; border-radius: 10px; }
        /* Admin message bubble: light gray, left-aligned */
        .message.admin .message-bubble { background: #e9ecef; color: #333; }
        /* Seller message bubble: purple, right-aligned */
        .message.me .message-bubble { background: #764ba2; color: white; }
        /* Timestamp displayed below message text */
        .message-time { font-size: 10px; margin-top: 5px; color: #888; }
        /* Message input area fixed at bottom */
        .chat-input { padding: 20px; border-top: 1px solid #ddd; background: #f8f9fa; }
        /* Flex row for textarea and send button */
        .chat-input form { display: flex; gap: 10px; }
        /* Textarea input with fixed height */
        .chat-input textarea { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px; resize: none; height: 60px; font-family: Arial, sans-serif; }
        /* Purple glow on textarea focus */
        .chat-input textarea:focus { outline: none; border-color: #764ba2; box-shadow: 0 0 5px rgba(118, 75, 162, 0.3); }
        /* Send button styling */
        .btn { padding: 10px 20px; background: #764ba2; color: white; border: none; border-radius: 5px; cursor: pointer; transition: background 0.3s; }
        .btn:hover { background: #5a3d82; } /* Darker purple on hover */
        /* Green success notification banner */
        .message-success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        h2 { color: #764ba2; }
        /* Back to dashboard navigation link */
        .back-link { display: inline-block; margin-top: 20px; color: #764ba2; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="header-logo-icon">
                <!-- Simple clock SVG icon representing "Past Times" vintage brand -->
                <svg width="45" height="45" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
                    <!-- Outer decorative circle with transparency -->
                    <circle cx="30" cy="30" r="28" fill="#ffffff" stroke="#ffffff" stroke-width="2" opacity="0.3"/>
                    <!-- Inner transparent fill -->
                    <circle cx="30" cy="30" r="24" fill="rgba(255,255,255,0.1)"/>
                    <!-- Clock numerals at cardinal positions -->
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
        <div class="header-right">
            <!-- Display logged-in seller's name -->
            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <!-- Navigation links -->
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <!-- Display success message when a message is sent -->
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'sent'): ?>
            <div class="message-success">✅ Message sent successfully!</div>
        <?php endif; ?>
        
        <div class="chat-container">
            <!-- Chat header with description -->
            <div class="chat-header">
                <h2>💬 Communication with Admin</h2>
                <small>Discuss your clothing items, get updates, and resolve issues</small>
            </div>
            
            <!-- Scrollable message history container -->
            <div class="chat-messages" id="chatMessages">
                <!-- Check if messages exist in the conversation -->
                <?php if (mysqli_num_rows($messages) > 0): ?>
                    <!-- Loop through all messages in chronological order -->
                    <?php while($msg = mysqli_fetch_assoc($messages)): ?>
                        <!-- Apply 'me' class for seller's messages, 'admin' class for admin messages -->
                        <div class="message <?php echo ($msg['sender_name'] == 'Me') ? 'me' : 'admin'; ?>">
                            <div class="message-bubble">
                                <!-- Display sender name in bold -->
                                <div><strong><?php echo $msg['sender_name']; ?>:</strong></div>
                                <!-- Display message text with line break conversion for formatting -->
                                <div><?php echo nl2br(htmlspecialchars($msg['message_text'])); ?></div>
                                <!-- Format and display message timestamp -->
                                <div class="message-time"><?php echo date('M d, H:i', strtotime($msg['time_sent'])); ?></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <!-- Placeholder when no messages exist yet -->
                    <div style="text-align: center; color: #888; padding: 50px;">
                        No messages yet. Start a conversation with admin!
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Message input area at bottom of chat -->
            <div class="chat-input">
                <form method="POST">
                    <!-- Textarea for composing message with helpful placeholder -->
                    <textarea name="message_text" placeholder="Type your message here... (Ask about your clothing requests, delivery, or any issues)" required></textarea>
                    <!-- Send button submits the form -->
                    <button type="submit" name="send_message" class="btn">Send to Admin</button>
                </form>
            </div>
        </div>
        
        <!-- Navigation link to return to seller dashboard -->
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
    
    <script>
        // Auto-scroll chat window to the bottom to show the most recent messages
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    </script>
</body>
</html>