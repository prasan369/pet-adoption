<?php
require_once 'includes/config.php';
$error = '';
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Adoption - Sign Up</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body class="login-page">
    <div class="login-container" style="max-width: 450px;">
        <div class="login-card" style="padding: 30px 40px;">
            <div class="login-header">
                <h1>🐾 Pet <span>Adoption</span></h1>
                <p>Find & Adopt Your Perfect Pet Partner</p>
            </div>
            
            <form id="registerForm" method="POST" action="register_process.php">
                <div class="form-group">
                    <label for="fullName">Full Name</label>
                    <input type="text" id="fullName" name="full_name" required>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" placeholder="e.g. +1 555-0199" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" minlength="6" required>
                </div>
                
                <div id="errorMessage" class="error-message" data-error="<?php echo htmlspecialchars($error); ?>"></div>
                
                <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Create Account</button>
            </form>
            
            <div class="login-footer">
                <p>Already have an account? <a href="login.php" style="color: #0866ff; text-decoration: none; font-weight: 600;">Login</a></p>
            </div>
        </div>
    </div>
    
    <script src="js/register.js"></script>
</body>
</html>
