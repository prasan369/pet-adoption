<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_user()) {
    header('Location: ../login.php');
    exit();
}

$user = get_user_by_id($_SESSION['user_id']);

// Get user's adoption requests
$result = $conn->query("
    SELECT ar.*, p.name as pet_name, p.pet_image 
    FROM adoption_requests ar 
    JOIN pets p ON ar.pet_id = p.id 
    WHERE ar.user_id = {$_SESSION['user_id']}
    ORDER BY ar.created_at DESC
");
$my_requests = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Adoption Requests</title>
    <link rel="stylesheet" href="../css/marketplace.css">
</head>
<body class="marketplace">

    <!-- Navigation -->
    <nav class="mp-nav">
        <div class="mp-nav-inner">
            <a href="dashboard.php" class="mp-logo">🐾 Pet<span>Adoption</span></a>
            <button class="mp-hamburger" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
            <ul class="mp-nav-links">
                <li><a href="dashboard.php">Browse Dogs</a></li>
                <li><a href="my_requests.php" class="active">My Requests</a></li>
                <li><a href="my_listings.php">My Listings</a></li>
                <li><a href="messages.php">Messages</a></li>
                <li><a href="post_listing.php" class="mp-nav-cta">+ Post a Dog</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="../logout.php" class="mp-nav-logout">Logout</a></li>
            </ul>
        </div>
    </nav>
    
    <main class="user-content">
        <div class="page-header">
            <h1>My Adoption Requests</h1>
        </div>
        
        <div class="requests-container">
            <?php if (empty($my_requests)): ?>
                <p class="no-data">You haven't submitted any adoption requests yet.</p>
            <?php else: ?>
                <table class="requests-table">
                    <thead>
                        <tr>
                            <th>Pet Image</th>
                            <th>Pet Name</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Submitted Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_requests as $req): ?>
                            <tr>
                                <td><img src="../<?php echo htmlspecialchars($req['pet_image']); ?>" alt="<?php echo htmlspecialchars($req['pet_name']); ?>" style="height: 50px; border-radius: 5px;"></td>
                                <td><?php echo htmlspecialchars($req['pet_name']); ?></td>
                                <td><?php echo htmlspecialchars(substr($req['message'], 0, 50)); ?>...</td>
                                <td><span class="status-badge status-<?php echo $req['status']; ?>"><?php echo ucfirst($req['status']); ?></span></td>
                                <td><?php echo date('Y-m-d', strtotime($req['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
