<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Session check, must be admin
if (!is_logged_in() || !is_admin()) {
    header('Location: ../login.php');
    exit();
}

$report_id = isset($_GET['report_id']) ? (int)$_GET['report_id'] : 0;
$pet_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$report_id || !$pet_id) {
    header('Location: reports.php');
    exit();
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'remove_pet') {
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

// Fetch the specific report
$stmt_report = $pdo->prepare("
    SELECT r.*, u.username AS reporter_name
    FROM reports r
    JOIN users u ON r.reporter_id = u.id
    WHERE r.id = ?
");
$stmt_report->execute([$report_id]);
$report = $stmt_report->fetch();

if (!$report) {
    header('Location: reports.php');
    exit();
}

// Fetch pet by ID
$stmt_pet = $pdo->prepare("
    SELECT p.*, u.username AS owner_name, u.email AS owner_email, u.phone AS owner_phone
    FROM pets p
    JOIN users u ON p.owner_id = u.id
    WHERE p.id = ?
");
$stmt_pet->execute([$pet_id]);
$pet = $stmt_pet->fetch();

if (!$pet) {
    header('Location: reports.php');
    exit();
}

// Fetch all photos from pet_photos for this pet
$stmt_photos = $pdo->prepare("SELECT * FROM pet_photos WHERE pet_id = ? ORDER BY is_primary DESC, id ASC");
$stmt_photos->execute([$pet_id]);
$photos = $stmt_photos->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Details - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .preview-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            color: #667eea;
            font-weight: 500;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .detail-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        .detail-card h3 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        .info-row {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: 600;
            color: #666;
            width: 120px;
            display: inline-block;
        }
        .photo-gallery {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .photo-gallery img {
            height: 150px;
            border-radius: 5px;
            object-fit: cover;
        }
        .action-container {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        .btn-grey { background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 1em; display: inline-block; }
        .btn-grey:hover { background: #5a6268; }
        .btn-red { background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 1em; display: inline-block; }
        .btn-red:hover { background: #c82333; }
        .inline-form { display: inline-block; margin: 0; }
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
            <div class="preview-container">
                <a href="reports.php" class="back-link">← Back to Reports</a>
                
                <div class="detail-card">
                    <h3>REPORT DETAILS</h3>
                    <div class="info-row"><span class="info-label">Reported by:</span> <?php echo htmlspecialchars($report['reporter_name']); ?></div>
                    <div class="info-row"><span class="info-label">Reason:</span> <?php echo nl2br(htmlspecialchars($report['reason'])); ?></div>
                    <div class="info-row"><span class="info-label">Date:</span> <?php echo date('Y-m-d H:i', strtotime($report['created_at'])); ?></div>
                </div>

                <div class="detail-card">
                    <h3>PET INFO</h3>
                    
                    <?php if (!empty($photos)): ?>
                        <div class="photo-gallery">
                            <?php foreach ($photos as $photo): ?>
                                <img src="../<?php echo htmlspecialchars($photo['photo_path']); ?>" alt="Pet Photo">
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="info-row" style="color: #999; font-style: italic;">No photos available for this pet.</div>
                    <?php endif; ?>

                    <div class="info-row">
                        <strong><?php echo htmlspecialchars($pet['name']); ?></strong> &middot; 
                        <?php echo htmlspecialchars($pet['breed']); ?> &middot; 
                        <?php echo (int)$pet['age']; ?> yr(s) &middot; 
                        <?php echo htmlspecialchars(ucfirst($pet['gender'])); ?>
                    </div>
                    <div class="info-row"><span class="info-label">Status:</span> 
                        <?php if ($pet['status'] === 'active'): ?>
                            <span class="status-badge status-approved">Active</span>
                        <?php elseif ($pet['status'] === 'removed'): ?>
                            <span class="status-badge status-rejected">Removed</span>
                        <?php else: ?>
                            <span class="status-badge status-pending"><?php echo htmlspecialchars(ucfirst($pet['status'])); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="info-row"><span class="info-label">Area / Location:</span> <?php echo htmlspecialchars($pet['area'] ?? $pet['location'] ?? '—'); ?></div>
                    <div class="info-row">
                        <span class="info-label" style="vertical-align: top;">Description:</span> 
                        <div style="display: inline-block; width: calc(100% - 130px);"><?php echo nl2br(htmlspecialchars($pet['description'])); ?></div>
                    </div>
                </div>

                <div class="detail-card">
                    <h3>OWNER INFO</h3>
                    <div class="info-row">
                        <?php echo htmlspecialchars($pet['owner_name']); ?> &middot; 
                        <?php echo htmlspecialchars($pet['owner_email']); ?> &middot; 
                        <?php echo htmlspecialchars($pet['owner_phone']); ?>
                    </div>
                </div>

                <div class="action-container">
                    <?php if ($pet['status'] !== 'removed'): ?>
                        <form action="pet_preview.php?id=<?php echo $pet_id; ?>&report_id=<?php echo $report_id; ?>" method="POST" class="inline-form">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="remove_pet">
                            <button type="submit" class="btn-red" onclick="return confirm('Are you sure you want to remove this pet?');">🗑 Remove Pet</button>
                        </form>
                    <?php endif; ?>
                    <form action="pet_preview.php?id=<?php echo $pet_id; ?>&report_id=<?php echo $report_id; ?>" method="POST" class="inline-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="dismiss">
                        <button type="submit" class="btn-grey" onclick="return confirm('Are you sure you want to dismiss this report?');">✓ Dismiss Report</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
