<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// --- Auth guard ---
if (!is_logged_in() || !is_user()) {
    header('Location: ../login.php');
    exit();
}

// --- Validate pet ID ---
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$pet_id = (int)$_GET['id'];

// --- Fetch pet (active only) ---
$stmt = $pdo->prepare("
    SELECT p.*, u.full_name AS owner_name, u.city AS owner_city, u.id AS owner_user_id
    FROM pets p
    JOIN users u ON p.owner_id = u.id
    WHERE p.id = ? AND p.status = 'active'
");
$stmt->execute([$pet_id]);
$pet = $stmt->fetch();

if (!$pet) {
    header('Location: dashboard.php');
    exit();
}

// --- Fetch all photos ---
$photo_stmt = $pdo->prepare("
    SELECT photo_path, is_primary
    FROM pet_photos
    WHERE pet_id = ?
    ORDER BY is_primary DESC, id ASC
");
$photo_stmt->execute([$pet_id]);
$photos = $photo_stmt->fetchAll();

// Primary photo (first in the sorted list)
$primary_photo = $photos[0]['photo_path'] ?? '';

$user = get_user_by_id($_SESSION['user_id']);

// Owner initials for avatar
$initials = '';
$name_parts = explode(' ', $pet['owner_name'] ?? '');
foreach ($name_parts as $part) {
    $initials .= mb_strtoupper(mb_substr($part, 0, 1));
}
$initials = mb_substr($initials, 0, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pet['name']); ?> — PawMarket</title>
    <meta name="description" content="<?php echo htmlspecialchars($pet['breed'] . ' available for adoption in ' . ($pet['location'] ?? '')); ?>">
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
                <li><a href="messages.php">Messages</a></li>
                <li><a href="post_listing.php" class="mp-nav-cta">+ Post a Dog</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="../logout.php" class="mp-nav-logout">Logout</a></li>
            </ul>
        </div>
    </nav>

    <main class="mp-container">
        <a href="dashboard.php" class="mp-back">← Back to Browse</a>

        <!-- Flash messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mp-alert mp-alert-success">
                <span>✅</span>
                <div><?php echo htmlspecialchars($_SESSION['success']); ?></div>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_GET['reported'])): ?>
            <div class="mp-alert mp-alert-success">
                <span>🚩</span>
                <div>Thank you. Your report has been submitted.</div>
            </div>
        <?php endif; ?>

        <div class="mp-detail-layout">

            <!-- ===== Gallery ===== -->
            <div class="mp-gallery">
                <div class="mp-gallery-main">
                    <?php if ($primary_photo): ?>
                        <img id="galleryMain"
                             src="../<?php echo htmlspecialchars($primary_photo); ?>"
                             alt="<?php echo htmlspecialchars($pet['name']); ?>">
                    <?php else: ?>
                        <div class="mp-no-photo" style="height:100%;font-size:5rem;">🐕</div>
                    <?php endif; ?>
                </div>

                <?php if (count($photos) > 1): ?>
                <div class="mp-gallery-thumbs">
                    <?php foreach ($photos as $i => $photo): ?>
                        <div class="mp-thumb <?php echo $i === 0 ? 'active' : ''; ?>"
                             data-full="../<?php echo htmlspecialchars($photo['photo_path']); ?>">
                            <img src="../<?php echo htmlspecialchars($photo['photo_path']); ?>"
                                 alt="<?php echo htmlspecialchars($pet['name'] . ' photo ' . ($i + 1)); ?>"
                                 loading="lazy">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- ===== Info Panel ===== -->
            <div class="mp-detail-panel">

                <!-- Details card -->
                <div class="mp-detail-card">
                    <h1><?php echo htmlspecialchars($pet['name']); ?></h1>
                    <div class="mp-detail-breed"><?php echo htmlspecialchars($pet['breed']); ?></div>

                    <div class="mp-detail-stats">
                        <div class="mp-stat">
                            <div class="mp-stat-label">Age</div>
                            <div class="mp-stat-value"><?php echo (int)$pet['age']; ?> yr<?php echo (int)$pet['age'] !== 1 ? 's' : ''; ?></div>
                        </div>
                        <div class="mp-stat">
                            <div class="mp-stat-label">Gender</div>
                            <div class="mp-stat-value"><?php echo htmlspecialchars(ucfirst($pet['gender'])); ?></div>
                        </div>
                        <div class="mp-stat">
                            <div class="mp-stat-label">City</div>
                            <div class="mp-stat-value"><?php echo htmlspecialchars($pet['location'] ?? '—'); ?></div>
                        </div>
                        <div class="mp-stat">
                            <div class="mp-stat-label">Listed</div>
                            <div class="mp-stat-value"><?php echo date('M j, Y', strtotime($pet['created_at'])); ?></div>
                        </div>
                    </div>

                    <div class="mp-detail-desc">
                        <h3>About <?php echo htmlspecialchars($pet['name']); ?></h3>
                        <p><?php echo nl2br(htmlspecialchars($pet['description'])); ?></p>
                    </div>
                </div>

                <!-- Owner card -->
                <div class="mp-detail-card">
                    <div class="mp-owner-card">
                        <div class="mp-owner-avatar"><?php echo htmlspecialchars($initials); ?></div>
                        <div class="mp-owner-info">
                            <div class="mp-owner-name"><?php echo htmlspecialchars($pet['owner_name']); ?></div>
                            <?php if (!empty($pet['owner_city'])): ?>
                                <div class="mp-owner-city">📍 <?php echo htmlspecialchars($pet['owner_city']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="mp-detail-card">
                    <div class="mp-actions">
                        <a href="messages.php?to=<?php echo (int)$pet['owner_user_id']; ?>"
                           class="mp-btn mp-btn-primary">
                            💬 Message Owner
                        </a>
                        <button type="button" id="reportToggle" class="mp-btn mp-btn-danger">
                            🚩 Report Listing
                        </button>
                    </div>

                    <!-- Inline report form -->
                    <form action="report.php" method="POST" class="mp-report-form" id="reportForm">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="pet_id" value="<?php echo (int)$pet['id']; ?>">
                        <h3>🚩 Report this listing</h3>
                        <textarea name="reason" placeholder="Tell us why you're reporting this listing…" required></textarea>
                        <button type="submit" class="mp-btn mp-btn-danger" style="width:100%;">Submit Report</button>
                    </form>
                </div>

            </div>
        </div>
    </main>

    <script src="../js/marketplace.js"></script>
</body>
</html>
