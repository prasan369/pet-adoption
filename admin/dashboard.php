<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ../login.php');
    exit();
}

$admin = get_user_by_id($_SESSION['user_id']);

$totalUsers     = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalPets      = $pdo->query("SELECT COUNT(*) FROM pets")->fetchColumn();
$totalAdoptions = $pdo->query("SELECT COUNT(*) FROM pets WHERE status = 'adopted'")->fetchColumn();
$totalReports   = $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
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
            <li><a href="reports.php">Reports</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="../logout.php" class="logout-link">Logout</a></li>
        </ul>
    </aside>

    <main class="admin-content">

        <div class="top-bar">
            <h1>Dashboard</h1>
            <div class="user-info">
                <span><?php echo htmlspecialchars($admin['full_name'] ?? $admin['username']); ?></span>
            </div>
        </div>

        <!-- STAT CARDS -->
        <div class="dashboard-grid">

            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-content">
                    <h3>Total Users</h3>
                    <p class="stat-number"><?php echo $totalUsers; ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🐶</div>
                <div class="stat-content">
                    <h3>Total Pets</h3>
                    <p class="stat-number"><?php echo $totalPets; ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <h3>Adoptions</h3>
                    <p class="stat-number"><?php echo $totalAdoptions; ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🚨</div>
                <div class="stat-content">
                    <h3>Pending Reports</h3>
                    <p class="stat-number" style="color:#e74c3c;"><?php echo $totalReports; ?></p>
                </div>
            </div>

        </div>

        <!-- QUICK ACTIONS -->
        <div class="content-section">
            <h2>Quick Actions</h2>
            <div class="action-buttons">
                <a href="manage_pets.php" class="btn btn-primary">Manage Pets</a>
                <a href="manage_users.php" class="btn btn-secondary">Manage Users</a>
                <a href="reports.php" class="btn btn-danger">
                    View Reports<?php if ($totalReports > 0): ?> (<?php echo $totalReports; ?>)<?php endif; ?>
                </a>
            </div>
        </div>

    </main>
</div>

<script src="../js/admin.js"></script>
</body>
</html>