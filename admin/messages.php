<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ../login.php');
    exit();
}

$admin = get_user_by_id($_SESSION['user_id']);

// Get all inquiries/messages
$result = $conn->query("
    SELECT i.*, p.name as pet_name, s.username as sender_name, r.username as receiver_name 
    FROM inquiries i 
    JOIN pets p ON i.pet_id = p.id 
    JOIN users s ON i.sender_id = s.id 
    JOIN users r ON i.receiver_id = r.id 
    ORDER BY i.created_at DESC
");
$messages = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body class="admin-page">
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>🐾 PetAdopt Admin</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage_pets.php">Manage Pets</a></li>
                <li><a href="manage_users.php">Manage Users</a></li>
                <li><a href="adoption_requests.php">Adoption Requests</a></li>
                <li><a href="messages.php" class="active">Messages</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="../logout.php" class="logout-link">Logout</a></li>
            </ul>
        </aside>
        
        <main class="admin-content">
            <div class="top-bar">
                <h1>Messages & Inquiries</h1>
            </div>
            
            <div class="content-section">
                <table class="messages-table">
                    <thead>
                        <tr>
                            <th>From</th>
                            <th>To</th>
                            <th>Pet</th>
                            <th>Message</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $msg): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($msg['sender_name']); ?></td>
                                <td><?php echo htmlspecialchars($msg['receiver_name']); ?></td>
                                <td><?php echo htmlspecialchars($msg['pet_name']); ?></td>
                                <td><?php echo htmlspecialchars(substr($msg['message'], 0, 100)) . '...'; ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($msg['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
