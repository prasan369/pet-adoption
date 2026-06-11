<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Session check, must be admin
if (!is_logged_in() || !is_admin()) {
    header('Location: ../login.php');
    exit();
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    if (isset($_POST['action']) && isset($_POST['report_id'])) {
        $action = $_POST['action'];
        $report_id = (int)$_POST['report_id'];

        if ($action === 'remove_pet' && isset($_POST['pet_id'])) {
            $pet_id = (int)$_POST['pet_id'];
            $stmt = $pdo->prepare("UPDATE pets SET status='removed' WHERE id=?");
            $stmt->execute([$pet_id]);
        } elseif ($action === 'dismiss') {
            $stmt = $pdo->prepare("DELETE FROM reports WHERE id=?");
            $stmt->execute([$report_id]);
        }
        
        header('Location: reports.php');
        exit();
    }
}

// Fetch all reports using PDO
$stmt = $pdo->query("
    SELECT r.id, r.reason, r.created_at,
           reporter.username AS reporter_name,
           owner.username AS owner_name,
           p.name AS pet_name, p.id AS pet_id,
           p.status AS pet_status
    FROM reports r
    JOIN users reporter ON r.reporter_id = reporter.id
    JOIN pets p ON r.pet_id = p.id
    JOIN users owner ON p.owner_id = owner.id
    ORDER BY r.created_at DESC
");
$reports = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .btn-grey { background: #6c757d; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; }
        .btn-grey:hover { background: #5a6268; }
        .btn-red { background: #dc3545; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; }
        .btn-red:hover { background: #c82333; }
        .form-inline-action { display: inline-block; margin: 0; }
    </style>
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
                <li><a href="reports.php" class="active">Reports</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="../logout.php" class="logout-link">Logout</a></li>
            </ul>
        </aside>
        
        <main class="admin-content">
            <div class="top-bar">
                <h1>Reports</h1>
            </div>
            
            <div class="content-section">
                <h2>All Reports</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Pet Name</th>
                            <th>Reported By</th>
                            <th>Owner</th>
                            <th>Reason</th>
                            <th>Date Reported</th>
                            <th>Pet Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reports)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">No reports found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reports as $report): ?>
                                <tr>
                                    <td>
                                        <a href="pet_preview.php?id=<?php echo $report['pet_id']; ?>&report_id=<?php echo $report['id']; ?>">
                                            <?php echo htmlspecialchars($report['pet_name']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($report['reporter_name']); ?></td>
                                    <td><?php echo htmlspecialchars($report['owner_name']); ?></td>
                                    <td><?php echo htmlspecialchars($report['reason']); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($report['created_at'])); ?></td>
                                    <td>
                                        <?php if ($report['pet_status'] === 'active'): ?>
                                            <span class="status-badge status-approved">Active</span>
                                        <?php elseif ($report['pet_status'] === 'removed'): ?>
                                            <span class="status-badge status-rejected">Removed</span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending"><?php echo htmlspecialchars(ucfirst($report['pet_status'])); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($report['pet_status'] !== 'removed'): ?>
                                            <form action="reports.php" method="POST" class="form-inline-action">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="remove_pet">
                                                <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                                <input type="hidden" name="pet_id" value="<?php echo $report['pet_id']; ?>">
                                                <button type="submit" class="btn-red" onclick="return confirm('Are you sure you want to remove this pet?');">Remove Pet</button>
                                            </form>
                                        <?php endif; ?>
                                        <form action="reports.php" method="POST" class="form-inline-action">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="dismiss">
                                            <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                            <button type="submit" class="btn-grey" onclick="return confirm('Are you sure you want to dismiss this report?');">Dismiss Report</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
