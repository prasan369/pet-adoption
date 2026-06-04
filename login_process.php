<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (!empty($username) && !empty($password)) {
        $result = login($username, $password);
        
        if ($result['success']) {
            // Redirect based on role
            if ($result['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: user/dashboard.php');
            }
            exit();
        } else {
            $_SESSION['error'] = $result['message'];
            header('Location: login.php');
            exit();
        }
    } else {
        $_SESSION['error'] = 'Please enter username and password';
        header('Location: login.php');
        exit();
    }
} else {
    header('Location: login.php');
    exit();
}
?>
