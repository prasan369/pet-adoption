<?php
require_once 'includes/config.php';
$error = '';
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
$success = '';
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Adoption - Login</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>🐾 Pet <span>Adoption</span></h1>
                <p>Find Your Perfect Pet</p>
            </div>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success" style="margin-bottom: 20px; text-align: center;"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form id="loginForm" method="POST" action="login_process.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div id="errorMessage" class="error-message" data-error="<?php echo htmlspecialchars($error); ?>"></div>
                
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            
            <div class="login-footer">
                <p style="font-size: 0.95em; margin: 0;">Don't have an account? <a href="register.php" style="color: #0866ff; text-decoration: none; font-weight: 600;">Sign Up</a></p>
            </div>
        </div>
    </div>
    
    <script src="js/login.js"></script>
</body>
</html>
