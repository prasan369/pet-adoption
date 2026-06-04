<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ../login.php');
    exit();
}

$admin = get_user_by_id($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Pet Adoption</title>
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
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="manage_pets.php">Manage Pets</a></li>
                <li><a href="manage_users.php">Manage Users</a></li>
                <li><a href="adoption_requests.php">Adoption Requests</a></li>
                <li><a href="messages.php">Messages</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="../logout.php" class="logout-link">Logout</a></li>
            </ul>
        </aside>
        
        <main class="admin-content">
            <div class="top-bar">
                <h1>Dashboard</h1>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($admin['full_name']); ?></span>
                </div>
            </div>
            
            <div class="dashboard-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <h3>Total Users</h3>
                        <p class="stat-number" id="totalUsers">0</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🐶</div>
                    <div class="stat-content">
                        <h3>Total Pets</h3>
                        <p class="stat-number" id="totalPets">0</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">✓</div>
                    <div class="stat-content">
                        <h3>Adoptions</h3>
                        <p class="stat-number" id="totalAdoptions">0</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📋</div>
                    <div class="stat-content">
                        <h3>Pending Requests</h3>
                        <p class="stat-number" id="pendingRequests">0</p>
                    </div>
                </div>
            </div>
            
            <div class="content-section">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                    <a href="manage_pets.php" class="btn btn-primary">Add New Pet</a>
                    <a href="manage_users.php" class="btn btn-secondary">View All Users</a>
                    <a href="adoption_requests.php" class="btn btn-secondary">View Requests</a>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../js/admin.js"></script>
</body>
</html>
