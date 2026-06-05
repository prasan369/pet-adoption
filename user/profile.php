<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_user()) {
    header('Location: ../login.php');
    exit();
}

$user = get_user_by_id($_SESSION['user_id']);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $zip_code = trim($_POST['zip_code']);
    
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, city = ?, state = ?, zip_code = ? WHERE id = ?");
    $user_id = $_SESSION['user_id'];
    $stmt->bind_param("sssssssi", $full_name, $email, $phone, $address, $city, $state, $zip_code, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Profile updated successfully!';
        $user = get_user_by_id($user_id);
    } else {
        $_SESSION['error'] = 'Error updating profile';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — Pet Adoption</title>
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
                <li><a href="dashboard.php">Browse Dogs</a></li>
                <li><a href="my_listings.php">My Listings</a></li>
                <li><a href="messages.php">Messages</a></li>
                <li><a href="post_listing.php" class="mp-nav-cta">+ Post a Dog</a></li>
                <li><a href="profile.php" class="active">Profile</a></li>
                <li><a href="../logout.php" class="mp-nav-logout">Logout</a></li>
            </ul>
        </div>
    </nav>
    
    <main class="mp-profile-wrapper">
        <div class="mp-profile-card">
            
            <?php 
                $initials = '';
                $name_parts = explode(' ', !empty($user['full_name']) ? $user['full_name'] : $user['username']);
                foreach ($name_parts as $part) {
                    if (!empty($part)) {
                        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
                    }
                }
                $initials = mb_substr($initials, 0, 2);
            ?>

            <div class="mp-profile-header">
                <div class="mp-profile-avatar"><?php echo htmlspecialchars($initials); ?></div>
                <div class="mp-profile-names"><?php echo htmlspecialchars($user['full_name'] ?? ''); ?> · <?php echo htmlspecialchars($user['username']); ?></div>
                <div class="mp-profile-email"><?php echo htmlspecialchars($user['email']); ?></div>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="mp-alert mp-alert-success mp-profile-alert">
                    <span>✅</span> <?php echo htmlspecialchars($_SESSION['success']); ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="mp-alert mp-alert-error mp-profile-alert">
                    <span>⚠️</span> <?php echo htmlspecialchars($_SESSION['error']); ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <form method="POST">
                <!-- Keep email hidden to not break PHP logic that expects it -->
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">

                <div class="mp-profile-form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                </div>
                
                <div class="mp-profile-form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>
                
                <div class="mp-profile-form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                </div>
                
                <div class="mp-profile-form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                </div>
                
                <div class="mp-profile-form-group">
                    <label for="state">State</label>
                    <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($user['state'] ?? ''); ?>">
                </div>
                
                <div class="mp-profile-form-group">
                    <label for="zip_code">Zip Code</label>
                    <input type="text" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars($user['zip_code'] ?? ''); ?>">
                </div>
                
                <button type="submit" class="mp-btn-profile">Update Profile</button>
            </form>
        </div>
    </main>

    <script src="../js/marketplace.js?v=<?php echo time(); ?>"></script>
</body>
</html>
