<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    
    // Server-side validation
    if (empty($full_name) || empty($username) || empty($email) || empty($phone) || empty($password)) {
        $_SESSION['error'] = 'All fields are required';
        header('Location: register.php');
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Please enter a valid email address';
        header('Location: register.php');
        exit();
    }
    
    // Attempt registration
    $result = register($username, $email, $password, $full_name, $phone);
    
    if ($result['success']) {
        $_SESSION['success'] = 'Account created successfully! You can now log in.';
        header('Location: login.php');
        exit();
    } else {
        $_SESSION['error'] = $result['message'];
        header('Location: register.php');
        exit();
    }
} else {
    header('Location: register.php');
    exit();
}
?>
