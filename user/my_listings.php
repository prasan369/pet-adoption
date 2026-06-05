<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// --- Auth guard ---
if (!is_logged_in() || !is_user()) {
    header('Location: ../login.php');
    exit();
}

// --- Handle Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    
    $action = $_POST['action'] ?? '';
    $pet_id = (int)($_POST['pet_id'] ?? 0);
    $user_id = $_SESSION['user_id'];

    if ($pet_id > 0) {
        if ($action === 'mark_adopted') {
            $stmt = $pdo->prepare("UPDATE pets SET status = 'adopted' WHERE id = ? AND owner_id = ?");
            if ($stmt->execute([$pet_id, $user_id])) {
                $_SESSION['success'] = 'Listing marked as adopted!';
            }
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM pets WHERE id = ? AND owner_id = ?");
            if ($stmt->execute([$pet_id, $user_id])) {
                $_SESSION['success'] = 'Listing deleted successfully!';
            }
        }
    }
    
    // Redirect to avoid form resubmission
    header('Location: my_listings.php');
    exit();
}

$user = get_user_by_id($_SESSION['user_id']);

// --- Fetch user's listings ---
$stmt = $pdo->prepare("
    SELECT p.id, p.name, p.breed, p.status, p.created_at, pp.photo_path
    FROM pets p
    LEFT JOIN pet_photos pp ON p.id = pp.pet_id AND pp.is_primary = 1
    WHERE p.owner_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$my_pets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Listings — Pet Adoption</title>
    <meta name="description" content="Manage your dog listings on Pet Adoption.">
    <link rel="stylesheet" href="../css/marketplace.css">
    <style>
        .mp-listing-card {
            display: flex;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            overflow: hidden;
            margin-bottom: 16px;
            align-items: center;
        }
        .mp-listing-img {
            width: 120px;
            height: 120px;
            flex-shrink: 0;
            background: var(--bg-tertiary);
        }
        .mp-listing-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .mp-listing-info {
            flex: 1;
            padding: 16px;
        }
        .mp-listing-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        .mp-listing-meta {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        .mp-listing-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 8px;
        }
        .badge-active { background: rgba(45, 136, 255, 0.15); color: var(--accent); }
        .badge-adopted { background: rgba(49, 162, 76, 0.15); color: var(--green); }
        .badge-removed { background: rgba(240, 40, 73, 0.15); color: var(--red); }
        
        .mp-listing-actions {
            padding: 16px;
            display: flex;
            gap: 10px;
            flex-direction: column;
        }
        
        @media (max-width: 600px) {
            .mp-listing-card { flex-direction: column; text-align: center; }
            .mp-listing-img { width: 100%; height: 200px; }
            .mp-listing-actions { width: 100%; flex-direction: row; justify-content: center; }
            .mp-listing-actions form { flex: 1; }
            .mp-listing-actions button { width: 100%; }
        }
    </style>
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
                <li><a href="my_listings.php" class="active">My Listings</a></li>
                <li><a href="messages.php">Messages</a></li>
                <li><a href="post_listing.php" class="mp-nav-cta">+ Post a Dog</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="../logout.php" class="mp-nav-logout">Logout</a></li>
            </ul>
        </div>
    </nav>

    <main class="mp-container">
        
        <div class="mp-page-header">
            <h1>My Listings</h1>
            <p>Manage the dogs you've posted for adoption.</p>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="mp-alert mp-alert-success">
                <span>✅</span>
                <div><?php echo htmlspecialchars($_SESSION['success']); ?></div>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div style="max-width: 800px;">
            <?php if (empty($my_pets)): ?>
                <div class="mp-empty">
                    <span class="mp-empty-icon">📝</span>
                    <p>You haven't listed any dogs yet. <a href="post_listing.php">Post a dog</a></p>
                </div>
            <?php else: ?>
                <?php foreach ($my_pets as $pet): ?>
                    <div class="mp-listing-card">
                        <div class="mp-listing-img">
                            <?php if (!empty($pet['photo_path'])): ?>
                                <img src="../<?php echo htmlspecialchars($pet['photo_path']); ?>" alt="<?php echo htmlspecialchars($pet['name']); ?>">
                            <?php else: ?>
                                <div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:3rem;color:var(--text-muted)">🐕</div>
                            <?php endif; ?>
                        </div>
                        <div class="mp-listing-info">
                            <div class="mp-listing-name">
                                <a href="pet_detail.php?id=<?php echo $pet['id']; ?>" style="color:inherit;">
                                    <?php echo htmlspecialchars($pet['name']); ?>
                                </a>
                            </div>
                            <div class="mp-listing-meta"><?php echo htmlspecialchars($pet['breed']); ?></div>
                            <div class="mp-listing-meta" style="margin-top:2px; font-size: 0.8rem;">Listed: <?php echo date('M j, Y', strtotime($pet['created_at'])); ?></div>
                            
                            <span class="mp-listing-badge badge-<?php echo htmlspecialchars($pet['status']); ?>">
                                <?php echo htmlspecialchars(ucfirst($pet['status'])); ?>
                            </span>
                        </div>
                        <div class="mp-listing-actions">
                            <?php if ($pet['status'] === 'active'): ?>
                                <form method="POST" onsubmit="return confirm('Mark this dog as adopted?');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="mark_adopted">
                                    <input type="hidden" name="pet_id" value="<?php echo $pet['id']; ?>">
                                    <button type="submit" class="mp-btn mp-btn-secondary" style="padding: 8px 16px; font-size: 0.85rem;">Mark Adopted</button>
                                </form>
                            <?php endif; ?>
                            
                            <form method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this listing?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="pet_id" value="<?php echo $pet['id']; ?>">
                                <button type="submit" class="mp-btn mp-btn-danger" style="padding: 8px 16px; font-size: 0.85rem;">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>

    <script src="../js/marketplace.js"></script>
</body>
</html>
