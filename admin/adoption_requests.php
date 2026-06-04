<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ../login.php');
    exit();
}

// Handle adoption request updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $request_id = (int)$_POST['request_id'];
    $status = trim($_POST['status']);
    
    $stmt = $conn->prepare("UPDATE adoption_requests SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $request_id);
    $stmt->execute();
    
    if ($status === 'approved') {
        // Get pet_id from adoption request
        $result = $conn->query("SELECT pet_id FROM adoption_requests WHERE id = $request_id");
        $row = $result->fetch_assoc();
        $pet_id = $row['pet_id'];
        
        // Mark pet as adopted
        $conn->query("UPDATE pets SET is_adopted = TRUE WHERE id = $pet_id");
    }
    
    $_SESSION['success'] = 'Request status updated!';
}

// Get all adoption requests
$result = $conn->query("
    SELECT ar.*, p.name as pet_name, u.username, u.email 
    FROM adoption_requests ar 
    JOIN pets p ON ar.pet_id = p.id 
    JOIN users u ON ar.user_id = u.id 
    ORDER BY ar.created_at DESC
");
$requests = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adoption Requests - Admin</title>
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
                <li><a href="adoption_requests.php" class="active">Adoption Requests</a></li>
                <li><a href="messages.php">Messages</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="../logout.php" class="logout-link">Logout</a></li>
            </ul>
        </aside>
        
        <main class="admin-content">
            <div class="top-bar">
                <h1>Adoption Requests</h1>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <div class="content-section">
                <table class="requests-table">
                    <thead>
                        <tr>
                            <th>Pet Name</th>
                            <th>Requester</th>
                            <th>Email</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($req['pet_name']); ?></td>
                                <td><?php echo htmlspecialchars($req['username']); ?></td>
                                <td><?php echo htmlspecialchars($req['email']); ?></td>
                                <td><?php echo htmlspecialchars(substr($req['message'], 0, 50)) . '...'; ?></td>
                                <td><span class="status-badge status-<?php echo $req['status']; ?>"><?php echo ucfirst($req['status']); ?></span></td>
                                <td><?php echo date('Y-m-d', strtotime($req['created_at'])); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                        <select name="status" onchange="this.form.submit()">
                                            <option value="pending" <?php echo $req['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="approved" <?php echo $req['status'] === 'approved' ? 'selected' : ''; ?>>Approve</option>
                                            <option value="rejected" <?php echo $req['status'] === 'rejected' ? 'selected' : ''; ?>>Reject</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
