<?php
// admin_messages.php - Admin communicates with sellers and buyers
session_start();
include 'DBConn.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Send reply to user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reply_message'])) {
    $receiver_id = intval($_POST['receiver_id']);
    $message_text = mysqli_real_escape_string($conn, $_POST['message_text']);
    $admin_id = $_SESSION['admin_id'];
    
    $stmt = mysqli_prepare($conn, "INSERT INTO message (sender_id, receiver_id, message_text, time_sent) VALUES (?, ?, ?, NOW())");
    mysqli_stmt_bind_param($stmt, "iis", $admin_id, $receiver_id, $message_text);
    mysqli_stmt_execute($stmt);
    header("Location: admin_messages.php?msg=sent");
    exit();
}

// Get all users (sellers and buyers)
$users = mysqli_query($conn, "SELECT user_id, name, email, role FROM user WHERE role IN ('seller', 'buyer') ORDER BY name");

// Get all conversations (group by user)
$conversations = mysqli_query($conn, "SELECT 
    u.user_id, u.name, u.email, u.role,
    (SELECT message_text FROM message WHERE (sender_id = u.user_id OR receiver_id = u.user_id) AND (sender_id = 1 OR receiver_id = 1) ORDER BY time_sent DESC LIMIT 1) as last_message,
    (SELECT time_sent FROM message WHERE (sender_id = u.user_id OR receiver_id = u.user_id) AND (sender_id = 1 OR receiver_id = 1) ORDER BY time_sent DESC LIMIT 1) as last_time
    FROM user u 
    WHERE u.user_id IN (SELECT DISTINCT sender_id FROM message UNION SELECT DISTINCT receiver_id FROM message)
    ORDER BY last_time DESC");

// Get messages for specific user
$selected_user = null;
$messages = null;
if (isset($_GET['chat_user'])) {
    $user_id = intval($_GET['chat_user']);
    $stmt = mysqli_prepare($conn, "SELECT * FROM user WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $selected_user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    
    // Get messages between admin and this user
    $admin_id = 1; // Assuming admin_id = 1, adjust as needed
    $msg_stmt = mysqli_prepare($conn, "SELECT m.*, 
        CASE WHEN m.sender_id = ? THEN 'Admin' ELSE u.name END as sender_name
        FROM message m
        JOIN user u ON m.sender_id = u.user_id
        WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.time_sent ASC");
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
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
        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .welcome-text {
            opacity: 0.9;
        }
        .container { max-width: 1200px; margin: 40px auto; padding: 20px; }
        .message-container { display: flex; gap: 20px; min-height: 500px; }
        .users-list { width: 30%; background: white; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); overflow: hidden; }
        .chat-area { width: 70%; background: white; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); display: flex; flex-direction: column; }
        .user-item { padding: 15px; border-bottom: 1px solid #ddd; cursor: pointer; transition: background 0.3s; }
        .user-item:hover { background: #f0f0f0; }
        .user-item.active { background: #764ba2; color: white; }
        .user-name { font-weight: bold; }
        .user-role { font-size: 12px; color: #666; }
        .user-item.active .user-role { color: #ddd; }
        .last-message { font-size: 12px; color: #888; margin-top: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .chat-header { padding: 20px; border-bottom: 1px solid #ddd; background: #f8f9fa; }
        .chat-messages { flex: 1; padding: 20px; overflow-y: auto; min-height: 400px; max-height: 500px; }
        .message { margin-bottom: 15px; display: flex; }
        .message.admin { justify-content: flex-end; }
        .message-bubble { max-width: 70%; padding: 10px 15px; border-radius: 10px; }
        .message.user .message-bubble { background: #e9ecef; color: #333; }
        .message.admin .message-bubble { background: #764ba2; color: white; }
        .message-time { font-size: 10px; margin-top: 5px; color: #888; }
        .chat-input { padding: 20px; border-top: 1px solid #ddd; background: #f8f9fa; }
        .chat-input form { display: flex; gap: 10px; }
        .chat-input textarea { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px; resize: none; height: 60px; font-family: Arial, sans-serif; }
        .chat-input textarea:focus { outline: none; border-color: #764ba2; box-shadow: 0 0 5px rgba(118, 75, 162, 0.3); }
        .success-message { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        h2 { color: #764ba2; margin-bottom: 20px; }
        .no-messages { text-align: center; color: #888; padding: 50px; }
        
        /* Improved Button Styles */
        .btn-send {
            background: linear-gradient(135deg, #764ba2, #5a3d8a);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(118, 75, 162, 0.3);
            letter-spacing: 0.5px;
            min-width: 120px;
            height: 60px;
        }
        .btn-send:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(118, 75, 162, 0.4);
            background: linear-gradient(135deg, #8a5fc7, #6a4a9e);
        }
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
        .nav-btn {
            color: white;
            text-decoration: none;
            padding: 8px 18px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.15);
            transition: all 0.3s ease;
            font-size: 14px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .nav-btn.logout {
            background: rgba(220, 53, 69, 0.3);
            border-color: rgba(220, 53, 69, 0.4);
        }
        .nav-btn.logout:hover {
            background: rgba(220, 53, 69, 0.5);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header { flex-direction: column; gap: 15px; text-align: center; }
            .message-container { flex-direction: column; }
            .users-list { width: 100%; }
            .chat-area { width: 100%; }
            .header-right { flex-wrap: wrap; justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="header-logo-icon">
                <!-- Simple clock SVG icon -->
                <svg width="45" height="45" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="30" cy="30" r="28" fill="#ffffff" stroke="#ffffff" stroke-width="2" opacity="0.3"/>
                    <circle cx="30" cy="30" r="24" fill="rgba(255,255,255,0.1)"/>
                    <!-- Clock numbers -->
                    <text x="30" y="10" text-anchor="middle" font-size="8" fill="white" font-weight="bold">12</text>
                    <text x="48" y="33" text-anchor="middle" font-size="8" fill="white" font-weight="bold">3</text>
                    <text x="30" y="56" text-anchor="middle" font-size="8" fill="white" font-weight="bold">6</text>
                    <text x="12" y="33" text-anchor="middle" font-size="8" fill="white" font-weight="bold">9</text>
                    <!-- Hour marks -->
                    <line x1="30" y1="8" x2="30" y2="13" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="52" y1="30" x2="47" y2="30" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="30" y1="52" x2="30" y2="47" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <line x1="8" y1="30" x2="13" y2="30" stroke="white" stroke-width="1.5" opacity="0.8"/>
                    <!-- Clock hands pointing to 10:10 -->
                    <line x1="30" y1="30" x2="30" y2="12" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                    <line x1="30" y1="30" x2="42" y2="20" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    <!-- Center dot -->
                    <circle cx="30" cy="30" r="2.5" fill="white"/>
                </svg>
            </div>
            <h1>Past Times</h1>
        </div>
        <div class="header-right">
            <span class="welcome-text">Welcome, <?php echo $_SESSION['admin_name']; ?></span>
            <div class="nav-buttons">
                <a href="admin_dashboard.php" class="nav-btn">📊 Dashboard</a>
                <a href="admin_clothing.php" class="nav-btn">👕 Clothing</a>
                <a href="admin_requests.php" class="nav-btn">📋 Requests</a>
                <a href="logout.php" class="nav-btn logout">🚪 Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php if (isset($_GET['msg'])): ?>
            <div class="success-message">✅ Message sent successfully!</div>
        <?php endif; ?>
        
        <div class="message-container">
            <!-- Users List -->
            <div class="users-list">
                <div style="padding: 15px; background: #764ba2; color: white; font-weight: bold;">
                    💬 Conversations
                </div>
                <?php while($user = mysqli_fetch_assoc($users)): ?>
                    <a href="?chat_user=<?php echo $user['user_id']; ?>" style="text-decoration: none; color: inherit;">
                        <div class="user-item <?php echo (isset($_GET['chat_user']) && $_GET['chat_user'] == $user['user_id']) ? 'active' : ''; ?>">
                            <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                            <div class="user-role"><?php echo ucfirst($user['role']); ?> | <?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
            
            <!-- Chat Area -->
            <div class="chat-area">
                <?php if ($selected_user): ?>
                    <div class="chat-header">
                        <h3>Chat with <?php echo htmlspecialchars($selected_user['name']); ?></h3>
                        <small><?php echo ucfirst($selected_user['role']); ?> | <?php echo htmlspecialchars($selected_user['email']); ?></small>
                    </div>
                    
                    <div class="chat-messages" id="chatMessages">
                        <?php if ($messages && mysqli_num_rows($messages) > 0): ?>
                            <?php while($msg = mysqli_fetch_assoc($messages)): ?>
                                <div class="message <?php echo ($msg['sender_name'] == 'Admin') ? 'admin' : 'user'; ?>">
                                    <div class="message-bubble">
                                        <div><?php echo nl2br(htmlspecialchars($msg['message_text'])); ?></div>
                                        <div class="message-time"><?php echo date('M d, H:i', strtotime($msg['time_sent'])); ?></div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="no-messages">No messages yet. Start a conversation!</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="chat-input">
                        <form method="POST">
                            <input type="hidden" name="receiver_id" value="<?php echo $selected_user['user_id']; ?>">
                            <textarea name="message_text" placeholder="Type your message here... (Discuss item conditions, delivery, etc.)" required></textarea>
                            <button type="submit" name="reply_message" class="btn-send">📤 Send</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="no-messages" style="padding: 100px;">
                        <h3>💬 Select a user to start messaging</h3>
                        <p style="margin-top: 10px;">Communicate with sellers about their items or with buyers about orders</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-scroll to bottom of chat
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    </script>
</body>
</html>