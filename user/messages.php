<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_user()) {
    header('Location: ../login.php');
    exit();
}

$user = get_user_by_id($_SESSION['user_id']);

// Get user's messages
$result = $conn->query("
    SELECT i.*, p.name as pet_name, u.username as sender_name 
    FROM inquiries i 
    JOIN pets p ON i.pet_id = p.id 
    JOIN users u ON i.sender_id = u.id 
    WHERE i.receiver_id = {$_SESSION['user_id']}
    ORDER BY i.created_at DESC
");
$messages = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
    <link rel="stylesheet" href="../css/marketplace.css">
</head>
<body class="marketplace">

    <!-- Navigation -->
    <nav class="mp-nav">
        <div class="mp-nav-inner">
            <a href="dashboard.php" class="mp-logo">🐾 Paw<span>Market</span></a>
            <button class="mp-hamburger" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
            <ul class="mp-nav-links">
                <li><a href="dashboard.php">Browse Dogs</a></li>
                <li><a href="my_requests.php">My Requests</a></li>
                <li><a href="my_listings.php">My Listings</a></li>
                <li><a href="messages.php" class="active">Messages</a></li>
                <li><a href="post_listing.php" class="mp-nav-cta">+ Post a Dog</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="../logout.php" class="mp-nav-logout">Logout</a></li>
            </ul>
        </div>
    </nav>
    
    <main class="user-content">
        <div class="page-header">
            <h1>Messages</h1>
        </div>
        
        <div class="messages-container">
            <?php if (empty($messages)): ?>
                <p class="no-data">You have no messages yet.</p>
            <?php else: ?>
                <table class="messages-table">
                    <thead>
                        <tr>
                            <th>From</th>
                            <th>Pet</th>
                            <th>Message</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $msg): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($msg['sender_name']); ?></td>
                                <td><?php echo htmlspecialchars($msg['pet_name']); ?></td>
                                <td><?php echo htmlspecialchars(substr($msg['message'], 0, 100)); ?>...</td>
                                <td><?php echo date('Y-m-d H:i', strtotime($msg['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
