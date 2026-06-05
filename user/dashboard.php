<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// --- Auth guard ---
if (!is_logged_in() || !is_user()) {
    header('Location: ../login.php');
    exit();
}

$user = get_user_by_id($_SESSION['user_id']);

// --- Fetch active dogs with primary photo ---
$stmt = $pdo->prepare("
    SELECT p.id, p.name, p.breed, p.area, p.location AS city, p.age, p.gender, pp.photo_path
    FROM pets p
    LEFT JOIN pet_photos pp ON p.id = pp.pet_id AND pp.is_primary = 1
    WHERE p.status = 'active'
    ORDER BY p.created_at DESC
");
$stmt->execute();
$pets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Dogs — Pet Adoption</title>
    <meta name="description" content="Find dogs available for adoption near you on Pet Adoption.">
    <link rel="stylesheet" href="../css/marketplace.css?v=<?php echo time(); ?>">
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
                <li><a href="dashboard.php" class="active">Browse Dogs</a></li>
                <li><a href="my_listings.php">My Listings</a></li>
                <li><a href="messages.php">Messages</a></li>
                <li><a href="post_listing.php" class="mp-nav-cta">+ Post a Dog</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="../logout.php" class="mp-nav-logout">Logout</a></li>
            </ul>
        </div>
    </nav>

    <main class="mp-container">

        <!-- Header -->
        <div class="mp-page-header">
            <h1>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?> 👋</h1>
            <p>Browse dogs looking for a forever home</p>
        </div>

        <!-- Success alert -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mp-alert mp-alert-success">
                <span>✅</span>
                <div><?php echo htmlspecialchars($_SESSION['success']); ?></div>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Search & Filter Bar -->
        <div class="search-filter-bar">
            <div class="search-box">
                <span>🔍</span>
                <input type="text" id="dog-search" placeholder="Search by name, breed or area...">
            </div>
            <select id="filter-age">
                <option value="">All Ages</option>
                <option value="puppy">Puppy (0-1 yr)</option>
                <option value="young">Young (1-3 yr)</option>
                <option value="adult">Adult (3+ yr)</option>
            </select>
            <select id="filter-gender">
                <option value="">All Genders</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
            </select>
        </div>

        <!-- Pet Grid -->
        <div class="mp-grid">
            <?php if (empty($pets)): ?>
                <div class="mp-empty">
                    <span class="mp-empty-icon">🐶</span>
                    <p>No dogs listed yet. Be the first to <a href="post_listing.php">post a dog</a>!</p>
                </div>
            <?php else: ?>
                <?php foreach ($pets as $pet): ?>
                    <a href="pet_detail.php?id=<?php echo (int)$pet['id']; ?>"
                       class="mp-card"
                       data-name="<?php echo htmlspecialchars(strtolower($pet['name'])); ?>"
                       data-breed="<?php echo htmlspecialchars(strtolower($pet['breed'])); ?>"
                       data-area="<?php echo htmlspecialchars(strtolower($pet['area'] ?? $pet['city'] ?? '')); ?>"
                       data-age="<?php echo (int)$pet['age']; ?>"
                       data-gender="<?php echo htmlspecialchars(strtolower($pet['gender'] ?? '')); ?>">

                        <div class="mp-card-img">
                            <?php if (!empty($pet['photo_path'])): ?>
                                <img src="../<?php echo htmlspecialchars($pet['photo_path']); ?>"
                                     alt="<?php echo htmlspecialchars($pet['name']); ?>"
                                     loading="lazy">
                            <?php else: ?>
                                <div class="mp-no-photo">🐕</div>
                            <?php endif; ?>
                        </div>

                        <div class="mp-card-body">
                            <div class="mp-card-name"><?php echo htmlspecialchars($pet['name']); ?></div>
                            <div class="mp-card-breed"><?php echo htmlspecialchars($pet['breed']); ?></div>
                            <?php $displayArea = $pet['area'] ?? $pet['city'] ?? ''; ?>
                            <?php if (!empty($displayArea)): ?>
                                <div class="mp-card-city"><?php echo htmlspecialchars($displayArea); ?></div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>

    <script src="../js/marketplace.js?v=<?php echo time(); ?>"></script>
</body>
</html>
