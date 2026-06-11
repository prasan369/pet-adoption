<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ../login.php');
    exit();
}

$admin = get_user_by_id($_SESSION['user_id']);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, city = ? WHERE id = ?");
    $admin_id = $_SESSION['user_id'];
    $stmt->bind_param("sssssi", $full_name, $email, $phone, $address, $city, $admin_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Profile updated successfully!';
        $admin = get_user_by_id($admin_id);
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
    <title>Admin Profile</title>
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
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage_pets.php">Manage Pets</a></li>
                <li><a href="manage_users.php">Manage Users</a></li>
                <li><a href="reports.php">Reports</a></li>
                <li><a href="profile.php" class="active">Profile</a></li>
                <li><a href="../logout.php" class="logout-link">Logout</a></li>
            </ul>
        </aside>
        
        <main class="admin-content">
            <div class="top-bar">
                <h1>My Profile</h1>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <div class="content-section">
                <form method="POST" class="profile-form">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" value="<?php echo htmlspecialchars($admin['username']); ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($admin['phone']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($admin['address']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($admin['city']); ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
