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
    <link rel="stylesheet" href="../css/marketplace.css">
</head>
<body class="marketplace">

<nav class="mp-nav">
    <div class="mp-nav-inner">
        <a href="dashboard.php" class="mp-logo">
            🐾 Pet<span>Adoption</span>
            <span style="background:#e74c3c;color:#fff;font-size:0.65rem;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:6px;vertical-align:middle;letter-spacing:0.05em;">ADMIN</span>
        </a>
        <button class="mp-hamburger" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>
        <ul class="mp-nav-links">
            <li><a href="dashboard.php" class="active">Dashboard</a></li>
            <li><a href="manage_pets.php">Manage Pets</a></li>
            <li><a href="manage_users.php">Manage Users</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="../logout.php" class="mp-nav-logout">Logout</a></li>
        </ul>
    </div>
</nav>

<main class="user-content">

    <div class="page-header">
        <h1>Dashboard</h1>
        <p style="color:#8a8d91;margin-top:4px;">Welcome back, <?php echo htmlspecialchars($admin['full_name'] ?? $admin['username']); ?></p>
    </div>

    <!-- STAT CARDS -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:32px;">

        <div style="background:#242526;border:1px solid #3a3b3c;border-radius:12px;padding:28px;">
            <div style="font-size:0.8rem;text-transform:uppercase;letter-spacing:0.08em;color:#8a8d91;margin-bottom:8px;">Total Users</div>
            <div style="font-size:2.4rem;font-weight:700;color:#0866ff;"><?php echo $totalUsers; ?></div>
            <div style="font-size:1.5rem;margin-top:8px;">👥</div>
        </div>

        <div style="background:#242526;border:1px solid #3a3b3c;border-radius:12px;padding:28px;">
            <div style="font-size:0.8rem;text-transform:uppercase;letter-spacing:0.08em;color:#8a8d91;margin-bottom:8px;">Total Pets</div>
            <div style="font-size:2.4rem;font-weight:700;color:#0866ff;"><?php echo $totalPets; ?></div>
            <div style="font-size:1.5rem;margin-top:8px;">🐶</div>
        </div>

        <div style="background:#242526;border:1px solid #3a3b3c;border-radius:12px;padding:28px;">
            <div style="font-size:0.8rem;text-transform:uppercase;letter-spacing:0.08em;color:#8a8d91;margin-bottom:8px;">Adoptions</div>
            <div style="font-size:2.4rem;font-weight:700;color:#0866ff;"><?php echo $totalAdoptions; ?></div>
            <div style="font-size:1.5rem;margin-top:8px;">✅</div>
        </div>

        <div style="background:#242526;border:1px solid #3a3b3c;border-radius:12px;padding:28px;">
            <div style="font-size:0.8rem;text-transform:uppercase;letter-spacing:0.08em;color:#8a8d91;margin-bottom:8px;">Pending Reports</div>
            <div style="font-size:2.4rem;font-weight:700;color:#e74c3c;"><?php echo $totalReports; ?></div>
            <div style="font-size:1.5rem;margin-top:8px;">🚨</div>
        </div>

    </div>

    <!-- QUICK ACTIONS -->
    <div style="background:#242526;border:1px solid #3a3b3c;border-radius:12px;padding:28px;">
        <h2 style="margin-bottom:20px;color:#e4e6eb;font-size:1.2rem;">Quick Actions</h2>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <a href="manage_pets.php" style="background:#0866ff;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;">Manage Pets</a>
            <a href="manage_users.php" style="background:#3a3b3c;color:#e4e6eb;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;">Manage Users</a>
            <a href="reports.php" style="background:#e74c3c;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;">
                View Reports<?php if ($totalReports > 0): ?> (<?php echo $totalReports; ?>)<?php endif; ?>
            </a>
        </div>
    </div>

</main>

<script src="../js/marketplace.js"></script>

</body>
</html>