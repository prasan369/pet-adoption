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
    <title>My Profile</title>
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
                <li><a href="my_listings.php">My Listings</a></li>
                <li><a href="messages.php">Messages</a></li>
                <li><a href="post_listing.php" class="mp-nav-cta">+ Post a Dog</a></li>
                <li><a href="profile.php" class="active">Profile</a></li>
                <li><a href="../logout.php" class="mp-nav-logout">Logout</a></li>
            </ul>
        </div>
    </nav>
    
    <main class="user-content">
        <div class="page-header">
            <h1>My Profile</h1>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <div class="profile-container">
            <form method="POST" class="profile-form">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user['city']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="state">State</label>
                    <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($user['state']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="zip_code">Zip Code</label>
                    <input type="text" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars($user['zip_code']); ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>
        </div>
    </main>
</body>
</html>
