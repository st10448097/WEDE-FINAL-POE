<?php
// seller_messages.php - Sellers communicate with admin
session_start();
include 'DBConn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Send message to admin
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_message'])) {
    $message_text = mysqli_real_escape_string($conn, $_POST['message_text']);
    $admin_id = 1; // Assuming admin_id = 1
    
    $stmt = mysqli_prepare($conn, "INSERT INTO message (sender_id, receiver_id, message_text, time_sent) VALUES (?, ?, ?, NOW())");
    mysqli_stmt_bind_param($stmt, "iis", $user_id, $admin_id, $message_text);
    mysqli_stmt_execute($stmt);
    header("Location: seller_messages.php?msg=sent");
    exit();
}

// Get messages between user and admin
$admin_id = 1;
$stmt = mysqli_prepare($conn, "SELECT m.*, 
    CASE WHEN m.sender_id = ? THEN 'Me' ELSE 'Admin' END as sender_name
    FROM message m
    WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY m.time_sent ASC");
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
        .header-right a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .header-right a:hover {
            background: rgba(255,255,255,0.2);
        }
        .welcome-text {
            opacity: 0.9;
        }
        .container { max-width: 800px; margin: 40px auto; padding: 20px; }
        .chat-container { background: white; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); overflow: hidden; }
        .chat-header { padding: 20px; background: #764ba2; color: white; }
        .chat-messages { padding: 20px; min-height: 400px; max-height: 500px; overflow-y: auto; }
        .message { margin-bottom: 15px; display: flex; }
        .message.me { justify-content: flex-end; }
        .message-bubble { max-width: 70%; padding: 10px 15px; border-radius: 10px; }
        .message.admin .message-bubble { background: #e9ecef; color: #333; }
        .message.me .message-bubble { background: #764ba2; color: white; }
        .message-time { font-size: 10px; margin-top: 5px; color: #888; }
        .chat-input { padding: 20px; border-top: 1px solid #ddd; background: #f8f9fa; }
        .chat-input form { display: flex; gap: 10px; }
        .chat-input textarea { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px; resize: none; height: 60px; font-family: Arial, sans-serif; }
        .chat-input textarea:focus { outline: none; border-color: #764ba2; box-shadow: 0 0 5px rgba(118, 75, 162, 0.3); }
        .btn { padding: 10px 20px; background: #764ba2; color: white; border: none; border-radius: 5px; cursor: pointer; transition: background 0.3s; }
        .btn:hover { background: #5a3d82; }
        .message-success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        h2 { color: #764ba2; }
        .back-link { display: inline-block; margin-top: 20px; color: #764ba2; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
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
            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'sent'): ?>
            <div class="message-success">✅ Message sent successfully!</div>
        <?php endif; ?>
        
        <div class="chat-container">
            <div class="chat-header">
                <h2>💬 Communication with Admin</h2>
                <small>Discuss your clothing items, get updates, and resolve issues</small>
            </div>
            
            <div class="chat-messages" id="chatMessages">
                <?php if (mysqli_num_rows($messages) > 0): ?>
                    <?php while($msg = mysqli_fetch_assoc($messages)): ?>
                        <div class="message <?php echo ($msg['sender_name'] == 'Me') ? 'me' : 'admin'; ?>">
                            <div class="message-bubble">
                                <div><strong><?php echo $msg['sender_name']; ?>:</strong></div>
                                <div><?php echo nl2br(htmlspecialchars($msg['message_text'])); ?></div>
                                <div class="message-time"><?php echo date('M d, H:i', strtotime($msg['time_sent'])); ?></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; color: #888; padding: 50px;">
                        No messages yet. Start a conversation with admin!
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="chat-input">
                <form method="POST">
                    <textarea name="message_text" placeholder="Type your message here... (Ask about your clothing requests, delivery, or any issues)" required></textarea>
                    <button type="submit" name="send_message" class="btn">Send to Admin</button>
                </form>
            </div>
        </div>
        
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
    
    <script>
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    </script>
</body>
</html>